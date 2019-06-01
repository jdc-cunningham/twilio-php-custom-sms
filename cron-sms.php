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


    $cur_timestamp = time(); // 5 minutes back - took off try 1 minute earlier
    
    
    // find any messages scheudled to be sent within the last 5 minutes
    function check_scheduled_messages() {
        global $dbh, $cur_timestamp;

        $stmt = $dbh->prepare('SELECT id, sms_body FROM reminders WHERE remind_at_timestamp <= ' . $cur_timestamp . ' AND reminder_sent = 0');
        if ($stmt->execute()) {
            $reminders = $stmt->fetchAll();
            if (!empty($reminders)) {
                foreach ($reminders as $reminder) {
                    send_sms( substr($reminder['sms_body'], 0, 143) );
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
    }

    check_scheduled_messages();