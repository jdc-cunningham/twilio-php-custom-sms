<?php

    // get db credentials
    require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR.'db-connect.php');


    // terrible guard
    if ( 
        !$env_arr['twilio_sid'] || 
        !$env_arr['twilio_token'] || 
        !$env_arr['twilio_phone_number'] || 
        !$env_arr['my_phone_number'] 
    ) {
        die( 'Missing credentials' );
    }
    

    class Credentials {
        // pulled from db-connect.php included parse_env.php
        private $twilio_sid;
        private $twilio_token;
        private $twilio_phone_number;
        private $your_phone_number;

        public function __construct( $env_arr ) {
            $this->twilio_sid = $env_arr['twilio_sid'];
            $this->twilio_token = $env_arr['twilio_token'];
            $this->twilio_phone_number = $env_arr['twilio_phone_number'];
            $this->my_phone_number = $env_arr['my_phone_number'];
        }

        public function get_twilio_sid() {
            return $this->twilio_sid;
        }

        public function get_twilio_token() {
            return $this->twilio_token;
        }

        public function get_twilio_phone_number() {
            return $this->twilio_phone_number;
        }

        public function get_my_phone_number() {
            return $this->my_phone_number;
        }
    }