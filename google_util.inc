<?php
ini_set('include_path', CLIENT_LIBRALY_PATH);
require_once "Google/autoload.php";

class GoogleCalenderAuth {

	private static $client;
	private static $service;

	public static function getCalenderService() {
		if (! isset(self::$client)) {
			self::createClient();
		}
		
		if (! isset(self::$service)) {
			self::$service = new Google_Service_Calendar(self::$client);
		}
		return self::$service;
	}

	private static function createClient() {
		self::$client = new Google_Client();
		//self::$client->setApplicationName('Application Name');
		self::$client->setClientId(CLIENT_ID);

		$credential = new Google_Auth_AssertionCredentials(
												SERVICE_ACCOUNT_NAME,
												array('https://www.googleapis.com/auth/calendar.readonly'),
												file_get_contents(KEY_FILE)
											);
		self::$client->setAssertionCredentials($credential);
	}
}
?>
