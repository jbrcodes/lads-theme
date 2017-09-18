<?php

# File: functions.php

require_once 'vendor/autoload.php';  # Twig
require_once 'lib/lads_autoload.php';  # LAdS theme and widgets

#
# We don't cache on the dev/stage sites
#

if (defined('JBR_DEV') || defined('JBR_STAGE')) {
    $cacheDir = '';
} else {
    $cacheDir = WP_CONTENT_DIR . '/lads_twig_cache';
}

#
# Instantiate/initialize theme class
#

$opts = [ 'twigCacheDir' => $cacheDir ];
LadsTheme::SetOptions($opts);

$T = LadsTheme::Get();

#?>