<?

/*
 * FightActionController class file
 *
 * @author Thomas Steinke
 */
namespace Fight\Controller;

use \Fight\Model\FightAppModel;

class FightAppController
{
   public static function getUserInfo($team_id, $user_id) {
      $app = FightAppModel::findById($team_id);

      if (!$app) {
         throw new Exception("App not installed!");
      }

      $ch = curl_init("https://slack.com/api/users.info");
      curl_setopt_array($ch, [
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_POST => true,
         CURLOPT_POSTFIELDS => [
            "token" => $app->api_token,
            "user" => $user_id
         ]
      ]);

      $info = json_decode(curl_exec($ch), true);

      return $info["user"]["profile"];
   }
}