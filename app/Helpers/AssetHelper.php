<?php

namespace App\Helpers;

class AssetHelper
{
    /**
     * Get the logo URL with proper asset path
     */
    public static function logoUrl(): string
    {
        return asset('images/logo.png');
    }
    
    /**
     * Get the favicon URL
     */
    public static function faviconUrl(): string
    {
        return asset('images/logo.png');
    }
    
    /**
     * Get the apple touch icon URL
     */
    public static function appleTouchIconUrl(): string
    {
        return asset('images/logo.png');
    }
    
    /**
     * Get all logo-related URLs
     */
    public static function logoUrls(): array
    {
        return [
            'logo_url' => self::logoUrl(),
            'favicon_url' => self::faviconUrl(),
            'apple_touch_icon_url' => self::appleTouchIconUrl(),
        ];
    }
    
    /**
     * Test method to verify asset generation
     */
    public static function testAssets(): array
    {
        return [
            'logo_url' => self::logoUrl(),
            'logo_exists' => file_exists(public_path('images/logo.png')),
            'app_url' => config('app.url'),
            'asset_helper_working' => true,
        ];
    }
}
