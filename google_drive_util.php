<?php
ini_set('display_errors', '0');
//ini_set('display_errors', 'on');
//error_reporting(E_ALL);

require_once(dirname(__FILE__)."/const/const.inc");
require_once(dirname(__FILE__)."/func.inc");
//require_once(dirname(__FILE__)."/const/login_func.inc");
//$result = check_user($db, "1");

//ini_set('include_path', CLIENT_LIBRALY_PATH);
ini_set('include_path', get_include_path() . PATH_SEPARATOR . CLIENT_LIBRALY_PATH);
//ini_set('include_path', get_include_path() . PATH_SEPARATOR . realpath(str_replace('\\', '/', dirname(__FILE__)).'/../vendor'));

require_once "Google/autoload.php";
//require_once("GoogleDriveAuth.php");
//require_once('../vendor/autoload.php');
require_once(dirname(__FILE__)."/../vendor/autoload.php");
use Google\Spreadsheet\DefaultServiceRequest;
use Google\Spreadsheet\ServiceRequestFactory;
//set_time_limit(60);

class google_drive_util {

	var $client;
	var $spreadsheetFeed;
	var $spreadsheetService;

	function __construct() {

		// --- createClient ---
		$this->client = new Google_Client();
		$this->client->setApplicationName('calender-project');
		$this->client->setClientId(CLIENT_ID);
		$cred = new Google_Auth_AssertionCredentials(
		    SERVICE_ACCOUNT_NAME,
		    array('https://spreadsheets.google.com/feeds'),
		    file_get_contents(KEY_FILE)
		);
		$this->client->setAssertionCredentials($cred);

		// --- getAccessToken ---
		if($this->client->isAccessTokenExpired()) {
	    $this->client->getAuth()->refreshTokenWithAssertion($cred);
		}
		$obj_token  = json_decode($this->client->getAccessToken());
		$accessToken = $obj_token->access_token;
		//$obj = json_decode($client->getAccessToken());
    //$token = $obj->{'access_token'};
    //write($accessToken);
    //read($accessToken);

		// --- initServiceRequest ---
		$serviceRequest = new DefaultServiceRequest($accessToken);
		ServiceRequestFactory::setInstance($serviceRequest);

		$this->spreadsheetService = new Google\Spreadsheet\SpreadsheetService();
		$this->spreadsheetFeed = $this->spreadsheetService->getSpreadsheets();

	}

/*
session_start();
$client = new Google_Client();
$client->setClientId('クライアントID');
$client->setClientSecret('クライアントシークレット');
$client->setRedirectUri('リダイレクトURI');

// 許可されてリダイレクトされると URL に code が付加されている
// code があったら受け取って、認証する
if (isset($_GET['code'])) {
    // 認証
    $client->authenticate($_GET['code']);
    $_SESSION['token'] = $client->getAccessToken();
    // リダイレクト GETパラメータを見えなくするため（しなくてもOK）
    header('Location: http://'.$_SERVER['HTTP_HOST']."/");
    exit;
}

// セッションからアクセストークンを取得
if (isset($_SESSION['token'])) {
    // トークンセット
    $client->setAccessToken($_SESSION['token']);
}

// トークンがセットされていたら
if ($client->getAccessToken()) {
    try {
        echo "Google Drive Api 連携完了！<br>";
        $obj = json_decode($client->getAccessToken());
        $token = $obj->{'access_token'};
        write($token);
        read($token);
    } catch (Google_Exception $e) {
        echo $e->getMessage();
    }
} else {
    // 認証スコープ(範囲)の設定
    $client->setScopes(Google_Service_Drive::DRIVE);
    // 一覧を取得する場合はhttps://spreadsheets.google.com/feedsが必要
    $client->addScope('https://spreadsheets.google.com/feeds');

    $authUrl = $client->createAuthUrl();
    echo '<a href="'.$authUrl.'">アプリケーションのアクセスを許可してください。</a>';
}
*/

}
?>

