<?php

/**
 * Determines if a Pamesa tile is rectified based on its code
 * 
 * @param string|int|null $code The rectification code
 * @return string Returns "Ja" if rectified, "Nee" if not rectified
 */
function importPamesaRectified($code = null): string 
{
    // Set of codes that indicate rectified tiles
    $rectifiedCodes = [
        4, 17, 21, 25, 31, 32, 35, 39, 46, 50, 51, 52, 54, 71, 86
    ];

    // Convert input to integer if it's a numeric string
    $code = is_numeric($code) ? (int)$code : null;

    // Check if code is in the rectified set
    return in_array($code, $rectifiedCodes) ? 'Ja' : 'Nee';
}

function import_aleluia_rectified($data = null) {
    if(strtolower($data)==='yes'){
        return 'Ja';
    }
    elseif(strtolower($data)==='no'){
        return 'Nee';
    }else{
            return 'error-404'.$data;
    }
}

function import_ceragni_rectified($input = null) {
    $mapping = [
        'yes' => 'Ja',
        'no' => 'Nee'
    ];
    
    // Convert input to lowercase for case-insensitive matching
    $lowercaseInput = strtolower(trim($input));
    
    // Return mapped value if exists, otherwise return original input
    return isset($mapping[$lowercaseInput]) ? $mapping[$lowercaseInput] : $input;
}

function import_cerrad_rectified($data = null) {
    
}

function import_cliper_rectified($data = null) {
    
}

function import_douglas_jones_rectified($data = null) {
    
}

function import_granitifiandre_rectified($data = null) {
    
}

function import_marbles_rectified($data = null) {
    
}

function import_mosavit_rectified($data = null) {
    
}