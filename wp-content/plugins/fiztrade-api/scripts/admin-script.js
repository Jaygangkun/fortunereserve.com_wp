jQuery(document).ready(function ($){
    console.log(123)
    $('#fiztrade_api_product').click(function (e) {
        e.preventDefault();
        let data = {
            action: 'fiz_trade_get_products',
        };
        $.post( ajaxurl, data, function( response ){
            alert( 'Success' );
        } );
    });

    $('#fiz_trade_create_product_csv').click(function (e) {
        e.preventDefault();
        let data = {
            action: 'fiz_trade_create_product_csv',
        };
        $.post( ajaxurl, data, function( response ){
            console.log(response)
        } );
    });

    $('#fiz_trade_create_price_csv').click(function (e) {
        e.preventDefault();
        let data = {
            action: 'fiz_trade_create_price_csv',
        };
        $.post( ajaxurl, data, function( response ){
            console.log(response)
        } );
    });

    $('#fiz_trade_import_product_csv').click(function (e) {
        e.preventDefault();
        let data = {
            action: 'fiz_trade_import_product_csv',
        };
        $.post( ajaxurl, data, function( response ){
            console.log(response)
        } );
    });

    $('#fiztrade_api_price').click(function (e) {
        e.preventDefault();
        let data = {
            action: 'fiz_trade_get_prices',
        };
        $.post( ajaxurl, data, function( response ){
            alert( 'Success' );
        } );
    });

    $('#fiztrade_api_image').click(function (e) {
        e.preventDefault();
        let data = {
            action: 'fiz_trade_get_images',
        };
        $.post( ajaxurl, data, function( response ){
            alert( 'Success' );
        } );
    });
})
