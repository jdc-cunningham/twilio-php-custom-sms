<?php

	if ($_SERVER['REQUEST_METHOD'] == 'POST') {

		require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR.'db-connect.php'); // get db credentials
		$twilio_sid = $env_arr['twilio_sid'];
		$twilio_token = $env_arr['twilio_token'];
		$twilio_phone_number = $env_arr['twilio_phone_number'];
		$your_phone_number = $env_arr['your_phone_number']; // pulled from db-connect.php included parse_env.php

		// terrible guard
		if ( !$twilio_sid || !$twilio_token || !$twilio_phone_number || !$your_phone_number ) {
			die( 'Missing credentials' );
		}

		// Twilio sms function
		// require db connection
		require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR.'twilio-php/Twilio/autoload.php');
		
		function sendSMS($msg) {
			$sid = $twilio_sid; // Your Account SID from www.t$
			$token = $twilio_token; // Your Auth Token from www.tw$

			$client = new Twilio\Rest\Client($sid, $token);
			$message = $client->messages->create(
				$your_phone_number, // Text this number
				array(
					'from' => $twilio_phone_number, // From a valid Twilio number
					'body' => $msg
				)
			);
		}

		$sms_from = $_POST['From'];
		$sms_body = $_POST['Body'];

		if ( $sms_from == '+1' . $your_phone_number ) {

			function get_btc_price() {
				$coindesk_dump = file_get_contents("https://api.coindesk.com/v1/bpi/currentprice.json");
				$coindesk_dump = json_decode($coindesk_dump, True);
				$bitcoin_usd = 'The current price of Bitcoin is $' . number_format((float)$coindesk_dump['bpi']['USD']['rate'], 2, '.', '');

				// send SMS by Twilio
				sendSMS($bitcoin_usd);
			}

			function parseMsg($sms) {
				if (strpos($sms, "\n") !== false) {
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
					
					return $return_arr;
				}
				else {
					return false;
				}
			}

			function save_reminder($sms_body, $dbh) {
				$id = null;
				$sms_comps = parseMsg($sms_body);
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


			$cmds = [
				'cmd bitcoin-price' => 'btc price',
				'Remind me' => 'sms reminder'
			];
			
			$cmd = null;
			
			foreach ($cmds as $cmd_key => $text) {
				if (strpos($sms_body, $cmd_key) !== false) {
					$cmd = $text;
				}
			}
			
			switch ($cmd) {
				case 'btc price':
					get_btc_price();
					break;
				case 'sms reminder':
					save_reminder($sms_body, $dbh);
					break;
			}

		}

	}
