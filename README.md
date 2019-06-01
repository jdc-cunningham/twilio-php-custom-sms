# Twilio PHP - Custom SMS Commands

## Twilio PHP SDK required!
Get it here: https://github.com/twilio/twilio-php <br />
Then extract into the twilio-php folder

## Requirements
* Active Twilio account with a phone number/forwarding SMS to remote server
* LAMP stack vps
* Your own phone/number

## Disclaimers
4 SMS text messages cost $0.03 so keep that in mind and the delay. <br />
For me this is just an intermediary app as I can't develop PWAs/Native Apps yet. <br />

### No encryption
Please keep in mind that you're sending your data to a third party service unecrypted as plain text.

## Current functionality
This is expanded from an [earlier project](https://github.com/jdc-cunningham/Twilio-SMS-Remind-Me) which supported 2 commands, the `Remind me` command and `btc` <br />
which returned the current price of BTC from Coindesk. <br /><br />
I have since updated the code structure and added more functionality to it. Specifically tracking credit cards and accruing interest. <br />
I realize other people probably don't need this but you can see from what I have how you can tailor it to suit your own needs.

## Setup
* Create your own .env file based on example
* Import the database into MySQL
* Download a copy of Twilio's PHP SDK and extract contents into `twilio-php`
* Upload code to public server folder
* Add cron jobs:
  * `* * * * * /usr/bin/php /your-full-cron-path/twilio-php-custom-sms-commands/cron-sms.php` - this one is for the `Remind me` function
  * `30 12 * * * /usr/bin/php /your-full-cron-path/twilio-php-custom-sms-commands/cron-credit-card-interest.php` - this one is for the credit card interest function

## Testing
You can test this locally by changing the flag(s) from `$production = true` to `false`. <br />
Then instead of sending the sms message/response, it will echo it out on the screen.

## Current commands
You can see the current commands by typing `cmds` which will then return a list: <br /><br />
btc <br />
Save cc cmd <br />
Remind me #hrs/#mins <br />
ls cards <br />
Delete cc cmd <br />
Update cc cmd

### Note
The memind me command expects a body below and only supports plural values eg. 1hr/1min would not work currently.

## Basics on how it works
When you send a text message, the receiving code(`index.php` > `parse_msg.php`) looks for a linebreak. <br />
The line break shows up as `\n` so depending on the existence of this linebreak, the next step(commands) <br />
follow that eg. short commands vs. `try_strpos` commands. The latter is usually associated with parameters.

## Expanding on functionality
There is base command to list commands `cmds` so whatever new flag you come up with, you'd have to add that to it <br />
Under sms-functions.php you can see functions or anonymous functions. These are assigned to lookups. <br />
So if you add a short command, you'd have to add that command into the `$short_commands` array in the `short_commands` function <br />
towards the bottom of the `sms-functions.php` file. Then define that function above as an anonymous function. <br />
Similarly if you have a command with dynamic parameters or a 2-line command, you would use the `try_strpos` command set.
