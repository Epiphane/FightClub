<?

namespace Fight\Data;

class DB
{
	// Database connection
	private static $host     = DB_HOST;
	private static $username = DB_USER;
	private static $password = DB_PASS;
	private static $database = DB_NAME;

	public static function getConnection() {
		$connect = mysqli_connect(self::$host, self::$username, self::$password, self::$database);

		if (mysqli_connect_errno()) {
			throw new \Exception("Failed to connect to MySQL: " . mysqli_connect_error());
		}

		return $connect;
	}
}
