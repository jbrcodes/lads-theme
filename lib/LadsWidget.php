<?php

#
# File: themes/lads/lib/LadsWidget.php
# An abstract superclass for all svieta widgets
#

class LadsWidget extends WP_Widget {

	# --------------------------------------------------------------------------
	# Class Methods
	# --------------------------------------------------------------------------

	# --------------------------------------------------------------------------
	# Constructor
	# --------------------------------------------------------------------------

	public function __construct($idBase, $name, $widgetOpts = array()) {
		parent::__construct($idBase, $name, $widgetOpts);
	}

	# --------------------------------------------------------------------------
	# Default Required Methods
	# --------------------------------------------------------------------------

	public function widget($args, $instance) {
		echo $args['before_widget'];
		$title = $this->_getTitle($args, $instance);
		if ($title)
			echo $args['before_title'], $title, $args['after_title'];
		echo $this->_getContent($args, $instance);
		echo $args['after_widget'];
	}

	public function update($new_instance, $old_instance) {
		return $new_instance;
	}

	public function form($instance) {
	}

	# --------------------------------------------------------------------------
	# Helpers
	# --------------------------------------------------------------------------

	protected function _twigRender($args = []) {
		$T = LadsTheme::Get();
		return $T->renderWidget(get_class($this), $args);
	}
	
	# --------------------------------------------------------------------------
	# Default Private Methods
	# --------------------------------------------------------------------------

	protected function _getTitle($args, $instance) {
		return '';
	}

	protected function _getContent($args, $instance) {
		return '';
	}

}

#?>
