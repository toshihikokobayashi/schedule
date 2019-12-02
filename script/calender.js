//function openwin2() {
	//window.open("./schedule_form.html", "schedule_form", "width=500,height=400");
//}

function closewin() {
window.open('about:blank','_self').close();
}

//function add_new_row(col_num){
//function add_new_row(added_row_num){
function add_new_row(){
	var item = document.getElementsByName("lesson_id[]");
	// item:配列(name='lesson_id[]'の場合、lesson_idのオブジェクト配列)
	//if(!item.options && item.length){
	if(item.length){
		len = item.length;
	}else{
		len = 1;
	}
	// 入力されたのが最後の行の場合
	// 行と空のセル(td)を追加する
	row = document.getElementById("fee_table").insertRow(len+1);
	var new_cell1 = row.insertCell(0);
	var new_cell2 = row.insertCell(1);
	var new_cell3 = row.insertCell(2);
	var new_cell4 = row.insertCell(3);
	var new_cell5 = row.insertCell(4);
	var new_cell6 = row.insertCell(5);
	var new_cell7 = row.insertCell(6);
	//if (added_row_num > 1) {
	// 料金が2件以上登録されている場合、削除ボタンの列を表示するので、列数が増える
	// 料金の1件目はＤＢに未登録の行のため、削除ボタンの列を表示しない
	//if (col_num > 5) {
	// 更新・削除画面の場合のみ、削除ボタンの列を表示
		var new_cell8 = row.insertCell(7);
	//}
	// 追加した行に最後の行の入力欄をセットする
	new_cell1.innerHTML = document.getElementById("cell1").innerHTML;
	new_cell2.innerHTML = document.getElementById("cell2").innerHTML;
	new_cell3.innerHTML = document.getElementById("cell3").innerHTML;
	new_cell4.innerHTML = document.getElementById("cell4").innerHTML;
	new_cell5.innerHTML = document.getElementById("cell5").innerHTML;
	new_cell6.innerHTML = document.getElementById("cell6").innerHTML;
	new_cell7.innerHTML = document.getElementById("cell7").innerHTML;
	// 追加した行には削除ボタンを表示しない
	var last_disp_row_no;
	last_disp_row_no = parseInt(document.getElementsByName("disp_row_no[]").item(len-1).value);
	//new_cell6.innerHTML = "	<input type='button' value='取消"+len+"' onclick='delete_row("+(len+1)+")'>";
	new_cell8.innerHTML = "	<input type='button' value='取消' onclick='delete_row("+(last_disp_row_no+1)+")'>";
	// 追加した行(td)にidをつける
	//new_cell1.id = "cell1";
	//new_cell2.id = "cell2";
	//new_cell3.id = "cell3";
	//new_cell4.id = "cell4";
	//new_cell5.id = "cell5";
//alert(last_disp_row_no);
	// 初期値をセットする（セットしないと最後の行に初期値があれば表示されてしまう）
	// item(len)のlenは0から始まるので注意
	document.getElementsByName("lesson_id[]").item(len).value = "";
	document.getElementsByName("subject_id[]").item(len).value = "0";
	document.getElementsByName("course_id[]").item(len).value = "";
	document.getElementsByName("teacher_id[]").item(len).value = "";
	document.getElementsByName("fee[]").item(len).value = "";
	document.getElementsByName("family_minus_price[]").item(len).value = "";
	document.getElementsByName("disp_row_no[]").item(len).value = (last_disp_row_no+1);
	//document.getElementsByName("row_no[]").item(len).value = len;
	document.getElementsByName("fee_no[]").item(len).value = "";
}

function delete_row(row_no){
var item = document.getElementsByName("disp_row_no[]");
	for (i=0; i<item.length; i++) {
		if (document.getElementsByName("disp_row_no[]").item(i).value == row_no) {
    	row_no = i + 1;
			break;
		}
	}
  if (row_no == 1) {
	// （ＤＢに）未登録でも、1行目であれば削除できない
		alert('1行目は取り消せません。');
	} else {
		document.getElementById("fee_table").deleteRow(row_no);
	}
}

function m_add_new_row(){
	var item = document.getElementsByName("m_lesson_id[]");
	if(item.length){
		len = item.length;
	}else{
		len = 1;
	}
	row = document.getElementById("m_fee_table").insertRow(len+1);
	var new_cell1 = row.insertCell(0);
	var new_cell2 = row.insertCell(1);
	var new_cell3 = row.insertCell(2);
	var new_cell4 = row.insertCell(3);
	var new_cell5 = row.insertCell(4);
	var new_cell6 = row.insertCell(5);
	var new_cell7 = row.insertCell(6);
	new_cell1.innerHTML = document.getElementById("m_cell1").innerHTML;
	new_cell2.innerHTML = document.getElementById("m_cell2").innerHTML;
	new_cell3.innerHTML = document.getElementById("m_cell3").innerHTML;
	new_cell4.innerHTML = document.getElementById("m_cell4").innerHTML;
	new_cell5.innerHTML = document.getElementById("m_cell5").innerHTML;
	new_cell6.innerHTML = document.getElementById("m_cell6").innerHTML;
	var last_disp_row_no;
	last_disp_row_no = parseInt(document.getElementsByName("m_disp_row_no[]").item(len-1).value);
	new_cell7.innerHTML = "	<input type='button' value='取消' onclick='m_delete_row("+(last_disp_row_no+1)+")'>";
	document.getElementsByName("m_lesson_id[]").item(len).value = "";
	document.getElementsByName("m_subject_id[]").item(len).value = "0";
	document.getElementsByName("m_course_id[]").item(len).value = "";
	document.getElementsByName("m_fee[]").item(len).value = "";
	document.getElementsByName("m_minus_price[]").item(len).value = "";
	document.getElementsByName("m_disp_row_no[]").item(len).value = (last_disp_row_no+1);
	document.getElementsByName("m_fee_no[]").item(len).value = "";
}

function m_delete_row(row_no){
var item = document.getElementsByName("m_disp_row_no[]");
	for (i=0; i<item.length; i++) {
		if (document.getElementsByName("m_disp_row_no[]").item(i).value == row_no) {
    	row_no = i + 1;
			break;
		}
	}
  if (row_no == 1) {
		alert('1行目は取り消せません。');
	} else {
		document.getElementById("m_fee_table").deleteRow(row_no);
	}
}

/*
function delete_last_row(min_row_count){
	var item = document.getElementsByName("lesson_id[]");
alert(item.length+"-"+min_row_count);
	//var cnt = 0;
	//for (i=0; i<item.length; i++) {
	//}
	//var item = document.getElementsByName("lesson_id[]");
	//if (min_row_count == 1) { min_row_count = 2; } // 「ＤＢに登録されている行数＋新規登録の行」のため1行たす
//alert(item.length+"_"+min_row_count);//入力欄の数、新規登録行を含むrow_num
  //if (item.length == 1 && min_row_count == 1) {
  if (item.length == 1) {
	// （ＤＢに）未登録でも、1行目であれば削除できない
		alert('1行目は取り消せません。');
  //} else if(item.length <= min_row_count)){
  } else if(item.length == min_row_count){
		alert('登録済みデータの行は取り消せません。\nデータを削除したい場合は、\n右にあります削除ボタンを使ってください。。');
	} else {
		document.getElementById("fee_table").deleteRow(-1);
	}
}
*/