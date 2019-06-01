<?php

    /**
     * Sends str to phone by Twilio PHP SDK
     */
    function send_sms( $msg ) {
        global $env_arr, $production;

        if ( !$production ) {
            echo nl2br( $msg );
            return;
        }

        $credentials = new Credentials( $env_arr );
        $sid = $credentials->get_twilio_sid(); // Your Account SID from www.t$
        $token = $credentials->get_twilio_token(); // Your Auth Token from www.tw$
        $client = new Twilio\Rest\Client($sid, $token);

        $message = $client->messages->create(
            $credentials->get_my_phone_number(), // Text this number
            array(
                'from' => $credentials->get_twilio_phone_number(), // From a valid Twilio number
                'body' => $msg
            )
        );

        return;
    }


    function get_all_credit_cards() {
        global $dbh;

        $stmt = $dbh->prepare('SELECT card_id, name, balance, apr FROM credit_cards WHERE balance > 0');

        if ( $stmt->execute() ) {
            $credit_cards = $stmt->fetchAll();
            return $credit_cards;
        }
    }


    $list_all_credit_cards = function() {
        $credit_cards = get_all_credit_cards();

        if ( !empty($credit_cards) ) {
            $response_str = 'Saved credit cards:' . "\n\n";

            foreach ( $credit_cards as $credit_card ) {
                $response_str .= $credit_card['name'] . "\n";
                $response_str .= 'card id: ' . $credit_card['card_id'] . "\n";
                $response_str .= 'balance: $' . $credit_card['balance'] . "\n\n";
            }

            send_sms( $response_str );
        } else {
            send_sms( 'No credit cards' ); // not great should be none or no balance
        }
    };


    function  calculate_credit_card_interest() {
        $credit_cards = get_all_credit_cards();

        if ( !empty($credit_cards) ) {
            $response_str = 'Accrued interest on cards:' . "\n\n";

            foreach ( $credit_cards as $credit_card ) {
                $monthly_apr = ( $credit_card['apr'] / 100 ) / 12; // assumes not empty
                $accrued_interest = number_format( $credit_card['balance'] * $monthly_apr, 2, '.', '' ); // will accrue
                $response_str .= $credit_card['name'] . "\n";
                $response_str .= 'balance: $' . $credit_card['balance'] . "\n";
                $response_str .= '$' . $accrued_interest . "\n\n";
            }

            send_sms( $response_str );
        } else {
            send_sms( 'No credit cards' ); // not great should be none or no balance
        }
    }

    
    /**
     * Gets BTC price from Coindesk
     */
    $get_btc_price = function() {
        $coindesk_dump = json_decode( file_get_contents("https://api.coindesk.com/v1/bpi/currentprice.json"), true );
        $bitcoin_usd = 'The current price of Bitcoin is $' . substr($coindesk_dump['bpi']['USD']['rate'], 0, 8);
        send_sms( $bitcoin_usd );
    };


    /**
     * Inserts remind me message with timestamp for minutely CRON scheduler
     */
    function save_reminder( $sms_comps ) {
        global $dbh;

        $id = null;
        $sms_msg = $sms_comps['message'];
        $remind_at_timestamp = time() + $sms_comps['timestamp'];
        $reminder_sent = 0;

        $stmt = $dbh->prepare('INSERT INTO reminders VALUES (:id, :sms_body, :remind_at_timestamp, :reminder_sent)');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':sms_body', $sms_msg, PDO::PARAM_STR);
        $stmt->bindParam(':remind_at_timestamp', $remind_at_timestamp, PDO::PARAM_INT);
        $stmt->bindParam(':reminder_sent', $reminder_sent, PDO::PARAM_INT);
        $stmt->execute();
    }


    /**
     * Command to save a credit card
     */
    $save_cc_params = function() {
        send_sms(
            'Save cc' . "\n" .
            'name' . "\n" .
            'balance - #.##' . "\n" .
            'credit - #' . "\n" .
            'due date - ##' . "\n" .
            'apr - #.##' . "\n" .
            'annual fee - $##' . "\n"
        );
    };

    // courtesy of Stack Overflow
    function generateRandomString( $length = 6 ) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    /**
     * Checks against db for existing card
     * that has initially generated string
     */
    function get_card_uid() {
        global $dbh;

        $uid = generateRandomString();
        $stmt = $dbh->prepare('SELECT * FROM credit_cards WHERE card_id = :card_id');
        $stmt->bindParam( ':card_id', $uid, PDO::PARAM_STR );

        if ( $stmt->execute() ) {
            $credit_cards = $stmt->fetchAll();
            if ( count($credit_cards) > 0 ) {
                get_card_uid();
            } else {
                return $uid;
            }
        }
    }


    /**
     * Expects 6 parameters
     */
    $save_cc = function( $sms ) {
        global $dbh;

        $cc_details = explode( "\n", $sms );

        $id = null;
        $card_id = get_card_uid();
        $name = empty_type_check( $cc_details[1], 'str' );
        $balance = empty_type_check( (float)$cc_details[2], 'float' );
        $credit = empty_type_check( (int)$cc_details[3], 'int' );
        $due_date = empty_type_check( (int)$cc_details[4], 'int', 'month' );
        $apr = empty_type_check( (float)$cc_details[5], 'float' );
        $annual_fee = empty_type_check( $cc_details[6], 'str' );

        $stmt = $dbh->prepare(
            'INSERT INTO credit_cards VALUES
            (:id, :card_id, :name, :balance, :credit, :due_date, :apr, :annual_fee)'
        );

        $stmt->bindParam( ':id', $id, PDO::PARAM_INT );
        $stmt->bindParam( ':card_id', $card_id, PDO::PARAM_STR );
        $stmt->bindParam( ':name', $name, PDO::PARAM_STR );
        $stmt->bindParam( ':balance', $balance, PDO::PARAM_STR );
        $stmt->bindParam( ':credit', $credit, PDO::PARAM_STR );
        $stmt->bindParam( ':due_date', $due_date, PDO::PARAM_STR );
        $stmt->bindParam( ':apr', $apr, PDO::PARAM_STR );
        $stmt->bindParam( ':annual_fee', $annual_fee, PDO::PARAM_STR ); // display only

        if ($stmt->execute()) {
            send_sms( 'Credit card saved' );
        } else {
            send_sms( 'Failed to save credit card' );
        }
    };


    /**
     * Delete cc command
     */
    $delete_cc_cmd = function() {
        send_sms(
            'Delete cc' . "\n" .
            'card id'
        );
    };


    /**
     * Delete CC
     */
    $delete_cc = function( $sms ) {
        global $dbh;

        // could query first to know what card is deleted
        $sms_parts = explode( "\n", $sms );
        $uid = $sms_parts[1];
        $stmt = $dbh->prepare('DELETE FROM credit_cards WHERE card_id = :card_id');
        $stmt->bindParam( ':card_id', $uid, PDO::PARAM_STR );

        if ( $stmt->execute() ) {
            send_sms( 'Card deleted' );
        } else {
            send_sms( 'Failed to delete card' );
        }
    };


    /**
     * Update CC cmd
     */
    $update_cc_cmd = function() {
        send_sms(
            "Note: except for card_id, at least 1 field is required" . "\n\n" .
            'Update cc' . "\n" .
            'card_id=' . "\n" .
            'name=' . "\n" .
            'balance='  . "\n" .
            'credit=' . "\n" .
            'due_date=' . "\n" .
            'apr=' . "\n" .
            'annual_fee='
        );
    };

    /**
     * Update CC
     */
    $update_cc = function( $sms ) {
        global $dbh;

        $sms_parts = explode( "\n", $sms );
        $params = [];

        if ( strpos($sms, 'card_id') === false ) {
            send_sms( 'Failed to update, missing card_id' );
            return;
        }

        foreach ( $sms_parts as $key => $sms_part ) {
            if ( $key > 0 ) {
                if ( strpos($sms_part, '=') === false ) {
                    send_sms( 'Failed to update, invalid params' );
                    break;
                }

                $key_vals = explode( '=', $sms_part );
                $params[$key_vals[0]] = $key_vals[1];
            }
        }

        // check card exists
        $stmt = $dbh->prepare('SELECT * FROM credit_cards WHERE card_id = :card_id');
        $stmt->bindParam( ':card_id', $params['card_id'], PDO::PARAM_STR );

        if ( $stmt->execute() ) {
            $credit_card = $stmt->fetchAll()[0]; // should only be 1 due to uid check on save
            
            if ( empty($credit_card) ) {
                send_sms( 'Card not found' );
            } else {
                // ternary use old or new values
                $cc_name = isset($params['name']) ? $params['name'] : $credit_card['name'];
                $cc_balance = isset($params['balance']) ? $params['balance'] : $credit_card['balance'];
                $cc_credit = isset($params['credit']) ? $params['credit'] : $credit_card['credit'];
                $cc_due_date = isset($params['due_date']) ? $params['due_date'] : $credit_card['due_date'];
                $cc_apr = isset($params['apr']) ? $params['apr'] : $credit_card['apr'];
                $cc_annual_fee = isset($params['annual_fee']) ? $params['annual_fee'] : $credit_card['annual_fee'];

                // check due date
                if ( $cc_due_date < 0 || $cc_due_date > 31 ) {
                    send_sms( 'Failed to update card: invalid due date' );
                    return;
                }

                $stmt = $dbh->prepare(
                    'UPDATE credit_cards
                    SET name=:name, balance=:balance, credit=:credit, 
                        due_date=:due_date, apr=:apr, annual_fee=:annual_fee
                    WHERE card_id = :card_id'
                );

                $stmt->bindParam( ':card_id', $params['card_id'], PDO::PARAM_STR );
                $stmt->bindParam( ':name', $cc_name, PDO::PARAM_STR );
                $stmt->bindParam( ':balance', $cc_balance, PDO::PARAM_STR );
                $stmt->bindParam( ':credit', $cc_credit, PDO::PARAM_INT );
                $stmt->bindParam( ':due_date', $cc_due_date, PDO::PARAM_INT );
                $stmt->bindParam( ':apr',$cc_apr, PDO::PARAM_STR );
                $stmt->bindParam( ':annual_fee', $cc_annual_fee, PDO::PARAM_STR );

                if ( $stmt->execute() ) {
                    send_sms( 'Card updated' );
                } else {
                    send_sms( 'Failed to update card' );
                }

            }
        } else {
            send_sms( 'Failed to update card' );
        }
    };


    /**
     * Parses remind me string and inserts future reminder into db
     */
    $remind_me = function( $sms ) {
        $sms_parts = explode("\n", $sms);
        $sms_text = $sms_parts[1];
        // determine minutes or hours
        $remind_time_str = explode(' ', $sms_parts[0])[2];
        $return_arr = [];
        $return_arr['message'] = $sms_text;
        
        if (strpos($remind_time_str, 'mins') !== false) {
            // mins
            $return_arr['timestamp'] = (int)(explode('mins', $remind_time_str)[0]) * 60;
        }
        else if (strpos($remind_time_str, 'hrs') !== false) {
            // hrs
            $return_arr['timestamp'] = (int)(explode('hrs', $remind_time_str)[0]) * 3600;
        }
        else {
            // invalid
            $return_arr = false;
        }
        
        if ( $return_arr ) {
            save_reminder( $return_arr );
        } else {
            send_sms( 'Failed to schedule message' );
        }
    };


    /**
     * Returns all commands, technically redundant since not pulled from
     * arrays in short_command and try_strpos functions
     * this could be fine you have to specify patterns anyway
     */
    $return_commands = function() {
        $commands = [
            'btc',
            'Save cc cmd',
            'Remind me #hrs/#mins',
            'ls cards',
            'Delete cc cmd',
            'Update cc cmd'
        ];

        send_sms( implode("\n", $commands) );
    };


    /**
     * Short direct commands mapping
     */
    function short_command( $sms ) {
        global 
            $return_commands, $get_btc_price, $save_cc_params, $list_all_credit_cards, $delete_cc_cmd,
            $update_cc_cmd;

        $short_commands = [
            'cmds' => $return_commands,
            'btc' => $get_btc_price,
            'Save cc cmd' => $save_cc_params,
            'Delete cc cmd' => $delete_cc_cmd,
            'Update cc cmd' => $update_cc_cmd,
            'ls cards' => $list_all_credit_cards
        ];

        if ( array_key_exists($sms, $short_commands) ) {
            $short_commands[$sms]();
        } else {
            send_sms( 'Command not found' );
        }
    }


    /**
     * Map sms commands to functions
     */
    function try_strpos( $sms ) {
        global $remind_me, $save_cc, $delete_cc, $update_cc;

        // partial command str lookup
        $param_commands = [
            'Remind me' => $remind_me,
            'Save cc' => $save_cc,
            'Delete cc' => $delete_cc,
            'Update cc' => $update_cc
        ];

        $command_found = false;
        $param_command = null;

        foreach ( $param_commands as $param_command_partial => $param_command_fcn ) {
            if ( strpos($sms, $param_command_partial) !== false ) {
                $command_found = true;
                $param_command = $param_command_fcn;
                break;
            }
        }

        if ( $command_found ) {
            $param_command( $sms );
        } else {
            send_sms( 'Param command not found' );
        }
    }
