<?php
    $env = file_get_contents(__DIR__ . '/.env');

    if ( $env && strstr($env, "\n") ) {
        $env = explode( "\n", $env );
        if ( is_array($env) ) {
            $env_arr = [];
            foreach ( $env as $env_key_pair ) {
                if ( strpos($env_key_pair, '=') !== false ) {
                    $key_pair = explode( '=', $env_key_pair );
                    $env_arr[$key_pair[0]] = trim( $key_pair[1] );
                }
            }
        }
    }
?>