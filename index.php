<?php

define(DS, DIRECTORY_SEPARATOR);
define(ROOT_DIRECTORY, dirname(__FILE__));
define(PUBLIC_DIRECTORY, 'public');
define(INDEX, 'index.php');

define('REWRITE_ENGINE', false);

if(is_file(ROOT_DIRECTORY . DS . PUBLIC_DIRECTORY . DS . INDEX)) {
	require_once(ROOT_DIRECTORY . DS . PUBLIC_DIRECTORY . DS . INDEX);
} else {
	echo 'System cannot find public file root';
}

?>