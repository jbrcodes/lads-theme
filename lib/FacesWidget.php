<?php

#
# File: themes/lads/lib/FacesWidget.php
# Show a random face from the wp-content/uploads/faces directory
#

class FacesWidget extends LadsWidget {

	# --------------------------------------------------------------------------
	# Constructor
	# --------------------------------------------------------------------------

	public function __construct() {
		$widgetOps = array( 'description' => 'Show a random face of a LAdS kid' );
		parent::__construct('lads-faces', 'LAdS Faces', $widgetOps);
	}

	# --------------------------------------------------------------------------
	# Overloaded Methods
	# --------------------------------------------------------------------------

	protected function _getContent($args, $instance) {
        $url = FacesCache::GetRandomUrl();
		
		return $this->_twigRender(['imgUrl' => $url]);
	}

}

#?>