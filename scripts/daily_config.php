<?php
$file = BLOTTO_DAILY_CFG;
if (is_readable($file) && gmdate("Y-m-d",filemtime($file))==gmdate('Y-m-d')) {
	error_log("daily config from today");
	return;
}
error_log("new daily config required");

