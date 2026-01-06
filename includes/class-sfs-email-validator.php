<?php
/**
 * Email Validator with DNS MX Record Check
 */

if (!defined('ABSPATH')) {
    exit;
}

class SFS_Email_Validator {
    
    /**
     * Validate email address
     * 
     * @param string $email Email address to validate
     * @return bool True if valid, false otherwise
     */
    public static function validate($email) {
        // First check basic format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        // Extract domain from email
        $domain = substr(strrchr($email, "@"), 1);
        
        // Check if domain has MX records
        return self::check_mx_records($domain);
    }
    
    /**
     * Check if domain has valid MX records
     * 
     * @param string $domain Domain to check
     * @return bool True if MX records exist, false otherwise
     */
    private static function check_mx_records($domain) {
        // Check for MX records
        if (checkdnsrr($domain, 'MX')) {
            return true;
        }
        
        // Fallback: check for A record (some domains don't have MX but have A)
        if (checkdnsrr($domain, 'A')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Validate and provide detailed error message
     * 
     * @param string $email Email address to validate
     * @return array Array with 'valid' boolean and 'message' string
     */
    public static function validate_detailed($email) {
        // Check basic format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return array(
                'valid' => false,
                'message' => 'Il formato dell\'indirizzo email non è valido.'
            );
        }
        
        // Extract domain
        $domain = substr(strrchr($email, "@"), 1);
        
        // Check MX records
        if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
            return array(
                'valid' => false,
                'message' => 'Il dominio email non esiste o non è configurato correttamente.'
            );
        }
        
        return array(
            'valid' => true,
            'message' => 'Indirizzo email valido.'
        );
    }
    
    /**
     * Check if email is from a temporary/disposable email provider
     * 
     * @param string $email Email address to check
     * @return bool True if disposable, false otherwise
     */
    public static function is_disposable($email) {
        // List of common disposable email domains
        $disposable_domains = array(
            'tempmail.com',
            'guerrillamail.com',
            'mailinator.com',
            '10minutemail.com',
            'throwaway.email',
            'temp-mail.org',
            'getnada.com',
            'maildrop.cc',
            'trashmail.com',
            'yopmail.com',
        );
        
        $domain = substr(strrchr($email, "@"), 1);
        
        return in_array(strtolower($domain), $disposable_domains);
    }
}
