<?php

class Fiz_Trade_Api
{
    protected $apiKey;
    protected $apiUrl;

    public function __construct() {
        $this->apiKey = get_field('fiz_trade_api_key', 'option');
        $this->apiUrl = get_field('fiz_trade_api_url', 'option');
		add_action('woocommerce_checkout_order_created', [$this, 'orderCreated']);
        add_action( 'woocommerce_order_status_completed', array( $this, 'statusCompleted' ) );
    }

    public function getResponse (array $options){
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "{$options['url']}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "{$options['type']}",
            CURLOPT_POSTFIELDS => "{$options['fields']}",
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        return json_decode($response, true);
    }
	
    public function statusCompleted( $order_id ){
        $confirmationNumber = get_post_meta($order_id, '_confirmation_number', true);
        return $this->getResponse([
            'url' => "{$this->apiUrl}ClearDropShipHold/{$this->apiKey}",
            'type' => 'POST',
            'fields'=> "{
                \"traderId\":\"dc@sourceangel.dk\",
	            \"confirmationNumber\": \"{$confirmationNumber}\"
            }"
        ]);
    }

    public function updateShippingInfo( $order_id ){
        $order = wc_get_order( $order_id );
        $confirmationNumber = get_post_meta($order_id, '_confirmation_number', true);
        $order_data = $order->get_data();
        return $this->getResponse([
            'url' => "{$this->apiUrl}UpdateShippingInfo/{$this->apiKey}",
            'type' => 'POST',
            'fields'=> "{
                        \"confirmationNumber\": \"{$confirmationNumber}\",
                        \"traderId\":\"dc@sourceangel.dk\",                  
                        \"selection\":\"drop_ship\",
                        \"shippingInfo\": {
                                            \"name\":\"{$order_data['billing']['first_name']} - {$order_data['billing']['last_name']}\",
                                            \"address1\": \"{$order_data['billing']['address_1']}\",
                                            \"address4\": \"{$order_data['billing']['phone']}\",
                                            \"city\": \"{$order_data['billing']['city']}\",
                                            \"state\": \"{$order_data['billing']['state']}\",
                                            \"postalCode\": \"{$order_data['billing']['postcode']}\",
                                            \"country\":\"{$order_data['billing']['country']}\"
                                           }
                        }"
        ]);
    }

    public function orderCreated( $order ) {
        $confirmation = $this->executeTrade($order );
        update_post_meta( $order->get_id(), '_confirmation_number', $confirmation['confirmationNumber'][0] );
        return $confirmation;
    }

    public function executeTrade ( $order, $lockToken = false ) {
        $order_data = $order->get_data();
        $lockToken  = $this->lockPrices($order);
        return $this->getResponse([
            'url' => "{$this->apiUrl}ExecuteTrade/{$this->apiKey}",
            'type' => 'POST',
            'fields'=> "{
                        \"transactionId\": \"{$order->get_id()}\",
                        \"referenceNumber\":\"{$order->get_id()}\",                  
                        \"shippingOption\":\"hold\",
                        \"dropShipInfo\": {
                                            \"name\":\"{$order_data['billing']['first_name']} - {$order_data['billing']['last_name']}\",
                                            \"address1\": \"{$order_data['billing']['address_1']}\",
                                            \"address4\": \"{$order_data['billing']['phone']}\",
                                            \"city\": \"{$order_data['billing']['city']}\",
                                            \"state\": \"{$order_data['billing']['state']}\",
                                            \"postalCode\": \"{$order_data['billing']['postcode']}\",
                                            \"country\":\"{$order_data['billing']['country']}\"
                                           },                       
                        \"lockToken\":\"{$lockToken['lockToken']}\",
                        \"traderId\":\"dc@sourceangel.dk\"
                        }"
        ]);
    }

    public function lockPrices( $order ){
        $items = $order->get_items();
        $api_items = '';
        foreach ($items as $item) {
            $product_item = $item->get_product();
            $api_items .= "{\"code\":\"{$product_item->get_sku()}\",\"transactionType\":\"buy\",\"qty\":\"{$item->get_quantity()}\"},";
        }
        $api_items = rtrim($api_items, ',');

        return $this->getResponse([
            'url' => "{$this->apiUrl}LockPrices/{$this->apiKey}",
            'type' => 'POST',
            'fields'=> "{
                            \"transactionId\": \"{$order->get_id()}\",
                            \"items\":
                            [{$api_items}]
                        }"
        ]);
    }
	
    public function getProductsByMetal($metalType = "Gold"){
        $url = $this->apiUrl;
        $key = $this->apiKey;
        return $this->getResponse([
            'url' => "{$url}GetProductsByMetalV2/{$key}/{$metalType}",
            'type' => 'GET',
            'fields'=> ''
        ]);
    }

    public function getAllRetailPrices (){
        $url = $this->apiUrl;
        $key = $this->apiKey;
        return $this->getResponse([
            'url' => "{$url}GetAllRetailPricesV2/{$key}",
            'type' => 'GET',
            'fields'=> ''
        ]);
    }

    public function getAllRetailTiers (){
        $url = $this->apiUrl;
        $key = $this->apiKey;
        return $this->getResponse([
            'url' => "{$url}GetAllRetailPriceBreaks/{$key}",
            'type' => 'GET',
            'fields'=> ''
        ]);
    }

    public function getAllRetailProductInfo (){
        $url = $this->apiUrl;
        $key = $this->apiKey;
        return $this->getResponse([
            'url' => "{$url}GetAllRetailProductInfo/{$key}",
            'type' => 'GET',
            'fields'=> ''
        ]);
    }

    public function getProductCatalog($coins){
        $url = $this->apiUrl;
        $key = $this->apiKey;
        return $this->getResponse([
            'url' => "{$url}GetProductCatalog/{$key}",
            'type' => 'POST',
            'fields'=> "{
                \"items\": {$coins}
            }"
        ]);
    }

    public function getProducts() {
        $metalType = get_field('metal_type', 'option');
        $url = $this->apiUrl;
        $key = $this->apiKey;
        $products = $this->getResponse([
            'url' => "{$url}GetProductsByMetalV2/{$key}/{$metalType}",
            'type' => 'GET',
            'fields'=> ''
        ]);
        $parentCategory = get_term_by('slug', strtolower($metalType), 'product_cat');
        if(!$parentCategory) {
            $parentCategory = wp_insert_term(
                $metalType,
                'product_cat',
                array(
                    'slug' => strtolower($metalType)
                )
            );
            $parentCategoryID = $parentCategory['term_id'];
        }else{
            $parentCategoryID = $parentCategory->term_id;
        }
        $codes = get_field("fiz_trade_product_codes_{$metalType}", 'option');
        $codes = explode('||', $codes);
        foreach ($products as $product) {
            if (!in_array($product['code'], $codes)) {
                $codes[] = $product['code'];
            }
            if(wc_get_product_id_by_sku($product['code']) === 0){
                $wc_product = new WC_Product();
            }else{
                $wc_product = new WC_Product(wc_get_product_id_by_sku($product['code']));
            }
            $childCategory = get_term_by('slug', $product['category'] .'-'.strtolower($metalType), 'product_cat');
            if(!$childCategory) {
                $childCategory = wp_insert_term(
                    $product['category'],
                    'product_cat',
                    array(
                        'slug' => $product['category'] .'-'.strtolower($metalType),
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
            $wc_product->save();
        }
        $codes = implode('||', $codes);
        update_field("fiz_trade_product_codes_{$metalType}", ltrim($codes, '||'), 'option');
    }

    public function getPricesForProducts($metalType) {
        $codes = get_field("fiz_trade_product_codes_{$metalType}", 'option');
        $codes = str_replace('||', '","', $codes);
        $codes = "[\"{$codes}\"]";
        $url = $this->apiUrl;
        $key = $this->apiKey;
        return $this->getResponse([
            'url' => "{$url}GetPricesForProducts/{$key}",
            'type' => 'POST',
            'fields' => "{$codes}"
        ]);
    }

    public function getProductPrice() {
        $metalType = get_field('metal_type', 'option');
        $url = $this->apiUrl;
        $key = $this->apiKey;
        $codes = get_field("fiz_trade_product_codes_{$metalType}", 'option');
        $codes = explode('||', $codes);
        foreach ($codes as $code) {
            if(wc_get_product_id_by_sku($code) !== 0) {
                $prices = $this->getResponse([
                    'url' => "{$url}GetPrice/{$key}/{$code}/none",
                    'type' => 'GET',
                    'fields' => ''
                ]);
                $product = new WC_Product(wc_get_product_id_by_sku($code));
                $product->set_price($prices['ask']);
                $product->set_regular_price($prices['ask']);
                $product->save();
            }
        }
    }

    public function getProductImages($metalType = "Gold") {
        $url = $this->apiUrl;
        $key = $this->apiKey;
        $codes = get_field("fiz_trade_product_codes_{$metalType}", 'option');
        $codes = explode('||', $codes);
        foreach ($codes as $code) {
            if(wc_get_product_id_by_sku($code) !== 0) {
                $images = $this->getResponse([
                    'url' => "{$url}GetCoinImages/{$key}/{$code}",
                    'type' => 'GET',
                    'fields' => ''
                ]);
                $product = new WC_Product(wc_get_product_id_by_sku($code));
                $product->set_short_description( $images[0]['imageURL'] );
                $product->save();
            }
        }
    }

    public function getProductFamilies($familyName) {
        $url = $this->apiUrl;
        $key = $this->apiKey;
        //return "${url}GetProductFamiliesByMetal/{$key}/Gold/false";
        return $this->getResponse([
            'url' => "${url}GetProductFamiliesByMetal/{$key}/{$familyName}/false",
            'type' => 'GET',
            'fields'=> ''
        ]);
    }

    public function getAllCodes($familyName) {
        $url = $this->apiUrl;
        $key = $this->apiKey;
        $return = [];
        $gold = $this->getProductFamilies($familyName);
        foreach ($gold as $item) {
            foreach ( $this->getResponse([
                'url' => "${url}GetProductCatalog/{$key}",
                'type' => 'POST',
                'fields'=> '{
                    "familyId": "'.$item['id'].'",
                }'
            ]) as $value){
                $return[] = $value;
            }
        }
        return $return;
    }

}