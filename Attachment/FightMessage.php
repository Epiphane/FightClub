<?

/*
 * FightMessage class file
 *
 * @author Thomas Steinke
 */

namespace Fight\Attachment;

class FightMessage extends FightAttachment
{
   protected $message;
   protected $color;

   public function __construct($color, $message = null) {
      if ($message === null) {
         // The message was sent as the first parameter
         $message = $color;
         $color   = null;
      }

      if (!is_array($message)) {
         $message = [$message];
      }

      $this->message = $message;
      $this->color = $color;
   }

   public function toAttachment($user) {
      return [
         "author_name" => $user ? "@" . $user->slack_name : "",
         "author_icon" => "https://s3-us-west-2.amazonaws.com/slack-files2/avatars/2015-10-21/12951962519_a5f5bebd7affa4fc602b_48.jpg",
         "fallback" => "Message: " . implode("\n", $this->message),
         "color" => $this->color,
         "text" => implode("\n", $this->message),
         "mrkdwn_in" => ["text", "pretext"]
      ];
   }

   public function toString() {
      return $this->message;
   }
}
