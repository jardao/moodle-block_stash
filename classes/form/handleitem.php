<?php

//namespace block_stash\form;
//defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class handleitem_form extends moodleform {

	public function definition(){

		global $CFG;

		$mform = $this->_form;

		/*$mform->addElement('text', 'email', 'email');
		$mform->setType('email', PARAMA_NOTAGS);
		$mform->setDefault('email', 'Please enter email');*/
	}

	function validation($data, $files){

		return array();
	}
}