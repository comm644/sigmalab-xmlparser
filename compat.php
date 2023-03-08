<?php

use Sigmalab\IsKPHP;

require_once __DIR__.'/../IsKPHP.php';

if (IsKPHP::$isKPHP) {
	define('XML_OPTION_CASE_FOLDING', 1);
	define('XML_OPTION_TARGET_ENCODING', 2);
	define('XML_OPTION_SKIP_TAGSTART', 3);
	define('XML_OPTION_SKIP_WHITE', 4);
}
