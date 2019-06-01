<?php

    // ini_set('display_errors', 1);
    // ini_set('display_startup_errors', 1);
    // error_reporting(E_ALL);


    // connect to db, get credentials for Twilio, include Twilio-SDK
    require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'authenticate.php' );
    require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'twilio-php/Twilio/autoload.php' );
    require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'misc-functions.php' );
    require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'sms-functions.php' );
    require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'parse_msg.php' );


    $production = true;
    $credentials = new Credentials( $env_arr );


    if ( $production && $_SERVER['REQUEST_METHOD'] == 'POST' ) {
        // received POST parameters on remote server
        $sms_from = $_POST['From'];
        $sms_body = $_POST['Body'];
    }
    else {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        /**
         * local testing
         * use this part to test your commands locally  without
         * sending actual sms messages
         * responses echoed out
         */
        
        $sms_from = '+1' . $credentials->get_my_phone_number();
        // $sms_body =
        //     'Remind me 2mins' . "\n" . 
        //     "Message";
        // $sms_body = 
        //     'Save cc' . "\n" .
        //     'Cap One' . "\n" .
        //     '979.39' . "\n" .
        //     '2500' . "\n" .
        //     '13' . "\n" .
        //     '25.99' . "\n" .
        //     '$0';
        // $sms_body = 'Save cc cmd';
        // $sms_body = 'ls cards';
        // $sms_body =
        //     'Delete cc' . "\n" .
        //     '5h3ORg';
        // $sms_body = 'Delete cc cmd';
        // $sms_body = 'Update cc cmd';
        // $sms_body =
        //     'Update cc' . "\n" .
        //     'card_id=k0rawr'  . "\n" .
        //     'annual_fee=$99';
    }
    

    // check who the message is from
    if ( $sms_from == '+1' . $credentials->get_my_phone_number() ) {
        parse_msg( $sms_body );
    }
