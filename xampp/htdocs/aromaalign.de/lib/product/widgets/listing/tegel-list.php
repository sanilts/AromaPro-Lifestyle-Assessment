<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class TM_Tegel_List extends \Elementor\Widget_Base {

    public function get_name() {
        return 'tm-tegel-list-widget';
    }

    public function get_title() {
        return esc_html__('Tegel List', 'plugin-name');
    }

    public function get_icon() {
        return 'eicon-image';
    }

    public function get_categories() {
        return ['tm-widgets'];
    }

    protected function register_controls() {
        // Add controls here
    }

    protected function render() {
        global $post;
        if (!$post) {
            return;
        }

        $post_id = $post->ID;
        $settings = $this->get_settings_for_display();
        $mdata = get_post_meta($post_id, 'tegels_meta', true);

        if (empty($mdata)) {
            return;
        }

        $data = $this->multiplyAndSortArray($mdata);

        $extremes = $this->findWidthExtremes($data);
        $ratio = 30 / $extremes['smallest'];

        if (!empty($data) && is_array($data)) {
            foreach ($data as $i => $item) {
                if (!isset($item['image'], $item['tile_size'])) {
                    continue;
                }

                $image = esc_url($item['image']);
                $size = strtolower($item['tile_size']);
                $rectified = isset($item['rectified']) && $item['rectified'] === 'Ja' ? 'R' : '';

                $result = $this->splitSize($size);
                if (!$result) {
                    continue;
                }

                $length = floatval($result['length']);
                $width = floatval($result['width']);

                $this->render_tile($image, $length, $width, $rectified, $post_id, $i, $ratio);
            }
        }
        ?>
        <div class="tegel-lightbox">
            <span class="close">&times;</span>
            <img src="" alt="<?php echo esc_attr__('Lightbox Image', 'plugin-name'); ?>">
        </div>

        <script>
            function openTegelLightbox(imageUrl) {
                const lightbox = document.querySelector('.tegel-lightbox');
                const lightboxImg = lightbox.querySelector('img');
                lightboxImg.src = imageUrl;
                lightbox.classList.add('active');

                // Close on clicking outside the image
                lightbox.addEventListener('click', function (e) {
                    if (e.target !== lightboxImg) {
                        closeTegelLightbox();
                    }
                });
            }

            function closeTegelLightbox() {
                const lightbox = document.querySelector('.tegel-lightbox');
                lightbox.classList.remove('active');
            }

            // Close on escape key
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    closeTegelLightbox();
                }
            });

            // Close button event
            document.querySelector('.tegel-lightbox .close').addEventListener('click', closeTegelLightbox);
        </script>
        <?php
    }

    private function findWidthExtremes($tiles) {
        $widths = array_map(function ($tile) {
            $dimensions = explode('x', $tile['tile_size']);
            return (float) $dimensions[0];
        }, $tiles);

        return [
            'smallest' => min($widths),
            'largest' => max($widths)
        ];
    }

    private function adjustDimensions($width, $length) {
        // Calculate original ratio
        $ratio = $width / $length;

        // Minimum dimension checks
        if ($width < 100) {
            $width = 100;
            $length = $width / $ratio;
        }

        if ($length < 80) {
            $length = 80;
            $width = $length * $ratio;
        }

        // Maximum dimension checks
        if ($width > 400) {
            $width = 400;
            $length = $width / $ratio;
        }

        if ($length > 300) {
            $length = 300;
            $width = $length * $ratio;
        }

        return [
            'width' => round($width, 2),
            'length' => round($length, 2)
        ];
    }

    private function render_tile($image, $length, $width, $rectified, $post_id, $index, $ratio) {
        $image_width = $width * $ratio;
        $image_length = $length * $ratio;

        $dimensions = $this->adjustDimensions($image_width, $image_length);
        ?>
        <div class="single-tegel" style="position: relative; display: block; margin: 10px; width:<?php echo esc_attr($dimensions['width']); ?>px; height:<?php echo esc_attr($dimensions['length']); ?>px;">
            <img src="<?php echo esc_url($image); ?>" 
                 alt="<?php echo esc_attr__('Tile Image', 'plugin-name'); ?>" 
                 style="width: 100%; height:100%"
                 onclick="openTegelLightbox('<?php echo esc_js($image); ?>')">

            <div class="single-tegel-size" style="position: absolute; top: 0; right: 0; color: white; background-color: rgba(0, 0, 0, 0.5); padding: 5px; border-radius: 0">
        <?php echo esc_html(dotToComma($length) . 'x' . dotToComma($width) . $rectified); ?>
            </div>

        <?php echo wp_kses_post($this->tegelMetaDataPopup($post_id, 'fa-info-circle', $index)); ?>
        </div>
        <?php
    }

    private function splitSize($size) {
        if (empty($size)) {
            return false;
        }

        $size = str_replace(',', '.', $size);
        $size = str_ireplace('x', 'x', $size);
        $dimensions = explode('x', $size);

        if (count($dimensions) !== 2) {
            return false;
        }

        return [
            'length' => floatval(trim($dimensions[0])),
            'width' => floatval(trim($dimensions[1]))
        ];
    }

    private function multiplyAndSortArray($array) {
        if (!is_array($array)) {
            return [];
        }

        usort($array, function ($a, $b) {
            if (!isset($a['tile_size'], $b['tile_size'])) {
                return 0;
            }

            $sizeA = $this->splitSize($a['tile_size']);
            $sizeB = $this->splitSize($b['tile_size']);

            if (!$sizeA || !$sizeB) {
                return 0;
            }

            $areaA = $sizeA['length'] * $sizeA['width'];
            $areaB = $sizeB['length'] * $sizeB['width'];

            return $areaA <=> $areaB;
        });

        return $array;
    }

    private function wraphtml_attribute($title, $value) {
        return sprintf(
                '<div class="popup-row"><span class="popup-label">%s</span><span class="popup-value">%s</span></div>',
                esc_html($title),
                $value
        );
    }

    private function tegelMetaDataPopup($post_id, $icon, $index) {
        $mdata = get_post_meta($post_id, 'tegels_meta', true);
        

        if (empty($mdata)) {
            return;
        }

        $tegels_meta = $this->multiplyAndSortArray($mdata);
        

        // First check if tegels_meta exists and is an array
        if (!empty($tegels_meta) && is_array($tegels_meta)) {
            $item_index = 'item-' . $index;

            // Initialize meta_data as null
            $meta_data = null;

            // Check if numeric index exists
            if (isset($tegels_meta[$index]) && is_array($tegels_meta[$index])) {
                $meta_data = $tegels_meta[$index];
            }
            // Check if string index exists
            elseif (isset($tegels_meta[$item_index]) && is_array($tegels_meta[$item_index])) {
                $meta_data = $tegels_meta[$item_index];
            } else {
                $meta_data = $tegels_meta[$post_id];
            }

            // If no valid meta_data was found, return empty string
            if (!$meta_data) {
                return '';
            }
        } else {
            return '';
        }

        $fields = [
            'color' => __('Kleur', 'plugin-name'),
            'ean_13' => __('EAN', 'plugin-name'),
            'boxes_pallet' => __('Dozen pallet', 'plugin-name'),
            'pieces_box' => __('Stuks/doos', 'plugin-name'),
            'sqm_box' => __('M2/doos', 'plugin-name'),
            'kg_pallet' => __('KG pallet', 'plugin-name'),
            'creation_date' => __('Aanmaakdatum', 'plugin-name'),
            'abrasion_pei' => __('Slijtage PEI', 'plugin-name'),
            'antislip_shoes' => __('Antislipschoenen', 'plugin-name'),
            'sqm_pal' => __('SQM Pal', 'plugin-name'),
            'kg_box' => __('KG Doos', 'plugin-name'),
            'lenght_pal_cm' => __('Lengte PAL (cm)', 'plugin-name'),
            'width_pal_cm' => __('Breedte  PAL(cm)', 'plugin-name'),
            'height_pal_cm' => __('Hoogte Pal(cm)', 'plugin-name'),
            'stackable_heights' => __('Stapelbare hoogtes', 'plugin-name'),
            'tile_thickness' => __('Dikte', 'plugin-name'),
            'chromatic_variation' => __('Chromatische variatie', 'plugin-name'),
            'manufature_color' => __('Vervaardigingskleur', 'plugin-name')
        ];

        $output = sprintf(
                '<div class="popover__wrapper"><a href="#"><i class="fas %s" style="color: #F8F8F8; font-size: 24px; background-color:#333; border: 2px solid #333; border-radius:50px;"></i></a><div class="popover__content"><div class="popup-container">',
                esc_attr($icon)
        );

        foreach ($fields as $key => $label) {
            if (isset($meta_data[$key]) && !empty($meta_data[$key])) {
                $output .= $this->wraphtml_attribute($label, $meta_data[$key]);
            }
        }

        $output .= '</div></div></div>';
        return $output;
    }
}
