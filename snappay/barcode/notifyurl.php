<?php
    require_once( 'lib/snappay-sign-utils.php' );

    //MUST keep sign key secretly. Best to load from somewhere else, like database.
    $signKey = '7e2083699dd510575faa1c72f9e35d43';
    
    # Get JSON as a string
    $json_str = file_get_contents('php://input');
    error_log('json_str: '.$json_str, 3, 'log.log');

    # Get as an object
    $json_obj = json_decode($json_str, true);
    
    # Merge json object into request object
    $_REQUEST = array_merge($_REQUEST, $json_obj);

    if(snappay_sign_verify($_REQUEST, $signKey)){
        
        //do business logic here, such as notify business system the transaction has finished.
        
        if($json_obj['trans_status'] === 'SUCCESS'){
            //must only show 'SUCCESS', so that SNAPPAY server will no longer re-send notification
            $post_data = array(
                'code' => '0',
                'msg' => 'SUCCESS',
                'sign_type' => 'MD5'
            );
            $post_data_sign = snappay_sign_post_data($post_data, $signKey);
            echo json_encode( $post_data_sign );
        }
    } else {
        echo 'illegal sign';
    }
?>
