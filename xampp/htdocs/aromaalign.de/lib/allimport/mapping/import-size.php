<?php

/**
 * Class to handle Pamesa tile size processing and categorization
 */
class PamesaSizeProcessor
{
    /**
     * Size categories with their corresponding length ranges
     */
    private const SIZE_CATEGORIES = [
        'Klein' => ['min' => 0, 'max' => 45],
        'Medium' => ['min' => 45, 'max' => 80],
        'Groot' => ['min' => 80, 'max' => 100],
        'XXL' => ['min' => 100, 'max' => PHP_FLOAT_MAX]
    ];

    /**
     * Clean and normalize size string
     * 
     * @param string|null $sizeString Original size string
     * @return string Cleaned size string
     */
    public function cleanSizeString(?string $sizeString): string
    {
        if ($sizeString === null) {
            return '';
        }

        // Remove content in parentheses
        $cleaned = preg_replace("/\([^)]+\)/", "", $sizeString);
        
        // Remove special characters except numbers, spaces, and commas
        $cleaned = preg_replace("/[^a-zA-Z0-9\s,]/", "", $cleaned);
        
        // Remove all letters except 'x'
        $cleaned = preg_replace("/[a-wy-zA-WY-Z]/", '', $cleaned);
        
        return strtolower($cleaned);
    }

    /**
     * Calculate size category with dimensions
     * 
     * @param string|null $sizeString Original size string
     * @return string Size category with dimensions or error message
     */
    public function calculateSizeCategory(?string $sizeString): string
    {
        // Clean the size string
        $cleaned = $this->cleanSizeString($sizeString);
        if (empty($cleaned)) {
            return '404 error-null input';
        }

        // Split into dimensions
        $dimensions = array_map('trim', explode('x', $cleaned));
        if (count($dimensions) !== 2) {
            return '404 error-invalid format';
        }

        // Sort dimensions (larger becomes length)
        $length = max((float)$dimensions[0], (float)$dimensions[1]);
        $width = min((float)$dimensions[0], (float)$dimensions[1]);

        // Map the dimensions
        $length = $this->mapSize($length);
        $width = $this->mapSize($width);

        // Determine category
        foreach (self::SIZE_CATEGORIES as $category => $range) {
            if ($length > $range['min'] && $length <= $range['max']) {
                return "$category > {$length}x{$width}";
            }
        }

        return '404 error-size out of range'.$sizeString;
    }

    /**
     * Map size value to standard measurement
     * 
     * @param float $size Size value to map
     * @return float Mapped size value
     */
    private function mapSize(float $size): float
    {
        // This is a placeholder for the size_mapping function mentioned in the original code
        // Implement your specific mapping logic here
        return $size;
    }
}

// Example usage:
$processor = new PamesaSizeProcessor();

/**
 * Process raw size string into cleaned format
 * 
 * @param string|null $data Original size string
 * @return string Cleaned size string
 */
function importPamesaSize(?string $data): string 
{
    $processor = new PamesaSizeProcessor();
    return $processor->cleanSizeString($data);
}

/**
 * Process raw size string into categorized format with dimensions
 * 
 * @param string|null $data Original size string
 * @return string Size category with dimensions
 */
function importPamesaSizeCat(?string $data): string 
{
    $processor = new PamesaSizeProcessor();
    return $processor->calculateSizeCategory($data);
}

function import_aleluia_size($length=null, $width=null){
    $length=mmtocm($length);
    $width=mmtocm($width);
    
    $length=dotToComma($length);
    $width=dotToComma($width);
    
    return $length.'x'.$width;
    
}

function import_aleluia_sizeCat($length=null, $width=null) {
    if ($length < $width) {
        $temp = $length;
        $length = $width;
        $width = $temp;
    }
    $length=mmtocm($length);
    $width=mmtocm($width);
    
    if($length)
    
    $size=$length.'x'.$width;
    $processor = new PamesaSizeProcessor();
    return $processor->calculateSizeCategory($size);
}

function import_ceragni_size($data = null) {
    $processor = new PamesaSizeProcessor();
    return $processor->calculateSizeCategory($data);
}

function import_cerrad_size($data = null) {
    
}

function import_cliper_size($data = null) {
    
}

function import_douglas_jones_size($data = null) {
    
}

function import_granitifiandre_size($data = null) {
    
}

function import_marbles_size($data = null) {
    
}

function import_mosavit_size($data = null) {
    
}