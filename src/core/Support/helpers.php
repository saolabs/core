<?php

use Saola\Core\Support\ViewState;
use Saola\Core\View\Services\ViewStorageService;
use Saola\Core\Engines\ViewContextManager;

/**
 * Get ViewContextManager instance
 * 
 * @return ViewContextManager
 */
function viewContextManager(): ViewContextManager
{
    return app(ViewContextManager::class);
}

/**
 * Render view using ViewContextManager
 * 
 * @param string $context Context name (web, admin, api)
 * @param string $module Module name (optional)
 * @param string $blade Blade view name
 * @param array $data View data
 * @param string $type View type (base, modules, pages, components, layouts, templates)
 * @return \Illuminate\Contracts\View\View
 */
function viewContext(string $context, string $module, string $blade, array $data = [], string $type = '')
{
    return viewContextManager()->render($context, $module, $blade, $data, $type);
}

/**
 * Render module view
 * 
 * @param string $context Context name
 * @param string $module Module name
 * @param string $blade Blade view name
 * @param array $data View data
 * @return \Illuminate\Contracts\View\View
 */
function viewModule(string $context, string $module, string $blade, array $data = [])
{
    return viewContextManager()->renderModule($context, $module, $blade, $data);
}

/**
 * Render page view
 * 
 * @param string $context Context name
 * @param string $blade Blade view name
 * @param array $data View data
 * @return \Illuminate\Contracts\View\View
 */
function viewPage(string $context, string $blade, array $data = [])
{
    return viewContextManager()->renderPage($context, '', $blade, $data);
}

/**
 * Share data to a context
 * 
 * @param string $context Context name
 * @param array $data Data to share
 * @return ViewContextManager
 */
function shareContext(string $context, array $data): ViewContextManager
{
    return viewContextManager()->share($context, $data);
}

/**
 * Get context variables
 * 
 * @param string $context Context name
 * @return array|null
 */
function getContextVariables(string $context): ?array
{
    return viewContextManager()->getContextVariables($context);
}

/**
 * Get context variable
 * 
 * @param string $context Context name
 * @param string $variable Variable name (e.g., '__component__', '__module__')
 * @return string|null
 */
function getContextVariable(string $context, string $variable): ?string
{
    return viewContextManager()->getContextVariable($context, $variable);
}

if(!function_exists('useState')) {
    function useState($value) {
        $viewState = new ViewState($value);
        return [$viewState->getState(), function($value) use ($viewState) {
            $viewState->setState($value);
        }];
    }
}

if(!function_exists('add_view_init_js')) {
    function add_view_init_js($id, $content) {
        preg_match_all('/<script[^>]*>(.*?)<\/script>/s', $content, $matches);
        $scripts = $matches[1][0];
        if($scripts) {
            app(ViewStorageService::class)->addJs($id, $scripts);
        }
        return;
    }
}

if(!function_exists('add_view_init_css')) {
    function add_view_init_css($id, $content) {
        preg_match_all('/<style[^>]*>(.*?)<\/style>/s', $content, $matches);
        $css = $matches[1][0];
        if($css) {
            app(ViewStorageService::class)->addCss($id, $css);
        }
        return;
    }
}

if(!function_exists('add_view_init_tags')) {
    function add_view_init_tags($id, $content) {
        preg_match_all('/<script[^>]*>(.*?)<\/script>/s', $content, $matches);
        $scripts = $matches[1][0];
        if($scripts) {
            app(ViewStorageService::class)->addJs($id, $scripts);
        }
        preg_match_all('/<style[^>]*>(.*?)<\/style>/s', $content, $matches);
        $css = $matches[1][0];
        if($css) {
            app(ViewStorageService::class)->addCss($id, $css);
        }
        return;
    }
}


if (!function_exists('format_currency')) {
    /**
     * Format currency
     */
    function format_currency($amount, $currency = 'VND', $locale = 'vi_VN'): string
    {
        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        return $formatter->formatCurrency($amount, $currency);
    }
}

if (!function_exists('format_number')) {
    /**
     * Format number
     */
    function format_number($number, $decimals = 2, $locale = 'vi_VN'): string
    {
        $formatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);
        return $formatter->format($number);
    }
}

if (!function_exists('format_date')) {
    /**
     * Format date
     */
    function format_date($date, $format = 'd/m/Y', $locale = 'vi'): string
    {
        if (is_string($date)) {
            $date = new DateTime($date);
        }
        
        setlocale(LC_TIME, $locale);
        return $date->format($format);
    }
}

if (!function_exists('format_datetime')) {
    /**
     * Format datetime
     */
    function format_datetime($datetime, $format = 'd/m/Y H:i', $locale = 'vi'): string
    {
        if (is_string($datetime)) {
            $datetime = new DateTime($datetime);
        }
        
        setlocale(LC_TIME, $locale);
        return $datetime->format($format);
    }
}

if (!function_exists('generate_slug')) {
    /**
     * Generate slug from string
     */
    function generate_slug(string $string): string
    {
        $string = strtolower(trim($string));
        $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
        $string = preg_replace('/[\s-]+/', '-', $string);
        return trim($string, '-');
    }
}

if (!function_exists('generate_unique_slug')) {
    /**
     * Generate unique slug
     */
    function generate_unique_slug(string $string, callable $existsCallback): string
    {
        $baseSlug = generate_slug($string);
        $slug = $baseSlug;
        $counter = 1;
        
        while ($existsCallback($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
}

if (!function_exists('mask_phone')) {
    /**
     * Mask phone number
     */
    function mask_phone(string $phone): string
    {
        if (strlen($phone) < 7) {
            return $phone;
        }
        
        return substr($phone, 0, 3) . '****' . substr($phone, -3);
    }
}

if (!function_exists('mask_email')) {
    /**
     * Mask email address
     */
    function mask_email(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return $email;
        }
        
        $username = $parts[0];
        $domain = $parts[1];
        
        if (strlen($username) <= 2) {
            $maskedUsername = $username;
        } else {
            $maskedUsername = substr($username, 0, 1) . str_repeat('*', strlen($username) - 2) . substr($username, -1);
        }
        
        return $maskedUsername . '@' . $domain;
    }
}

if (!function_exists('generate_otp')) {
    /**
     * Generate OTP code
     */
    function generate_otp(int $length = 6): string
    {
        return str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('generate_uuid')) {
    /**
     * Generate UUID v4
     */
    function generate_uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}

if (!function_exists('array_to_dot')) {
    /**
     * Convert array to dot notation
     */
    function array_to_dot(array $array, string $prefix = ''): array
    {
        $result = [];
        
        foreach ($array as $key => $value) {
            $newKey = $prefix ? $prefix . '.' . $key : $key;
            
            if (is_array($value)) {
                $result = array_merge($result, array_to_dot($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        
        return $result;
    }
}

if (!function_exists('dot_to_array')) {
    /**
     * Convert dot notation to array
     */
    function dot_to_array(array $array): array
    {
        $result = [];
        
        foreach ($array as $key => $value) {
            $keys = explode('.', $key);
            $current = &$result;
            
            foreach ($keys as $k) {
                if (!isset($current[$k])) {
                    $current[$k] = [];
                }
                $current = &$current[$k];
            }
            
            $current = $value;
        }
        
        return $result;
    }
}

if (!function_exists('is_ajax_request')) {
    /**
     * Check if request is AJAX
     */
    function is_ajax_request(): bool
    {
        return request()->ajax() || request()->wantsJson();
    }
}

if (!function_exists('is_mobile')) {
    /**
     * Check if user is on mobile device
     */
    function is_mobile(): bool
    {
        $userAgent = request()->userAgent();
        
        return preg_match('/(android|iphone|ipad|mobile|tablet)/i', $userAgent);
    }
}

if (!function_exists('get_client_ip')) {
    /**
     * Get client IP address
     */
    function get_client_ip(): string
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'HTTP_CLIENT_IP', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return request()->ip();
    }
}

if (!function_exists('sanitize_filename')) {
    /**
     * Sanitize filename
     */
    function sanitize_filename(string $filename): string
    {
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        $filename = preg_replace('/\.+/', '.', $filename);
        return trim($filename, '.');
    }
}

if (!function_exists('format_file_size')) {
    /**
     * Format file size
     */
    function format_file_size(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

if(!function_exists('quickAssign')) {
    function quickAssign(...$values) {
        
    }
}

if(!function_exists('getUser')) {
    function getUser() {
        return (object)[
            'id' => 123,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'admin'
        ];
    }
}

if(!function_exists('getIpAddress')) {
    function getIpAddress() {
        return '192.168.1.100';
    }
}

if(!function_exists('getChartData')) {
    function getChartData() {
        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr'],
            'data' => [100, 200, 150, 300]
        ];
    }
}

if(!function_exists('getConfig')) {
    function getConfig() {
        return (object)[
            'title' => 'Dashboard Config',
            'description' => 'Configuration for dashboard',
            'theme' => 'dark',
            'language' => 'en'
        ];
    }
}

if(!function_exists('text')){
    function text($textAddress = null, $replace = null, $default = null, $locale = 'vi'){
        // return __($textAddress, $default, $locale);
        if(is_string($replace) && !$default){
            $default = $replace;
        }
        $replace = is_array($replace) ? $replace : [];
        return $default?:$textAddress;
    }
}