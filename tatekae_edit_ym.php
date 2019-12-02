<?php
ini_set( 'display_errors', 0 );
require_once("./const/const.inc");
require_once("./func.inc");
require_once("./const/login_func.inc");
$result = check_user($db, "1");

$errArray = array();
$message = "";

$kind_array = get_kind_array("0");

$year = trim($_POST["y"]);
$month = trim($_POST["m"]);
if (!$year) { $year = $_GET["y"]; }
if (!$month) { $month = $_GET["m"]; }

$y1=$year; $m1=$month-1; if ($m1<1)  { $y1--; $m1=12; }
$y2=$year; $m2=$month+1; if ($m2>12) { $y2++; $m2=1; }

if (isset($_POST['add'])) {
	$action = 'add';
} else if (isset($_POST['delete'])) {
	$action = 'delete';
} else if (isset($_POST['selected_tatekae_no'])) {
	$action = 'selected_tatekae_no';
} else {
	$action = "";
}

if ($action == 'add' || $action == 'delete') {
	$tatekae_array["year"] = $year;
	$tatekae_array["month"] = $month;
	$tatekae_array["tatekae_no"] = trim($_POST["tatekae_no"]);
	$tatekae_array["employee_type"] = trim($_POST["employee_type"]);
	$tatekae_array["employee_no"] = trim($_POST["employee_no"]);
	$tatekae_array["name"] = trim($_POST["name"]);
	$tatekae_array["lesson_id"]  = trim($_POST["lesson_id"]);
	$tatekae_array["price"] = trim($_POST["price"]);
	$tatekae_array["memo"] = trim($_POST["memo"]);
	$tatekae_array["status"]    = trim($_POST["status"]);
}


if ($action == 'add') {
// 更新処理

	// 入力チェック処理
//	$result = check_tatekae_list($errArray, $tatekae_array);
	if (count($errArray) == 0) {
	// 入力エラーがない場合
		$errFlag = 0;
		try{
			$db->beginTransaction();
			$result = edit_tatekae($db, $tatekae_array);
			if (!$result) $errFlag = 1;
		}catch (PDOException $e){
			$errFlag = 1;
		  $errArray[] = $e->getMessage();
		}
		if ($errFlag == 0) {
			$db->commit();
 			if ($tatekae_array["tatekae_no"] == ""){ 
				// 登録時、フォームを初期化
				$tatekae_array = array("tatekae_no"=>"", "employee_type"=>"", "employee_no"=>"", "year"=>"", "month"=>"", "name"=>"", "price"=>"", "memo"=>"");
			}
			$message = "登録できました。";
		} else {
			array_push($errArray, "登録中にエラーが発生しました。");
			$db->rollback();
		}
	}

} else if ($action == 'delete') {

//	$result = check_number($errArray, "項目No", $_POST["tatekae_no"], true);
	if (count($errArray) == 0) {
	// 入力エラーがない場合
		$errFlag = 0;
		try{
			$db->beginTransaction();
			$result = delete_tatekae($db, $_POST["tatekae_no"]);
			if (!$result) $errFlag = 1;
		}catch (PDOException $e){
			$errFlag = 1;
		  $errArray[] = $e->getMessage();
		}
		if ($errFlag == 0) {
			$db->commit();
			$tatekae_array = array("tatekae_no"=>"", "employee_type"=>"", "employee_no"=>"", "year"=>"", "month"=>"", "name"=>"", "price"=>"", "memo"=>"");
			$message = "削除できました。";
		} else {
			array_push($errArray, "削除中にエラーが発生しました。");
			$db->rollback();
		}
	}

} else if ($action == 'selected_tatekae_no') {
// 選択時
		$tatekae_array = array("tatekae_no"=>"", "employee_type"=>"", "employee_no"=>"", "year"=>"", "month"=>"", "name"=>"", "price"=>"", "memo"=>"");
		$tatekae_list = get_tatekae_list($db, array("tbl_tatekae.tatekae_no = ?"), array($_POST["selected_tatekae_no"]));
		if (count($tatekae_list) == 1) {
			$tatekae_array = $tatekae_list[0];
		}
} else {
// 初期表示処理
		$tatekae_array = array("tatekae_no"=>"", "employee_type"=>"", "employee_no"=>"", "year"=>"", "month"=>"", "name"=>"", "price"=>"", "memo"=>"");
}

$param_array = array("tbl_tatekae.year = ?","tbl_tatekae.month = ?");
$value_array = array($year, $month);
$order_array = array("tbl_tatekae.tatekae_no");
$tatekae_list = get_tatekae_list($db, $param_array, $value_array, $order_array);

try {
	$sql = "SELECT p.* FROM tbl_tatekae as p ".
			"LEFT OUTER JOIN tbl_teacher as t ON p.employee_type=".TEACHER." AND employee_no=t.no ".
			"LEFT OUTER JOIN tbl_staff   as s ON p.employee_type=".STAFF.  " AND employee_no=s.no ".
			"WHERE p.year = ? AND p.month = ? ".
			"ORDER BY s.furigana, t.furigana";
	$stmt = $db->prepare($sql);
	$stmt->execute(array($year, $month));
	$tatekae_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e){
	$errFlag = 1;
	$errArray[] = $e->getMessage();
}
		
function edit_tatekae(&$db, $tatekae_array) {
	$errFlag = 0;
		try {
				if ($tatekae_array["tatekae_no"] && $tatekae_array["tatekae_no"] > 0) {
				// 更新時
					$result = update_tatekae($db, $tatekae_array);
					if (!$result) {
						$errFlag = 1;
					}
				} else {
		    // 新規登録時
					$result = insert_tatekae($db, $tatekae_array);
					if (!$result) {
						$errFlag = 1;
					}
				}
		}catch (PDOException $e){
			$errFlag = 1;
		  throw $e;
		}
	if ($errFlag == 0) {
		return true;
	} else {
		return false;
	}

}

// 管理画面の生徒管理からテキストブック名の変更があるかもしれないので、引数に年月は入れない
function insert_tatekae(&$db, $tatekae_array) {
	$errFlag = 0;
	try{
		$sql = "INSERT INTO tbl_tatekae (employee_type, employee_no, year, month, name, lesson_id, price, memo, status, insert_timestamp, update_timestamp".
					" ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, now(), now())";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(1, $employee_type);
		$stmt->bindParam(2, $employee_no);
		$stmt->bindParam(3, $year);
		$stmt->bindParam(4, $month);
		$stmt->bindParam(5, $name);
		$stmt->bindParam(6, $lesson_id);
		$stmt->bindParam(7, $price);
		$stmt->bindParam(8, $memo);
		$stmt->bindParam(9, $status);
		$employee_type = $tatekae_array["employee_type"];
		$employee_no  = $tatekae_array["employee_no"];
		$year     = $tatekae_array["year"];
		$month    = $tatekae_array["month"];
		$name     = $tatekae_array["name"];
		$lesson_id = $tatekae_array["lesson_id"];
		$price    = $tatekae_array["price"];
		$memo     = $tatekae_array["memo"];
		$status   = $tatekae_array["status"];
		$stmt->execute();
	}catch (PDOException $e){
		$errFlag = 1;
		throw $e;
	}
	if ($errFlag == 0) {
		return true;
	} else {
		return false;
	}
}

// 管理画面の生徒管理から項目名の変更があるかもしれないので、引数に年月は入れない
function update_tatekae(&$db, $tatekae_array) {
	$errFlag = 0;
	try{
		$sql = "UPDATE tbl_tatekae SET employee_type=?, employee_no=?, year=?, month=?, name=?, lesson_id=?, price=?, memo=?, status=?, update_timestamp=now() ".
					 "WHERE tbl_tatekae.tatekae_no = ?";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(1, $employee_type);
		$stmt->bindParam(2, $employee_no);
		$stmt->bindParam(3, $year);
		$stmt->bindParam(4, $month);
		$stmt->bindParam(5, $name);
		$stmt->bindParam(6, $lesson_id);
		$stmt->bindParam(7, $price);
		$stmt->bindParam(8, $memo);
		$stmt->bindParam(9, $status);
		$stmt->bindParam(10, $no);
		$employee_type = $tatekae_array["employee_type"];
		$employee_no   = $tatekae_array["employee_no"];
		$year  = $tatekae_array["year"];
		$month = $tatekae_array["month"];
		$name  = $tatekae_array["name"];
		$lesson_id = $tatekae_array["lesson_id"];
		$price = $tatekae_array["price"];
		$memo  = $tatekae_array["memo"];
		$status= $tatekae_array["status"];
		$no    = $tatekae_array["tatekae_no"];
		$stmt->execute();
	}catch (PDOException $e){
		$errFlag = 1;
		throw $e;
	}
	if ($errFlag == 0) {
		return true;
	} else {
		return false;
	}
}

function delete_tatekae(&$db, $tatekae_no) {
	$errFlag = 0;
	try{
		$sql = "DELETE FROM tbl_tatekae ".
					 "WHERE tbl_tatekae.tatekae_no = ?";
		$stmt = $db->prepare($sql);
		$stmt->execute(array($tatekae_no));
	}catch (PDOException $e){
		$errFlag = 1;
		throw $e;
	}
	if ($errFlag == 0) {
		return true;
	} else {
		return false;
	}
}

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF8">
<meta name="robots" content="noindex,nofollow">
<title>事務システム</title>
<script type = "text/javascript">
<!--
function delete_tatekae() {
	result = window.confirm("フォームに表示中の情報を削除します。\nよろしいですか");
	if (result) {
		document.forms["tatekae_form"].submit();
	} else {
		return false;
	}
}

function selected_tatekae(tatekae_no) {
		document.forms["tatekae_form"].selected_tatekae_no.value=tatekae_no;
		document.forms["tatekae_form"].submit();
}

function reset_form() {
		//document.forms["tatekae_form"].year.value="";
		//document.forms["tatekae_form"].month.value="";
		document.forms["tatekae_form"].employee_type.value="";
		document.forms["tatekae_form"].employee_no.value="";
		document.forms["tatekae_form"].tatekae_no.value="";
		document.forms["tatekae_form"].name.value="";
		document.forms["tatekae_form"].price.value="";
		document.forms["tatekae_form"].memo.value="";
}


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

<h3>年月別一覧 - 立替経費</h3>
<table>
<td><a href='tatekae_edit_ym.php?y=<?= $y1 ?>&m=<?= $m1 ?>'>先月</a></td>
<td align="center" width="400"><a href="menu.php">メニューへ戻る</a></td>
<td><a href='tatekae_edit_ym.php?y=<?= $y2 ?>&m=<?= $m2 ?>'>翌月</a></td>
</table>

<?php
	if (count($errArray) > 0) {
		foreach( $errArray as $error) {
?>
			<font color="red" size="3"><?= $error ?></font><br>
<?php
		}
	}
	if ($message) {
?>
			<font color="blue" size="3"><?= $message ?></font><br>
<?php
	}
?>

　<h3><?=$year?>年<?=$month?>月</h3>

<form method="post" name="tatekae_form" action="tatekae_edit_ym.php">

<?php
$teacher_list = get_teacher_list($db,null,null,null,2);
$staff_list   = get_staff_list  ($db,null,null,null,2);

// 20150731暫定対応
	if ($tatekae_array["employee_no"]) {
		if ($tatekae_array["employee_type"] == TEACHER) {
			$employee = $teacher_list[$tatekae_array["employee_no"]];
		} else
		if ($tatekae_array["employee_type"] == STAFF) {
			$employee = $staff_list[$tatekae_array["employee_no"]];
		}
?>

	<table border="1" id="tatekae_table">
	<tr>
		<th>名前</th>
		<?php if ($tatekae_array["employee_type"] == TEACHER) { ?>
		<th><font color="red">*</font>教室</th>
		<?php } ?>
		<th><font color="red">*</font>項目名</th>
		<th><font color="red">*</font>金額（税込）</th>
		<th>備考</th><th>承認状況</th>
	</tr>
	<tr>
		<td align="center">
			<input type="hidden" name="employee_type" value="<?=$tatekae_array["employee_type"]?>">
			<input type="hidden" name="employee_no" value="<?=$tatekae_array["employee_no"]?>">
			<?=$employee["name"]?>
		</td>
		<?php
		if ($tatekae_array["employee_type"] == TEACHER) {
			echo "<td>";
			disp_lesson_menu($lesson_list, "lesson_id", $tatekae_array["lesson_id"]);
			echo "</td>";
		}
		?>
		<td align="center">
			<input type="hidden" name="tatekae_no" value="<?=$tatekae_array["tatekae_no"]?>">
			<input type="text" name="name" size="16" value="<?=$tatekae_array["name"]?>">
		</td>
		<td align="right">
			<input type="text" name="price" size="10" value="<?=$tatekae_array["price"]?>">
		</td>
		<td align="left">
			<input type="text" name="memo" size="80" value="<?=$tatekae_array["memo"]?>">
		</td>
		<td>
		<?php
			echo "<select name=\"status\">";
			echo "<option value=\"承認待ち\" ".($tatekae_array["status"]=='承認待ち'?'selected':'').">承認待ち</option>";
			echo "<option value=\"承認済\" ".($tatekae_array["status"]=='承認済'?'selected':'').">承認済</option>";
			echo "<option value=\"不可\" ".($tatekae_array["status"]=='不可'?'selected':'').">不可</option>";
			echo "</select>";
		?>
		</td>
	</tr>
</table>
<table>
	<tr>
  <td align="center">
		<input type="submit" name="add" value="登録">
		<input type="reset" value="リセット">
<?php if ($tatekae_array["tatekae_no"]) { ?>
		<input type="submit" name="delete" value="削除" onclick="delete_tatekae()">
<?php // 20150731 暫定対応 ?>
<?php } ?>
	</td>
	</tr>
</table>

<?php
// 20150731暫定対応
	}
?>

<input type="hidden" name="y" value="<?=$year?>">
<input type="hidden" name="m" value="<?=$month?>">
<input type="hidden" name="selected_tatekae_no" value="">
</form>

<?php
if (count($tatekae_list) > 0) {
?>
<br>
<h3>講師</h3>
<table border="1">
	<tr>
		<th>名前</th><th>教室</th><th>項目名</th><th>金額（税込）</th><th>備考</th><th>承認状況</th><th>&nbsp;</th>
	</tr>
 	<?php
	foreach ($tatekae_list as $item) {
		if ($item["employee_type"] != TEACHER) { continue; }
		$employee = $teacher_list[$item["employee_no"]];
	?>
	<tr>
		<td align="center" width="110"><input type="hidden" name="tatekae_no[]" value="<?=$item["tatekae_no"]?>">
			<?=$employee["name"]?></td>
		<td align="center" width="50"><?=$lesson_list[$item["lesson_id"]]?></td>
		<td align="left" width="120"><?=$item["name"]?></td>
		<td align="right" width="80"><?=number_format($item["price"])?> 円</td>
		<td align="left" width="550"><?=$item["memo"]?></td>
		<td align="left"><?=$item["status"]?></td>
		<td align="center" width="60">
			<?php if ($item["tatekae_no"]) { ?>
				<input type="button" value="選択" onclick="selected_tatekae(<?=$item["tatekae_no"]?>)">
			<?php } ?>
		</td>
	</tr>
 	<?php
	}
	?>
</table>
<br>
<h3>事務員</h3>
<table border="1">
	<tr>
		<th>名前</th><th>項目名</th><th>金額（税込）</th><th>備考</th><th>承認状況</th><th>&nbsp;</th>
	</tr>
 	<?php
	foreach ($tatekae_list as $item) {
		if ($item["employee_type"] != STAFF) { continue; }
		$employee = $staff_list[$item["employee_no"]];
	?>
	<tr>
		<td align="center" width="110"><input type="hidden" name="tatekae_no[]" value="<?=$item["tatekae_no"]?>">
			<?=$employee["name"]?></td>
		<td align="left" width="120"><?=$item["name"]?></td>
		<td align="right" width="80"><?=number_format($item["price"])?> 円</td>
		<td align="left" width="550"><?=$item["memo"]?></td>
		<td align="left"><?=$item["status"]?></td>
		<td align="center" width="60">
			<?php if ($item["tatekae_no"]) { ?>
				<input type="button" value="選択" onclick="selected_tatekae(<?=$item["tatekae_no"]?>)">
			<?php } ?>
		</td>
	</tr>
 	<?php
	}
	?>
</table>
<?php
}
?>


</div>

</body>
</html>
