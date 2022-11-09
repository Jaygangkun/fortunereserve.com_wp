<?php

class Fiz_Trade_Ajax
{
    protected $api;
    protected $allowedTypes = [
        "Gold",
        "Silver"
    ];

    public function __construct(Fiz_Trade_Api $api){
        $this->api = $api;
        add_action( 'wp_ajax_fiz_trade_get_products', [$this, 'getProducts'] );
        add_action( 'wp_ajax_fiz_trade_get_images', [$this,'getProductImages'] );
        add_action( 'wp_ajax_fiz_trade_get_prices', [$this,'getProductPrice'] );
        add_action( 'wp_ajax_fiz_trade_create_product_csv', [$this,'productsCsv'] );
        add_action( 'wp_ajax_fiz_trade_create_price_csv', [$this,'pricesCsv'] );
        add_action( 'wp_ajax_fiz_trade_import_product_csv', [$this,'importProductsCsv'] );
    }

    public function productsCsv() {
        foreach ($this->allowedTypes as $allowedType) {
            $this->createProductsCsv($allowedType);
        }
    }

    public function pricesCsv() {
        foreach ($this->allowedTypes as $allowedType) {
            $this->createPricesCsv($allowedType);
        }
    }

    public function createProductsCsv($metalType){
        $products = $this->api->getProductsByMetal($metalType);
        $codes = [];
        $productsToCsv = [];
        foreach ($products as $key => $product) {
            if (!in_array($product['code'], $codes)) {
                $codes[] = $product['code'];
            }
            $productsToCsv[$key]['name'] = $product['name'];
            $productsToCsv[$key]['description'] = $product['description'];
            $productsToCsv[$key]['sku'] = $product['code'];
            $productsToCsv[$key]['weight'] = $product['weight'];
            $productsToCsv[$key]['category_ids'] = "{$product['metalType']} > {$product['category']}, {$product['metalType']}";

        }
        $codes = implode('||', $codes);
        update_field("fiz_trade_product_codes_{$metalType}", ltrim($codes, '||'), 'option');
        return $this->createCsv($productsToCsv, "products-{$metalType}.csv");
    }

    public function createPricesCsv($metalType){
        $products = $this->api->getPricesForProducts($metalType);
        $productsToCsv = [];
        foreach ($products as $key => $product) {
            $productsToCsv[$key]['short_description'] = $product['images'][0]['imgPath'];
            $productsToCsv[$key]['sku'] = $product['code'];
            $productsToCsv[$key]['price'] = $product['code'];
        }
        return $this->createCsv($productsToCsv, "products-{$metalType}.csv");
    }


    public function importProductsCsv(){
        $metalType = get_field('metal_type', 'option');
        $this->importFromCsv('products.csv', $_POST['position']);
    }

    public function importFromCsv($fileName, $start_pos = 0, $update = false){
        include_once WC_ABSPATH . 'includes/admin/importers/class-wc-product-csv-importer-controller.php';
        include_once WC_ABSPATH . 'includes/import/class-wc-product-csv-importer.php';
        $uploads_dir = trailingslashit( wp_upload_dir()['basedir'] ) . 'fiz-trade-import';
        $csvImportFile = $fileName;
        $file = $uploads_dir .'/'. $csvImportFile;
        $params = array(
            'delimiter'       =>  ',',
            'start_pos'       => $start_pos,
//            'mapping'         => [
//                'from' => [
//                    'name',
//                    'description',
//                    'sku',
//                    'weight',
//                    'categories',
//                ],
//                'to' => [
//                    'name',
//                    'description',
//                    'sku',
//                    'weight',
//                    'category_ids',
//                ]
//            ],
            'update_existing' =>  $update,
            'lines'           => apply_filters( 'woocommerce_product_import_batch_size', 30 ),
            'parse'           => true,
        );
        $importer         = WC_Product_CSV_Importer_Controller::get_importer( $file, $params );
        $results          = $importer->import();
        $percent_complete = $importer->get_percent_complete();
        if ( 100 === $percent_complete ) {
            wp_send_json_success(
                array(
                    'position'   => 'done',
                    'imported'   => count( $results['imported'] ),
                    'failed'     => count( $results['failed'] ),
                    'updated'    => count( $results['updated'] ),
                    'skipped'    => count( $results['skipped'] ),
                )
            );
        }else{
            wp_send_json_success(
                array(
                    'position'   => $importer->get_file_position(),
                    'imported'   => count( $results['imported'] ),
                    'failed'     => count( $results['failed'] ),
                    'updated'    => count( $results['updated'] ),
                    'skipped'    => count( $results['skipped'] ),
                )
            );
//            sleep(5);
//            $this->import($importer->get_file_position());
        }
    }

    public function createCsv ($products, $fileName){
        $uploads_dir = trailingslashit( wp_upload_dir()['basedir'] ) . 'fiz-trade-import';
        wp_mkdir_p( $uploads_dir );
        $csvExportFile = $fileName;
        $output = fopen($uploads_dir .'/'.$csvExportFile, "w");
        $header = array_keys($products[0]);
        fputcsv($output, $header);
        foreach ($products as $key => $product) {
            fputcsv($output, $product);
        }

        fclose($output);
        return $products;
    }

    public function import($start_pos = 0){
        include_once WC_ABSPATH . 'includes/admin/importers/class-wc-product-csv-importer-controller.php';
        include_once WC_ABSPATH . 'includes/import/class-wc-product-csv-importer.php';
        $params = array(
            'delimiter'       =>  ',', // PHPCS: input var ok.
            'start_pos'       => $start_pos, // PHPCS: input var ok.
            'mapping'         =>  [
                'from' => [
                    'description',
                    'code',
                    'family',
                    'isActiveSell',
                    'isActiveBuy',
                    'metalType',
                    'images',
                    'purity',
                    'origin',
                    'weight',
                    'copy',
                    ],
                'to' => [
                    'name',
                    'sku',
                    '',
                    '',
                    '',
                    '',
                    'short_description',
                    '',
                    '',
                    'weight',
                    'description',
                ]
            ], // PHPCS: input var ok.
//            'mapping'         =>  [
//                'from' => [
//                    'ID',
//                    'Type',
//                    'SKU',
//                    'Name',
//                    'Published',
//                    'Short description',
//                    'Description',
//                    'In stok?',
//                    'Categories',
//                    'Regular price'
//                    ],
//                'to' => [
//                    'id',
//                    'type',
//                    'sku',
//                    'name',
//                    'published',
//                    'short_description',
//                    'description',
//                    'stok_status',
//                    'category_ids',
//                    'regular_price'
//                ]
//            ], // PHPCS: input var ok.
            'update_existing' =>  false, // PHPCS: input var ok.
            'lines'           => apply_filters( 'woocommerce_product_import_batch_size', 30 ),
            'parse'           => true,
        );
        $uploads_dir = trailingslashit( wp_upload_dir()['basedir'] ) . 'evaluation-uploads';
        $csvExportFile = 'gettemplates.csv';
        $file = $uploads_dir .'/'.$csvExportFile;
        $importer         = WC_Product_CSV_Importer_Controller::get_importer( $file, $params );
        $results          = $importer->import();
        $percent_complete = $importer->get_percent_complete();
        if ( 100 === $percent_complete ) {
            wp_send_json_success(
                array(
                    'position'   => 'done',
                    'imported'   => count( $results['imported'] ),
                    'failed'     => count( $results['failed'] ),
                    'updated'    => count( $results['updated'] ),
                    'skipped'    => count( $results['skipped'] ),
                )
            );
        }else{
            wp_send_json_success(
                array(
                    'position'   => $importer->get_file_position(),
                    'imported'   => count( $results['imported'] ),
                    'failed'     => count( $results['failed'] ),
                    'updated'    => count( $results['updated'] ),
                    'skipped'    => count( $results['skipped'] ),
                )
            );
//            sleep(5);
//            $this->import($importer->get_file_position());
        }
    }
}