<?php
ini_set( 'display_errors', 0 );
require_once("./const/const.inc");
require_once("./func.inc");
require_once("./const/login_func.inc");
$result = check_user($db, "1");

$errArray = array();
$message = "";

$kind_array = get_kind_array("0");

//$student_no = trim($_POST["no"]);
$year = trim($_POST["y"]);
$month = trim($_POST["m"]);

if (isset($_POST['add'])) {
	$action = 'add';
} else if (isset($_POST['delete'])) {
	$action = 'delete';
} else if (isset($_POST['selected_entrance_fee_no'])) {
	$action = 'selected_entrance_fee_no';
} else {
	$action = "";
}

if ($action == 'add' || $action == 'delete') {
	//$student_no = trim($_POST["no"]);
	//$year = trim($_POST["y"]);
	//$month = trim($_POST["m"]);
	$entrance_fee_array["year"] = $year;
	$entrance_fee_array["month"] = $month;
	$entrance_fee_array["entrance_fee_no"] = trim($_POST["entrance_fee_no"]);
	$entrance_fee_array["member_no"] = trim($_POST["member_no"]);
	$entrance_fee_array["price"] = trim($_POST["price"]);
	$entrance_fee_array["memo"] = trim($_POST["memo"]);

/*
	$result1 = check_number($errArray, "生徒No", $entrance_fee_array["member_no"], true);
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
}

/*
if ($action == 'selected_entrance_fee_no') {
	//$student_no = trim($_POST["no"]);
	//$year = trim($_POST["y"]);
	//$month = trim($_POST["m"]);
	$selected_entrance_fee_no = trim($_POST["selected_entrance_fee_no"]);
	$param_array = array("tbl_entrance_fee.entrance_fee_no = ?");
	$value_array = array($selected_entrance_fee_no);
	$tmp_entrance_fee_list = get_entrance_fee_list($db, $param_array, $value_array);
	//$entrance_fee_array["entrance_fee_no"] = trim($_POST["entrance_fee_no"]);
	//$entrance_fee_array["year"] = trim($_POST["year"]);
	//$entrance_fee_array["month"] = trim($_POST["month"]);
	//$entrance_fee_array["no"] = trim($_POST["no"]);
	//$entrance_fee_array["name"] = trim($_POST["name"]);
	//$entrance_fee_array["price"] = trim($_POST["price"]);
	//$entrance_fee_array["memo"] = trim($_POST["memo"]);
	$entrance_fee_array["entrance_fee_no"] = $tmp_entrance_fee_list[0]["entrance_fee_no"];
	//$entrance_fee_array["year"] = trim($_POST["year"]);
	//$entrance_fee_array["month"] = trim($_POST["month"]);
	$entrance_fee_array["member_no"] = $tmp_entrance_fee_list[0]["member_no"];
	$entrance_fee_array["name"] = $tmp_entrance_fee_list[0]["name"];
	$entrance_fee_array["price"] = $tmp_entrance_fee_list[0]["price"];
	$entrance_fee_array["memo"] = $tmp_entrance_fee_list[0]["memo"];
	$member = get_member($db, array("tbl_member.no = ?"), array($entrance_fee_array["member_no"]));
}
*/

if ($action == 'add') {
// 更新処理

	// 入力チェック処理
	$result = check_entrance_fee_list($errArray, $entrance_fee_array);
	if (count($errArray) == 0) {
	// 入力エラーがない場合
		$errFlag = 0;
		try{
			$db->beginTransaction();
			$result = edit_entrance_fee($db, $entrance_fee_array, $errArray);
			if (!$result) $errFlag = 1;
		}catch (PDOException $e){
			$errFlag = 1;
		  //print('Error:'.$e->getMessage());
		}
		if ($errFlag == 0) {
			$db->commit();
 			if ($entrance_fee_array["entrance_fee_no"] == ""){ 
				// 登録時、フォームを初期化
				// 20160103 初期表示の時、kindを入会金にする
				//$entrance_fee_array = array("entrance_fee_no"=>"", "year"=>"", "month"=>"", "name"=>"", "price"=>"", "memo"=>"");
				$entrance_fee_array = array("entrance_fee_no"=>"", "member_no"=>"", "year"=>"", "month"=>"", "price"=>"", "memo"=>"");
			}
			$message = "登録できました。";
			//header("Location: entrance_fee_edit.php?no=".$student_no."&year=".$year."&month=".$month."&f");
			//exit;
		} else {
			array_push($errArray, "登録中にエラーが発生しました。");
			$db->rollback();
		}
	}

	// エラーが発生した場合、編集画面を再表示する
	// 再表示時に、料金の新規追加行を追加表示する
		//array_push($entrance_fee_list, array("entrance_fee_no"=>"", "year"=>"", "month"=>"", "day"=>"", "name"=>"", "price"=>""));

} else if ($action == 'delete') {

	$result = check_number($errArray, "項目No", $_POST["entrance_fee_no"], true);
	if (count($errArray) == 0) {
	// 入力エラーがない場合
		$errFlag = 0;
		try{
			$db->beginTransaction();
			$result = delete_entrance_fee($db, $_POST["entrance_fee_no"]);
			if (!$result) $errFlag = 1;
		}catch (PDOException $e){
			$errFlag = 1;
		  //print('Error:'.$e->getMessage());
		}
		if ($errFlag == 0) {
			$db->commit();
 			//if ($entrance_fee_array["entrance_fee_no"] == ""){ 
				// 登録時、フォームを初期化
				// 20160103 初期表示の時、kindを入会金にする
				//$entrance_fee_array = array("entrance_fee_no"=>"", "year"=>"", "month"=>"", "name"=>"", "price"=>"", "memo"=>"");
				$entrance_fee_array = array("entrance_fee_no"=>"", "member_no"=>"", "year"=>"", "month"=>"", "price"=>"", "memo"=>"");
			//}
			$message = "削除できました。";
		} else {
			array_push($errArray, "削除中にエラーが発生しました。");
			$db->rollback();
		}
	}

} else if ($action == 'selected_entrance_fee_no') {
// 選択時
		// 20160103 初期表示の時、kindを入会金にする
		//$entrance_fee_array = array("entrance_fee_no"=>"", "year"=>"", "month"=>"", "name"=>"", "price"=>"", "memo"=>"");
		$entrance_fee_array = array("entrance_fee_no"=>"", "member_no"=>"", "year"=>"", "month"=>"", "price"=>"", "memo"=>"");
		$entrance_fee_list = get_entrance_fee_list($db, array("tbl_entrance_fee.entrance_fee_no = ?"), array($_POST["selected_entrance_fee_no"]));
		if (count($entrance_fee_list) == 1) {
			$entrance_fee_array = $entrance_fee_list[0];
		}
} else {
// 初期表示処理
		// 20160103 初期表示の時、kindを入会金にする
		//$entrance_fee_array = array("entrance_fee_no"=>"", "year"=>"", "month"=>"", "name"=>"", "price"=>"", "memo"=>"");
		$entrance_fee_array = array("entrance_fee_no"=>"", "member_no"=>"", "year"=>"", "month"=>"", "price"=>"", "memo"=>"");
}


if ($entrance_fee_array["member_no"]) {
	$member = get_member($db, array("tbl_member.no = ?"), array($entrance_fee_array["member_no"]));
}

// 一覧表示
//$param_array = array("tbl_entrance_fee.member_no = ?",
//												"tbl_entrance_fee.year = ?",
//												"tbl_entrance_fee.month = ?");
//$value_array = array($student_no, $year, $month);
//20150731 変更
//$param_array = array("tbl_entrance_fee.member_no = ?");
//$value_array = array($student_no);
//$order_array = array("tbl_entrance_fee.year, tbl_entrance_fee.month, tbl_entrance_fee.entrance_fee_no");
//$entrance_fee_list = get_entrance_fee_list($db, $param_array, $value_array, $order_array);
$param_array = array("tbl_entrance_fee.year = ?","tbl_entrance_fee.month = ?");
$value_array = array($year, $month);
$order_array = array("tbl_entrance_fee.year, tbl_entrance_fee.month, tbl_entrance_fee.entrance_fee_no");
$entrance_fee_list = get_entrance_fee_list($db, $param_array, $value_array, $order_array);
$member_list = get_member_list($db);

function edit_entrance_fee(&$db, $entrance_fee_array, &$errArray) {
	$errFlag = 0;
		try {
				if ($entrance_fee_array["entrance_fee_no"] && $entrance_fee_array["entrance_fee_no"] > 0) {
				// 更新時
					$result = update_entrance_fee($db, $entrance_fee_array);
					if (!$result) {
						$errFlag = 1;
					}
				} else {
		    // 新規登録時
					$result = insert_entrance_fee($db, $entrance_fee_array);
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
function insert_entrance_fee(&$db, $entrance_fee_array) {
	$errFlag = 0;
	try{
		$sql = "INSERT INTO tbl_entrance_fee (member_no, year, month, price, memo, insert_timestamp, update_timestamp".
					" ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, now(), now())";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(1, $member_no);
		$stmt->bindParam(2, $year);
		$stmt->bindParam(3, $month);
		$stmt->bindParam(4, $price);
		$stmt->bindParam(5, $memo);
		$member_no = $entrance_fee_array["member_no"];
		$year = $entrance_fee_array["year"];
		$month = $entrance_fee_array["month"];
		$price = $entrance_fee_array["price"];
		$memo = $entrance_fee_array["memo"];
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

// 管理画面の生徒管理から項目名の変更があるかもしれないので、引数に年月は入れない
function update_entrance_fee(&$db, $entrance_fee_array) {
	$errFlag = 0;
	try{
		$sql = "UPDATE tbl_entrance_fee SET member_no=?, year=?, month=?, price=?, memo=?, update_timestamp=now() ".
					 "WHERE tbl_entrance_fee.entrance_fee_no = ?";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(1, $member_no);
		$stmt->bindParam(2, $year);
		$stmt->bindParam(3, $month);
		$stmt->bindParam(4, $price);
		$stmt->bindParam(5, $memo);
		$stmt->bindParam(6, $entrance_fee_no);
		$member_no = $entrance_fee_array["member_no"];
		$year = $entrance_fee_array["year"];
		$month = $entrance_fee_array["month"];
		$price = $entrance_fee_array["price"];
		$memo = $entrance_fee_array["memo"];
		$entrance_fee_no = $entrance_fee_array["entrance_fee_no"];
		$stmt->execute();
	}catch (PDOException $e){
	  //print('Error:'.$e->getMessage());
		$errFlag = 1;
		//throw $e;
	}
	if ($errFlag == 0) {
		return true;
	} else {
		return false;
	}
}

function delete_entrance_fee(&$db, $entrance_fee_no) {
	$errFlag = 0;
	try{
		$sql = "DELETE FROM tbl_entrance_fee ".
					 "WHERE tbl_entrance_fee.entrance_fee_no = ?";
		$stmt = $db->prepare($sql);
		$stmt->execute(array($entrance_fee_no));
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
<meta name="robots" content="noindex,nofollow">
<title>事務システム</title>
<script type = "text/javascript">
<!--
function delete_entrance_fee() {
	result = window.confirm("フォームに表示中の情報を削除します。\nよろしいですか");
	if (result) {
		document.forms["entrance_fee_form"].submit();
	} else {
		return false;
	}
}

function selected_entrance_fee(entrance_fee_no) {
		document.forms["entrance_fee_form"].selected_entrance_fee_no.value=entrance_fee_no;
		document.forms["entrance_fee_form"].submit();
}

function reset_form() {
		//document.forms["entrance_fee_form"].year.value="";
		//document.forms["entrance_fee_form"].month.value="";
		document.forms["entrance_fee_form"].member_no.value="";
		document.forms["entrance_fee_form"].entrance_fee_no.value="";
		document.forms["entrance_fee_form"].price.value="";
		document.forms["entrance_fee_form"].memo.value="";
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
<h3>生徒の登録 - 入会金</h3>
<a href="student_fee_list.php">生徒の登録 - 生徒一覧へ</a>&nbsp;&nbsp;
-->
<h3>年月別一覧 - 入会金</h3>
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

<form method="post" name="entrance_fee_form" action="entrance_fee_edit_ym.php">

<?php
// 20150731暫定対応
	if ($entrance_fee_array["member_no"]) {
?>

<!--
	<?=$member["name"]?>様
-->
	
	<table border="1" id="entrance_fee_table">
	<tr>
<!--
		<th><font color="red">*</font>年</th>
		<th><font color="red">*</font>月</th>
-->
		<th width="210">生徒名</th>
		<th width="80"><font color="red">*</font>金額</th>
		<th width="550">備考</th>
	</tr>
	<tr>
<!--
		<td align="center">
			<input type="text" name="year" value="<?=$entrance_fee_array["year"]?>" size="5">
		</td>
		<td align="center">
			<input type="text" name="month" value="<?=$entrance_fee_array["month"]?>" size="5">
		</td>
-->
		<td align="center">
			<input type="hidden" name="member_no" value="<?=$entrance_fee_array["member_no"]?>">
			<?=$member["name"]?>
		</td>
		<td align="right">
			<input type="hidden" name="entrance_fee_no" value="<?=$entrance_fee_array["entrance_fee_no"]?>">
			<input type="text" name="price" size="10" value="<?=$entrance_fee_array["price"]?>">
		</td>
		<td align="left">
			<input type="text" name="memo" size="80" value="<?=$entrance_fee_array["memo"]?>">
		</td>
	</tr>
</table>
<table>
	<tr>
  <td align="center">
		<input type="submit" name="add" value="登録">
		<input type="reset" value="リセット">
<?php if ($entrance_fee_array["entrance_fee_no"]) { ?>
		<input type="submit" name="delete" value="削除" onclick="delete_entrance_fee()">
<?php // 20150731 暫定対応 ?>
<!--
		<a href="#" onclick="javascript:reset_form();">新規登録</a>
-->
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
<input type="hidden" name="selected_entrance_fee_no" value="">
</form>

<?php
if (count($entrance_fee_list) > 0) {
?>
<!--
<hr>
-->
<br>

<table border="1">
	<tr>
		<th>生徒名</th><th>金額</th><th>備考</th><th>&nbsp;</th>
	</tr>
 	<?php
	foreach ($entrance_fee_list as $item) {
	?>
	<tr>
		<td align="center" width="210"><input type="hidden" name="entrance_fee_no[]" value="<?=$item["entrance_fee_no"]?>">
			<?=$member_list[$item["member_no"]]["name"]?></td>
		<td align="right" width="80"><?=number_format($item["price"])?> 円</td>
		<td align="left" width="550"><?=$item["memo"]?></td>
		<td align="center" width="60">
			<?php if ($item["entrance_fee_no"]) { ?>
				<input type="button" value="選択" onclick="selected_entrance_fee(<?=$item["entrance_fee_no"]?>)">
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
