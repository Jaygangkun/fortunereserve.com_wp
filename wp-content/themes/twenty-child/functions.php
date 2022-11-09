<?php
use TierPricingTable\PriceManager;
add_filter('woocommerce_product_get_image', 'api_image' , 10, 5);

function api_image($image, $product, $size, $attr, $placeholder){
    $image = "<img src='{$product->short_description}'>";
    return $image;
}

function exclude_empty_cat_menu_items( $items, $menu, $args ) {
  // Get a list of product categories that excludes empty categories
  $non_empty_categories = get_categories(array('taxonomy' => 'product_cat'));
  // Iterate over the menu items
  foreach ( $items as $key => $item ) {
    $is_empty = true;
    // check current item id is in the non-empty categories array
    foreach ( $non_empty_categories as $key2 => $cat )
      if ($item->title === $cat->name) 
        $is_empty = false;
      // if it is empty remove it from array
      if ($is_empty) unset($items[$key]);
  }
  return $items;
}
//add_filter( 'wp_get_nav_menu_items', 'exclude_empty_cat_menu_items', null, 3 );
//

add_filter( 'cron_schedules', 'cron_add_five_min' );

function cron_add_five_min( $schedules ) {
    $schedules['five_min'] = array(
        'interval' => 60 * 5,
        'display' => 'Раз в 5 минут'
    );
    return $schedules;
}

if ( ! wp_next_scheduled( 'my_five_min_event' ) ) {
    wp_schedule_event( time(), 'five_min', 'my_five_min_event' );
}

//add_action( 'my_five_min_event', 'my_task_function' );

function my_task_function() {
    $key = get_field('fiz_trade_api_key', 'option');
    $url = get_field('fiz_trade_api_url', 'option');
    $full_url = "{$url}GetProductsByMetalV2/{$key}/Gold";
    $full_url_price = "{$url}GetPricesForProducts/{$key}";
    $full_url_tiers = "{$url}GetPriceTiers/{$key}/Gold";
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $full_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => '{
                    "items":
                    [
                        "1EAGLE"
                    ]
                }',
    ));


    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_setopt_array($curl, array(
        CURLOPT_URL => $full_url_price,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => '["1EAGLE"]',
    ));

    $response2 = curl_exec($curl);
    $err2 = curl_error($curl);

    curl_setopt_array($curl, array(
        CURLOPT_URL => $full_url_tiers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_POSTFIELDS => '',
    ));

    $response3 = curl_exec($curl);
    $err3 = curl_error($curl);


    $products  = json_decode($response, true);

    $price  =json_decode($response2, true);

    $tiers = json_decode($response3, true);

    foreach ($products as $product) {
        if($product['code'] === '1EAGLE') {
            $parentCategory = get_term_by('slug', strtolower($product['metalType']), 'product_cat');
            if(!$parentCategory) {
                $parentCategory = wp_insert_term(
                    strtolower($product['metalType']),
                    'product_cat',
                    array(
                        'slug' => strtolower($product['metalType'])
                    )
                );
                $parentCategoryID = $parentCategory['term_id'];
            }else{
                $parentCategoryID = $parentCategory->term_id;
            }
            if(wc_get_product_id_by_sku($product['code']) === 0){
                $wc_product = new WC_Product();
            }else{
                $wc_product = new WC_Product(wc_get_product_id_by_sku($product['code']));
            }
            $childCategory = get_term_by('slug', $product['category'] .'-'.strtolower($product['metalType']), 'product_cat');
            if(!$childCategory) {
                $childCategory = wp_insert_term(
                    $product['category'],
                    'product_cat',
                    array(
                        'slug' => $product['category'] .'-'.strtolower($product['metalType']),
                        'parent' => $parentCategoryID
                    )
                );
                $childCategoryID = $childCategory['term_id'];
            }else{
                $childCategoryID = $childCategory->term_id;
            }
            $wc_product->set_name( $product['name'] );
            $wc_product->set_sku( $product['code'] );
            $wc_product->set_description( $product['description'] );

            if($product['availability'] == 'Live'){
                $wc_product->set_stock_status();
            }else if($product['availability'] == 'Not Available') {
                $wc_product->set_stock_status('outofstock');
            }else{
                $wc_product->set_stock_status('onbackorder');
            }
            $wc_product->set_category_ids([$parentCategoryID, $childCategoryID]);
            $wc_product->set_weight( $product['weight'] );
        }
    }
    $id = $wc_product->get_id();
    $premium = get_field('fiz_trade_premiums', $id );
    if($premium < 1) {
        $premium = 20;
    }
    $premium = (100 + $premium) / 100;
    $wc_product->set_short_description( $price[0]['images'][0]['imgPath'] );
    $wc_product->set_price($price[0]['tiers'][1]['ask'] * $premium );
    $wc_product->set_regular_price($price[0]['tiers'][1]['ask']  * $premium);
    $wc_product->save();
    $amounts = [];

    foreach ($tiers as $tier) {
        if($tier['tier'] !== 1) {
            $amounts[] = $tier['minQty'];
        }
    }
    PriceManager::updateFixedPriceRules( $amounts, [$price[0]['tiers'][2]['ask'] * $premium,$price[0]['tiers'][3]['ask'] * $premium,$price[0]['tiers'][4]['ask'] * $premium], $wc_product->get_id() );

}