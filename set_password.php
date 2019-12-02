<?php
ini_set( 'display_errors', 0 );
require_once("./const/const.inc");
require_once("./func.inc");
require_once("./const/login_func.inc");
$result = check_user($db, "1");

define (STR_TITLE1,        '八王子さくらアカデミー');
define (STR_CURRENT_PASS,  '現在のパスワード');
define (STR_NEW_PASSWORD1, '新しいパスワード');
define (STR_NEW_PASSWORD2, '新しいパスワード（再入力）');
define (STR_CHANGE,        '変更する');
define (STR_SET_PASSWORD,  'パスワード設定');
define (STR_LOGOUT,        'ログアウト');
define (STR_MAINMENU,      'メインメニューへ戻る');
define (STR_ERROR1,        '現在のパスワードが違います。');
define (STR_ERROR2,        '新しいパスワードと新しいパスワード（再入力）が一致しません。');
define (STR_ERROR3,        '新しいパスワードが空です。');
define (STR_COMMENT,       'パスワードは40文字までの任意の半角英数字記号が使用できます。');

if (isset($_POST['add'])) {
	$action = 'add';
} else {
	$action = "";
}

if ($action == 'add') {
	
	$id = $_SESSION['login']['id'];
	$password0 = trim($_POST['password0']);
	$password1 = trim($_POST['password1']);
	$password2 = trim($_POST['password2']);

	$user = get_member($db, array("id = ?"), array($id));

	if ($user['passwd'] != md5($password0)){
		$errArray[] = STR_ERROR1;
	} else if ($password1!=$password2) {
		$errArray[] = STR_ERROR2;
	} else if ($password1=='') {
		$errArray[] = STR_ERROR3;
	} else {
		try{
			$password1 = md5($password1);
			$db->beginTransaction();
			$db->query("UPDATE tbl_member SET passwd='$password1', update_timestamp=now() WHERE id='$id'");		
			$db->commit();
			header('Location: menu.php');
			exit;
		}catch (PDOException $e){
			$db->rollback();
			array_push($errArray, 'Error:'.$e->getMessage());
		}
	}
} else {
}

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="robots" content="index,follow">
</head>
<body>
<div align="center">
<h3><?= STR_SET_PASSWORD ?></h3>
<?php
	if (count($errArray) > 0) {
		foreach( $errArray as $error) {
			echo "<font color=\"red\" size=\"5\">$error</font><br>";
		}
	}
?>

<form method="post" name="password_form" action="set_password.php">
<input type="hidden" name="id" value="<?= $id ?>">
<table>
<tr><td><?= STR_CURRENT_PASS  ?></a></td><td><input type="password" name="password0" size="20" maxlength="40"></td></tr>
<tr><td><?= STR_NEW_PASSWORD1 ?></a></td><td><input type="password" name="password1" size="20" maxlength="40"></td></tr>
<tr><td><?= STR_NEW_PASSWORD2 ?></a></td><td><input type="password" name="password2" size="20" maxlength="40"></td></tr>
<tr><td colspan="2"><?= STR_COMMENT ?></td></tr>
<tr><td colspan="2" align="center"><input type="submit" name="add" value="<?= STR_CHANGE ?>"></td></tr>
<tr><td colspan="2">&nbsp;</td></tr>
<tr><td colspan="2" align="center"><a href="./menu.php"><?= STR_MAINMENU ?></a></td></tr>
</table>
</form>
</div>
</body>
</html>