<?php
require_once(dirname(__FILE__)."/const.inc");
require_once(dirname(__FILE__)."/../func.inc");

// ログインエラーの時、$resultにfalseを返す
// ログイン成功の時、$resultに$login=array("id"=>*, "name"=>*, "kind"=>*)を返す
function login($db, $id, $passwd) {
		try{
			$user = get_member($db, array("id = ?"), array($id));
			if ($user == null) {
				$db = null;
			  return false;
			}
			if ($user['passwd'] == md5($passwd)) {
				$login_array = array('id' => $user['id'],
														'name' => $user['name'],
														'kind' => $user['kind']);
				$_SESSION['login'] = $login_array;
			} else {
				$db = null;
			  return false;
			}
	}catch (PDOException $e){
		$db = null;
		return false;
	}
	$db = null;
	return $login_array;
}

function check_user($db, $kind="") {
	global $lms_mode;
	if ($lms_mode)	return true;
	
	$result = null;

	if (array_key_exists('login', $_SESSION) == true) {
		$user = get_member($db, array("id = ?"), array($_SESSION['login']['id']));
		if ($user == null) {
		// DBにユーザ情報が登録されていない場合
			$db = null;
    	$result = false;
		} else {
			if ($kind == "1" && $user['kind'] != "1") {
			// 本部しか見れないのに、本部以外の人か見ようとしている場合
				$db = null;
				$result = false;
			}
			if ($kind == "2" && $user['kind'] != "2") {
			// 職員しか見れないのに、職員以外の人か見ようとしている場合
				$db = null;
				$result = false;
			}
		}
	} else {
	// セッションにログイン情報が登録されていない場合
	 $db = null;
   $result = false;
	}
	if ($result === false) {
		header('location: login.php');
		exit();
	}

	return true;
}

function set_current_session() {
	global $db;
	try{
		// 導入時
		//$sql = "INSERT INTO tbl_session (id, insert_timestamp, update_timestamp) VALUES (?, now(), now())";
		//		$stmt = $db->prepare($sql);
		//		$stmt->execute(array(session_id()));
		// 導入後
		$sql = "UPDATE tbl_session SET id=?, update_timestamp=now()";
				$stmt = $db->prepare($sql);
				$stmt->execute(array(session_id()));
	}catch (PDOException $e){
//var_dump($e);
  	return false;
	}
	return true;
}

function get_current_session() {
	global $db;
	try{
		$sql = "SELECT * FROM tbl_session";
		$stmt = $db->prepare($sql);
		$stmt->execute();
		$session_array = $stmt->fetchAll(PDO::FETCH_BOTH);
		if (count($session_array) == 1) {
			return $session_array[0]["id"];
		} else {
     	return false;
		}
	}catch (PDOException $e){
//var_dump($e);
  	return false;
	}
	return true;
}

function check_current_session() {
	$result = get_current_session();
	if ($result == false) {
	 return false;
	}
//var_dump($result);
//var_dump(session_id());
	if ($result != session_id()) {
		return false;
	}
	return true;
}

?>