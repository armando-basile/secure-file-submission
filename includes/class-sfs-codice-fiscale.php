<?php
/**
 * Italian Codice Fiscale Validator
 * Based on official algorithm
 */

if (!defined('ABSPATH')) {
    exit;
}

class SFS_Codice_Fiscale {
    
    private static $odd_chars = array(
        '0' => 1, '1' => 0, '2' => 5, '3' => 7, '4' => 9, '5' => 13,
        '6' => 15, '7' => 17, '8' => 19, '9' => 21,
        'A' => 1, 'B' => 0, 'C' => 5, 'D' => 7, 'E' => 9, 'F' => 13,
        'G' => 15, 'H' => 17, 'I' => 19, 'J' => 21, 'K' => 2, 'L' => 4,
        'M' => 18, 'N' => 20, 'O' => 11, 'P' => 3, 'Q' => 6, 'R' => 8,
        'S' => 12, 'T' => 14, 'U' => 16, 'V' => 10, 'W' => 22, 'X' => 25,
        'Y' => 24, 'Z' => 23
    );
    
    private static $even_chars = array(
        '0' => 0, '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5,
        '6' => 6, '7' => 7, '8' => 8, '9' => 9,
        'A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4, 'F' => 5,
        'G' => 6, 'H' => 7, 'I' => 8, 'J' => 9, 'K' => 10, 'L' => 11,
        'M' => 12, 'N' => 13, 'O' => 14, 'P' => 15, 'Q' => 16, 'R' => 17,
        'S' => 18, 'T' => 19, 'U' => 20, 'V' => 21, 'W' => 22, 'X' => 23,
        'Y' => 24, 'Z' => 25
    );
    
    private static $check_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    
    /**
     * Validate Italian Codice Fiscale
     * 
     * @param string $cf The codice fiscale to validate
     * @return bool True if valid, false otherwise
     */
    public static function validate($cf) {
        // Convert to uppercase and remove spaces
        $cf = strtoupper(trim($cf));
        
        // Check length (must be 16 characters)
        if (strlen($cf) != 16) {
            return false;
        }
        
        // Check format: 6 letters, 2 digits, 1 letter, 2 digits, 1 letter, 3 digits, 1 letter
        if (!preg_match('/^[A-Z]{6}[0-9]{2}[A-Z][0-9]{2}[A-Z][0-9]{3}[A-Z]$/', $cf)) {
            return false;
        }
        
        // Calculate checksum
        $sum = 0;
        
        // Process first 15 characters
        for ($i = 0; $i < 15; $i++) {
            $char = $cf[$i];
            
            if ($i % 2 === 0) {
                // Odd position (1st, 3rd, 5th, etc. - 0-indexed)
                $sum += self::$odd_chars[$char];
            } else {
                // Even position
                $sum += self::$even_chars[$char];
            }
        }
        
        // Calculate check character
        $check_index = $sum % 26;
        $calculated_check = self::$check_chars[$check_index];
        
        // Compare with actual check character (last character)
        return $calculated_check === $cf[15];
    }
    
    /**
     * Validate and provide detailed error message
     * 
     * @param string $cf The codice fiscale to validate
     * @return array Array with 'valid' boolean and 'message' string
     */
    public static function validate_detailed($cf) {
        $cf = strtoupper(trim($cf));
        
        if (strlen($cf) != 16) {
            return array(
                'valid' => false,
                'message' => 'Il Codice Fiscale deve essere di 16 caratteri.'
            );
        }
        
        if (!preg_match('/^[A-Z]{6}[0-9]{2}[A-Z][0-9]{2}[A-Z][0-9]{3}[A-Z]$/', $cf)) {
            return array(
                'valid' => false,
                'message' => 'Il formato del Codice Fiscale non è valido.'
            );
        }
        
        if (!self::validate($cf)) {
            return array(
                'valid' => false,
                'message' => 'Il carattere di controllo del Codice Fiscale non è corretto.'
            );
        }
        
        return array(
            'valid' => true,
            'message' => 'Codice Fiscale valido.'
        );
    }
}
