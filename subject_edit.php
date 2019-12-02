<?php
ini_set( 'display_errors', 0 );
require_once("./const/const.inc");
require_once("./func.inc");
require_once("./const/login_func.inc");
$result = check_user($db, "1");

$errArray = array();

if (isset($_POST['add'])) {
	$action = 'add';
} else if (isset($_POST['delete'])) {
	$action = 'delete';
} else {
	$action = "";
}

if ($action == 'add') {

	$name = trim($_POST['name']);
	if ($name == '') { array_push($errArray, "科目名を入力してください。"); }
	if (mb_strlen($name, "UTF-8") > 18) { array_push($errArray, "科目名を１８文字以内で入力してください。"); }
	
	if (count($errArray) == 0) {
		try{

			$stmt = $db->prepare("SELECT MAX(subject_id) AS max_subject_id FROM tbl_subject");
			$stmt->execute();
			$max_subject_id = $stmt->fetchAll(PDO::FETCH_ASSOC)[0]['max_subject_id'];

			$db->beginTransaction();
			$sql = "INSERT INTO tbl_subject (subject_id, subject_name, insert_timestamp, update_timestamp".
						" ) VALUES (?, ?, now(), now())";
			$stmt = $db->prepare($sql);
			$stmt->bindParam(1, $subject_id);
			$stmt->bindParam(2, $subject_name);
			$subject_id = $max_subject_id + 1;
			$subject_name = $name;
			$stmt->execute();
			$db->commit();
		} catch (PDOException $e){
			$db->rollback();
			$errArray[] =  $e->getMessage();
	}}
} else if ($action == 'delete') {

	try{
		$db->beginTransaction();
		$stmt = $db->prepare("DELETE FROM tbl_subject WHERE subject_id = '".$_POST['delete']."'");
		$stmt->execute();
		$db->commit();
	} catch (PDOException $e){
		$db->rollback();
		$errArray[] =  $e->getMessage();
	}

} else {
}

// 科目一覧を取得
try{
	$stmt = $db->prepare("SELECT subject_id,subject_name FROM tbl_subject");
	$stmt->execute();
	$subject_array = $stmt->fetchAll(PDO::FETCH_ASSOC);
}catch (PDOException $e){
	$errArray[] =  $e->getMessage();
}
$subject_list = array();
foreach ( $subject_array as $row ) {
	$subject_list[$row["subject_id"]] = $row["subject_name"];
}

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF8">
<meta name="robots" content="noindex,nofollow">
<title>事務システム</title>
<script type = "text/javascript">
function deleteProc( name ) { return window.confirm( name+"を削除します。\nよろしいですか？" ); }
</script>
<link rel="stylesheet" type="text/css" href="./script/style.css">
<script type="text/javascript" src="./script/calender.js"></script>
</head>
<body>

<div id="header">
	事務システム 
</div>


<div id="content" align="center">

<h3>科目の登録・削除</h3>

<?php
	if (count($errArray) > 0) {
		foreach( $errArray as $error) {
?>
			<font color="red"><?= $error ?></font><br><br>
<?php
		}
	}
?>

<a href="menu.php">メニューへ戻る</a><br><br>

<form method="post" name="subject_list" action="subject_edit.php">
<br>

<table>
<tr>
<td align="center">
	追加科目名：<input type="text" name="name" size="40">
	<input type="submit" name="add" value="登録">
</td>
</tr>
	</table>
<br>
<br>
<table><tr><td align="center"><th align="center">科目一覧</div></th></tr><tr>
<table border="1">
		<tr>
<th>科目</th>
<th></th>
		</tr>
	<?php
			foreach ($subject_list as $key=>$item) {
			if ($key == 0) { continue; }
	?>
		<tr>
			<td>&nbsp;<?= $item ?>&nbsp;</td>
			<td><button type="submit" name="delete" value="<?= $key ?>" onclick="return deleteProc('<?= $item ?>')">削除</button></td>
		</tr>
	<?php
			}
	?>
</table>

</form>
</div>

<div id="footer">
</div>

</body>
</html>
