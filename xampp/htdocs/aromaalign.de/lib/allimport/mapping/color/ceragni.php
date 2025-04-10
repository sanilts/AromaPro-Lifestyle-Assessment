<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

/**
 * Translates English color names to their closest Dutch equivalents
 * 
 * @param string $englishColor The English color name
 * @return string The closest Dutch color equivalent
 */
function translateColorCeragni($englishColor) {
    // Normalize input by trimming and converting to lowercase
    $englishColor = trim(strtolower($englishColor));
    
    // Define color mapping dictionary from English to Dutch
    $colorMapping = [
        // White family (Wit)
        'white' => 'Wit',
        'old white' => 'Wit',
        'ivory' => 'Wit',
        'cream' => 'Wit',
        'polar blue' => 'Wit',
        'magnolia' => 'Wit',
        
        // Beige family (Beige)
        'beige' => 'Beige',
        'shell beige' => 'Beige',
        'sand beige' => 'Beige',
        'recife bege' => 'Beige',
        
        // Black family (Zwart)
        'black' => 'Zwart',
        'anthracite' => 'Zwart',
        
        // Blue family (Blauw)
        'blue' => 'Blauw',
        'blue tiffany' => 'Blauw',
        'cobalt blue' => 'Blauw',
        'ocean blue' => 'Blauw',
        'royal blue' => 'Blauw',
        'pacific blue' => 'Blauw',
        'nautical blue' => 'Blauw',
        'mocha blue' => 'Blauw',
        
        // Brown family (Bruin)
        'chocolate' => 'Bruin',
        'mocha' => 'Bruin',
        'caramel' => 'Bruin',
        
        // Green family (Groen)
        'green' => 'Groen',
        'olive' => 'Groen',
        'pistache' => 'Groen',
        'pistachio' => 'Groen',
        'cape green' => 'Groen',
        'seaweed green' => 'Groen',
        'moss green' => 'Groen',
        'acqua green' => 'Groen',
        'pacific green' => 'Groen',
        'nautical green' => 'Groen',
        
        // Grey family (Grijs)
        'grey' => 'Grijs',
        'fog grey' => 'Grijs',
        'rain grey' => 'Grijs',
        
        // Orange family (Oranje)
        'orange' => 'Oranje',
        'fire' => 'Oranje',
        'desert orange' => 'Oranje',
        
        // Purple family (Paars)
        'purple' => 'Paars',
        'aubergine' => 'Paars',
        'lilas' => 'Paars',
        'mauve' => 'Paars',
        
        // Red family (Rood)
        'red' => 'Rood',
        'cherry' => 'Rood',
        'crimson' => 'Rood',
        'ruby' => 'Rood',
        'pink' => 'Rood',
        'salmon' => 'Rood',
        
        // Yellow family (Geel)
        'yellow' => 'Geel',
        'anis' => 'Geel',
        'mustard' => 'Geel',
        'yellow sunset' => 'Geel',

        // Gold (Goud)
        'gold' => 'Goud',
        
        // Silver (Zilver)
        'silver' => 'Zilver'
    ];
    
    // Direct match
    if (isset($colorMapping[$englishColor])) {
        return $colorMapping[$englishColor];
    }
    
    // If no direct match, try to find colors that contain the input as a substring
    foreach ($colorMapping as $key => $value) {
        if (strpos($key, $englishColor) !== false) {
            return $value;
        }
    }
    
    // If no match is found at all, return a default
    return "Onbekend"; // "Unknown" in Dutch
}

// Example usage:
// echo translateColorToDutch("blue tiffany"); // Outputs: Blauw
// echo translateColorToDutch("sand beige");   // Outputs: Beige
// echo translateColorToDutch("crimson");      // Outputs: Rood
