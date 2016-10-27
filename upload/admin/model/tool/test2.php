<?php
//echo header('Content-Type: text/html; charset=windows-1251', true);
$string = array();
$string[] = "1.Product 2 (text in skobki)    (skobki-2)    (130, blue, LG (-130L))";
$string[] = "1. Product 2 (text in skobki) (skobki-2) (130, blue, LG (-130L))";
$string[] = "1.     Product 2 (text in skobki)    (skobki-2)    (130, blue, LG (-130L))";
$string[] = "120";
$string[] = "130, blue, LG (-130L)";
$string[] = "1.Product 2 (text in skobki)(skobki-2)(130, blue, LG (-130L))";
$string[] = "Product 2 (text in skobki) (skobki-2) (130, blue, LG (-130L))";
$string[] = "2. LG";

$pattern1 = "'^((.+\))|(.+?)) \((.+)\)$'";
$pattern2 = "'^(?:(\d+)\.)?(\s)?((.+\))|(.+?))(\s)?\((.+)\)$'";
foreach ($string as $s) {
	$matches = array();
	preg_match($pattern2, $s, $matches);

	echo "String: ".$s."\n";
	echo print_r($matches);
	echo "\n";

}
?>