<?php

require_once(__DIR__ . '/../../config.php');
require_once('./classes/form/handleitem_form.php');

global $PAGE;
global $DB;

use coding_exception;

$courseid = required_param('courseid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

require_login($courseid);

$manager = \block_stash\manager::get($courseid);
$manager->require_enabled();
$manager->require_manage();

$context = context_course::instance($courseid);
$url = new moodle_url('/blocks/stash/handle_items.php', array('userid' => $userid, 'courseid' => $courseid));

$title = 'Report';
$subtitle = 'Handling items';

$PAGE->set_context($context);
$PAGE->set_pagelayout('course');
$PAGE->set_title($title);
$PAGE->set_heading($manager->get_context()->get_context_name());
$PAGE->set_url($url);

$returnurl = new moodle_url('/blocks/stash/report.php', ['courseid' => $courseid]);

$PAGE->navbar->add(get_string('stash', 'block_stash'));
$PAGE->navbar->add($title, $returnurl);
$PAGE->navbar->add($subtitle);

$renderer = $PAGE->get_renderer('block_stash');
$form = new handleitem_form(null, array('courseid' => $courseid, 'userid' => $userid));

if($data = $form->get_data()){

	$courseid = $data -> courseid;
	$userid = $data -> userid;
	$itemid = $data -> itemid;
	$itemquantity = $data -> itemquantity;

	//to check courseid
	$form_manager = \block_stash\manager::get($courseid);

	//to check userid
	$form_manager -> require_acquire_items($userid);

	//to check wehter the item belongs to the stash course
    if (!\block_stash\item::is_item_in_stash($itemid, $form_manager->get_stash()->get_id())) {

    	throw new coding_exception("Invalid item");
	} 

    //to check itemquantity
    if($itemquantity < 1){

    	throw new coding_exception("Invalid item quantity");
    }

    //we check wether the user already has the item
    if($DB -> record_exists('block_stash_user_items', array('itemid' => $itemid, 'userid' => $userid))){

    	$DB -> set_field('block_stash_user_items', 'quantity', $itemquantity, array('itemid' => $itemid, 'userid' => $userid));
    
    } else{

    	$params = ['userid' => $userid, 'itemid' => $itemid]; 
    	$user_item = new \block_stash\user_item(null, (object) $params);
    	$user_item -> create();
    	$user_item -> set_quantity($itemquantity);
    	$user_item -> update();
    }

	redirect($returnurl);

} elseif( $form->is_cancelled()){

	redirect($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($title,2);
echo $renderer->navigation($manager, 'report');
echo $OUTPUT->heading($subtitle, 3);
$form->display();
echo $OUTPUT->footer();
?>