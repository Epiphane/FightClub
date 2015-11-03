<?

namespace Fight\Data;

class DB
{
	// Database connection
	private static $host     = FIGHT_DB_HOST;
	private static $username = FIGHT_DB_USER;
	private static $password = FIGHT_DB_PASS;
	private static $database = FIGHT_DB_NAME;

	public static function getConnection() {
		$connect = mysqli_connect(self::$host, self::$username, self::$password, self::$database);

		if (mysqli_connect_errno()) {
			throw new \Exception("Failed to connect to MySQL: " . mysqli_connect_error());
		}

		return $connect;
	}
}
