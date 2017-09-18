<?php

#
# File: themes/lads/lib/TwigWidget.php
# Render a .twig file from themes/lads/twig/widgets
#

class TwigWidget extends LadsWidget {

	# --------------------------------------------------------------------------
	# Constructor
	# --------------------------------------------------------------------------

	public function __construct() {
		$widgetOps = array( 'description' => 'LAdS Twig Widget' );
		parent::__construct('twig', 'LAdS Twig', $widgetOps);
	}

	# --------------------------------------------------------------------------
	# Overloaded Methods
	# --------------------------------------------------------------------------
	
	function form($instance) {
		$args = [
			'f_id' => $this->get_field_id('fn'),
			'f_name' => $this->get_field_name('fn'),
			'f_val' => isset($instance['fn']) ? $instance['fn'] : ''
		];

		$T = LadsTheme::Get();
		echo $T->renderWidget('TwigWidget_form', $args);
	}

	function update($newInstance, $oldInstance) {
		$instance = [];
		$instance['fn'] = strip_tags( $newInstance['fn'] );

		return $instance;
	}
	
	protected function _getContent($args, $instance) {
		$T = LadsTheme::Get();
		return $T->renderWidget('TwigWidget_' . $instance['fn'], $args);
	}

}

#?>