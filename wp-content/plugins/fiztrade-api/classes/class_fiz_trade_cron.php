<?php

use TierPricingTable\PriceManager;

class Fiz_Trade_Cron
{
    protected $api;

    public function __construct(Fiz_Trade_Api $api)
    {
        $this->api = $api;
        add_action( 'wp', [ $this, 'scheduleEvent' ] );
        add_filter( 'cron_schedules', [$this, 'addCronschedules'] );
        add_action( 'cronImport', [$this, 'cronImportPrices'] );
        add_action( 'cronImportImages', [$this, 'cronImportImages'] );
    }

    public function addCronschedules($schedules){
        $schedules['fiz_trade_five_min'] = array(
            'interval' => 60 * 5,
            'display' => 'FizTrade 5 minutes'
        );
        return $schedules;
    }

    public function scheduleEvent() {
        if ( ! wp_next_scheduled( 'cronImport' ) ) {
            wp_schedule_event( time(), 'five_min', 'cronImport' );
        }
        if ( ! wp_next_scheduled( 'cronImportImages' ) ) {
            wp_schedule_event( time(), 'hourly', 'cronImportImages' );
        }
    }

    public function cronImportPrices(){
        $products = $this->api->getAllRetailPrices();
        $tiers = $this->api->getAllRetailTiers();

        foreach ($products as $product) {
            $amounts = [];
            $prices = [];
            if(wc_get_product_id_by_sku($product['code']) === 0){
                $wc_product = new WC_Product();
            }else{
                $wc_product = new WC_Product(wc_get_product_id_by_sku($product['code']));
            }
            if(array_key_exists($product['code'], $tiers)){

                foreach ($tiers[$product['code']] as $tier) {
                    if($tier['tier'] !== 1) {
                        $amounts[] = $tier['minQty'];
                    }
                }
            }elseif (array_key_exists(ucfirst($product['metalType']), $tiers)) {
                foreach ($tiers[ucfirst($product['metalType'])] as $tier) {
                    if($tier['tier'] !== 1) {
                        $amounts[] = $tier['minQty'];
                    }
                }
            }
            $wc_product->set_name( $product['description'] );
            $wc_product->set_sku( $product['code'] );
            if($product['availability'] == 'Live'){
                $wc_product->set_stock_status();
            }else if($product['availability'] == 'Not Available') {
                $wc_product->set_stock_status('outofstock');
            }else{
                $wc_product->set_stock_status('onbackorder');
            }
            $wc_product->set_price($product['tiers'][1]['ask']  );
            $wc_product->set_regular_price($product['tiers'][1]['ask'] );
            foreach ($amounts as $key => $amount){
                $prices[] = $product['tiers'][$key +2]['ask'];
            }

            //$wc_product->set_category_ids([$parentCategoryID, $childCategoryID]);
            $wc_product->save();
            PriceManager::updateFixedPriceRules( $amounts, $prices, $wc_product->get_id() );
        }

    }

    public function cronImportImages (){
        $products = $this->api->getAllRetailProductInfo();
        $codes = '[';
        foreach ($products as $key => $product) {
            if($key === 0){
                $codes .= "\"{$product['code']}\"";
            }else{
                $codes .= ", \"{$product['code']}\"";
            }
        }
        $codes .= ']';
        $products = $this->api->getProductCatalog($codes);
        foreach ($products as $product) {
            if(wc_get_product_id_by_sku($product['code']) !== 0){
                $wc_product = new WC_Product(wc_get_product_id_by_sku($product['code']));
                $wc_product->set_description( $product['copy'] );
                $wc_product->set_weight( $product['weight'] );
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
                $wc_product->set_short_description( $product['images'][0]['imgPath'] );
                $wc_product->set_category_ids([$parentCategoryID]);
                $wc_product->save();
            }
        }
    }
}