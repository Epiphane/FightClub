<?

/*
 * FightErrorMessage class file
 *
 * @author Thomas Steinke
 */

namespace Fight\Attachment;

class FightErrorMessage extends FightMessage
{
   public function __construct($message) {
      parent::__construct("danger", $message);
   }

   public function toAttachment($user) {
      return array_merge(parent::toAttachment($user), [
         "type" => "error"
      ]);
   }
}
