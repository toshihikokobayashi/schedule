<?php
ini_set( 'display_errors', 0 );
require_once("./const/const.inc");
require_once("./func.inc");
require_once("./const/login_func.inc");
$result = check_user($db, "1");

$errArray = array();

if ($_POST["search_name"]) {
	$search_name = trim($_POST["search_name"]);
}

if ($search_name) {
	$whereParam = "WHERE tbl_furikomisha.furikomisha_name like concat('%',?,'%') ";
}

// 振込者一覧を取得
try{
	$stmt = $db->prepare("SELECT no,member_name,furikomisha_name FROM tbl_furikomisha ".$whereParam."ORDER BY tbl_furikomisha.furikomisha_name asc");
	$stmt->execute(array($search_name));
	$furikomisha_array = $stmt->fetchAll(PDO::FETCH_ASSOC);
}catch (PDOException $e){
	$errArray[] =  $e->getMessage();
}
$furikomisha_list = array();
foreach ( $furikomisha_array as $row ) {
	$furikomisha_list[$row["furikomisha_name"]] = $row;
}

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF8">
<meta name="robots" content="noindex,nofollow">
<title>事務システム</title>
<script type = "text/javascript">

function edit_furikomisha(no) {
	document.forms["furikomisha_list"].elements["no"].value = no;
	document.forms["furikomisha_list"].action = "furikomisha_edit.php";
	document.forms["furikomisha_list"].submit();
}

function search_clear() {
	document.forms["furikomisha_list"].elements["search_name"].value = "";
	document.forms["furikomisha_list"].submit();
}

</script>
<link rel="stylesheet" type="text/css" href="./script/style.css">
<script type="text/javascript" src="./script/calender.js"></script>
</head>
<body>

<div id="header">
	事務システム 
</div>


<div id="content" align="center">

<h3>振込者の登録 - 振込者一覧</h3>

<?php
	if (count($errArray) > 0) {
		foreach( $errArray as $error) {
?>
			<font color="red"><?= $error ?></font><br><br>
<?php
		}
	}
?>

<a href="furikomisha_add.php">新規登録へ</a>&nbsp;&nbsp;
<a href="menu.php">メニューへ戻る</a><br><br>

<form method="post" name="furikomisha_list" action="furikomisha_list.php">
	<input type="hidden" name="no" value="">	<?php /* 編集ボタンクリック時にfurikomisha_noをセットする */ ?>

<table border="1">
	<tr>
		<th>振込者名</th>
		<td><input type="text" name="search_name" value="<?= $search_name; ?>">※氏名の部分一致で検索します</td>
	</tr>
	<tr>
		<td colspan="4" align="center">
			<input type="submit" value="検索">&nbsp;&nbsp;
			<input type="button" value="検索解除" onclick="search_clear()">
		</td>
	</tr>
</table>

<br>

<table border="1">
		<tr>
<th>振込者名</th>
<th>生徒名</th>
<th>編集</th>
		</tr>
	<?php
			mb_regex_encoding("UTF-8");
			foreach ($furikomisha_list as $key=>$item) {
	?>
		<tr>
			<td><?= $key ?></td>
			<td align="center">
			<?php
			$array = mb_split(':',$item['member_name']);
			foreach( $array as $member_name) {
					echo $member_name."<br>";
			}
			?>
			</td>
			<td align="center">
			<input type="button" value="編集" onclick="edit_furikomisha('<?= $item['no']?>')" target="_blank"> <?php /* ボタンにはnameを付けないこと。jsに渡すnoは'で囲むことStringとして渡すため*/ ?>
			</td>
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
