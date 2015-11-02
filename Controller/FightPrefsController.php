<?

/*
 * FightPrefsController class file
 *
 * @author Thomas Steinke
 */
namespace Fight\Controller;

use \Fight\Model\FightPrefsModel;

class FightPrefsController
{
   public static function findById($channel_id) {
      $model = FightPrefsModel::findById($channel_id);

      if (!$model) {
         $model = FightPrefsModel::build([ "channel_id" => $channel_id ]);

         $model->save();
      }

      return $model;
   }

   public static function updateSettings($settings, $params) {
      if ($params[0] === "reactions") {
         $settings->update(["reactions" => $params[1] === "on"]);
      }
      if ($params[0] === "api") {
         $settings->update(["api_token" => $params[1]]);
      }

      return "Settings updated!";
   }
}