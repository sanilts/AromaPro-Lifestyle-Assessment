<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 * 
 */

add_shortcode('san_color_box','san_color_box_shortcode');

function san_color_box_shortcode($data){
    
    return extractColorFromString($data['id']);
    
}

function findWordInSentence($sentence, $wordArray) {
    
    // Convert sentence to lowercase for case-insensitive matching
    $sentence = mb_strtolower(trim($sentence));
    
    // Create an array of words from the sentence
    $sentenceWords = explode(' ', $sentence);
    
    foreach ($wordArray as $searchWord) {
        $searchWord = mb_strtolower(trim($searchWord));
        
        // First check for exact word match
        if (in_array($searchWord, $sentenceWords)) {
            return $searchWord;
        }
        
        // Then check for partial word match
        foreach ($sentenceWords as $sentenceWord) {
            if (str_contains($sentenceWord, $searchWord) || str_contains($searchWord, $sentenceWord)) {
                return $searchWord;
            }
        }
    }
    
    return "Error 404".$sentence; // Return empty string if no match found
}

function extractColorFromString($data){
    
    $characterSets = [
        'ACQUILEA',
        'ALMOND',
        'AMBAR',
        'APPLE',
        'AQUA',
        'ARENA',
        'ARGENT',
        'ARTICO',
        'ASH',
        'AZZURRO',
        'BARK',
        'BCO',
        'BEIGE',
        'BERRY',
        'BIANCO',
        'BLACK',
        'BLANCO',
        'BLU',
        'BLUE',
        'BONE',
        'BRASS',
        'BRILLO',
        'BRONZE',
        'BROWN',
        'CELESTE',
        'CENIZA',
        'CEREZO',
        'CLASICO',
        'COAL',
        'COPPER',
        'CREAM',
        'CREMA',
        'DARK',
        'DESERT',
        'DORADO',
        'EARTH',
        'ESMERALDA',
        'FANGO',
        'GOLD',
        'GRAFITO',
        'GRANA',
        'GRAPHITE',
        'GRAY',
        'GREEN',
        'GREIGE',
        'GREY',
        'GRIGIO',
        'GRIS',
        'HONEY',
        'IVORY',
        'LINEN',
        'MARENGO',
        'MARFIL',
        'MATE',
        'MENTA',
        'MICA',
        'MINT',
        'MOKA',
        'MOSS',
        'MULTI',
        'NACAR',
        'NATURA',
        'NAVI',
        'NEGRO',
        'NERO',
        'NOCE',
        'NOGAL',
        'NUDE',
        'NUT',
        'OCEAN',
        'OCRE',
        'OPALO',
        'ORO',
        'PEACH',
        'PEARL',
        'PERLA',
        'ROBLE',
        'ROSE',
        'ROSSO',
        'SABBIA',
        'SAGGIO',
        'SAND',
        'SILVER',
        'SMERALDO',
        'SNOW',
        'TABACO',
        'TAUPE',
        'TERRA',
        'TORTORA',
        'TURQUESA',
        'VERT',
        'VIOLA',
        'WENGUE',
        'WHITE',
        'MARENGO',
        'MARFIL',
        'ZIRCON',
        'MOKA',
        'MENTA',
        'BEIGE',
        'SIENA',
        'MINT',
        'MULTI',
        'MOKA',
        'METAL',
        'MIX-GRIS',
        'ARGENTO',
        'HAYA',
        'ARCE'
    ];
    
    
    return ucwords(strtolower(findWordInSentence($data, $characterSets)));
} 

 /*

function extractColorFromString(string $productName): ?string
{
    $colors = getBaseColors();
    $productWords = preg_split('/[\s.]+/', strtoupper($productName));
    
    foreach ($productWords as $word) {
        // Clean the word from common prefixes and special characters
        $word = trim($word, '().,- ');
        $word = preg_replace('/^(CR\.|RLV\.|EXT\.|ES\.|AT\.|M)/', '', $word);
        
        if (in_array($word, $colors)) {
            return $word;
        }
    }
    
    return '404 Error'.$productName;
}
// Example usage:
/*
$productLines = [
    "120X120 ACQUILEA ORO",
    "120X120 ANTIC.CALACATA GOLD",
    "120X120 CROMAT BLANCO"
];

$colors = getColorsFromProducts($productLines);
print_r($colors);
*/