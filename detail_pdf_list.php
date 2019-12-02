<?php
ini_set( 'display_errors', 0 );
//ini_set('display_errors', 'on');
//error_reporting(E_ALL);
//error_reporting(E_ALL ^E_NOTICE ^E_DEPRECATED);	

require_once("./const/const.inc");
require_once("./func.inc");
require_once("./const/login_func.inc");

$result = check_user($db, "1");

$pdfDirName = './fees_pdf/';
$monthDirNameArray = array();
if ($handle = opendir($pdfDirName)) {
  while (false !== ($entry = readdir($handle))) {
   if ($entry != "." && $entry != "..") {
     $monthDirNameArray[] = $entry;
   }
  }
  closedir($handle);
}
usort($monthDirNameArray, 'cmp_dir_name');

$PDFfileNameArray = array();
foreach ($monthDirNameArray as $tmp_monthDirName) {
	$monthDirName = './fees_pdf/'.$tmp_monthDirName.'/';
	$tmp_PDFfileDirNameArray = array();
	if ($handle = opendir($monthDirName)) {
	  while (false !== ($entry = readdir($handle))) {
	   if ($entry != "." && $entry != "..") {
			 $time = filemtime($monthDirName."/".$entry);
			 $entry = str_replace(".pdf","",$entry);
	     $tmp_PDFfileDirNameArray[] = array("name"=>$entry, "ext"=>"pdf", "time"=>$time);
	   }
	  }
	  closedir($handle);
	}

	//usort($tmp_PDFfileDirNameArray, 'cmp_month_name');
	usort($tmp_PDFfileDirNameArray, 'cmp_pdf_time');

	$year = substr($tmp_monthDirName, 0, 4); 
	$month = substr($tmp_monthDirName, 4, 2);
	$PDFfileNameArray[$monthDirName]['name'] = $year."年".$month."月";
	$PDFfileNameArray[$monthDirName]['list'] = $tmp_PDFfileDirNameArray;
}

//var_dump($monthDirNameArray);

// 年月フォルダ名順（昇順）
function cmp_dir_name($a, $b) {
  if ($a == $b) {
    return 0;
  }
  return ($a > $b) ? -1 : 1;
}

/*
// PDFファイル名順（降順）
function cmp_month_name($a, $b) {
  if ($a == $b) {
    return 0;
  }
  return ($a > $b) ? -1 : 1;
}
*/

// PDFファイルの時間順（降順）
function cmp_pdf_time($a, $b) {
  if ($a["time"] == $b["time"]) {
    return 0;
  }
  return ($a["time"] > $b["time"]) ? -1 : 1;
}






?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="robots" content="index,follow">
<link rel="stylesheet" type="text/css" href="./script/style.css">
<script type="text/javascript" src="./script/calender.js"></script>
<script type = "text/javascript">
<!--
function back() {
	document.back_form.submit();
}
//-->
</script>
</head>
<body>
<div id="header">
	事務システム
</div>
<div id="content" align="center">
<!--
<div class="title_box">明細</div>
-->
<h3>明細書PDFファイルの表示</h3>

<?php
if ($_SESSION['login']['kind'] == "1") {
	//if ($_SESSION['login']['id'] != $_SESSION['member_id']) {
?> 
	<div>
		<a href="menu.php">メニューへ戻る</a>
	</div>
<?php
	//}
}
?>
<br>
<?php
foreach ($PDFfileNameArray as $month=>$item) {
?>
	<h3><?=$item['name']?></h3>
<?php
	if (count($item['list']) > 0) {
?>
	<table border="1">
	<tr><th>ファイル名</th><th>出力日時</th></tr>
<?php
		foreach ($item['list'] as $PDFfile) {
?>
		<tr>
			<td><a href="detail_pdf.php?nm=<?=$PDFfile['name']?>" target="_blank"><?=$PDFfile['name'].".".$PDFfile['ext']?></a></td>
			<td><?=date("Y/m/d H:i:s", filemtime($month.$PDFfile['name'].".".$PDFfile['ext']))?></td>
		</tr>
<?php
		}
?>
	</table>
<?php
	}
}
?>
</div>

</body></html>

