<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

function capitalizeWords(string $text): string {
    // Handle empty input
    if (empty($text)) {
        return '';
    }
    
    // Split text into words, capitalize each word, and recombine
    $words = explode(' ', $text);
    $capitalizedWords = array_map(function($word) {
        // Handle empty words or spaces
        if (empty($word)) {
            return $word;
        }
        // Convert first character to uppercase and rest to lowercase
        return mb_strtoupper(mb_substr($word, 0, 1)) . 
               mb_strtolower(mb_substr($word, 1));
    }, $words);
    
    return implode(' ', $capitalizedWords);
}