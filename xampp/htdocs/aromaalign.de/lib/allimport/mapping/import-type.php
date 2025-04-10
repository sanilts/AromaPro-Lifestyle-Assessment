<?php

/**
 * Maps Pamesa tile type codes to their usage descriptions in Dutch
 * 
 * @param string|int|null $typeCode The tile type code
 * @return string The tile usage description in Dutch or error message if not found
 */
function importPamesaType($typeCode = null): string 
{
    // Return error if no type code provided
    if ($typeCode === null) {
        return '404 error-null';
    }

    // Constants for tile types
    $wallOnly = 'Wandtegels';
    $wallAndFloor = 'Vloertegels; Wandtegels';

    // Codes that are wall-only tiles
    $wallOnlyCodes = [2, 5, 27, 31, 36];

    // Codes that are both wall and floor tiles
    $wallAndFloorCodes = [
        1, 4, 9, 11, 15, 17, 21, 25, 28, 32, 33, 35, 
        38, 39, 40, 41, 46, 50, 51, 52, 54, 63, 71, 86
    ];

    // Convert input to integer if it's a numeric string
    $typeCode = is_numeric($typeCode) ? (int)$typeCode : $typeCode;

    // Check if code is in wall-only set
    if (in_array($typeCode, $wallOnlyCodes)) {
        return $wallOnly;
    }

    // Check if code is in wall-and-floor set
    if (in_array($typeCode, $wallAndFloorCodes)) {
        return $wallAndFloor;
    }

    // Return error for unknown codes
    return '404 error-' . $typeCode;
}

function import_aleluia_type($data = null) {
    if($data=='Wall Tiles'){
        return 'Wandtegels';
    }else{
            return 'Vloertegels; Wandtegels';
        }
}

function import_ceragni_type($data = null) {
    if ($data=="wall" || $data=="Wall") {
        return "Wandtegels";
    } else {
        throw new Exception("Error 404:".$data);
    }
}

function import_cerrad_type($data = null) {
    
}

function import_cliper_type($data = null) {
    
}

function import_douglas_jones_type($data = null) {
    
}

function import_granitifiandre_type($data = null) {
    
}

function import_marbles_type($data = null) {
    
}

function import_mosavit_type($data = null) {
    
}