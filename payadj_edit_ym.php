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
} else if (isset($_POST['selected_payadj_no'])) {
	$action = 'selected_payadj_no';
} else {
	$action = "";
}

if ($action == 'add' || $action == 'delete') {
	$payadj_array["year"] = $year;
	$payadj_array["month"] = $month;
	$payadj_array["payadj_no"] = trim($_POST["payadj_no"]);
	$payadj_array["employee_type"] = trim($_POST["employee_type"]);
	$payadj_array["employee_no"] = trim($_POST["employee_no"]);
	$payadj_array["name"] = trim($_POST["name"]);
	$payadj_array["lesson_id"] = array_search(trim($_POST["lesson_id"]), $lesson_list);
	$payadj_array["tax_flag"]  = trim($_POST["tax_flag"]);
	$payadj_array["price"] = trim($_POST["price"]);
	$payadj_array["memo"] = trim($_POST["memo"]);
}


if ($action == 'add') {
// 更新処理

	// 入力チェック処理
//	$result = check_payadj_list($errArray, $payadj_array);
	if (count($errArray) == 0) {
	// 入力エラーがない場合
		$errFlag = 0;
		try{
			$db->beginTransaction();
			$result = edit_payadj($db, $payadj_array);
			if (!$result) $errFlag = 1;
		}catch (PDOException $e){
			$errFlag = 1;
		  $errArray[] = $e->getMessage();
		}
		if ($errFlag == 0) {
			$db->commit();
 			if ($payadj_array["payadj_no"] == ""){ 
				// 登録時、フォームを初期化
				$payadj_array = array("payadj_no"=>"", "employee_type"=>"", "employee_no"=>"", "year"=>"", "month"=>"", "name"=>"", "price"=>"", "memo"=>"");
			}
			$message = "登録できました。";
		} else {
			array_push($errArray, "登録中にエラーが発生しました。");
			$db->rollback();
		}
	}

} else if ($action == 'delete') {

//	$result = check_number($errArray, "項目No", $_POST["payadj_no"], true);
	if (count($errArray) == 0) {
	// 入力エラーがない場合
		$errFlag = 0;
		try{
			$db->beginTransaction();
			$result = delete_payadj($db, $_POST["payadj_no"]);
			if (!$result) $errFlag = 1;
		}catch (PDOException $e){
			$errFlag = 1;
		  $errArray[] = $e->getMessage();
		}
		if ($errFlag == 0) {
			$db->commit();
			$payadj_array = array("payadj_no"=>"", "employee_type"=>"", "employee_no"=>"", "year"=>"", "month"=>"", "name"=>"", "price"=>"", "memo"=>"");
			$message = "削除できました。";
		} else {
			array_push($errArray, "削除中にエラーが発生しました。");
			$db->rollback();
		}
	}

} else if ($action == 'selected_payadj_no') {
// 選択時
		$payadj_array = array("payadj_no"=>"", "employee_type"=>"", "employee_no"=>"", "year"=>"", "month"=>"", "name"=>"", "price"=>"", "memo"=>"");
		$payadj_list = get_payadj_list($db, array("tbl_payadj.payadj_no = ?"), array($_POST["selected_payadj_no"]));
		if (count($payadj_list) == 1) {
			$payadj_array = $payadj_list[0];
		}
} else {
// 初期表示処理
		$payadj_array = array("payadj_no"=>"", "employee_type"=>"", "employee_no"=>"", "year"=>"", "month"=>"", "name"=>"", "price"=>"", "memo"=>"");
}

$param_array = array("tbl_payadj.year = ?","tbl_payadj.month = ?");
$value_array = array($year, $month);
$order_array = array("tbl_payadj.payadj_no");
$payadj_list = get_payadj_list($db, $param_array, $value_array, $order_array);

try {
	$sql = "SELECT p.* FROM tbl_payadj as p ".
			"LEFT OUTER JOIN tbl_teacher as t ON p.employee_type=".TEACHER." AND employee_no=t.no ".
			"LEFT OUTER JOIN tbl_staff   as s ON p.employee_type=".STAFF.  " AND employee_no=s.no ".
			"WHERE p.year = ? AND p.month = ? ".
			"ORDER BY s.furigana, t.furigana";
	$stmt = $db->prepare($sql);
	$stmt->execute(array($year, $month));
	$payadj_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e){
	$errFlag = 1;
	$errArray[] = $e->getMessage();
}
		
function edit_payadj(&$db, $payadj_array) {
	$errFlag = 0;
		try {
				if ($payadj_array["payadj_no"] && $payadj_array["payadj_no"] > 0) {
				// 更新時
					$result = update_payadj($db, $payadj_array);
					if (!$result) {
						$errFlag = 1;
					}
				} else {
		    // 新規登録時
					$result = insert_payadj($db, $payadj_array);
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
function insert_payadj(&$db, $payadj_array) {
	$errFlag = 0;
	try{
		$sql = "INSERT INTO tbl_payadj (employee_type, employee_no, year, month, name, tax_flag, price, memo, insert_timestamp, update_timestamp".
					" ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, now(), now())";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(1, $employee_type);
		$stmt->bindParam(2, $employee_no);
		$stmt->bindParam(3, $year);
		$stmt->bindParam(4, $month);
		$stmt->bindParam(5, $name);
		$stmt->bindParam(6, $tax_flag);
		$stmt->bindParam(7, $price);
		$stmt->bindParam(8, $memo);
		$employee_type = $payadj_array["employee_type"];
		$employee_no  = $payadj_array["employee_no"];
		$year     = $payadj_array["year"];
		$month    = $payadj_array["month"];
		$name     = $payadj_array["name"];
		$tax_flag = $payadj_array["tax_flag"];
		$price    = $payadj_array["price"];
		$memo     = $payadj_array["memo"];
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
function update_payadj(&$db, $payadj_array) {
	$errFlag = 0;
	try{
		$sql = "UPDATE tbl_payadj SET employee_type=?, employee_no=?, year=?, month=?, name=?, tax_flag=?, price=?, memo=?, lesson_id=?, update_timestamp=now() ".
					 "WHERE tbl_payadj.payadj_no = ?";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(1, $employee_type);
		$stmt->bindParam(2, $employee_no);
		$stmt->bindParam(3, $year);
		$stmt->bindParam(4, $month);
		$stmt->bindParam(5, $name);
		$stmt->bindParam(6, $tax_flag);
		$stmt->bindParam(7, $price);
		$stmt->bindParam(8, $memo);
		$stmt->bindParam(9, $lesson_id);
		$stmt->bindParam(10, $no);
		$employee_type = $payadj_array["employee_type"];
		$employee_no   = $payadj_array["employee_no"];
		$year  = $payadj_array["year"];
		$month = $payadj_array["month"];
		$name  = $payadj_array["name"];
		$tax_flag = $payadj_array["tax_flag"];
		$price = $payadj_array["price"];
		$memo  = $payadj_array["memo"];
		$lesson_id = $payadj_array["lesson_id"];
		$no    = $payadj_array["payadj_no"];
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

function delete_payadj(&$db, $payadj_no) {
	$errFlag = 0;
	try{
		$sql = "DELETE FROM tbl_payadj ".
					 "WHERE tbl_payadj.payadj_no = ?";
		$stmt = $db->prepare($sql);
		$stmt->execute(array($payadj_no));
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
function delete_payadj() {
	result = window.confirm("フォームに表示中の情報を削除します。\nよろしいですか");
	if (result) {
		document.forms["payadj_form"].submit();
	} else {
		return false;
	}
}

function selected_payadj(payadj_no) {
		document.forms["payadj_form"].selected_payadj_no.value=payadj_no;
		document.forms["payadj_form"].submit();
}

function reset_form() {
		//document.forms["payadj_form"].year.value="";
		//document.forms["payadj_form"].month.value="";
		document.forms["payadj_form"].employee_type.value="";
		document.forms["payadj_form"].employee_no.value="";
		document.forms["payadj_form"].payadj_no.value="";
		document.forms["payadj_form"].name.value="";
		document.forms["payadj_form"].price.value="";
		document.forms["payadj_form"].memo.value="";
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

<h3>年月別一覧 - 給与調整金</h3>
<table>
<td><a href='payadj_edit_ym.php?y=<?= $y1 ?>&m=<?= $m1 ?>'>先月</a></td>
<td align="center" width="400"><a href="menu.php">メニューへ戻る</a></td>
<td><a href='payadj_edit_ym.php?y=<?= $y2 ?>&m=<?= $m2 ?>'>翌月</a></td>
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

<form method="post" name="payadj_form" action="payadj_edit_ym.php">

<?php
$teacher_list = get_teacher_list($db,null,null,null,2);
$staff_list   = get_staff_list  ($db,null,null,null,2);
$employee_type= $payadj_array["employee_type"];
// 20150731暫定対応
	if ($payadj_array["employee_no"]) {
		if ($employee_type == TEACHER) {
			$employee = $teacher_list[$payadj_array["employee_no"]];
		} else
		if ($employee_type == STAFF) {
			$employee = $staff_list[$payadj_array["employee_no"]];
		}
?>

	<table border="1" id="payadj_table">
	<tr>
		<th width="100">名前</th>
		<th width="200"><font color="red">*</font>項目名</th>
		<?php if ($employee_type == TEACHER)	echo '<th><font color="red">*</font>教室</th>'; ?>
		<th><font color="red">*</font>種別</th>
		<th width="80"><font color="red">*</font>金額</th>
		<th width="550">備考</th>
	</tr>
	<tr>
		<td align="center">
			<input type="hidden" name="employee_type" value="<?=$payadj_array["employee_type"]?>">
			<input type="hidden" name="employee_no" value="<?=$payadj_array["employee_no"]?>">
			<?=$employee["name"]?>
		</td>
		<td align="center">
			<input type="hidden" name="payadj_no" value="<?=$payadj_array["payadj_no"]?>">
			<input type="text" name="name" size="40" value="<?=$payadj_array["name"]?>">
		</td>
		<?php
			if ($employee_type == TEACHER) {
				echo "<td>";
				disp_pulldown_menu1($lesson_list, "lesson_id", $lesson_list[$payadj_array["lesson_id"]]);
				echo "</td>";
			}
		?>
		<td align="center">
		<select name="tax_flag">
		<option value="1" <?= ($payadj_array["tax_flag"])?"selected":"" ?>>給料</option>
		<option value="0" <?= ($payadj_array["tax_flag"])?"":"selected" ?>>交通費</option>
		</select>
		</td>
		<td align="right">
			<input type="text" name="price" size="10" value="<?=$payadj_array["price"]?>">
		</td>
		<td align="left">
			<input type="text" name="memo" size="80" value="<?=$payadj_array["memo"]?>">
		</td>
	</tr>
</table>
<table>
	<tr>
  <td align="center">
		<input type="submit" name="add" value="登録">
		<input type="reset" value="リセット">
<?php if ($payadj_array["payadj_no"]) { ?>
		<input type="submit" name="delete" value="削除" onclick="delete_payadj()">
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
<input type="hidden" name="selected_payadj_no" value="">
</form>

<?php
if (count($payadj_list) > 0) {
?>
<br>
<h3>講師</h3>
<table border="1">
	<tr>
		<th>名前</th><th>項目名</th><th>教室</th><th>種別</th><th>金額</th><th>備考</th><th>&nbsp;</th>
	</tr>
 	<?php
	foreach ($payadj_list as $item) {
		if ($item["employee_type"] != TEACHER) { continue; }
		$employee = $teacher_list[$item["employee_no"]];
	?>
	<tr>
		<td align="center" width="100"><input type="hidden" name="payadj_no[]" value="<?=$item["payadj_no"]?>">
			<?=$employee["name"]?></td>
		<td align="left" width="200"><?=$item["name"]?></td>
		<td align="center" width="80"><?=$lesson_list[$item["lesson_id"]]?></td>
		<td align="center"><?= $item["tax_flag"]?"給料":"交通費" ?></td>
		<td align="right" width="80"><?=number_format($item["price"])?> 円</td>
		<td align="left" width="550"><?=$item["memo"]?></td>
		<td align="center" width="60">
			<?php if ($item["payadj_no"]) { ?>
				<input type="button" value="選択" onclick="selected_payadj(<?=$item["payadj_no"]?>)">
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
		<th>名前</th><th>項目名</th><th>種別</th><th>金額</th><th>備考</th><th>&nbsp;</th>
	</tr>
 	<?php
	foreach ($payadj_list as $item) {
		if ($item["employee_type"] != STAFF) { continue; }
		$employee = $staff_list[$item["employee_no"]];
	?>
	<tr>
		<td align="center" width="100"><input type="hidden" name="payadj_no[]" value="<?=$item["payadj_no"]?>">
			<?=$employee["name"]?></td>
		<td align="left" width="200"><?=$item["name"]?></td>
		<td align="center"><?= $item["tax_flag"]?"給料":"交通費" ?></td>
		<td align="right" width="80"><?=number_format($item["price"])?> 円</td>
		<td align="left" width="550"><?=$item["memo"]?></td>
		<td align="center" width="60">
			<?php if ($item["payadj_no"]) { ?>
				<input type="button" value="選択" onclick="selected_payadj(<?=$item["payadj_no"]?>)">
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
