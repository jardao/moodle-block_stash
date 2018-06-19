<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class handleitem_form extends moodleform {

	public function definition(){

		global $CFG;

		$mform = $this->_form;

		$mform->addElement('header', 'generalhdr', get_string('general'));

		$mform->addElement('select','itemid','Item id',$this->_customdata['itemsf']);

		$mform->addElement('text', 'itemquantity', 'Item quantity');
		$mform->setType('itemquantity', PARAM_INT);

		//Hidden
		$mform->addElement('hidden', 'courseid', $this->_customdata['courseid']);
		$mform->addElement('hidden', 'userid', $this->_customdata['userid']);

        // Buttons.
		$buttonarray = [];
		$buttonarray[] = &$mform->createElement('submit', 'submitbutton', 'submit');
		$buttonarray[] = &$mform->createElement('cancel');
		$mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
		$mform->closeHeaderBefore('buttonar');
	}

	function validation($data, $files){

		return array();
	}
}