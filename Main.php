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

namespace Fight;

require_once __DIR__ . "/config.php";

if (!defined(NO_AUTOLOAD)) {
   require_once __DIR__ . "/autoload.php";
}

use Fight\Controller\FightController;
use Fight\Controller\FightUserController;
use Fight\Attachment\FightErrorMessage;
use Fight\Attachment\FightMessage;

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
    *    "team_id" => "T0B2LSLP6",
    *    "channel_id" => "C0CS03RK4",
    *    "user_name" => "thomassteinke" ** Optional
    *  ]
    * @return array - [
    *    status => HTTP status,
    *    data => array of FightAttachments (could be FightErrorAttachment)
    * ]
    */
   public static function main($method, $params) {
      $user = FightUserController::findUser($params["team_id"], $params["user_id"]);
      if ($params["user_name"] && $params["user_name"] !== $user->alias->slack_name) {
         $user->alias->update(["slack_name" => $params["user_name"]]);
      }

      // Make sure the method is fine
      if (!self::isMethod($method)) {
         return self::packageData(400, [
            new FightErrorMessage("Command `" . $method . "` isn't available")
         ]);
      }

      try {
         $argv = explode(" ", $method . " " . $params["text"]);
         $argc = count($argv);
         $fight = FightController::findFight($user, $params["channel_id"]);

         $method .= "_";
         $result = FightController::$method($argc, $argv, $user, $fight, $params);

         return self::packageData(200, $result, $user);
      }
      catch (Exception $e) {
         if ($e->getCode() === 200) {
            $message = new FightMessage("warning", $e->getMessage());
         }
         else {
            $message = new FightErrorMessage($e->getMessage());
         }

         return self::packageData($e->getCode(), $message, $user);
      }
   }

   public static function packageData($status, $data, $user) {
      if (!is_array($data)) $data = [$data];

      return [
         "status" => $status,
         "data" => $data,
         "user" => $user
      ];
   }

   public static function isMethod($method) {
      return is_callable(["Fight\Controller\FightController", $method . "_"]);
   }
}