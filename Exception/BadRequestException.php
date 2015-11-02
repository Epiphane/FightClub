<?

/*
 * BadRequestException class file
 *
 * @author Thomas Steinke
 */

namespace Fight\Exception;

class FightException extends \Exception
{
   public function __construct($message) {
      parent::__construct($message, 400);
   }
}
