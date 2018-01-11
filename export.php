<?php
header("content-type:text/html;charset=utf8");
$check = !empty($_POST['check']) ? $_POST['check'] : '';
if (empty($check)) {
	echo"<script>alert('请选择等级...');history.go(-1);</script>";die;
}
if ($_FILES["file"]["error"] > 0) {
	$error = "Error: " . $_FILES["file"]["error"] . "　出错了。。";
	echo "<script>alert('$error');history.go(-1);</script>";die;
}
// 获取文件 二进制流
$file_str = file_get_contents($_FILES["file"]["tmp_name"]);
// 设置输出路径
$upload_name = uniqid();
$put_path = 'cache/' . $upload_name .'.txt';
$filename = $_FILES["file"]["name"];
// 设置脚本最大执行时间 为无限制
set_time_limit(0);
// 连接数据库
class MyDB extends SQLite3
{
    public function __construct()
    {
        $this->open('db.s3db');
    }
}
$db = new MyDB();
if(!$db){
	echo $db -> lastErrorMsg();
	die;
}
$arr = explode("\n", $file_str);
$arr3 = [];
foreach ($arr as $v) {
	$pattern = "/(\.|\:|\s)/";
	$arr1 = preg_split($pattern, $v);
	$arr2 = [];
	foreach ($arr1 as $val) {
		if (!empty($val)) {
			$arr2[] = $val;
		}
	}
	if (!empty($arr2)) {
		$arr3[] = $arr2;
	}
}
foreach ($arr3 as $v3) {
	foreach ($v3 as $val3) {
		$put = '';
		// $i = 0;
		if (is_numeric($val3)) {
			
			$put .= "\n\n";
			$put .= $val3;
			$put .= "、";
			$i = 1;
		} elseif ($val3 == '(low)') {
			$put .= '(low)　';
		} elseif ($val3 == '(high)') {
			$put .= "\n(high)　";
		} else {
			$sql = "select content 
		            from pyrus_word_translation c
		            join pyrus_word i
		            on c.word_id = i.id 
		            where i.lemma = '$val3'
		            and c.group_id = {$check}";
			$ret = $db->query($sql);
			$row = $ret->fetchArray(SQLITE3_ASSOC);
			if ($i == 1) {
				$put .= $val3 . ' ' . $row['content'] . "：\n";
				$i++;
			}else{
				$put .= $val3 . ' ' . $row['content'] . '　';
			}
		}	
		// 追加输出到文件
		file_put_contents($put_path, $put, FILE_APPEND);
	}
}
// 下载文件
header('Content-Type:text/plain'); //指定下载文件类型
header('Content-Disposition: attachment; filename="' . $filename . '"'); //指定下载文件的描述
header('Content-Length:'.filesize($put_path)); //指定下载文件的大小
//将文件内容读取出来并直接输出，以便下载
readfile($put_path);