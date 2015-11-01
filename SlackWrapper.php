<?

/*
 * SlackWrapper class file
 *
 * @author Thomas Steinke
 */

namespace Fight;

use Fight\Main;

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

   public static function respond() {
      $params = $_POST;
      $path = $params["trigger_word"];
      $text = substr($params["text"], 6);

      if ($path === "fight") {
         $command = explode(" ", $text)[0];
         if (Main::isMethod($command)) {
            $path = $command;
            $text = substr($text, strlen($command) + 1);
         }
      }

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
      $attachments = [];
      foreach ($result["data"] as $update) {
         $attachments[] = $update->toString();
      }

      // Add a space so we don't trigger ourselves
      echo json_encode([
         "text" => " " . implode("\n", $attachments)
      ]);
   }
}

// From http://www.gen-x-design.com/archives/create-a-rest-api-with-php/ 
$codes = parse_ini_file(__DIR__ . "/codes.ini");
function getStatusCodeMessage($status) {
   return (isset($codes[$status])) ? $codes[$status] : '';
}