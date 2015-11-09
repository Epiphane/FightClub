<?

/*
 * FightData class file
 *
 * @author Thomas Steinke
 */

namespace Fight\Attachment;

class FightData extends FightAttachment
{
   private $data;

   public function __construct($data) {
      $this->data = $data;
   }

   public function toAttachment() {
      return [
         "type" => "data",
         "data" => $this->data
      ];
   }
}
