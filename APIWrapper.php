<?

/*
 * APIWrapper class file
 *
 * @author Thomas Steinke
 */

namespace Fight;

require_once __DIR__ . "/config.php";

use Fight\Main;
use Fight\Model\FightAppModel;

class APIWrapper
{
   // Example payload
   /*
   [
      "text" => "<@USLACKBOT>,
      "user_id" => "U0B2QPTNU",
      "user_name" => "thomassteinke",
      "team_id" => "T0B2LSLP6",
      "channel_id":"C0CS03RK4"
   ]
   */

   public static $complete = false;

   public static function respond() {
      $url = $_SERVER["REQUEST_URI"];
      if (strrpos($url, "?"))
         $url = substr($url, 0, strrpos($url, "?"));
      $path = explode("/", $url);
      $path = $path[count($path) - 1];

      $params = json_decode(file_get_contents("php://input"), TRUE) ?: [];
      $params = array_merge($_POST, $params);
      $text = substr($params["text"], strlen($path) + 1);

      if ($path === "fight") {
         $command = explode(" ", $text)[0];
         if (Main::isMethod($command)) {
            $path = $command;
            $text = substr($text, strlen($command) + 1);
         }
      }

      ini_set("display_errors", 0);
      set_exception_handler(["Fight\APIWrapper", "error_handler"]);
      register_shutdown_function(["Fight\APIWrapper", "fatal_handler"]);
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

      $attachments = [];
      foreach ($result["data"] as $update) {
         $attachment = $update->toAttachment($result["user"]);
         if ($attachment) $attachments[] = $attachment;
      }

      echo json_encode($attachments);

      self::$complete = true;
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