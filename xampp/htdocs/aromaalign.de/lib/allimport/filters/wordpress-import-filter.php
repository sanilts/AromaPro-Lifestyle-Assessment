<?php
/**
 * Filter to skip products with empty required fields during WooCommerce All Import
 * Add this code to your theme's functions.php or a custom plugin
 */
add_filter('wp_all_import_is_post_to_skip', 'skip_empty_products', 10, 5);

function skip_empty_products($is_to_skip, $data, $import_id, $iteration, $post_type) {
    // Only run this filter for WooCommerce products
    if ($post_type !== 'product') {
        return $is_to_skip;
    }
    
    // Define the required fields exactly as they appear in your CSV headers
    // For example, if your CSV has columns like "Product Name", "Product Price", etc.
    $required_fields = array(
        'Collection',        // If this is your CSV column header for title
        'Color description',       // If this is your CSV column header for price
        'Finish',         // If this is your CSV column header for SKU
        'Size', // If this is your CSV column header for description
        'Colour',               // If this is your CSV column header for stock quantity
        'Size description',               // If this is your CSV column header for stock quantity
        'Family',               // If this is your CSV column header for stock quantity
        'Image code',               // If this is your CSV column header for stock quantity
        'EAN13'
    );
    
    // Check each required field
    foreach ($required_fields as $field) {
        // Check if the field exists in the data array and is empty
        if (!isset($data[$field]) || 
            empty(trim($data[$field])) || 
            $data[$field] === 'NULL' || 
            $data[$field] === 'null') {
            
            // Log the skipped product (optional)
            error_log(sprintf(
                'Skipping product import on iteration %d: Missing required field "%s"',
                $iteration,
                $field
            ));
            
            return true; // Skip this product
        }
    }
    
    return $is_to_skip; // Don't skip if all required fields are present
}
