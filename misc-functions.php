<?php

    /**
     * Mostly for save cc feature, default is str just checking if empty
     */
    function empty_type_check( $val, $type = '', $special = '' ) {
        if ( empty($val) ) {
            send_sms( 'Invalid parameters' );
        }

        $val_type = gettype( $val );

        switch ( $special ) {
            case 'month':
                if ( $val < 0 && $val > 31 ) {
                    send_sms( 'Invalid parameters' );
                }
                break;
        }

        switch ( $type ) {
            case 'float':
                if ( $val_type == 'float' || $val_type == 'double' ) {
                    return $val;
                }
                break;
            case 'int':
                if ( $val_type == 'integer' ) {
                    return $val;
                }
                break;
            case 'str':
                return $val;
                break;
            default:
                send_sms( 'Invalid parameters' );
                break;
        }
    }