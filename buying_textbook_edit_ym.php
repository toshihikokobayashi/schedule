<?php
ini_set( 'display_errors', 0 );
require_once("./const/const.inc");
require_once("./func.inc");
require_once("./const/login_func.inc");
$result = check_user($db, "1");

$errArray = array();
$message = "";

$kind_array = get_kind_array("1");

//$student_no = trim($_POST["no"]);
$year = trim($_POST["y"]);
$month = trim($_POST["m"]);

if (isset($_POST['add'])) {
	$action = 'add';
} else if (isset($_POST['delete'])) {
	$action = 'delete';
} else if (isset($_POST['selected_buying_no'])) {
	$action = 'selected_buying_no';
} else {
	$action = "";
}

if ($action == 'add' || $action == 'delete') {
	//$student_no = trim($_POST["no"]);
	//$year = trim($_POST["y"]);
	//$month = trim($_POST["m"]);
	$buying_array["year"] = $year;
	$buying_array["month"] = $month;
	$buying_array["buying_no"] = trim($_POST["buying_no"]);
	$buying_array["member_no"] = trim($_POST["member_no"]);
	$buying_array["input_year"] = trim($_POST["input_year"]);
	$buying_array["input_month"] = trim($_POST["input_month"]);
	$buying_array["input_day"] = trim($_POST["input_day"]);
	$buying_array["name"] = trim($_POST["name"]);
	$buying_array["price"] = trim($_POST["price"]);
	$buying_array["lesson_id"] = trim($_POST["lesson_id"]);
	$buying_array["kind"] = trim($_POST["kind"]);
}
/*
$result1 = check_number($errArray, "生徒No", $student_no, true);
//$result2 = check_number($errArray, "月", $year, true);
//$result3 = check_number($errArray, "日", $month, true);
//if ($result1 === false || $result2 === false  || $result3 === false) {
if ($result1 === false) {
	header('location: menu.php');
}
*/
/*
// メンバー情報を取得
$member = null;
if ($student_no != "") {
	$member = get_member($db, array("tbl_member.no = ?"), array($student_no));
}
if ($member == null) {
	header('location: menu.php');
}
*/

if ($action == 'add') {
// 更新処理

	// 入力チェック処理
	$result = check_buying_textbook_list($errArray, $buying_array);
	if (count($errArray) == 0) {
	// 入力エラーがない場合
		$errFlag = 0;
		try{
			$db->beginTransaction();
			$result = edit_buying_textbook($db, $buying_array, $errArray);
			if (!$result) $errFlag = 1;
		}catch (PDOException $e){
			$errFlag = 1;
		  //print('Error:'.$e->getMessage());
		}
		if ($errFlag == 0) {
			$db->commit();
 			if ($buying_array["buying_no"] == ""){ 
				// 登録時、フォームを初期化
				//$buying_array = array("buying_no"=>"", "year"=>"", "month"=>"", "day"=>"", "name"=>"", "price"=>"");
				$buying_array = array("buying_no"=>"", "member_no"=>"", "input_year"=>"", "input_month"=>"", "input_day"=>"", "name"=>"", "price"=>"", "lesson_id"=>"", "kind"=>"");
			}
			$message = "登録できました。";
			//header("Location: buying_textbook_edit.php?no=".$student_no."&year=".$year."&month=".$month."&f");
			//exit;
		} else {
			array_push($errArray, "登録中にエラーが発生しました。");
			$db->rollback();
		}
	}

	// エラーが発生した場合、編集画面を再表示する
	// 再表示時に、料金の新規追加行を追加表示する
		//array_push($buying_textbook_list, array("buying_no"=>"", "year"=>"", "month"=>"", "day"=>"", "name"=>"", "price"=>""));

} else if ($action == 'delete') {

	$result = check_number($errArray, "購入No", $_POST["buying_no"], true);
	if (count($errArray) == 0) {
	// 入力エラーがない場合
		$errFlag = 0;
		try{
			$db->beginTransaction();
			$result = delete_buying_textbook($db, $_POST["buying_no"]);
			if (!$result) $errFlag = 1;
		}catch (PDOException $e){
			$errFlag = 1;
		  //print('Error:'.$e->getMessage());
		}
		if ($errFlag == 0) {
			$db->commit();
 			//if ($buying_array["buying_no"] == ""){ 
				// 登録時、フォームを初期化
				//$buying_array = array("buying_no"=>"", "year"=>"", "month"=>"", "day"=>"", "name"=>"", "price"=>"");
				$buying_array = array("buying_no"=>"", "member_no"=>"", "input_year"=>"", "input_month"=>"", "input_day"=>"", "name"=>"", "price"=>"", "lesson_id"=>"", "kind"=>"");
			//}
			$message = "削除できました。";
		} else {
			array_push($errArray, "削除中にエラーが発生しました。");
			$db->rollback();
		}
	}

} else if ($action == 'selected_buying_no') {
// 選択時
		//$buying_array = array("buying_no"=>"", "year"=>"", "month"=>"", "day"=>"", "name"=>"", "price"=>"");
		$buying_array = array("buying_no"=>"", "member_no"=>"", "input_year"=>"", "input_month"=>"", "input_day"=>"", "name"=>"", "price"=>"", "lesson_id"=>"", "kind"=>"");
		$buying_list = get_buying_textbook_list($db, array("tbl_buying_textbook.buying_no = ?"), array($_POST["selected_buying_no"]));
		if (count($buying_list) == 1) {
			$buying_array = $buying_list[0];
		}
} else {
// 初期表示処理
		//$buying_array = array("buying_no"=>"", "year"=>"", "month"=>"", "day"=>"", "name"=>"", "price"=>"");
		$buying_array = array("buying_no"=>"", "member_no"=>"", "input_year"=>"", "input_month"=>"", "input_day"=>"", "name"=>"", "price"=>"", "lesson_id"=>"", "kind"=>"");
}

if ($buying_array["member_no"]) {
	$member = get_member($db, array("tbl_member.no = ?"), array($buying_array["member_no"]));
}

// 一覧表示
//$param_array = array("tbl_buying_textbook.member_no = ?",
//												"tbl_buying_textbook.year = ?",
//												"tbl_buying_textbook.month = ?");
//$value_array = array($student_no, $year, $month);
//$order_array = array("tbl_buying_textbook.day");
//20150731変更
//$param_array = array("tbl_buying_textbook.member_no = ?");
//$value_array = array($student_no);
//$order_array = array("tbl_buying_textbook.year", "tbl_buying_textbook.month", "tbl_buying_textbook.day");
//$buying_textbook_list = get_buying_textbook_list($db, $param_array, $value_array, $order_array);
$param_array = array("tbl_buying_textbook.year = ?","tbl_buying_textbook.month = ?");
$value_array = array($year, $month);
//$order_array = array("tbl_buying_textbook.input_year", "tbl_buying_textbook.input_month", "tbl_buying_textbook.input_day");
$order_array = array("tbl_buying_textbook.input_year", "tbl_buying_textbook.input_month", "tbl_buying_textbook.input_day", "tbl_buying_textbook.buying_no");
$buying_textbook_list = get_buying_textbook_list($db, $param_array, $value_array, $order_array);
$member_list = get_member_list($db);

function edit_buying_textbook(&$db, $buying_array, &$errArray) {
	$errFlag = 0;
		try {
				if ($buying_array["buying_no"] && $buying_array["buying_no"] > 0) {
				// 更新時
					$result = update_buying_textbook($db, $buying_array);
					if (!$result) {
						$errFlag = 1;
					}
				} else {
		    // 新規登録時
					$result = insert_buying_textbook($db, $buying_array);
					if (!$result) {
						$errFlag = 1;
					}
				}
		}catch (PDOException $e){
			$errFlag = 1;
		  //print('Error:'.$e->getMessage());
		}
	if ($errFlag == 0) {
		return true;
	} else {
		return false;
	}

}

// 管理画面の生徒管理からテキストブック名の変更があるかもしれないので、引数に年月は入れない
function insert_buying_textbook(&$db, $buying_array) {
	$errFlag = 0;
	try{
		$sql = "INSERT INTO tbl_buying_textbook (member_no, year, month, input_year, input_month, input_day, name, price, kind, lesson_id, insert_timestamp, update_timestamp".
					" ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, now(), now())";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(1, $member_no);
		$stmt->bindParam(2, $year);
		$stmt->bindParam(3, $month);
		$stmt->bindParam(4, $input_year);
		$stmt->bindParam(5, $input_month);
		$stmt->bindParam(6, $input_day);
		$stmt->bindParam(7, $name);
		$stmt->bindParam(8, $price);
		$stmt->bindParam(9, $kind);
		$stmt->bindParam(10, $lesson_id);
		$member_no = $buying_array["member_no"];
		$year = $buying_array["year"];
		$month = $buying_array["month"];
		$year = $buying_array["input_year"];
		$month = $buying_array["input_month"];
		$day = $buying_array["input_day"];
		$name = $buying_array["name"];
		$price = $buying_array["price"];
		$kind = $buying_array["kind"];
		$lesson_id = $buying_array["lesson_id"];
		$stmt->execute();
	}catch (PDOException $e){
		$errFlag = 1;
	  //print('Error:'.$e->getMessage());
		throw $e;
	}
	if ($errFlag == 0) {
		return true;
	} else {
		return false;
	}
}

// 管理画面の生徒管理からテキストブック名の変更があるかもしれないので、引数に年月は入れない
function update_buying_textbook(&$db, $buying_array) {
	$errFlag = 0;
	try{
		$sql = "UPDATE tbl_buying_textbook SET member_no=?, year=?, month=?, input_year=?, input_month=?, input_day=?, name=?, price=?, kind=?, lesson_id=?, update_timestamp=now() ".
					 "WHERE tbl_buying_textbook.buying_no = ?";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(1, $member_no);
		$stmt->bindParam(2, $year);
		$stmt->bindParam(3, $month);
		$stmt->bindParam(4, $input_year);
		$stmt->bindParam(5, $input_month);
		$stmt->bindParam(6, $input_day);
		$stmt->bindParam(7, $name);
		$stmt->bindParam(8, $price);
		$stmt->bindParam(9, $kind);
		$stmt->bindParam(10, $lesson_id);
		$stmt->bindParam(11, $buying_no);
		$member_no = $buying_array["member_no"];
		$year = $buying_array["year"];
		$month = $buying_array["month"];
		$input_year = $buying_array["input_year"];
		$input_month = $buying_array["input_month"];
		$input_day = $buying_array["input_day"];
		$name = $buying_array["name"];
		$price = $buying_array["price"];
		$kind = $buying_array["kind"];
		$lesson_id = $buying_array["lesson_id"];
		$buying_no = $buying_array["buying_no"];
		$stmt->execute();
	}catch (PDOException $e){
		$errFlag = 1;
		throw $e;
	  //print('Error:'.$e->getMessage());
	}
	if ($errFlag == 0) {
		return true;
	} else {
		return false;
	}
}

function delete_buying_textbook(&$db, $buying_no) {
	$errFlag = 0;
	try{
		$sql = "DELETE FROM tbl_buying_textbook ".
					 "WHERE tbl_buying_textbook.buying_no = ?";
		$stmt = $db->prepare($sql);
		$stmt->execute(array($buying_no));
	}catch (PDOException $e){
		$errFlag = 1;
		//throw $e;
	  //print('Error:'.$e->getMessage());
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
<title>事務システム</title>
<script type = "text/javascript">
<!--
function delete_buying() {
	result = window.confirm("テキスト購入情報を削除します。\nよろしいですか");
	if (result) {
		document.forms["buying_textbook_form"].submit();
	} else {
		return false;
	}
}

function selected_buying(buying_no) {
		document.forms["buying_textbook_form"].selected_buying_no.value=buying_no;
		document.forms["buying_textbook_form"].submit();
}

function reset_form() {
		document.forms["buying_textbook_form"].buying_no.value="";
		document.forms["buying_textbook_form"].member_no.value="";
		document.forms["buying_textbook_form"].input_year.value="";
		document.forms["buying_textbook_form"].input_month.value="";
		document.forms["buying_textbook_form"].input_day.value="";
		document.forms["buying_textbook_form"].price.value="";
		document.forms["buying_textbook_form"].name.value="";
		document.forms["buying_textbook_form"].lesson_id.value="";
		document.forms["buying_textbook_form"].kindame.value="";
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

<!--
<h3>生徒の登録 - テキスト代</h3>
<a href="student_fee_list.php">生徒の登録 - 生徒一覧へ</a>&nbsp;&nbsp;
-->
<h3>テキスト代</h3>
<a href="menu.php">メニューへ戻る</a><br><br>

<?php
	if (count($errArray) > 0) {
		foreach( $errArray as $error) {
?>
			<font color="red" size="3"><?= $error ?></font><br>
<?php
		}
?>
<!--	<br>-->
<?php
	}
?>
<?php
	if ($message) {
?>
			<font color="blue" size="3"><?= $message ?></font><br>
<?php
	}
?>


<h3><?=$year?>年<?=$month?>月</h3>
<form method="post" name="buying_textbook_form" action="buying_textbook_edit_ym.php">
	<!--<h3><?=$member["name"]?>様</h3>-->
<?php
// 20150731暫定対応
if ($buying_array["member_no"]) {
?>

	<table border="1" id="buying_textbook_table">
	<tr>
		<th colspan="3"><font color="red">*</font>記入日</th>
		<th>生徒氏名</th>
		<th><font color="red">*</font>テキストブック名</th>
		<th><font color="red">*</font>金額</th>
		<th><font color="red">*</font>教室</th>
		<th><font color="red">*</font>種類</th>
	</tr>
	<tr>
		<td align="center">
			<input type="text" name="input_year" size="4" value="<?=$buying_array["input_year"]?>">年
		</td>
		<td align="center">
			<input type="text" name="input_month" size="4" value="<?=$buying_array["input_month"]?>">月
		</td>
		<td align="center">
			<input type="text" name="input_day" size="4" value="<?=$buying_array["input_day"]?>">日
		</td>
		<td align="center">
			<input type="hidden" name="member_no" value="<?=$buying_array["member_no"]?>">
			<?=$member["name"]?>
		</td>
		<td align="center">
			<input type="hidden" name="buying_no" value="<?=$buying_array["buying_no"]?>">
			<input type="text" name="name" size="50" value="<?=$buying_array["name"]?>">
		</td>
		<td align="right">
			<input type="text" name="price" size="20" value="<?=$buying_array["price"]?>">
		</td>
		<td align="center">
			<?php disp_lesson_menu($lesson_list, "lesson_id", $buying_array["lesson_id"]) ?>
		</td>
		<td align="center">
			<?php disp_kind_menu($kind_list, "kind", "1", $buying_array["kind"]) ?>
		</td>
	</tr>
</table>
<table>
	<tr>
  <td align="center">
		<input type="submit" name="add" value="登録">
		<input type="reset" value="リセット">
<?php if ($buying_array["buying_no"]) { ?>
		<input type="submit" name="delete" value="削除" onclick="delete_buying()">
<?php } ?>
<?php /*20150731暫定対応*/ ?>
<!--
		<a href="#" onclick="javascript:reset_form();">新規登録</a>
-->
	</td>
	</tr>
</table>
<?php
// 20150731暫定対応
}
?>

<input type="hidden" name="y" value="<?=$year?>">
<input type="hidden" name="m" value="<?=$month?>">
<input type="hidden" name="selected_buying_no" value="">
</form>

<?php
if (count($buying_textbook_list) > 0) {
?>
<!--<hr>-->
<br>
<table border="1">
	<tr>
		<th>記入日</th><th>生徒氏名</th><th>テキストブック名</th><th>金額</th><th>教室</th><th>種類</th><th>&nbsp;</th>
	</tr>
 	<?php
	foreach ($buying_textbook_list as $item) {
	?>
	<tr>
		<td align="center" width="150"><input type="hidden" name="buying_no[]" value="<?=$item["buying_no"]?>">
			<?=$item["input_year"]?>年<?=$item["input_month"]?>月<?=$item["input_day"]?>日</td>
		<td align="left" width="150"><?=$member_list[$item["member_no"]]["name"]?></td>
		<td align="left" width="500"><?=$item["name"]?></td>
		<td align="right" width="80"><?=number_format($item["price"])?> 円</td>
		<td align="center" width="80"><?=$lesson_list[$item["lesson_id"]]?></td>
		<td align="center" width="80"><?=$kind_array[$item["kind"]]?></td>
		<td align="center" width="60">
			<?php if ($item["buying_no"]) { ?>
				<input type="button" value="選択" onclick="selected_buying(<?=$item["buying_no"]?>)">
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
