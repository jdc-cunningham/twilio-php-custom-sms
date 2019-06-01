<?php

    // functions called here are defined in commands.php
    function parse_msg( $sms ) {
        if ( strpos($sms, "\n") !== false ) {
            try_strpos( $sms );
        }
        else {
            short_command( $sms );
        }
    }