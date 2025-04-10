<?php
//add libraries
require('widgets/lib/icon-generator.php');

// Define the path to the widget
define('TEGEL_MONSTERS_PATH', plugin_dir_path(__FILE__));

// Register Custom Category
add_action('elementor/elements/categories_registered', 'add_custom_widget_category', 1);
function add_custom_widget_category($elements_manager) {
    $elements_manager->add_category(
        'tm-widgets',
        [
            'title' => 'Tegel Monster',
            'icon' => 'fa fa-plug',
            'priority' => 1,
        ]
    );
}

class Latest_Posts_AJAX_Loader {
    
    public function __construct() {
        add_action('elementor/widgets/widgets_registered', array($this, 'register_widgets'));
    }

    public function register_widgets()  {
        
        /* button start here */
         require_once(TEGEL_MONSTERS_PATH. 'widgets/button/tegel.php');
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new Tegel_Cart_Button_Widget()); 
        
        require_once(TEGEL_MONSTERS_PATH. 'widgets/button/brochure.php');
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new Brochure_Add_To_Cart_Widget());   
        
        require_once(TEGEL_MONSTERS_PATH. 'widgets/button/monstermap.php');
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new MonsterMap_Add_To_Cart_Widget()); 
        
        require_once(TEGEL_MONSTERS_PATH. 'widgets/button/tegel-new.php');
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new Custom_Woo_Add_To_Cart()); 
        
        
        /* downloads start here */
        require_once(TEGEL_MONSTERS_PATH. 'widgets/downloads/brochure.php');
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new Elementor_PDF_Handler_Widget());
        
        
        /* listing  start here */        
        require_once(TEGEL_MONSTERS_PATH. 'widgets/listing/image-widget.php');
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new Elementor_Dynamic_Image_Widget());
        
        require_once(TEGEL_MONSTERS_PATH. 'widgets/listing/tegel-list.php');
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new TM_Tegel_List());
        
        
        
        require_once(TEGEL_MONSTERS_PATH. 'widgets/taxonomy/list.php');
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new Tegel_Taxomony_List());
        
        require_once(TEGEL_MONSTERS_PATH. 'widgets/taxonomy/icons.php');
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new Tegel_Taxonomy_Icons());
        
       
        





//require_once(TEGEL_MONSTERS_PATH. 'widgets/monstermap-addtocart-button.php'); recommanded for delete
        //\Elementor\Plugin::instance()->widgets_manager->register_widget_type(new Custom_Add_To_Cart_Widget());
        
        
        
        
        
          
        
    }
}

new Latest_Posts_AJAX_Loader();

// Ensure Elementor is loaded
function check_elementor_dependency() {
    if (!did_action('elementor/loaded')) {
        add_action('admin_notices', function() {
            $message = sprintf(
                esc_html__('Tegel Monsters Pro requires Elementor plugin to be installed and activated. %1$s', 'tegel-monsters-pro'),
                '<a href="' . esc_url(admin_url('plugin-install.php?s=Elementor&tab=search&type=term')) . '">Install Elementor</a>'
            );
            printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
        });
        return;
    }
}
add_action('plugins_loaded', 'check_elementor_dependency');