<?php 



$tma_addon = new RapidAddon('Varible Product Custom Attributes', 'tma_addon');

$tma_addon->add_field('tile_size', 'Tile Size', 'text');                                //01
$tma_addon->add_field('rectified', 'Rectified', 'text');                                //02

$tma_addon->add_field('code_article', 'Code Article', 'text');                          //03
$tma_addon->add_field('ean_13', 'EAN 13', 'text');                                      //04

$tma_addon->add_field('boxes_pallet', 'Box/Pal', 'text');                               //05
$tma_addon->add_field('pieces_box', 'Pc/Box', 'text');                                  //06
$tma_addon->add_field('sqm_box', 'Sqm/Box', 'text');                                    //07
$tma_addon->add_field('kg_pallet', 'Kg/Pal', 'text');                                   //08

$tma_addon->add_field('creation_date', 'Creation Date', 'text');                        //09
$tma_addon->add_field('abrasion_pei', 'Abrasion PEI', 'text');                          //10
$tma_addon->add_field('antislip_shoes', 'Antislip Shoes', 'text');                      //11
$tma_addon->add_field('antislip_barefeet', 'Antislip Barefeet', 'text');                //12

/* 18-08-2022  added fields always missing*/
$tma_addon->add_field('sqm_pal', 'Sqm/Pal', 'text');                                    //13
$tma_addon->add_field('kg_box', 'Kg/Box', 'text');                                      //14

/* 18-08-2022  new fileds*/
$tma_addon->add_field('tile_um', 'UM', 'text');                                         //15
$tma_addon->add_field('lenght_pal_cm', 'Lenght Pal cm', 'text');                        //16
$tma_addon->add_field('width_pal_cm', 'Width Pal cm', 'text');                          //17
$tma_addon->add_field('height_pal_cm', 'Height Pal cm', 'text');                        //18
$tma_addon->add_field('stackable_heights', 'Stackable heights', 'text');                //19
$tma_addon->add_field('tile_thickness', 'Thickness', 'text');                           //20

$tma_addon->add_field('chromatic_variation', 'Chromatic variation', 'text');            //21
$tma_addon->add_field('une_41901_2017_ex', 'UNE 41901:2017 EX', 'text');                //22
$tma_addon->add_field('din_51097', 'DIN 51097', 'text');                                //23
$tma_addon->add_field('ptv_dry', 'PTV (DRY)', 'text');                                  //24
$tma_addon->add_field('ptv_wet', 'PTV (WET)', 'text');                                  //25
$tma_addon->add_field('flex_resistance', 'Flex Resistance', 'text');                    //26
$tma_addon->add_field('catalog_ref', 'Catalog Ref.', 'text');                           //27
$tma_addon->add_field('tile_catalogue', 'Catalogue', 'text');                           //28

$tma_addon->add_field('color', 'Color', 'text');                                        //29
$tma_addon->add_field('serie_name', 'Serie', 'text');                                   //30

$tma_addon->set_import_function('tma_addon_import');
$tma_addon->run();

function tma_addon_import($post_id, $data, $import_options){
    
    update_post_meta( $post_id, 'tile_size', $data['tile_size'] );                      //01
    update_post_meta( $post_id, 'rectified', $data['rectified'] );                      //02
    
    update_post_meta( $post_id, 'code_article', $data['code_article'] );                //03   
    update_post_meta( $post_id, 'ean_13', $data['ean_13'] );                            //04
    
    update_post_meta( $post_id, 'boxes_pallet', $data['boxes_pallet'] );                //05
    update_post_meta( $post_id, 'pieces_box', $data['pieces_box'] );                    //06
    update_post_meta( $post_id, 'sqm_box', $data['sqm_box'] );                          //07   
    update_post_meta( $post_id, 'kg_pallet', $data['kg_pallet'] );                      //08
    
    update_post_meta( $post_id, 'creation_date', $data['creation_date'] );              //09
    update_post_meta( $post_id, 'abrasion_pei', $data['abrasion_pei'] );                //10
    update_post_meta( $post_id, 'antislip_shoes', $data['antislip_shoes'] );            //11
    update_post_meta( $post_id, 'antislip_barefeet', $data['antislip_barefeet'] );      //12
            
    /* 18-08-2022  added fields always missing*/    
    update_post_meta( $post_id, 'sqm_pal', $data['sqm_pal'] );                          //13
    update_post_meta( $post_id, 'kg_box', $data['kg_box'] );                            //14
    
    /* 18-08-2022  new fileds*/    
    update_post_meta( $post_id, 'tile_um', $data['tile_um'] );                          //15
    update_post_meta( $post_id, 'lenght_pal_cm', $data['lenght_pal_cm'] );              //16
    update_post_meta( $post_id, 'width_pal_cm', $data['width_pal_cm'] );                //17
    update_post_meta( $post_id, 'height_pal_cm', $data['height_pal_cm'] );              //18
    update_post_meta( $post_id, 'stackable_heights', $data['stackable_heights'] );      //19
    update_post_meta( $post_id, 'tile_thickness', $data['tile_thickness'] );            //20
    
    update_post_meta( $post_id, 'chromatic_variation', $data['chromatic_variation'] );  //21
    update_post_meta( $post_id, 'une_41901_2017_ex', $data['une_41901_2017_ex'] );      //22
    update_post_meta( $post_id, 'din_51097', $data['din_51097'] );                      //23
    update_post_meta( $post_id, 'ptv_dry', $data['ptv_dry'] );                          //24
    update_post_meta( $post_id, 'ptv_wet', $data['ptv_wet'] );                          //25
    update_post_meta( $post_id, 'flex_resistance', $data['flex_resistance'] );          //26
    update_post_meta( $post_id, 'catalog_ref', $data['catalog_ref'] );                  //27
    update_post_meta( $post_id, 'tile_catalogue', $data['tile_catalogue'] );            //28
    
    update_post_meta( $post_id, 'color', $data['color'] );                              //29
    update_post_meta( $post_id, 'serie_name', $data['serie_name'] );                    //30
}