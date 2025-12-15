<?php
namespace MYGraphQL\Auth;

/**
 * JWT Manager - Simple JWT implementation without external dependencies
 * Uses HMAC-SHA256 for signing tokens
 */
class JWTManager {
    private const OPTION_SECRET = 'mygraphql_jwt_secret';
    private const OPTION_SETTINGS = 'mygraphql_jwt_settings';
    
    private static ?self $instance = null;
    
    public static function getInstance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->ensureSecretExists();
        $this->registerAuthFilter();
    }
    
    /**
     * Ensure a secret key exists, generate one if not
     */
    private function ensureSecretExists(): void {
        if (!get_option(self::OPTION_SECRET)) {
            $secret = $this->generateSecret();
            update_option(self::OPTION_SECRET, $secret);
        }
    }
    
    /**
     * Generate a cryptographically secure secret
     */
    private function generateSecret(): string {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Get the current secret
     */
    public function getSecret(): string {
        return get_option(self::OPTION_SECRET, '');
    }
    
    /**
     * Regenerate the secret (invalidates all existing tokens)
     */
    public function regenerateSecret(): string {
        $secret = $this->generateSecret();
        update_option(self::OPTION_SECRET, $secret);
        return $secret;
    }
    
    /**
     * Get JWT settings
     */
    public function getSettings(): array {
        $defaults = [
            'token_expiry' => 86400 * 7, // 7 days
            'issuer' => get_bloginfo('url'),
        ];
        
        $settings = get_option(self::OPTION_SETTINGS, []);
        return array_merge($defaults, $settings);
    }
    
    /**
     * Update JWT settings
     */
    public function updateSettings(array $settings): bool {
        return update_option(self::OPTION_SETTINGS, $settings);
    }
    
    /**
     * Generate a JWT token for a user
     */
    public function generateToken(int $userId, ?int $expiresIn = null): ?array {
        $user = get_user_by('id', $userId);
        if (!$user) {
            return null;
        }
        
        $settings = $this->getSettings();
        $expiresIn = $expiresIn ?? $settings['token_expiry'];
        $issuedAt = time();
        $expiresAt = $issuedAt + $expiresIn;
        
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];
        
        $payload = [
            'iss' => $settings['issuer'],
            'iat' => $issuedAt,
            'exp' => $expiresAt,
            'sub' => $userId,
            'data' => [
                'user' => [
                    'id' => $userId,
                    'login' => $user->user_login,
                    'email' => $user->user_email,
                    'display_name' => $user->display_name,
                    'roles' => $user->roles,
                ]
            ]
        ];
        
        $token = $this->encode($header, $payload);
        
        return [
            'token' => $token,
            'user_id' => $userId,
            'user_login' => $user->user_login,
            'user_email' => $user->user_email,
            'user_display_name' => $user->display_name,
            'user_roles' => $user->roles,
            'issued_at' => $issuedAt,
            'expires_at' => $expiresAt,
            'expires_in' => $expiresIn,
        ];
    }
    
    /**
     * Validate and decode a JWT token
     */
    public function validateToken(string $token): ?array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        
        [$headerB64, $payloadB64, $signatureB64] = $parts;
        
        // Verify signature
        $expectedSignature = $this->sign($headerB64 . '.' . $payloadB64);
        if (!hash_equals($expectedSignature, $this->base64UrlDecode($signatureB64))) {
            return null;
        }
        
        // Decode payload
        $payload = json_decode($this->base64UrlDecode($payloadB64), true);
        if (!$payload) {
            return null;
        }
        
        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }
        
        return $payload;
    }
    
    /**
     * Encode header and payload into a JWT
     */
    private function encode(array $header, array $payload): string {
        $headerB64 = $this->base64UrlEncode(json_encode($header));
        $payloadB64 = $this->base64UrlEncode(json_encode($payload));
        
        $signature = $this->sign($headerB64 . '.' . $payloadB64);
        $signatureB64 = $this->base64UrlEncode($signature);
        
        return $headerB64 . '.' . $payloadB64 . '.' . $signatureB64;
    }
    
    /**
     * Sign data with HMAC-SHA256
     */
    private function sign(string $data): string {
        return hash_hmac('sha256', $data, $this->getSecret(), true);
    }
    
    /**
     * Base64 URL-safe encode
     */
    private function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL-safe decode
     */
    private function base64UrlDecode(string $data): string {
        $padding = strlen($data) % 4;
        if ($padding) {
            $data .= str_repeat('=', 4 - $padding);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
    
    /**
     * Register GraphQL authentication filter
     */
    private function registerAuthFilter(): void {
        // Authenticate GraphQL requests with Bearer token
        add_filter('graphql_request_data', function($request_data, $request) {
            $this->authenticateRequest();
            return $request_data;
        }, 10, 2);
        
        // Also check on determine_current_user for earlier auth
        add_filter('determine_current_user', [$this, 'authenticateUser'], 20);
    }
    
    /**
     * Authenticate user from JWT token in Authorization header
     */
    public function authenticateUser($userId) {
        // If already authenticated, skip
        if ($userId) {
            return $userId;
        }
        
        $token = $this->getTokenFromRequest();
        if (!$token) {
            return $userId;
        }
        
        $payload = $this->validateToken($token);
        if (!$payload || !isset($payload['sub'])) {
            return $userId;
        }
        
        // Verify user still exists
        $user = get_user_by('id', $payload['sub']);
        if (!$user) {
            return $userId;
        }
        
        return $user->ID;
    }
    
    /**
     * Authenticate the current request
     */
    private function authenticateRequest(): void {
        $token = $this->getTokenFromRequest();
        if (!$token) {
            return;
        }
        
        $payload = $this->validateToken($token);
        if (!$payload || !isset($payload['sub'])) {
            return;
        }
        
        // Set current user
        wp_set_current_user($payload['sub']);
    }
    
    /**
     * Extract JWT token from Authorization header
     */
    private function getTokenFromRequest(): ?string {
        $headers = $this->getAuthorizationHeader();
        if (!$headers) {
            return null;
        }
        
        if (preg_match('/Bearer\s+(.+)$/i', $headers, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Get Authorization header from request
     */
    private function getAuthorizationHeader(): ?string {
        // Check for Authorization header
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return $_SERVER['HTTP_AUTHORIZATION'];
        }
        
        // Apache specific
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        
        // Try to get from apache_request_headers
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers['Authorization'])) {
                return $headers['Authorization'];
            }
            // Case-insensitive check
            foreach ($headers as $key => $value) {
                if (strtolower($key) === 'authorization') {
                    return $value;
                }
            }
        }
        
        return null;
    }
}

