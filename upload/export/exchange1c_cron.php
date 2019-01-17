<?php

$ch = curl_init("http://you.site.ru/export/exchange1c.php?module=cronImport");
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_exec ($ch);
curl_close ($ch);

?>