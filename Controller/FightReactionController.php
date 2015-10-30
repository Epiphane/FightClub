<?

/*
 * FightReactionController class file
 *
 * @author Thomas Steinke
 */
namespace Fight\Controller;

use \Fight\Model\FightReactionModel;

class FightReactionController
{
   public static function addReaction($user, $image_url, $type = "attack") {
      if ($image_url[0] === "<") {
         $image_url = substr($image_url, 1, strlen($image_url) - 2);
      }

      if (!$type) {
         $type = "attack";
      }

      $reaction = FightReactionModel::findOneWhere([ "image_url" => $image_url ]);

      if (!$reaction) {
         $reaction = FightReactionModel::build([
            "user_id" => $user->user_id,
            "image_url" => $image_url,
            "type" => $type
         ]);

         $reaction->save();
      }

      return $image_url;
   }

   public static function getReaction($type) {
      $dao = new \Data\DAO("\Fight\Model\FightReactionModel");
      $img = $dao->query("SELECT * FROM fight_reaction ORDER BY RAND() LIMIT 1")->fetch_assoc()["image_url"];

      return $img;// . "?" . mt_rand(10000,99999);
   }
}