<?php
namespace HeadlessTheme\Admin\Interfaces;

interface AdminPageInterface {
    public function init(): void;
    public function render(): void;
    public function save(): void;
    public function getPageTitle(): string;
    public function getPageDescription(): string;
} 