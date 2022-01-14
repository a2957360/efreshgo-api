<?php
    require_once "stripe-php-master/init.php";
    require_once "products.php";

    $stripeDetail = array(
        "secretKey" => "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",
        "publishableKey" => "pk_live_51Hm1yyKs1o5wlkX1IkpeVA14C5yyJROmZ4UGC7p3MKnDj5rJ9nh3XRZ5F2GwpHykpBdGSLjCOV7eNkzn25AMkNA400xGwMQnxE"
    );

\Stripe\Stripe::setApiKey($stripeDetail['secretKey']);
?>