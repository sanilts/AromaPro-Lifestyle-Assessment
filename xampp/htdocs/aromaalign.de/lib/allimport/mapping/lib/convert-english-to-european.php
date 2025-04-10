<?php

/**
 * Converts numbers from English format (1,234.56) to European format (1.234,56)
 * 
 * @param string|float|int|null $number The number to convert (can be string with formatting or numeric)
 * @return string The number in European format or empty string if invalid input
 */
function convertEnglishToEuropean($number = null): string 
{
    // Handle null or empty input
    if ($number === null || $number === '') {
        return '';
    }

    // If input is already numeric, skip cleaning step
    if (is_numeric($number)) {
        $cleanNumber = $number;
    } else {
        // Remove any non-digit characters except decimal point
        $cleanNumber = preg_replace('/[^\d.]/', '', (string)$number);
        
        // Check if we have a valid number after cleaning
        if (!is_numeric($cleanNumber)) {
            return '';
        }
    }

    // Convert to float to handle decimal places properly
    $numericValue = floatval($cleanNumber);

    // Format with European conventions:
    // - Decimal separator: comma (,)
    // - Thousands separator: dot (.)
    // - No decimal places (as per original function)
    return number_format(
        $numericValue,
        2,  // decimal places
        ',', // decimal separator
        '.'  // thousands separator
    );
}

add_shortcode('decsan_info_box','decsan_info_box_shortcode');

function decsan_info_box_shortcode($data){
    
    $result=convertEnglishToEuropean($data['id']);
    if($result){
        return  $result;
    }else{
        return "Error 404".$data['id'];
    }
}