<?php

/**
 * Maps Pamesa finish types to standardized finish names
 * 
 * @param string|null $finishType The original Pamesa finish type
 * @return string The standardized finish name or error message if not found
 */
function importPamesaFinish(?string $finishType = null): string 
{
    // Return error if no finish type provided
    if ($finishType === null) {
        return '404 error-null';
    }

    // Mapping of original finish types to standardized names
    $finishMap = [
        'ALTO BRIILO SEMIPULIDO'=> 'Semi-Gepolijst',
        'BLANCOS'               => 'Glans',
        'BRILLO RECTIFICADO'    => 'Glans',
        'CR LUX'                => 'Gepolijst',
        'LEVIGLASS'             => 'Gepolijst',
        'LUXGLASS'              => 'Semi-Gepolijst',
        'MATE'                  => 'Mat',
        'MATE RECTIFICADO'      => 'Mat',
        'SATEN'                 => 'Mat',
        'SATEN RECTIFICADO'     => 'Mat',
        'SEMIPULIDO'            => 'Semi-Gepolijst',
        'BRILLO'                => 'Glans',
        'ANTIC'                 => 'Mat',
        'PULIDO'                => 'Gepolijst'
    ];

    // Return mapped finish type if it exists, otherwise return error
    return $finishMap[$finishType] ?? '404 error-' . $finishType;
}
function import_aleluia_finish($finishType = null) {
    // Return error if no finish type provided
    if ($finishType === null) {
        return '404 error-null';
    }

    // Mapping of original finish types to standardized names
    $finishMap = [
        'Anti-slip'         => 'Mat',
        'Natural (matte)'   => 'Mat',
        'Matte'             => 'Mat',
        'Shine'             => '',
        'CI'                => 'Glans',
        'Soft Anti-slip'    => 'Semi-Gepolijst',
        'Drainage Textured' => 'Gepolijst',
        'Natural'           => 'Gepolijst',        
        'Textured CI'       => 'Gepolijst',                
        'Polished'          => 'Gepolijst',         
    ];

    // Return mapped finish type if it exists, otherwise return error
    return $finishMap[$finishType] ?? '404 error-' . $finishType;
}

function import_ceragni_finish($finish = null) {
    $finishMap = [
        'glossy' => 'Glans',
        'matt' => 'Mat',
        'mat' => 'Mat'
    ];
    
    // Convert to lowercase for case-insensitive matching
    $finishLower = strtolower($finish);
    
    // Return the mapped value if it exists, otherwise return the original
    return isset($finishMap[$finishLower]) ? $finishMap[$finishLower] : $finish;
}

function import_cerrad_finish($data = null) {
    return $data;
}

function import_cliper_finish($data = null) {
    return $data;
}

function import_douglas_jones_finish($data = null) {
    return $data;
}

function import_granitifiandre_finish($data = null) {
    return $data;
}

function import_marbles_finish($data = null) {
    return $data;
}

function import_mosavit_finish($data = null) {
    return $data;
}