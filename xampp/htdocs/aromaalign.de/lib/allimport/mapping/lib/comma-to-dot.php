<?php

/**
 * Converts comma decimal separators to dots in a string
 * 
 * @param string|null $string The string containing numbers with comma decimals
 * @return string The string with commas replaced by dots
 */
function commaToDot(?string $string = null): string 
{
    // Return empty string if input is null
    if ($string === null) {
        return '';
    }
    
    return str_replace(',', '.', $string);
}

function dotToComma(?string $string = null): string 
{
    // Return empty string if input is null
    if ($string === null) {
        return '';
    }
    
    return str_replace('.', ',', $string);
}