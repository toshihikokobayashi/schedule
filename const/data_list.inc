<?php
/*
  $teacher_list = array(""=>"（講師全員）","0"=>"テスト先生","1"=>"高木", "2"=>"鳴海","3"=>"西薗",
												"4"=>"戸塚","5"=>"進藤","6"=>"井上","7"=>"沼崎",
												 "8"=>"Johnny","9"=>"William","10"=>"Jinky","11"=>"Brian", "12"=>"Tazim", "13"=>"Krystle", "14"=>"Katrine");
*/
// テスト先生を含むテスト用
  $teacher_list = array(
												"0"=>array('no'=>"0",'furigana'=>"てすとせんせい",'name'=>"テスト先生",'lesson_id'=>"1"),
												"1"=>array('no'=>"15",'furigana'=>"ゆげ",'name'=>"弓削",'lesson_id'=>"1"),
												"2"=>array('no'=>"4",'furigana'=>"たかぎ",'name'=>"高木",'lesson_id'=>"1"),
												"3"=>array('no'=>"6",'furigana'=>"なるみ",'name'=>"鳴海",'lesson_id'=>"1"),
												"4"=>array('no'=>"7",'furigana'=>"にしぞの",'name'=>"西薗",'lesson_id'=>"1"),
												"5"=>array('no'=>"9",'furigana'=>"",'name'=>"Brian",'lesson_id'=>"2"),
												"6"=>array('no'=>"10",'furigana'=>"",'name'=>"Jinky",'lesson_id'=>"2"),
												"7"=>array('no'=>"11",'furigana'=>"",'name'=>"Johnny",'lesson_id'=>"2"),
												"8"=>array('no'=>"12",'furigana'=>"",'name'=>"Krystle",'lesson_id'=>"2"), 
												"9"=>array('no'=>"13",'furigana'=>"",'name'=>"Katrina",'lesson_id'=>"2"), 
												"10"=>array('no'=>"14",'furigana'=>"",'name'=>"Tazim",'lesson_id'=>"2"), 
												"11"=>array('no'=>"15",'furigana'=>"",'name'=>"William",'lesson_id'=>"2"),
												"12"=>array('no'=>"2",'furigana'=>"しらい",'name'=>"白井",'lesson_id'=>"2"),
												"13"=>array('no'=>"14",'furigana'=>"いしだ",'name'=>"石田",'lesson_id'=>"3"), 
												"14"=>array('no'=>"1",'furigana'=>"いのうえ",'name'=>"井上",'lesson_id'=>"3"),
												"15"=>array('no'=>"3",'furigana'=>"しんどう",'name'=>"進藤",'lesson_id'=>"3"),
												"16"=>array('no'=>"5",'furigana'=>"とつか",'name'=>"戸塚",'lesson_id'=>"3"),
												"17"=>array('no'=>"8",'furigana'=>"ぬまざき",'name'=>"沼崎",'lesson_id'=>"3"),
									);
/*
  $teacher_list = array("1"=>array('furigana'=>"いのうえ",'name'=>"井上"),
												"2"=>array('furigana'=>"しらい",'name'=>"白井"),"3"=>array('furigana'=>"しんどう",'name'=>"進藤"),
												"4"=>array('furigana'=>"たかぎ",'name'=>"高木"),"5"=>array('furigana'=>"とつか",'name'=>"戸塚"),
												"6"=>array('furigana'=>"なるみ",'name'=>"鳴海"),"7"=>array('furigana'=>"にしぞの",'name'=>"西薗"), "8"=>array('furigana'=>"ぬまざき",'name'=>"沼崎"),
												"9"=>array('furigana'=>"",'name'=>"Brian"),"10"=>array('furigana'=>"",'name'=>"Jinky"),"11"=>array('furigana'=>"",'name'=>"Johnny"),
												"12"=>array('furigana'=>"",'name'=>"Krystle"), "13"=>array('furigana'=>"",'name'=>"Katrina"), "14"=>array('furigana'=>"",'name'=>"Tazim"),
												"14"=>array('furigana'=>"",'name'=>"William"), "15"=>array('furigana'=>"いしだ",'name'=>"石田"));
*/

$north_id_list = array(
"塾_北口校" => "kfmapno5okooaug0dt3o4sfcqc",
"English_north" => "s29frvoab0j0q8rkvua0gtpjjo",
"事務_北口校" =>"16tsovs1gm54ae6ao5irkhnjs8",
"教材開発_北口校" =>"o4mgct1p0cnluhi6kqban4va7k",
"塾_北口校_3F" => "hachioji-sakura.com_nmcchj9ini9e33h2vnq5t6dp04",
"塾_北口校_4F" => "hachioji-sakura.com_17bmrjgcd20eqr9kqvik80vf7g",
"English_north_3F" => "hachioji-sakura.com_c7taliv4bbasek95f8nrn3jdik",
"English_north_4F" => "hachioji-sakura.com_e1euf1ekt89it4a6koge4jngak"
);
$south_id_list = array(
"塾_南口校" => "b11fj9hhpm14s64bocgq15r3do",
"English_south" => "ma913l65msq5acuk3ma16aqg0c",
"事務_南口校" => "53c206j8gpnis6rhbfbaegc48k",
"習い事_南口校" => "o9qcbs2l2bv8v15vbjjri9u3oc",
"教材開発_南口校" => "nf4l4v74vgrf41o3ei7o1ufbk4"
);
$home_id_list = array(
"塾_本校" => "egs4bmehdpecc4kp7t65rskqhs",
"English_home" => "gt4gj2bh1s4uos9q4nagr2rhs8",
"事務_本校" => "bqe65ja614f9it0qougsaf90e0",
"ピアノ" => "daqpa0f81lktuho4e4ktrcct78",
"習い事_本校" => "tn9dbipm2t80kg0ifv5e0aej4c"
);

$place_array = array(
								"1"=>array("jname"=>"北口校", "ename"=>"North", "roomnum"=>3,  "idlist"=>$north_id_list),
								"2"=>array("jname"=>"南口校", "ename"=>"South", "roomnum"=>2,  "idlist"=>$south_id_list),
								"3"=>array("jname"=>"本校", "ename"=>"Home",  "roomnum"=>1, "idlist"=>$home_id_list)
								);

?>