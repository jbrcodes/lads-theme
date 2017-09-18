<?php

#
# File: themes/lads/lib/LangWidget.php
# Show the WPML language switcher
#

class LangWidget extends LadsWidget {

	# --------------------------------------------------------------------------
	# Constructor
	# --------------------------------------------------------------------------

	public function __construct() {
		$widgetOps = array( 'description' => 'Show the LAdS language switcher' );
		parent::__construct('lads-lang', 'LAdS Language', $widgetOps);
	}

	# --------------------------------------------------------------------------
	# Overloaded Methods
	# --------------------------------------------------------------------------

	protected function _getContent($args, $instance) {
		ob_start();
		do_action('wpml_add_language_selector');
		# The prior line makes bad category URLs for foreign langs, like '/fr/fr/'
		# This whole widget was introduced for fix v2.0.1 (!!)
		$html = ob_get_clean();
		#$html = preg_replace('!/(fr|de)/\1/!', "/\$1/", $html);
		return $html;
	}

}

#?>