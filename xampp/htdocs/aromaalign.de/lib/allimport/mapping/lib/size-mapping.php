<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

function size_mapping($size){    
    $step_1=round($size/5);
    $step_2=(float)$step_1;
    $step_3=$step_2*5;    
    return $step_3;   
}

function mmtocm($size){
    return $size/10;
}

function almmToCm($dimensions) {
    // Remove any whitespace
    $dimensions = trim($dimensions);
    
    // Split the string at 'x' to get width and height
    $parts = explode('x', $dimensions);
    
    if (count($parts) !== 2) {
        return "Invalid format. Please use format like '445x456'";
    }
    
    // Convert each measurement from mm to cm
    $width_cm = number_format($parts[0] / 10, 1);
    $height_cm = number_format($parts[1] / 10, 1);
    
    // Return the formatted result
    return $width_cm . "x" . $height_cm;
}