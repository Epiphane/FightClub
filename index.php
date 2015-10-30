<?

/**
 * Fight Your Friends!
 *
 * @author Thomas Steinke (http://github.com/Epiphane)
 *
 * This is under the WTFPL License (http://www.wtfpl.net/). Mutilate it,
 * extend it, whatever, but if you come across something fun or neat
 * I'd love to hear more about it and maybe add it to my own version.
 * Contact me at exyphnos@gmail.com :)
 *
 * This is a cool little game to fight your friends on Slack or other (coming soon™)
 * chat clients.
 *
 * You can either include this file (fight.php) and use it, or include slack.php
 * and it will handle the re-parameterizing for Slack
 */

/*
 * COUPLE NOTES:
 * 
 * 1) If you have a custom __autoload function, comment out the line that
 *    includes autoload.php
 * 2) Make sure you create a config.php file that defines:
 *    - FIGHT_DB_HOST
 *    - FIGHT_DB_USER
 *    - FIGHT_DB_PASS
 *    - FIGHT_DB_NAME
 *    - NO_AUTOLOAD (if you don't want to include this version of autoload)
 * 
 */

require_once __DIR__ . "/config.php";

if (!defined(NO_AUTOLOAD)) {
   require_once __DIR__ . "/autoload.php";
}

namespace Fight;

class Main
{
   /**
    * Respond to command (from API, SlackWrapper, whatever)
    *
    * @param method - The method to call (fight, status, equip, etc)
    * @param params - Additional parameters
    *  Ex [
    *    "text" => "<@USLACKBOT>,
    *    "user_id" => "U0B2QPTNU",
    *    "user_name" => "thomassteinke",
    *    "team_id" => "T0B2LSLP6",
    *    "channel_id" => "C0CS03RK4"
    *  ]
    * @return array - [
    *    status => HTTP status,
    *    data => array of FightAttachments (could be FightErrorAttachment)
    * ]
    */
   public static function main($method, $params) {

   }
}