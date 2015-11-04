<?

/*
 * FightUserController class file
 *
 * @author Thomas Steinke
 */
namespace Fight\Controller;

use \Fight\Model\FightUserModel;
use \Fight\Model\FightItemModel;
use \Fight\Controller\FightAppController;

class FightUserController
{
   /**
    * Find a user by user ID and team ID, and create the user if they don't exist.
    *
    * @param $team_id - Slack (or any < 16-char team id)
    * @param $user_id - Unique user id, up to 16 characters
    * @return [FightUserModel, FightAliasModel]
    */
   public static function findUser($team_id, $user_id) {
      $request = new \Data\Request();
      $request->Filter[] = new \Data\Filter("team_id", $team_id);
      $request->Filter[] = new \Data\Filter("slack_user_id", $user_id);

      $alias = FightAliasModel::findOneWhere([
         "team_id" => $team_id,
         "slack_user_id" => $user_id
      ]);

      if (!$alias) {
         // Attempt to find user and connect him
         $email = FightCURLController::getUserInfo($team_id)["email"];

         $user = FightUserModel::findOneWhere([
            "email" => $email
         ]);

         if (!$user) {
            // Create user
            $user = FightUserModel::build([
               "team_id" => $team_id,
               "email" => $email,
               "name" => $user_id,
               "level" => 1,
               "experience" => 0
            ]);

            if (!$user->save()) return null;

            // Give them a basic attack
            $attack = FightItemModel::build([
               "user_id" => $user->user_id,
               "name" => "attack",
               "stats" => [ "physical" => 15 ],
               "type" => "move"
            ]);

            $attack->save();

            // Give them a basic armor too
            $armor = FightItemModel::build([
               "user_id" => $user->user_id,
               "name" => "clothes",
               "stats" => [
                  "alignment" => "none",
                  "physical" => 2,
                  "elemental" => 0,
                  "defense" => 4
               ],
               "type" => "item"
            ]);

            $armor->save();

            $user->update([ "armor" => $armor->item_id ]);
         }

         $alias = FightAliasModel::build([
            "user_id" => $user->user_id,
            "slack_user_id" => $user_id,
            "team_id" => $team_id,
         ]);

         if (!$alias->save()) throw new Exception("Alias not saved");
      }
      else {
         $user = FightUserModel::findById($alias->user_id);
      }

      $user->alias = $alias;

      return $user;
   }

   public static function findUserByTag($team_id, $user_tag) {
      if (strpos($user_tag, "<@") === false) {
         return false;
      }

      $user_id = substr($user_tag, strlen("<@"), strlen($user_tag) - 3);

      return self::findUser($team_id, $user_id);
   }
}