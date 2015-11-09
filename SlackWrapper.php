<?

/*
 * SlackWrapper class file
 *
 * @author Thomas Steinke
 */

namespace Fight;

require_once __DIR__ . "/config.php";

use Fight\Main;
use Fight\Model\FightAppModel;

class SlackWrapper
{
   // Example payload from Slack
   /*
   {  
      "token":"zuE4F5lLtrHkL7DKbpiwFH4m",
      "team_id":"T0B2LSLP6",
      "team_domain":"team-pc",
      "service_id":"12884001970",
      "channel_id":"C0CS03RK4",
      "channel_name":"testslack",
      "timestamp":"1445368606.000013",
      "user_id":"U0B2QPTNU",
      "user_name":"thomassteinke",
      "text":"fight dude",
      "trigger_word":"fight"
   }
   */

   public static $complete = false;

   public static function addApp() {
      $request = curl_init("https://slack.com/api/oauth.access");

      $params = [
         "client_id" => "11088904788.13274729057",
         "client_secret" => "b8d1c5e5875ea444adcbc595c52ee26c",
         "code" => $_GET["code"]
      ];

      curl_setopt($request, CURLOPT_POST, true);
      curl_setopt($request, CURLOPT_POSTFIELDS, $params);
      curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);

      $response = json_decode(curl_exec($request), true);

      if (!$response["ok"]) {
         $status_header = 'HTTP/1.1 200 OK';
         header($status_header);
         header('Content-Type: text/html');

         echo "App could not be added. Sorry!";

         return;
      }

      $app = FightAppModel::build([
         "team" => $response["team_id"],
         "api_token" => $response["access_token"],
         "channel" => $response["incoming_webhook"]["channel"]
      ]);

      $status_header = 'HTTP/1.1 200 OK';
      header($status_header);
      header('Content-Type: text/html');
      $app->save();

      echo "App added!<hr>" . 
         "Please refer to <a href='http://thomassteinke.com/api/fight#add-outgoing'>the previous page</a> to complete installation.";
   }

   public static function respond() {
      $params = $_POST;
      $path = $params["trigger_word"];
      $text = substr($params["text"], strlen($path) + 1);

      if ($path === "fight") {
         $command = explode(" ", $text)[0];
         if (Main::isMethod($command)) {
            $path = $command;
            $text = substr($text, strlen($command) + 1);
         }
      }

      ini_set("display_errors", 0);
      set_exception_handler(["Fight\SlackWrapper", "error_handler"]);
      register_shutdown_function(["Fight\SlackWrapper", "fatal_handler"]);
      $result = Main::main($path, [
         "text" => $text,
         "user_id" => $params["user_id"],
         "user_name" => $params["user_name"],
         "team_id" => $params["team_id"],
         "channel_id" => $params["channel_id"]
      ]);

      $status_header = 'HTTP/1.1 ' . $result["status"] . ' ' . getStatusCodeMessage($result["status"]);
      header($status_header);
      header('Content-Type: application/json');

      // TODO Send attachments to Slack if token is available
      $app = FightAppModel::findById($params["team_id"]);

      if ($app) {
         $attachments = [];
         foreach ($result["data"] as $update) {
            $attachment = $update->toAttachment($result["user"]);
            if ($attachment) $attachments[] = $attachment;
         }

         $ch = curl_init("https://slack.com/api/chat.postMessage");
         curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
               "token" => $app->api_token,
               "channel" => $params["channel_id"],
               "text" => "",
               "username" => "Fight Club",
               "attachments" => json_encode($attachments)
            ]
         ]);

         $result = curl_exec($ch);

         if (!$result) {
            // Add a space so we don't trigger ourselves
            echo json_encode([
               "text" => " " . implode("\n", $attachments)
            ]);
         }

         self::$complete = true;
      }
      else {
         $attachments = [];
         foreach ($result["data"] as $update) {
            $attachments[] = $update->toString();
         }

         // Add a space so we don't trigger ourselves
         echo json_encode([
            "text" => " " . implode("\n", $attachments)
         ]);

         self::$complete = true;
      }
   }

   public static function error_handler($e) {
      header('HTTP/1.1 200 OK');
      header('Content-Type: application/json');

      echo json_encode([
         "text" => $e->getMessage()
      ]);

      self::$complete = true;
      die();
   }

   public static function fatal_handler() {
      if (self::$complete) {
         return;
      }

      $errfile = "unknown file";
      $errstr  = "shutdown";
      $errno   = E_CORE_ERROR;
      $errline = 0;

      $error = error_get_last();

      if( $error !== NULL) {
         $errno   = $error["type"];
         $errfile = $error["file"];
         $errline = $error["line"];
         $errstr  = $error["message"];

         header('HTTP/1.1 200 OK');
         header('Content-Type: application/json');

         $message = "There was an error in a FightYourFriends call!<br><br>";
         $message .= "Error: " . $errstr . " at " . $errfile . ":" . $errline . "<br><br>";
         $message .= "Other info:<br>";
         $message .= "$_POST: " . json_encode($_POST) . "<br>";
         $sent = mail("exyphnos@gmail.com", "ERROR in FightYourFriends!", $message);

         echo json_encode([
            "text" => "ERROR: " . $errstr . " at " . $errfile . ":" . $errline . ". Email sent to FYF admin."
         ]);
      }
   }
}

// From http://www.gen-x-design.com/archives/create-a-rest-api-with-php/ 
$codes = parse_ini_file(__DIR__ . "/codes.ini");
function getStatusCodeMessage($status) {
   return (isset($codes[$status])) ? $codes[$status] : '';
}