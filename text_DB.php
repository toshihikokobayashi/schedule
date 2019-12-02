<?php
ini_set( 'display_errors', 0 );
require_once("./const/const.inc");
require_once("./func.inc");
require_once("./const/login_func.inc");
$result = check_user($db, "1");
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF8">
<meta name="robots" content="noindex,nofollow">
<title>事務システム</title>
<script type = "text/javascript">
<!--
function addCheck() {
	var names=document.getElementsByName("text_list_names[]");
	var subjs=document.getElementsByName("text_list_subjects[]");
	var grads=document.getElementsByName("text_list_grades[]");
	if (!document.text_form.text_name.value) {
		alert("テキスト名を入力してください。"); return false;
	}
	for (var i=0;i<names.length;i++)
		if (names[i].innerText==document.text_form.text_name.value)
			if (window.confirm("同じ名前のテキストが既に登録されています。新規に追加登録しますか？")) break; else return false;
	for (var i=0;i<names.length;i++) {
		if (names[i].innerText!=document.text_form.text_name.value) continue;
		var subj1=document.getElementsByName("text_subject[]");
		var subjflag=-2;
		for (var j=0;j<subj1.length;j++) {
			if (!subj1[j].checked) continue;
			subjflag=-1;
			if (subjs[i].innerText.indexOf(subj1[j].nextSibling.data.trim())!=-1) { subjflag=j; break; }
		}
		var grad1=document.getElementsByName("grade[]");
		var gradeflag=-2;
		for (var j=0;j<grad1.length;j++) {
			if (!grad1[j].checked) continue;
			gradeflag=-1;
			if (grads[i].innerText.indexOf(grad1[j].nextSibling.data.trim())!=-1)  { gradeflag=j; break; }
		}
		if (subjflag>=0 && gradeflag>=0) {
			alert('"'+names[i].innerText+'" "'+subj1[subjflag].nextSibling.data.trim()+'" "'+grad1[gradeflag].nextSibling.data.trim()+'"は既に登録されています。');
			return false;
		}
		if (subjflag>=0 && gradeflag==-2 && grads[i].innerText=="") {
			alert(gradeflag);
			alert('"'+names[i].innerText+'" "'+subj1[subjflag].nextSibling.data.trim()+'" "学年指定なし"は既に登録されています。');
			return false;
		}
		if (gradeflag>=0 && subjflag==-2 && subjs[i].innerText=="") {
			alert('"'+names[i].innerText+'" "'+grad1[gradeflag].nextSibling.data.trim()+'" "教科指定なし"は既に登録されています。');
			return false;
		}
	}
	return true;
}
function delCheck() {
	var sel=document.getElementsByName("select");
	var names=document.getElementsByName("text_list_names[]");
	for (var i=0;i<sel.length;i++)
		if (sel[i].checked)
			return window.confirm("\""+names[i].innerText+"\"を削除しますか？");
	return false;
}
function priceInput() {
	var val=document.getElementsByName("teika_price")[0].value;
	document.getElementsByName("tewatashi_price1")[0].value = Math.floor(val*1.4*<?= (1.0+CONS_TAX10) ?>);
	document.getElementsByName("tewatashi_price2")[0].value = Math.ceil(val/10)*10;
}
-->
</script>
<link rel="stylesheet" type="text/css" href="./script/style.css">
</head>
<?php
$errArray = array();

$search_mode = 0; $select_mode = 0;
if (!$_POST['reset']) {
	if ($_POST['select']) {
		$select_mode = 1;
	}
	if ($_POST['search'] == '検索') { 
		$search_mode = 1; $select_mode = 0;
	}
	if ($_POST['search'] != '検索解除') {
		if (!$search_mode)	$text_id = $_POST['text_id'];
		if (!$_POST['delete']) {
			$text_name      = $_POST['text_name'];
			$text_subjects  = $_POST['text_subject'];
			$other_subject  = $_POST['other_subject'];
			$grades         = $_POST['grade'];
			$publisher      = $_POST['publisher'];
			$other_publisher = $_POST['other_publisher'];
			$suppliers      = $_POST['supplier'];
			$other_supplier = $_POST['other_supplier'];
			$teika_price    = $_POST['teika_price'];
			$tewatashi_price1 = $_POST['tewatashi_price1'];
			$tewatashi_price2 = $_POST['tewatashi_price2'];
			$tewatashi_price3 = $_POST['tewatashi_price3'];
			$publisher_price = $_POST['publisher_price'];
			if ($select_mode)	$text_id = $_POST['select'];
		}
	}
}

try{

	if ($_POST['add'] || ($_POST['update'] && $text_id)) {
		$db->beginTransaction();

		if ($publisher==0 && $other_publisher) {
			$stmt = $db->query("INSERT INTO tbl_text_publisher_name (name, insert_timestamp, update_timestamp) VALUES (\"$other_publisher\", now(), now())");			
			$publisher = $db->lastInsertId();
		}
		$key = array_search(0, $suppliers);
		if ($key!==false && $other_supplier) {
			$stmt = $db->query("INSERT INTO tbl_text_supplier_name (name, insert_timestamp, update_timestamp) VALUES (\"$other_supplier\", now(), now())");			
			$suppliers[$key] = $db->lastInsertId();
		}
		$key = array_search(0, $text_subjects);
		if ($key!==false && $other_subject) {
			$stmt = $db->query("INSERT INTO tbl_text_subject_name (name, insert_timestamp, update_timestamp) VALUES (\"$other_subject\", now(), now())");			
			$text_subjects[$key] = $db->lastInsertId();
		}
		if ($_POST['add']) {
			$stmt = $db->query(
				"INSERT INTO tbl_text (name, publisher_id, teika_price, tewatashi_price1, tewatashi_price2, tewatashi_price3, publisher_price, insert_timestamp, update_timestamp) ".
				"VALUES (\"$text_name\", \"$publisher\", \"$teika_price\", \"$tewatashi_price1\", \"$tewatashi_price2\", \"$tewatashi_price3\", \"$publisher_price\", now(), now())");
			$text_id = $db->lastInsertId();
		} else {
			$stmt = $db->query(
				"UPDATE tbl_text SET name=\"$text_name\", publisher_id=\"$publisher\", teika_price=\"$teika_price\", ".
				"tewatashi_price1=\"$tewatashi_price1\", tewatashi_price2=\"$tewatashi_price2\", tewatashi_price3=\"$tewatashi_price3\", ".
				"publisher_price=\"$publisher_price\", update_timestamp=now() WHERE text_id=\"$text_id\"");
		}
		$stmt = $db->query("DELETE FROM tbl_text_subject WHERE text_id=\"$text_id\"");
		foreach ($text_subjects as $text_subject_id)
			if ($text_subject_id) 
			 $stmt = $db->query("INSERT INTO tbl_text_subject (text_id, subject_id) VALUES (\"$text_id\", \"$text_subject_id\")");
		$stmt = $db->query("DELETE FROM tbl_text_grade WHERE text_id=\"$text_id\"");
		foreach ($grades as $grade)
			$stmt = $db->query("INSERT INTO tbl_text_grade (text_id, grade) VALUES (\"$text_id\", \"$grade\")");
		$stmt = $db->query("DELETE FROM tbl_text_supplier WHERE text_id=\"$text_id\"");
		foreach ($suppliers as $supplier)
			$stmt = $db->query("INSERT INTO tbl_text_supplier (text_id, supplier_id) VALUES (\"$text_id\", \"$supplier\")");
		$db->commit();
	}
	
	if ($_POST['delete']) {
		$db->beginTransaction();
		$stmt = $db->query("DELETE FROM tbl_text           WHERE text_id=\"$text_id\"");
		$stmt = $db->query("DELETE FROM tbl_text_subject   WHERE text_id=\"$text_id\"");
		$stmt = $db->query("DELETE FROM tbl_text_grade     WHERE text_id=\"$text_id\"");
		$stmt = $db->query("DELETE FROM tbl_text_supplier  WHERE text_id=\"$text_id\"");
		$db->commit();
	} 
	
	$stmt = $db->query("SELECT * FROM tbl_text ORDER BY name");
	$text_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	$stmt = $db->query("SELECT * FROM tbl_text_subject");
	$rslt = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($rslt as $item) $text_subject_list[$item['text_id']][] = $item['subject_id'];
	
	$stmt = $db->query("SELECT * FROM tbl_text_grade");
	$rslt = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($rslt as $item) $text_grade_list[$item['text_id']][] = $item['grade'];

	$stmt = $db->query("SELECT * FROM tbl_text_supplier");
	$rslt = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($rslt as $item) $text_supplier_list[$item['text_id']][] = $item['supplier_id'];
	
	$stmt = $db->query("SELECT subject_id, name FROM tbl_text_subject_name");
	$rslt = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($rslt as $item) $text_subject_name_list[$item['subject_id']] = $item['name'];
	
	$stmt = $db->query("SELECT publisher_id, name FROM tbl_text_publisher_name");
	$rslt = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($rslt as $item) $publisher_name_list[$item['publisher_id']] = $item['name'];
	
	$stmt = $db->query("SELECT supplier_id, name FROM tbl_text_supplier_name");
	$rslt = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($rslt as $item) $supplier_name_list[$item['supplier_id']] = $item['name'];
	
	if ($select_mode)	{
		$key = array_search($text_id, array_column($text_list,'text_id'));
		if ($key!==false) $select_text = $text_list[$key];
	}
	
}catch (PDOException $e){
	$db->rollback();
	$errArray[] =  $e->getMessage();
}

?>
<body>

<div id="header">
	事務システム 
</div>

<div id="content" align="center">

<h3>テキスト登録・更新・一覧</h3>

<?php
	if (count($errArray) > 0) {
		foreach( $errArray as $error) {
			echo "<font color=\"red\">$error</font><br>";
		}
	}
?>

<a href="menu.php">メニューへ戻る</a><br><br>

<form method="post" name="text_form" action="text_DB.php">

<table>
<tr><th align="right">テキスト名</th><td width="20"></td><td><input type="text" name="text_name" size="80" maxlength="40" value="<?= $select_text?$select_text['name']:$text_name ?>"></td></tr>
<tr><th valign="top" align="right">教科</th><td width="20"></td><td>
<?php
	foreach ($text_subject_name_list as $key=>$subject_name) {
		$checked='';
		if ($search_mode && $text_subjects)
			$checked = (array_search($key,$text_subjects)!==false)?"checked":"";
		else if ($select_text && $text_subject_list[$text_id])
			$checked = (array_search($key,$text_subject_list[$text_id])!==false)?"checked":"";
		if ($key>1 && ($key-1)%8 == 0) echo "<br>";
		echo "<input type=\"checkbox\" name=\"text_subject[]\" value=\"$key\" $checked>$subject_name ";
	}
	echo "<br>";
	echo "<input type=\"checkbox\" name=\"text_subject[]\" value=\"0\"><input type=\"text\" name=\"other_subject\" size=\"10\" maxlength=\"20\">";
?>
</td></tr>
<tr><th valign="top" align="right">学年</th><td width="20"></td><td>
<?php
	foreach ($grade_list as $key=>$grade0) {
		if ($grade0=='未登録') continue;
		$checked='';
		if ($search_mode && $grades)
			$checked = (array_search($key,$grades)!==false)?"checked":"";
		else if ($select_text && $text_grade_list[$text_id])
			$checked = (array_search($key,$text_grade_list[$text_id])!==false)?"checked":"";
		if (preg_match("/小1|中1|高1|成人/u",$grade0)) echo '<br>';
		echo "<input type=\"checkbox\" name=\"grade[]\" value=\"$key\" $checked>$grade0 ";
	}
?>
</td></tr>
<tr><th valign="top" align="right">出版社</th><td width="20"></td><td>
<?php
	foreach ($publisher_name_list as $key=>$publisher_name) {
		$checked='';
		if ($search_mode && $publisher)
			$checked = ($key==$publisher)?"checked":"";
		else if ($select_text && $select_text['publisher_id'])
			$checked = ($key==$select_text['publisher_id'])?"checked":"";
		if ($key>1 && ($key-1)%6 == 0) echo "<br>";
		echo "<input type=\"radio\" name=\"publisher\" value=\"$key\" $checked>$publisher_name ";
	}
	echo "<br>";
	echo "<input type=\"radio\" name=\"publisher\" value=\"0\"><input type=\"text\" name=\"other_publisher\" size=\"10\" maxlength=\"20\">";
?>
</td></tr>
<tr><th valign="top" align="right">発注先</th><td width="20"></td><td>
<?php
	foreach ($supplier_name_list as $key=>$supplier0) {
		if (!$key) continue;
		$checked='';
		if ($search_mode && $suppliers)
			$checked = (array_search($key,$suppliers)!==false)?"checked":"";
		else if ($select_text && $text_supplier_list[$text_id])
			$checked = (array_search($key,$text_supplier_list[$text_id])!==false)?"checked":"";
		if ($key>1 && ($key-1)%6 == 0) echo "<br>";
		echo "<input type=\"checkbox\" name=\"supplier[]\" value=\"$key\" $checked>$supplier0 ";
	}
	echo "<input type=\"checkbox\" name=\"supplier\" value=\"0\"><input type=\"text\" name=\"other_supplier\" size=\"10\" maxlength=\"20\">";
//	echo "<br>";
//	$key++;
//	echo "<input type=\"checkbox\" name=\"supplier[]\" value=\"$key\"><input type=\"text\" name=\"other_supplier[]\" size=\"20\" maxlength=\"20\">";
	$teika_price = $select_text?$select_text['teika_price']:$teika_price;
	$tewatashi_price1 = $select_text?$select_text['tewatashi_price1']:$tewatashi_price1;
	$tewatashi_price2 = $select_text?$select_text['tewatashi_price2']:$tewatashi_price2;
	$tewatashi_price3 = $select_text?$select_text['tewatashi_price3']:$tewatashi_price3;
	$publisher_price  = $select_text?$select_text['publisher_price']:$publisher_price;
?>
	</td></tr>
	<tr><th align="right">定価（税抜き）</th><td width="20"></td><td><input type="text" name="teika_price" value="<?= $teika_price ?>"size="4" onkeyup="priceInput()">円
	<tr><th align="right">手渡し価格（税込み）</th><td width="20"></td>
	<td>教材販売：<input type="input" name="tewatashi_price1" value="<?= $tewatashi_price1 ?>" size="4" readonly>円
	　　amazon:<input type="input" name="tewatashi_price2" value="<?= $tewatashi_price2 ?>" size="4" readonly>円
	　　その他:<input type="text" name="tewatashi_price3" value="<?= $tewatashi_price3 ?>" size="4">円
	<tr><th align="right">出版社請求価格</th><td width="20"></td><td><input type="text" name="publisher_price" value="<?= $publisher_price ?>" size="4">円
	</td></tr>
</table>

	<div class="menu_box">
		<font color="black" size="-1">
		※&nbsp;検索条件設定：テキスト名は部分一致、税込価格は全体一致で検索します。教科、学年、発注先はそれぞれの選択欄で一つ以上チェックされていればその選択欄の検索対象となります。
		</font>
	</div>
<br>
<input type="hidden" name="text_id" value="<?= $text_id ?>">
<input type="submit" name="search" value="検索">
<input type="submit" name="search" value="検索解除" <?= !$search_mode?"disabled=\"disabled\"":"" ?>>
　　
<input type="submit" name="add"    value="新規登録" <?= $search_mode?"disabled=\"disabled\"":"" ?> onclick="return addCheck()">
　　
<input type="submit" name="update" value="更新" <?= ($search_mode || !$select_text)?"disabled=\"disabled\"":"" ?>>
<input type="submit" name="delete" value="削除" <?= ($search_mode || !$select_text)?"disabled=\"disabled\"":"" ?> onclick="return delCheck()">
　　
<input type="submit" name="reset"  value="リセット">
<br>
<br>

<table border='1'>
<tr>
<th width="30" rowspan="2"></th><th rowspan="2">選択</th><th rowspan="2">テキスト名</th><th rowspan="2">教科</th>
<th rowspan="2">学年</th><th rowspan="2">出版社</th><th rowspan="2">発注先</th><th rowspan="2">定価</th><th colspan="3">手渡し価格</th><th rowspan="2">出版社<br>請求価格</th></tr>
<tr><th>教材販売</th><th>amazon</th><th>その他</th><tr>
<?php

$no=1;
foreach ($text_list as $text) {
	$text_id0 = $text['text_id'];
	
	if ($search_mode) {
		if ($text_name && stripos($text['name'], $text_name)===false) continue;
		if ($text_subjects && !array_intersect($text_subject_list[$text_id0],  $text_subjects)) continue;
		if ($grades        && !array_intersect($text_grade_list[$text_id0],    $grades))        continue;
		if ($suppliers     && !array_intersect($text_supplier_list[$text_id0], $suppliers))     continue;
		if ($publisher        && $text['publisher_id']!=$publisher) continue;
		if ($teika_price      && $text['teika_price']!=$teika_price) continue;
		if ($tewatashi_price1 && $text['tewatashi_price1']!=$tewatashi_price1) continue;
		if ($tewatashi_price2 && $text['tewatashi_price2']!=$tewatashi_price2) continue;
		if ($tewatashi_price3 && $text['tewatashi_price3']!=$tewatashi_price3) continue;
		if ($publisher_price  && $text['publisher_price']!=$publisher_price) continue;
	}
	
	echo "<tr>";
	echo "<td>$no</td>";
	$checked = ($text_id && $text_id==$text_id0)?"checked":"";
	echo "<td><input type=\"radio\" name=\"select\" value=\"$text_id0\" $checked onclick=\"document.text_form.submit()\"></td>";
	echo "<td name=\"text_list_names[]\">{$text['name']}</td>";
	echo "<td name=\"text_list_subjects[]\">";
	foreach ($text_subject_list[$text_id0]  as $key=>$item) { if ($key) echo ','; if ($key && $key%5==0) echo '<br>'; echo $text_subject_name_list[$item]; }
	echo "</td>";
	echo "<td name=\"text_list_grades[]\">";
	sort($text_grade_list[$text_id0]);
	foreach ($text_grade_list[$text_id0]    as $key=>$item) { if ($key) echo ','; if ($key && $key%5==0) echo '<br>'; echo $grade_list[$item]; }
	echo "</td>";
	if ($text['publisher_id']) echo "<td>{$publisher_name_list[$text['publisher_id']]}</td>"; else echo "<td></td>";
	echo "<td>";
	$supplier_str='';
	foreach ($text_supplier_list[$text_id0] as $key=>$item) { if ($key) echo '<br>'; echo $supplier_name_list[$item]; $supplier_str .= $supplier_name_list[$item]; }
	echo "</td>";
	if ($text['teika_price']) {
		echo "<td>{$text['teika_price']}</td>";
	}	else {
		echo "<td></td>";
	}
	if ($text['tewatashi_price1'] && $supplier_str!='amazon') {
		echo "<td>{$text['tewatashi_price1']}</td>"; 
	} else {
		echo "<td></td>";
	}
	if ($text['tewatashi_price2'] && strpos($supplier_str,'amazon')!==false) {
		echo "<td>{$text['tewatashi_price2']}</td>";
	} else {
		echo "<td></td>";
	}
	if ($text['tewatashi_price3']) {
		echo "<td>{$text['tewatashi_price3']}</td>";
	} else {
		echo "<td></td>";
	}
	if ($text['publisher_price']) {
		echo "<td>{$text['publisher_price']}</td>";
	} else {
		echo "<td></td>";
	}
	echo '</tr>';
	$no++;
}
?>
</table>
</form>
</div>

<div id="footer">
</div>

</body>
</html>
