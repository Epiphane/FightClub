<?

/*
 * FightReaction class file
 *
 * @author Thomas Steinke
 */

namespace Fight\Attachment;

use \Fight\Controller\FightReactionController;
use \Fight\Controller\FightPrefsController;

class FightReaction
{
   protected $settings;

   public function __construct($channel_id) {
      $this->settings = FightPrefsController::findById($channel_id);
   }

   public function toAttachment($user) {
      if (!$this->settings->reactions) {
         return null;
      }

      return [
         "fallback" => "Wow!",
         "image_url" => FightReactionController::getReaction("attack")
      ];
   }

   public function toString() {
      if (!$this->settings->reactions) {
         return null;
      }

      return FightReactionController::getReaction("attack");
   }
}
