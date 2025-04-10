<?php
/**
 * Maps numeric color codes to their corresponding color names for Pamesa products
 * 
 * @param int|null $colorCode The numeric color code to look up
 * @return string The corresponding color name or error message if not found
 */

require('color/aleluia.php');
require('color/pamesa.php');
require('color/ceragni.php');
/*

*/
function importPamesaColor(?int $colorCode = null): string 
{
    // Color mapping arrays
    $colorMap = [
        'Beige'         => [689, 53, 102, 117, 119, 126, 150, 16, 163, 164, 165, 169, 17, 198, 199, 204, 208, 232, 24, 249, 265, 266, 270, 274, 299, 30, 310, 313, 323, 33, 358, 36, 370, 382, 383, 408, 469, 539, 59, 598, 7, 755, 78, 96, 97, 399],
        'Zwart'         => [157, 184, 215, 3, 535, 1011],
        'Blauw'         => [548, 393, 111, 118, 151, 158, 377, 379, 4, 417, 48, 498, 55, 171, 393],
        'Bruin'         => [9, 815, 704, 56, 109, 113, 126, 16, 160, 18, 197, 198, 199, 203, 208, 22, 231, 234, 24,240, 249, 285, 287, 310, 34, 342, 343, 358, 36, 408, 413, 449, 46, 476, 59, 755, 805, 91, 92, 95, 1012, 1013, 154, 23, 196, 166, 196, 154, 258, 283, 322, 322, 335, 336, 369],
        'Groen'         => [973, 942, 110, 14, 194, 27, 346, 5, 519, 543, 973, 1233, 339, 340, 478],
        'Grijs'         => [712, 711, 713, 656, 572, 505, 115, 125, 135, 139, 161, 19, 2, 213, 217, 223, 270, 28, 285, 294, 297, 298, 300, 341, 344, 364, 366, 37, 387, 418, 508, 540, 667, 68, 877, 1072, 1231, 47267,248,357, 368, 438],
        'Multicolor'    => [1, 6, 100, 32, 343, 395, 52, 524, 558, 570, 603, 1222, 1232, 227, 251, 271, 472],
        'Oranje'        => [126, 287, 331, 46, 57, 599, 1017],
        'Paars'         => [70, 271],
        'Rood'          => [766, 195, 21, 236, 243, 252, 40, 8, 92],
        'Wit'           => [781, 706, 63, 102, 103, 108, 12, 150, 163, 164, 165, 169, 17, 174, 204, 217, 223, 225, 260, 265, 274, 296, 299, 30, 313, 364, 370, 387, 413, 667, 78, 804, 96, 803],
        'Geel'          => [11, 52, 824, 876],
        'Goud'          => [189, 32, 701, 957, 793, 673, 210, 1071, 44798, 439],
        'Zilver'        => [183, 31, 443]
    ];


    // If no color code provided, return error
    if ($colorCode === null) {
        return '404 error-null';
    }

    // Search for color code in each color group
    foreach ($colorMap as $colorName => $codes) {
        if (in_array($colorCode, $codes)) {
            return $colorName;
        }
    }

    // Return error if color code not found
    return "404 error-$colorCode";
}


function importCeragniColor($specificColor) {
    // Convert to lowercase for case-insensitive comparison
    $specificColor = trim(strtolower($specificColor));
    
    // Define mapping of specific colors to general categories
    $colorMap = [
        // Beige category
        'beige' => 'Beige',
        'ivory' => 'Beige',
        'cream' => 'Beige',
        'shell beige' => 'Beige',
        'sand beige' => 'Beige',
        'recife bege' => 'Beige',
        'caramel' => 'Beige',
        
        // Black category
        'black' => 'Zwart',
        'black lily' => 'Zwart',
        'anthracite' => 'Zwart',
        
        // Blue category
        'blue' => 'Blauw',
        'blue cobalt' => 'Blauw',
        'blue tiffany' => 'Blauw',
        'polar blue' => 'Blauw',
        'ocean blue' => 'Blauw',
        'royal blue' => 'Blauw',
        
        // Brown category
        'chocolate' => 'Bruin',
        'mocha' => 'Bruin',
        
        // Green category
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
        'anis' => 'Groen',
        
        // Grey category
        'grey' => 'Grijs',
        'fog grey' => 'Grijs',
        'rain grey' => 'Grijs',
        
        // Orange category
        'orange' => 'Oranje',
        'fire' => 'Oranje',
        'desert orange' => 'Oranje',
        
        // Purple category
        'purple' => 'Paars',
        'lilas' => 'Paars',
        'aubergine' => 'Paars',
        'mauve' => 'Paars',
        
        // Red category
        'red' => 'Rood',
        'cherry' => 'Rood',
        'crimson' => 'Rood',
        'ruby' => 'Rood',
        'salmon' => 'Rood',
        'pink' => 'Rood',
        
        // White category
        'white' => 'Wit',
        'old white' => 'Wit',
        'magnolia' => 'Wit',
        
        // Yellow category
        'yellow' => 'Geel',
        'mustard' => 'Geel',
        'yellow sunset' => 'Geel',
        
        // Not in your target list but adding for completeness
        'gold' => 'Goud',
        'silver' => 'Zilver'
    ];
    
    // Return the mapped color or "Unknown" if not found
    return isset($colorMap[$specificColor]) ? $colorMap[$specificColor] : "404 error-".$specificColor;
}
