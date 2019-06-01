<?php

    // ini_set('display_errors', 1);
    // ini_set('display_startup_errors', 1);
    // error_reporting(E_ALL);


    /**
     * This is ran minutely primarily based on timestamps
     */

    $production = true; // used by sms functions


    require_once( '/your-full-cron-path/authenticate.php' );
    require_once( '/your-full-cron-path/twilio-php/Twilio/autoload.php' );
    require_once( '/your-full-cron-path/sms-functions.php' );

    // call interest function
    calculate_credit_card_interest();