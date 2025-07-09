<?php
/**
 * Localization Helper Functions
 * DPTI Rocket System - Internationalization (i18n) Support
 * 
 * This file provides functions for loading and accessing translations
 * in a secure and efficient manner.
 * 
 * @version 1.0
 * @charset UTF-8
 */

/**
 * Load language translations into session
 * 
 * This function loads a language file and stores the translations
 * in the session for efficient access throughout the application.
 * 
 * @param string $language_code Language code (e.g., 'th', 'en')
 * @param string $locales_path Path to locales directory (optional)
 * @return bool True if language loaded successfully, false otherwise
 */
function load_language($language_code = 'th', $locales_path = null) {
    try {
        // Default locales path if not provided
        if ($locales_path === null) {
            $locales_path = __DIR__ . '/../locales/';
        }
        
        // Sanitize language code to prevent directory traversal
        $language_code = preg_replace('/[^a-z0-9_-]/i', '', $language_code);
        
        if (empty($language_code)) {
            error_log("Invalid language code provided to load_language()");
            return false;
        }
        
        // Construct language file path
        $language_file = $locales_path . $language_code . '.php';
        
        // Check if language file exists
        if (!file_exists($language_file)) {
            error_log("Language file not found: " . $language_file);
            return false;
        }
        
        // Load the language array
        $translations = require $language_file;
        
        // Validate that the file returns an array
        if (!is_array($translations)) {
            error_log("Language file does not return an array: " . $language_file);
            return false;
        }
        
        // Store translations in session with language code
        $_SESSION['app_language'] = $language_code;
        $_SESSION['app_translations'] = $translations;
        $_SESSION['translations_loaded_at'] = time();
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error loading language: " . $e->getMessage());
        return false;
    }
}

/**
 * Translate a key to the current language
 * 
 * This function safely retrieves a translation for the given key.
 * If no translation is found, it returns the original key.
 * All output is properly escaped to prevent XSS attacks.
 * 
 * @param string $key Translation key
 * @param array $replacements Optional array of replacements for dynamic content
 * @param bool $escape Whether to escape HTML (default: true)
 * @return string Translated and escaped string
 */
function t($key, $replacements = [], $escape = true) {
    // Validate input
    if (!is_string($key) || empty($key)) {
        return $escape ? htmlspecialchars('') : '';
    }
    
    // Check if translations are loaded in session
    if (!isset($_SESSION['app_translations']) || !is_array($_SESSION['app_translations'])) {
        // Try to load default language if not loaded
        if (!load_language('th')) {
            // Fallback: return the key itself if no translations available
            return $escape ? htmlspecialchars($key) : $key;
        }
    }
    
    // Get translation from session
    $translations = $_SESSION['app_translations'];
    
    // Look up the translation
    $translated = isset($translations[$key]) ? $translations[$key] : $key;
    
    // Handle dynamic replacements if provided
    if (!empty($replacements) && is_array($replacements)) {
        foreach ($replacements as $placeholder => $value) {
            // Support both :placeholder and {placeholder} formats
            $placeholder_patterns = [
                ':' . $placeholder,
                '{' . $placeholder . '}',
                '{' . $placeholder . '}'  // Duplicate for safety
            ];
            
            foreach ($placeholder_patterns as $pattern) {
                $translated = str_replace($pattern, (string)$value, $translated);
            }
        }
    }
    
    // Escape HTML entities to prevent XSS (unless explicitly disabled)
    return $escape ? htmlspecialchars($translated, ENT_QUOTES, 'UTF-8') : $translated;
}

/**
 * Get current language code
 * 
 * @return string Current language code or 'en' as fallback
 */
function get_current_language() {
    return isset($_SESSION['app_language']) ? $_SESSION['app_language'] : 'en';
}

/**
 * Check if translations are loaded
 * 
 * @return bool True if translations are loaded in session
 */
function translations_loaded() {
    return isset($_SESSION['app_translations']) && 
           is_array($_SESSION['app_translations']) && 
           !empty($_SESSION['app_translations']);
}

/**
 * Get all available languages
 * 
 * Scans the locales directory for available language files
 * 
 * @param string $locales_path Path to locales directory (optional)
 * @return array Array of available language codes
 */
function get_available_languages($locales_path = null) {
    if ($locales_path === null) {
        $locales_path = __DIR__ . '/../locales/';
    }
    
    $languages = [];
    
    if (is_dir($locales_path)) {
        $files = scandir($locales_path);
        foreach ($files as $file) {
            if (preg_match('/^([a-z]{2})\.php$/i', $file, $matches)) {
                $languages[] = $matches[1];
            }
        }
    }
    
    return $languages;
}

/**
 * Reload translations (useful for development/debugging)
 * 
 * @return bool True if reload successful
 */
function reload_translations() {
    $current_language = get_current_language();
    
    // Clear current translations
    unset($_SESSION['app_translations']);
    unset($_SESSION['translations_loaded_at']);
    
    // Reload language
    return load_language($current_language);
}

/**
 * Format number with localization support
 * 
 * @param float $number Number to format
 * @param int $decimals Number of decimal places
 * @return string Formatted number
 */
function format_number($number, $decimals = 0) {
    $current_language = get_current_language();
    
    // Thai number formatting
    if ($current_language === 'th') {
        return number_format($number, $decimals, '.', ',');
    }
    
    // Default English formatting
    return number_format($number, $decimals, '.', ',');
}

/**
 * Format date with localization support
 * 
 * @param string|int $date Date string or timestamp
 * @param string $format Date format (optional, uses localized default)
 * @return string Formatted date
 */
function format_date($date, $format = null) {
    if (is_string($date)) {
        $timestamp = strtotime($date);
    } else {
        $timestamp = $date;
    }
    
    if ($format === null) {
        $format = t('date_format', [], false); // Don't escape format string
    }
    
    return date($format, $timestamp);
}

/**
 * Debug function to display all loaded translations (development only)
 * 
 * @return array Current translations array
 */
function debug_translations() {
    if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
        return $_SESSION['app_translations'] ?? [];
    }
    
    return [];
}
?>
