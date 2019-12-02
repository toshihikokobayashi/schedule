<?php
ini_set( 'display_errors', 0 );
require_once("./const/const.inc");
require_once("./func.inc");
require_once("./const/login_func.inc");
$result = check_user($db, "1");

$tokusoku_kingaku = 1080;

$errArray = array();
$message = "";

$kind_array = get_kind_array("0");
$initArray = array("others_no"=>"", "year"=>"", "month"=>"", "lesson_id"=>"",  "type_id"=>"", "kind"=>"", "name"=>"", "price"=>"", "tax_flag"=>"0", "memo"=>"");

foreach ($place_list as $item) {
	if ($item['name']!='北口校')	$place_menu_list[$item['no']] = $item['name'];
}

$student_no = trim($_POST["no"]);
if (!$student_no)	$student_no = trim($_GET["no"]);
//$year = trim($_GET["y"]);
//$month = trim($_GET["m"]);

if (isset($_POST['add'])) {
	$action = 'add';
} else if (isset($_POST['delete'])) {
	$action = 'delete';
} else if (isset($_POST['selected_others_no'])) {
	$action = 'selected_others_no';
} else {
	$action = "";
}

if ($action == 'add' || $action == 'delete' || $action == 'selected_others_no') {
	//$student_no = trim($_POST["no"]);
	//$year = trim($_POST["y"]);
	//$month = trim($_POST["m"]);
	$others_array["others_no"] = trim($_POST["others_no"]);
	$others_array["year"] = trim($_POST["year"]);
	$others_array["month"] = trim($_POST["month"]);
	$others_array["lesson_id"] = trim($_POST["lesson_id"]);
	$others_array["type_id"] = trim($_POST["type_id"]);
	$others_array["kind"] = trim($_POST["kind"]);
	$others_array["name"] = trim($_POST["name"]);
	$others_array["price"] = trim($_POST["price"]);
	$others_array["tax_flag"] = trim($_POST["tax_flag"]);
	$others_array["memo"] = trim($_POST["memo"]);
	$others_array["charge"] = array_search(trim($_POST["charge"]),$charge_list);
	if ($_POST["place_id"])
		$others_array["place_id"] = array_column($place_list,'no')[array_search($_POST["place_id"], array_column($place_list,'name'))];
	else
		$others_array["place_id"] = '';
}

$result1 = check_number($errArray, "生徒No", $student_no, true);
//$result2 = check_number($errArray, "月", $year, true);
//$result3 = check_number($errArray, "日", $month, true);
//if ($result1 === false || $result2 === false  || $result3 === false) {
if ($result1 === false) {
	header('location: menu.php');
}

// メンバー情報を取得
$member = null;
if ($student_no != "") {
	$member = get_member($db, array("tbl_member.no = ?"), array($student_no));
}
if ($member == null) {
	header('location: menu.php');
}

if ($action == 'add') {
// 更新処理

	// 入力チェック処理
	$result = check_others_list($errArray, $others_array);
	if (count($errArray) == 0) {
	// 入力エラーがない場合
		$errFlag = 0;
		try{
			$db->beginTransaction();
			$result = edit_others($db, $student_no, $others_array, $errArray);
			if (!$result) $errFlag = 1;
		}catch (PDOException $e){
			$errFlag = 1;
		  //print('Error:'.$e->getMessage());
		}
		if ($errFlag == 0) {
			$db->commit();
 			if ($others_array["others_no"] == ""){ 
				// 登録時、フォームを初期化
				$others_array = $initArray;
			}
			$message = "登録できました。";
			//header("Location: others_edit.php?no=".$student_no."&year=".$year."&month=".$month."&f");
			//exit;
		} else {
			array_push($errArray, "登録中にエラーが発生しました。");
			$db->rollback();
		}
	}

	// エラーが発生した場合、編集画面を再表示する
	// 再表示時に、料金の新規追加行を追加表示する
		//array_push($others_list, array("others_no"=>"", "year"=>"", "month"=>"", "day"=>"", "name"=>"", "price"=>""));

} else if ($action == 'delete') {

	$result = check_number($errArray, "項目No", $_POST["others_no"], true);
	if (count($errArray) == 0) {
	// 入力エラーがない場合
		$errFlag = 0;
		try{
			$db->beginTransaction();
			$result = delete_others($db, $_POST["others_no"]);
			if (!$result) $errFlag = 1;
		}catch (PDOException $e){
			$errFlag = 1;
		  //print('Error:'.$e->getMessage());
		}
		if ($errFlag == 0) {
			$db->commit();
 			//if ($others_array["others_no"] == ""){ 
				// 登録時、フォームを初期化
				$others_array = $initArray;
			//}
			$message = "削除できました。";
		} else {
			array_push($errArray, "削除中にエラーが発生しました。");
			$db->rollback();
		}
	}
	//$tax_flag_0 = "";
	//$tax_flag_1 = "";
	//if (trim($_POST["tax_flag"]) === "0") {
	//	$tax_flag_0 = " checked";
	//}
	//if (trim($_POST["tax_flag"]) === "1") {
	//	$tax_flag_1 = " checked";
	//}

} else if ($action == 'selected_others_no') {
// 選択時
		$others_array = $initArray;
		$others_list = get_others_list($db, array("tbl_others.others_no = ?"), array($_POST["selected_others_no"]));
		if (count($others_list) == 1) {
			$others_array = $others_list[0];
		}
		if ($other_array["tax_flag"] == null && $member["tax_flag"] == "1") {
		// 税種別追加以前のデータ用
			$other_array["tax_flag"] = "1";
		}
		//$tax_flag_0 = "";
		//$tax_flag_1 = "";
		//if ($others_array["tax_flag"] === "0") {
		//	$tax_flag_0 = " checked";
		//}
		//if ($others_array["tax_flag"] === "1") {
		//	$tax_flag_1 = " checked";
		//}
} else {
// 初期表示処理
		$others_array = $initArray;
		//$tax_flag_0 = "";
		//$tax_flag_1 = "";
}

//if ($tax_flag_0 === "" && $tax_flag_1 === "") {
//	$tax_flag_0 = " checked";
//}


// 一覧表示
//$param_array = array("tbl_others.member_no = ?",
//												"tbl_others.year = ?",
//												"tbl_others.month = ?");
//$value_array = array($student_no, $year, $month);
$param_array = array("tbl_others.member_no = ?");
$value_array = array($student_no);
$order_array = array("tbl_others.year, tbl_others.month, tbl_others.others_no");
$others_list = get_others_list($db, $param_array, $value_array, $order_array);


function edit_others(&$db, $student_no, $others_array, &$errArray) {
	$errFlag = 0;
		try {
				if ($others_array["others_no"] && $others_array["others_no"] > 0) {
				// 更新時
					$result = update_others($db, $student_no, $others_array);
					if (!$result) {
						$errFlag = 1;
					}
				} else {
		    // 新規登録時
					$result = insert_others($db, $student_no, $others_array);
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
function insert_others(&$db, $student_no, $others_array) {
	$errFlag = 0;
	try{
		$sql = "INSERT INTO tbl_others (member_no, year, month, lesson_id, type_id, kind, name, price, tax_flag, memo, charge, place_id, insert_timestamp, update_timestamp".
					" ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, now(), now())";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(1, $member_no);
		$stmt->bindParam(2, $year);
		$stmt->bindParam(3, $month);
		$stmt->bindParam(4, $lesson_id);
		$stmt->bindParam(5, $type_id);
		$stmt->bindParam(6, $kind);
		$stmt->bindParam(7, $name);
		$stmt->bindParam(8, $price);
		$stmt->bindParam(9, $tax_flag);
		$stmt->bindParam(10, $memo);
		$stmt->bindParam(11, $charge);
		$stmt->bindParam(12, $place_id);
		$member_no = $student_no;
		$year = $others_array["year"];
		$month = $others_array["month"];
		$lesson_id = $others_array["lesson_id"];
		//$type_id = $others_array["type_id"];
		$type = null; // 20151114 未使用
		$kind = $others_array["kind"];
		$name = $others_array["name"];
		$price = $others_array["price"];
		$tax_flag = $others_array["tax_flag"];
		$memo = $others_array["memo"];
		$charge = $others_array["charge"];
		$place_id = $others_array["place_id"];
		$stmt->execute();
	}catch (PDOException $e){
		$errFlag = 1;
	  print('Error:'.$e->getMessage());
		throw $e;
	}
	if ($errFlag == 0) {
		return true;
	} else {
		return false;
	}
}

// 管理画面の生徒管理から項目名の変更があるかもしれないので、引数に年月は入れない
function update_others(&$db, $student_no, $others_array) {
	$errFlag = 0;
	try{
		$sql = "UPDATE tbl_others SET member_no=?, year=?, month=?, lesson_id=?, type_id=?, kind=?, name=?, price=?, tax_flag=?, memo=?, charge=?, place_id=?, update_timestamp=now() ".
					 "WHERE tbl_others.others_no = ?";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(1, $member_no);
		$stmt->bindParam(2, $year);
		$stmt->bindParam(3, $month);
		$stmt->bindParam(4, $lesson_id);
		$stmt->bindParam(5, $type_id);
		$stmt->bindParam(6, $kind);
		$stmt->bindParam(7, $name);
		$stmt->bindParam(8, $price);
		$stmt->bindParam(9, $tax_flag);
		$stmt->bindParam(10, $memo);
		$stmt->bindParam(11, $charge);
		$stmt->bindParam(12, $place_id);
		$stmt->bindParam(13, $no);
		$member_no = $student_no;
		$year = $others_array["year"];
		$month = $others_array["month"];
		$lesson_id = $others_array["lesson_id"];
		//$type_id = $others_array["type_id"];
		$type = null; // 20151114 未使用
		$kind = $others_array["kind"];
		$name = $others_array["name"];
		$price = $others_array["price"];
		$tax_flag = $others_array["tax_flag"];
		$memo = $others_array["memo"];
		$charge = $others_array["charge"];
		$place_id = $others_array["place_id"];
		$no = $others_array["others_no"];
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

function delete_others(&$db, $others_no) {
	$errFlag = 0;
	try{
		$sql = "DELETE FROM tbl_others ".
					 "WHERE tbl_others.others_no = ?";
		$stmt = $db->prepare($sql);
		$stmt->execute(array($others_no));
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
function delete_others() {
	result = window.confirm("フォームに表示中の情報を削除します。\nよろしいですか");
	if (result) {
		document.forms["others_form"].submit();
	} else {
		return false;
	}
}

function selected_others(others_no) {
		document.forms["others_form"].selected_others_no.value=others_no;
		document.forms["others_form"].submit();
}

function reset_form() {
		document.forms["others_form"].year.value="";
		document.forms["others_form"].month.value="";
		document.forms["others_form"].others_no.value="";
		document.forms["others_form"].lesson_id.value="";
		//document.forms["others_form"].others_kind.value="";
		document.forms["others_form"].kind.value="";
		document.forms["others_form"].name.value="";
		document.forms["others_form"].price.value="";
		document.forms["others_form"].tax_flag.value="0";
		document.forms["others_form"].memo.value="";
}

function kindCheck(obj)
{
	if (obj.options[obj.selectedIndex].text=='督促金') {
		if (!document.forms["others_form"].name.value) document.forms["others_form"].name.value='督促金';
		if (!document.forms["others_form"].price.value) document.forms["others_form"].price.value='<?= $tokusoku_kingaku ?>';
	}
	if (obj.options[obj.selectedIndex].text=='授業') {
			document.forms["others_form"].place_id.style.display='block';
	} else {
			document.forms["others_form"].place_id.style.display='none';
	}
}

function setPlaceMenu() {
	var form = document.forms["others_form"];
	if (form.kind.options[form.kind.selectedIndex].text=='授業') {
		form.place_id.style.display='block';
	} else {
		form.place_id.style.display='none';
		form.place_id.value="";
	}
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


<h3>生徒の登録 - その他項目</h3>

<?php if (!$lms_mode) { ?>
<a href="student_fee_list.php">生徒の登録 - 生徒一覧へ</a>&nbsp;&nbsp;
<a href="menu.php">メニューへ戻る</a><br><br>
<br><br>

<?php
}
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

<form method="post" name="others_form" action="others_edit.php">
	<input type="hidden" name="no" value="<?=$student_no?>">
<!--
	<input type="hidden" name="y" value="<?=$year?>">
	<input type="hidden" name="m" value="<?=$month?>">
-->
	<h3><?=$member["name"]?>様
　<!--<?=$year?>年<?=$month?>月-->
	</h3>
	<table border="1" id="others_table">
	<tr>
		<th><font color="red">*</font>年</th>
		<th><font color="red">*</font>月</th>
		<th><font color="red">*</font>項目名</th>
		<th><font color="red">*</font>金額(税込)</th>
<!--
		<th><font color="red">*</font>税種別</th>
		<th>授業料の税種別</th>
-->
		<th><font color="red">*</font>教室</th>
<!--
		<th><font color="red">*</font>タイプ</th>
-->
		<th><font color="red">*</font>種類</th>
		<th><font color="red">*</font>校舎</th>
		<th><font color="red">*</font>請求</th>
		<th>備考</th>
	</tr>
	<tr>
		<td align="center">
			<input type="text" name="year" value="<?=$others_array["year"]?>" size="4">
		</td>
		<td align="center">
			<input type="text" name="month" value="<?=$others_array["month"]?>" size="2">
		</td>
		<td align="center">
			<input type="hidden" name="others_no" value="<?=$others_array["others_no"]?>">
			<input type="text" name="name" size="20" value="<?=$others_array["name"]?>">
		</td>
		<td align="right">
			<input type="text" name="price" size="10" value="<?=$others_array["price"]?>">
			<input type="hidden" name="tax_flag" value="0">
		</td>
<!--
		<td align="center">
<input type="radio" name="tax_flag" value="0" <?=$tax_flag_0?>>税込<br>
<input type="radio" name="tax_flag" value="1" <?=$tax_flag_1?>>税抜
		</td>
		<td align="center">
<?php
		if ($member["tax_flag"] === "0") {
			echo "税込";
		} else if ($member["tax_flag"] === "1") {
			echo "税抜";
		}
?>
		</td>
-->
		<td align="center">
<?php
		echo disp_lesson_menu($lesson_list, "lesson_id", $others_array["lesson_id"]);
?>
		</td>
<!--
		<td align="center">
<?php
		echo disp_type_menu($type_list, "type_id", $others_array["type_id"]);
?>
		</td>
-->
		<td align="center">
<?php
	  echo disp_kind_menu($kind_list, "kind", "", $others_array["kind"], "kindCheck(this)");
?>
		</td>
		<td align="center">
<?php
		echo disp_pulldown_menu($place_menu_list, "place_id", $place_list[$others_array["place_id"]]['name']);
?>
		</td>
		<td align="center">
<?php
	  echo disp_pulldown_menu($charge_list, "charge", "あり");
?>
		</td>
		<td align="left">
			<input type="text" name="memo" size="80" value="<?=$others_array["memo"]?>">
		</td>
	</tr>
</table>
<table>
	<tr>
  <td align="center">
		<input type="submit" name="add" value="登録">
		<input type="reset" value="リセット" onreset="setPlaceMenu()">
<?php if ($others_array["others_no"]) { ?>
		<input type="submit" name="delete" value="削除" onclick="delete_others()">
		<input type="button" value="新規登録" onclick="location.replace('./others_edit.php?no=<?=$student_no?>')">
<?php } ?>
	</td>
	</tr>
</table>

<input type="hidden" name="selected_others_no" value="">
</form>

<?php
if (count($others_list) > 0) {
?>
<hr>
<br>
<table border="1">
	<tr>
		<th>年月</th><th>項目名</th><th>金額</th>
<th>税種別</th>
<th>教室</th>
<th>種類</th><th>校舎</th>
<!--<th>タイプ</th>-->
<th>請求</th><th>備考</th><th>&nbsp;</th>
	</tr>
 	<?php
	foreach ($others_list as $item) {
	?>
	<tr>
		<td align="center"><input type="hidden" name="others_no[]" value="<?=$item["others_no"]?>"><?=$item["year"]?>年<?=$item["month"]?>月</td>
		<td align="left" width="200"><?=$item["name"]?></td>
		<td align="right" width="100"><?=number_format($item["price"])?> 円</td>
		<td align="center">
<?php
		if ($item["tax_flag"] === "0") {
			echo "税込";
		} else if ($item["tax_flag"] === "1") {
			echo "税抜";
		}
?>
		</td>
		<td align="center"><?=$lesson_list[$item["lesson_id"]]?></td>
<!--
		<td align="center" width="200"><?=$type_list[$item["type_id"]]?></td>
-->
		<td align="center"><?=$kind_list[array_search($item["kind"], array_column($kind_list,"no"))]['name']?></td>
		<td align="center"><?=$place_list[$item["place_id"]]['name']?></td>
		<td align="center"><?=$charge_list[$item["charge"]]?></td>
		<td align="left" width="500"><?=$item["memo"]?></td>
		<td align="center">
			<?php if ($item["others_no"]) { ?>
				<input type="button" value="選択" onclick="selected_others(<?=$item["others_no"]?>)">
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

<?php if ($lms_mode) { ?>
<br><input type="button" onclick="document.location='student_fee_list.php?student_id=<?=$student_no?>'" value="戻る">
		<input type="button" onclick="window.close()" value="閉じる">
<?php } ?>

</div>

<script type = "text/javascript">
<!--
setPlaceMenu();
//-->
</script>

</body>
</html>
