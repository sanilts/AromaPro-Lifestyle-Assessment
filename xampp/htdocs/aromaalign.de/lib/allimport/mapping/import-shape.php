<?php

/**
 * Maps numeric codes to tile shape descriptions in Dutch
 * 
 * @param int|null $shapeCode The numeric shape code to look up
 * @return string The corresponding shape name in Dutch or error message if not found
 */
/*
Metrotegels ->  Subway tiles
Mozaïek     ->  Mosaic
Rechthoek   ->  Rectangle
Vierkant    ->  Square
Zeshoek     ->  Hexagon
 *  
 */
function importVorm(?int $shapeCode = null): string 
{
    // Return error if no shape code provided
    if ($shapeCode === null) {
        return '404 error-null';
    }

    // Shape mapping array
    $shapeMap = [
        'Metrotegels' => [223, 101, 144, 166, 176, 177, 179, 202, 206, 231, 233,
            238, 254, 257, 318, 322, 323, 325, 342, 344, 357, 392, 393, 434, 441,
            446, 538, 550, 598, 617
            ],
        'Mozaïek' => [204, 237, 287, 590, 635],
        'Rechthoek' => [
            56, 87, 95, 121, 137, 174, 200, 220, 229, 241, 250, 285, 293, 295, 
            350, 380, 386, 389, 390, 412, 450, 451, 505, 511, 547, 555, 588, 
            627, 628, 633, 645, 655, 670, 681, 700, 722, 725, 781, 793, 805, 
            825, 838, 869, 871, 872, 889, 890, 896, 116, 125, 138, 172, 183, 
            185, 197, 227, 244, 245, 253, 254, 258, 260, 262, 263, 267, 288,
            294, 296, 30, 300, 303, 306, 313, 329, 398, 400, 420, 421, 428, 430,
            431, 467, 475, 498, 501, 522, 524, 527, 535, 536, 541, 545, 549, 553,
            583, 597, 599, 609, 612, 619, 636, 64, 640, 643, 657, 668, 84, 961
        ],
        'Vierkant' => [
            20, 94, 120, 281, 301, 302, 315, 340, 361, 440, 523, 618, 701, 
            804, 807, 817, 826, 840, 861, 875, 154, 240, 3, 397, 460, 90
        ],
        'Zeshoek' => [122, 292, 297, 514, 520]
    ];

    // Search for shape code in each shape group
    foreach ($shapeMap as $shapeName => $codes) {
        if (in_array($shapeCode, $codes)) {
            return $shapeName;
        }
    }

    // Return error if shape code not found
    return "404 error-$shapeCode";
}
 

function import_aleluia_shape($data = null) {
    
    if($data=='Square'){
        return 'Vierkant';
    }elseif($data=='Rectangle'){
        return 'Rechthoek';
    }else{
        return "errro-404";
    }
}


function import_ceragni_shape($dimensions) {
    // Extract width and height from the format "widthxheight"
    $parts = explode('x', strtolower($dimensions));
    
    if (count($parts) != 2) {
        return "Invalid format. Please use format like '15x15' or '7,5x30'";
    }
    
    $width = $parts[0];
    $height = $parts[1];
    
    // Normalize inputs - convert comma to dot for decimal notation
    $width = str_replace(',', '.', $width);
    $height = str_replace(',', '.', $height);
    
    // Convert to float for comparison
    $width = (float) $width;
    $height = (float) $height;
    
    // Check if width equals height
    if ($width == $height) {
        return "Vierkant";
    } else {
        return "Rechthoek";
    }
}
/*
function import_ceragni_shape($data = null) {
    
}

function import_cerrad_shape($data = null) {
    
}

function import_cliper_shape($data = null) {
    
}

function import_douglas_jones_shape($data = null) {
    
}

function import_granitifiandre_shape($data = null) {
    
}

function import_marbles_shape($data = null) {
    
}

function import_mosavit_shape($data = null) {
    
}

*/