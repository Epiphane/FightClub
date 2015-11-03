<?

if (!function_exists("__autoload")) {
   function __autoload($class_name) {
      $parts = explode("\\", $class_name);

      $path = join("/", $parts);

      if (file_exists($path . '.php')) { 
         require_once $path . '.php'; 
         return true; 
      } 
      return false;
   }
}