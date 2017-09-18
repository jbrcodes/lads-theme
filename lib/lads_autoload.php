<?php

spl_autoload_register(function($className) {
	$abspath = sprintf('%s/lib/%s.php', get_template_directory(), $className);
	if ( file_exists($abspath) )
		include $abspath;
});

#?>