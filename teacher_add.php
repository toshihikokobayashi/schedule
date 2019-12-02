<?php
ini_set( 'display_errors', 0 );
require_once("./const/const.inc");
require_once("./func.inc");
require_once("./const/login_func.inc");
$result = check_user($db, "1");

$course_list = get_course_list($db);
$teacher_list = get_teacher_list($db);

$errArray = array();

if (isset($_POST['add'])) {
	$action = 'add';
} else {
	$action = "";
}


if ($action == 'add') {
// 登録処理

	$teacher = array();
	$teacher["no"] = trim($_POST["no"]);
	$teacher["name"] = trim($_POST["name"]);
	$teacher["furigana"] = trim($_POST["furigana"]);
	$teacher["lesson_id"] = trim($_POST["lesson_id"]);
	$teacher["del_flag"] = trim($_POST["del_flag"]);
	$teacher["mail_address"] = trim($_POST["mail_address"]);
	$teacher["password"] = trim($_POST["password"]);
	$teacher["initial_password"] = trim($_POST["password"]);

	$teacher["transport_cost"] = '';
	$teacher["transport_DOW"]  = '0,1,2,3,4,5,6';
	$teacher["transport_limit"] = '';
	$teacher["gennsenn_choushuu_shubetu"] = '';
	$teacher["huyou_ninnzuu"] = '';
	$teacher["jyuuminnzei1"] = '';
	$teacher["jyuuminnzei2"] = '';

	// 入力チェック処理
	$result = check_teacher($db, $errArray, $teacher);

	if (count($errArray) == 0) {
	// 入力エラーがない場合
		$errFlag = 0;
		try{
			$db->beginTransaction();
			$teacher_no = insert_teacher($db, $teacher);
			if (!$teacher_no) $errFlag = 1;
		}catch (PDOException $e){
			$errFlag = 1;
			array_push($errArray, 'Error:'.$e->getMessage());
		}
		if ($errFlag == 0) {
			$db->commit();
			header('Location: teacher_list.php?sort_type=2');
			exit;
		} else {
			$db->rollback();
			array_push($errArray, "登録中にエラーが発生しました。");
		}
	}
	// エラー時、編集画面を再表示する

} else {
	// 初期表示処理

	$teacher["no"] = "";
	$teacher["name"] = "";
	$teacher["furigana"] = "";
	$teacher["lesson_id"] = "";
	$teacher["del_flag"] = "0";	// 初期値：現先生
	$teacher["mail_address"] = "";
	$teacher["password"] = substr(base_convert(bin2hex(openssl_random_pseudo_bytes(8)),16,36),0,8);

}


?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF8">
<meta name="robots" content="noindex,nofollow">
<title>事務システム</title>
<script type = "text/javascript">
<!--
//-->
</script>
<link rel="stylesheet" type="text/css" href="./script/style.css">
<script type="text/javascript" src="./script/calender.js"></script>
</head>
<body>

<div id="header">
	事務システム 
</div>

<div id="content" align="center">

<h3>先生の登録 - 新規登録</h3>

<a href="teacher_list.php">先生一覧へ</a>&nbsp;&nbsp;
<a href="menu.php">メニューへ戻る</a><br><br>

<?php
	if (count($errArray) > 0) {
		foreach( $errArray as $error) {
?>
			<font color="red" size="5"><?= $error ?></font><br>
<?php
		}
?>
	<br>
<?php
	}
?>

<form method="post" name="teacher_form" action="teacher_add.php">
	<input type="hidden" name="no" value="<?=$teacher["no"]?>">
	<input type="hidden" name="del_flag" value="0">

	<table>
	<tr>
  <td align="center">
		新規登録する場合は、先生情報を入力して、登録ボタンを押してください。<br>
		<input type="submit" name="add" value="登録">
		<input type="reset" name="reset" value="リセット">
	</td>
	</tr>
	</table>

	<br>

	<table id="form">
	<tr>
	<th><font color="red">*</font>&nbsp;名前</th>
	<td>
		<input type="text" name="name" size="20" value="<?=$teacher["name"]?>">
		<font color="red" size="-1">名字と名前の間に半角スペースを入れてください</font>
	</td>
	</tr>
	<tr>
	<th><font color="red">*</font>&nbsp;ふりがな</th>
	<td>
		<input type="text" name="furigana" size="35" value="<?=$teacher["furigana"]?>">
		<font color="red" size="-1">名字と名前の間に半角スペースを入れてください</font>
	</td>
	</tr>
	<tr>
	<th>教室</th><td>
		<select name="lesson_id">
<?php
	foreach ($lesson_list as $key => $name) {
?>
		<option value="<?=$key?>"<?php if ($teacher["lesson_id"] == $key) { echo "selected"; } ?>>
		<?php if ($key==0) { echo ""; } else { echo $name; } ?></option>
<?php
	}
?>
		</select>
	</td>
	</tr>
	<tr>
	<th>ステータス</th><td>
		<select name="del_flag">
		<option value="0" <?php if ($teacher["del_flag"] == 0) { echo "selected"; } ?>>現先生</option>
		<option value="2" <?php if ($teacher["del_flag"] == 2) { echo "selected"; } ?>>前先生</option>
		<option value="1" <?php if ($teacher["del_flag"] == 1) { echo "selected"; } ?>>削除</option>
		</select>
	</td>
	</tr>
	<tr>
	<th>メールアドレス</th><td>
		<input type="text" name="mail_address" size="60" maxlength="100" value="<?=$teacher["mail_address"]?>">
	</td>
	</tr>
	<tr>
	<th>初期パスワード</th><td>
		<input type="hidden" name="password" value="<?=$teacher["password"]?>">
		<?=$teacher["password"]?>
	</td>
	</tr>
	</table>
	<br>
</form>
</div>

</body>
</html>
