<?php
//echo header('Content-Type: text/html; charset=windows-1251', true);
$string = array();
$string[] = "1.Name (text1)  (text2) (130, blue, LG (-130L))";
$string[] = "1. Name (text1) (text2) (130, blue, LG (-130L))";
$string[] = "120";
$string[] = "Samsung (image)";
$string[] = "130, blue, LG (-130L)";
$string[] = "Product 2 (text in skobki) (130, blue, LG (-130L))";
$string[] = "2. LG";
$string[] = "2.LG";

$pattern = array();
$pattern[1] = "'^(.+\)) \((.+)\)$'";
$pattern[2] = "'^(?:(\d+)\.)?(.+\)) \((.+)\)$'";
$pattern[3] = "'^((.+\)) (\((.+)\)))?$'";
$pattern[4] = "'^(?:(\d+)\.)?(\s)?((.+\))(\s*)?\((.+)\))$'";
$pattern[5] = "'^(?:(\d+)\.)?(\s)?(.+)$'";
$pattern[6] = "'^((\d+)?\.)?(\s+)?((.+\)?) \((.+)\)$|(.+)$)'";

echo "<pre>";
foreach ($string as $v => $s) {

	$p = 6;
	$str = $string[$v];
	
	echo "Variant ".$v." =======================================================\n\n";
	echo "String: ".$str."\n";
	$name_start = 0;
	$opt_start = 0;

	$ps = 0;
	$end = 0;
	for ($i = strlen($str); $i > 0; $i-- ) {
		if ($str[$i] == ")") {
			$ps++;
			if (!$end) {
				$end = $i;
			}
		}
		if ($str[$i] == "(") {
			$ps--;
		}
		if ($str[$i] == "(" && $ps == 0) {
			$option = mb_substr($str, $i, $end);
			$opt_start = $i;
			echo "Option: '".$option."'\n";
			break;
		}
		//echo $str[$i]." - ".$ps."\n";
	}
	if (!$end) {
		$opt_start = strlen($str);
	}
	
	$len = 0;
	for ($i = 0; $i < strlen($str); $i++ ) {
		if (is_numeric($str[$i])) {
			$len++;
			if ($str[$i+1] == ".") {
				$order = mb_substr($str, 0, $len);
				$name_start = $i+2;
				echo "Order: '".$order."'\n";
				break;
			}
		}
		//echo $str[$i]."\n";
	}
	echo "Name: '".trim(mb_substr($str, $name_start, $opt_start-$name_start))."'\n";
	
	
	//echo mb_substr($str, 1, 1, 'UTF-8')."\n";
	//echo $str;
	//break;

}
echo "</pre>";
?>