<?php
add_action('joyas_shop_site_header', 'ft_topbar_spot_price_data', 10 );

function ft_topbar_spot_price_data() {
   
    $wp_admin_url = admin_url( 'admin-ajax.php' );

    echo <<<EX
        <style>
        .topbar-ft-spot-price-data {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            width: 100%;
            background: #d2a35c;
            flex-wrap: wrap;
        }

        .topbar-ft-spot-price-data-slice {
            margin: 0px 30px;
            color: #ffffff;
        }

        .topbar-ft-spot-price-data-slice-name {
            text-transform: uppercase;
        }

        .change-down {
            color: #d41313;
        }

        .change-up {
            color: #1a1ab2;
        }
        </style>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <script>
            function get_ft_spot_price_data() {
                jQuery.ajax({
                    url: '{$wp_admin_url}',
                    type: 'post',
                    data: {
                        action: 'ft_get_spot_price_data',
                    },
                    dataType: 'json',
                    success: function(resp) {
                        if(resp.hasOwnProperty('goldAsk')) {
                            jQuery('.type-gold .ft-spot-price').text(resp.goldAsk);
                        }

                        if(resp.hasOwnProperty('goldChange')) {
                            if(resp.goldChange < 0) {
                                jQuery('.type-gold .topbar-ft-spot-price-data-slice-change').addClass('change-down');
                                jQuery('.type-gold .topbar-ft-spot-price-data-slice-change').html('<i class="fa fa-arrow-down" aria-hidden="true"></i>' + resp.goldChange);
                            }
                            else {
                                jQuery('.type-gold .topbar-ft-spot-price-data-slice-change').addClass('change-up');
                                jQuery('.type-gold .topbar-ft-spot-price-data-slice-change').html('<i class="fa fa-arrow-up" aria-hidden="true"></i>' + resp.goldChange);
                            }
                        }

                        if(resp.hasOwnProperty('silverAsk')) {
                            jQuery('.type-silver .ft-spot-price').text(resp.silverAsk);
                        }

                        if(resp.hasOwnProperty('silverChange')) {
                            if(resp.silverChange < 0) {
                                jQuery('.type-silver .topbar-ft-spot-price-data-slice-change').addClass('change-down');
                                jQuery('.type-silver .topbar-ft-spot-price-data-slice-change').html('<i class="fa fa-arrow-down" aria-hidden="true"></i>' + resp.silverChange);
                            }
                            else {
                                jQuery('.type-silver .topbar-ft-spot-price-data-slice-change').addClass('change-up');
                                jQuery('.type-silver .topbar-ft-spot-price-data-slice-change').html('<i class="fa fa-arrow-up" aria-hidden="true"></i>' + resp.silverChange);
                            }
                        }

                        if(resp.hasOwnProperty('platinumAsk')) {
                            jQuery('.type-platinum .ft-spot-price').text(resp.platinumAsk);
                        }
                        
                        if(resp.hasOwnProperty('platinumChange')) {
                            if(resp.platinumChange < 0) {
                                jQuery('.type-platinum .topbar-ft-spot-price-data-slice-change').addClass('change-down');
                                jQuery('.type-platinum .topbar-ft-spot-price-data-slice-change').html('<i class="fa fa-arrow-down" aria-hidden="true"></i>' + resp.platinumChange);
                            }
                            else {
                                jQuery('.type-platinum .topbar-ft-spot-price-data-slice-change').addClass('change-up');
                                jQuery('.type-platinum .topbar-ft-spot-price-data-slice-change').html('<i class="fa fa-arrow-up" aria-hidden="true"></i>' + resp.platinumChange);
                            }
                        }

                        if(resp.hasOwnProperty('palladiumAsk')) {
                            jQuery('.type-palladium .ft-spot-price').text(resp.palladiumAsk);
                        }

                        if(resp.hasOwnProperty('palladiumChange')) {
                            if(resp.palladiumChange < 0) {
                                jQuery('.type-palladium .topbar-ft-spot-price-data-slice-change').addClass('change-down');
                                jQuery('.type-palladium .topbar-ft-spot-price-data-slice-change').html('<i class="fa fa-arrow-down" aria-hidden="true"></i>' + resp.palladiumChange);
                            }
                            else {
                                jQuery('.type-palladium .topbar-ft-spot-price-data-slice-change').addClass('change-up');
                                jQuery('.type-palladium .topbar-ft-spot-price-data-slice-change').html('<i class="fa fa-arrow-up" aria-hidden="true"></i>' + resp.palladiumChange);
                            }
                        }

                    }
                })
            }

            get_ft_spot_price_data();

            setInterval(function() {
                get_ft_spot_price_data();
            }, 1000 * 60);
        </script>
    EX;
}