<?php
function set_token() {
	$token_id = time();
	$_SESSION['token_id'] = $token_id;
	return $token_id;
}

function check_token($token_id) {
	if (empty($_SESSION['token_id']) === true || empty($token_id) === true || $_SESSION['token_id'] != $token_id) {
  	return false;
	}
	return true;
}

function clear_token() {
	$_SESSION['token_id'] = "";
}

?>