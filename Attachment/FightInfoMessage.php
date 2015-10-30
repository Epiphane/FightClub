<?

/*
 * FightInfoMessage class file
 *
 * @author Thomas Steinke
 */

namespace Fight\Attachment;

class FightInfoMessage extends FightMessage
{
   public function __construct($message) {
      parent::__construct("#23D5E4", $message);
   }

   public function toAttachment($user) {
      // Don't include the user! It's just information
      return parent::toAttachment();
   }
}
