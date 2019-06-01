<?php

    require('/db-connect.php'); // get db credentials

    // Twilio sms function
    // require db connection
    require_once('/twilio-php/Twilio/autoload.php');
    
    function sendSMS($msg) {
        $sid = ""; // Your Account SID from www.t$
        $token = ""; // Your Auth Token from www.tw$

        $client = new Twilio\Rest\Client($sid, $token);
        $message = $client->messages->create(
            '', // Text this number
            array(
                'from' => '', // From a valid Twilio number
                'body' => $msg
            )
        );
    }

    $cur_timestamp = time(); // 5 minutes back - took off try 1 minute earlier

    // find any messages scheudled to be sent within the last 5 minutes
    $stmt = $dbh->prepare('SELECT id, sms_body FROM reminders WHERE remind_at_timestamp <= ' . $cur_timestamp . ' AND reminder_sent = 0');
    if ($stmt->execute()) {
        $reminders = $stmt->fetchAll();
        if (!empty($reminders)) {
            foreach ($reminders as $reminder) {

                /**
                 * this end() part here was my attempt to estimate balance since Twilio at this time to my knowlege
                 * does not have a way to let you know of your current balance
                 */

                if ($reminder === end($reminders)) {
                    // get sms sent count
                    $stmt = $dbh->prepare('SELECT id FROM reminders WHERE reminder_sent=1');
                    if ($stmt->execute()) {
                        $result = $stmt->fetchAll();
                        if (!empty($result)) {
                            $sent_msg_count = count($result);
                            // current balance $17.63743 month is July, charged on 20th, every month is $1.00
                            $july_ts = 1531549572;
                            $estimated_value = "\n" . 'Est bal: $' . substr((string)( 17.63743 - 0.075 - ($sent_msg_count * 0.075) - floor( ( time() - $july_ts ) / 2592000) ), 0, 8);
                        }
                    }
                }
                else {
                    $estimated_value = '';
                }

                sendSMS(substr($reminder['sms_body'], 0, 143) . $estimated_value);
                $cur_id = $reminder['id'];
                $stmt = $dbh->prepare('UPDATE reminders SET reminder_sent = 1 WHERE id = :id');
                $stmt->bindParam(':id', $cur_id, PDO::PARAM_INT);
                $stmt->execute();
            }
        }
        else {
            error_log('empty result');
        }
    }