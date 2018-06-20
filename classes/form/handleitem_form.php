<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class handleitem_form extends moodleform {

	public function definition(){

		global $CFG;

		$mform = $this->_form;

		$mform->addElement('header', 'generalhdr', get_string('general'));

		$mform->addElement('select', 'itemid', get_string('item', 'block_stash'), $this->_customdata['itemsf']);

		$mform->addElement('text', 'itemquantity', get_string('itemquantity', 'block_stash'));
		$mform->setType('itemquantity', PARAM_INT);
		$mform->addRule('itemquantity',get_string('itemquantityexception', 'block_stash'),'numeric',null,'client');
		$mform->addRule('itemquantity', null, 'required', null, 'client');
        $mform->addHelpButton('itemquantity', 'itemquantity', 'block_stash');

		//Hidden
		$mform->addElement('hidden', 'courseid', $this->_customdata['courseid']);
		$mform->addElement('hidden', 'userid', $this->_customdata['userid']);

        // Buttons.
		$buttonarray = [];
		$buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('submit'));
		$buttonarray[] = &$mform->createElement('cancel');
		$mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
		$mform->closeHeaderBefore('buttonar');
	}

	function validation($data, $files){

		return array();
	}
}