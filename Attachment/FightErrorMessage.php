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
      parent::__construct("danger", $message);
   }
}
