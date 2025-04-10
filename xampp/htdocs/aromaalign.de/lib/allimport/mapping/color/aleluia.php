<?php

function importAleluiaColor($colorCode = null)
{
    // Color mapping arrays
    $colorMap = [
        'Beige'         => ['Beige', 'Sand', 'Cream', 'AREIA', 'Camurça', 'Camelo', 'Papiro', 'Linen', 'Areia', 'Bege', 'Champagne', 'Greige'],
        'Zwart'         => ['Black', 'CHARCOAL BLACK', 'Preto', 'Negro', 'Charcoal', 'Carbon', 'Lava', 'Black Lava'],
        'Blauw'         => ['Blue', 'Blue Pearl', 'Misty Blue', 'BLUE COBALT', 'COBALTO', 'Atlântico', 'Ártico', 'Azul nórdico', 'Safira', 'Petróleo', 'Celeste', 'Lagoa', 'Navy', 'Marinho', 'Índigo', 'Azul Nórdico', 'Blue Cobalt'],
        'Bruin'         => ['Cacau', 'Castanho', 'The CLAY', 'Terracota', 'SANGUE DE BOI', 'Clay'],
        'Groen'         => ['Alface','Verde Água','Lichen grren', 'Sage grren', 'Verde sage', 'Verde negro', 'Verde água', 'ESMERALDA', 'Verde garrafa', 'Bosque', 'Verde jade', 'Kiwi', 'Menta', 'Green', 'Sage Green', 'Esmeralda', 'Green Jade', 'Lichen Green', 'Olive', 'Verde Garrafa', 'Verde Negro', 'Verde Sage'],
        'Grijs'         => ['Oyster','Grey', 'Light grey', 'Soft GREY', 'PEPPER GRY', 'Graphite', 'Dark grey', 'Cinza Claro', 'Cinza escuro', 'SMOKE', 'Tempestade', 'Anthract', 'Soft Grey', 'Pepper Grey', 'Dark Grey', 'Light Grey', 'Smoke', 'Anthracite', 'Charcoal Black', 'Cinza Escuro', 'Taupe'],
        'Oranje'        => ['LARANJA', 'Tangerina', 'Papaia', 'Âmbar', 'Laranja'],
        'Paars'         => ['Beringela', 'Alfazema', 'Blush', 'Misty Coral', 'Rosa Clara', 'Salmão'],
        'Rood'          => ['Rose', 'Rosa velho', 'Rosa coun', 'Encarnado', 'Salmã', 'Boggundy', 'Burgundy', 'Sangue de Boi'],
        'Wit'           => ['Branco', 'White', 'Cotton White', 'OFF-White', 'Warm white', 'OYSTER', 'Ivory', 'Marfim', 'Pérala', 'Glaciar', 'Creme', 'Off', 'Pérola', 'Warm White'],
        'Geel'          => ['Ginkgo yellow', 'Safffron yellow', 'Amarelo', 'Camomila', 'Yellow', 'Saffron Yellow', 'Ginkgo Yellow'],
        'Goud'          => ['Âmbar'],
        'Zilver'        => ['Pearl', 'Pérala'],
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
    return "404 error-".$colorCode;
}