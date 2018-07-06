<?php

require_once(__DIR__ . '/../../config.php');
require_once('./classes/form/handleitem_form.php');

global $PAGE;
global $DB;
global $USER;

use coding_exception;
use block_stash\external\user_item_summary_exporter;

$courseid = required_param('courseid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$report_page = optional_param('report_page', '0',PARAM_INT);

require_login($courseid);

$manager = \block_stash\manager::get($courseid);
$manager->require_enabled();
$manager->require_manage();

$context = context_course::instance($courseid);
$url = new moodle_url('/blocks/stash/handle_items.php', array('userid' => $userid, 'courseid' => $courseid));

$title = get_string('report','block_stash');
$subtitle = get_string('handleitems','block_stash');

$PAGE->set_context($context);
$PAGE->set_pagelayout('course');
$PAGE->set_title($title);
$PAGE->set_heading($manager->get_context()->get_context_name());
$PAGE->set_url($url);

$returnurl = new moodle_url('/blocks/stash/report.php', ['courseid' => $courseid]);
if($report_page != '0'){

	$returnurl = new moodle_url('/blocks/stash/report.php', ['courseid' => $courseid, 'page' => $report_page]);
}


$PAGE->navbar->add(get_string('stash', 'block_stash'));
$PAGE->navbar->add($title, $returnurl);
$PAGE->navbar->add($subtitle);

$renderer = $PAGE->get_renderer('block_stash');

//obtención de los items del curso para enviarlos al formulario
$items = $manager -> get_items();

foreach ($items as $item) {
	
	$id = $item -> get_id();
	$name = $item -> get_name();
	$itemsf[$id] = $name;
}

$form = new handleitem_form(null, array('courseid' => $courseid, 'userid' => $userid, 'itemsf' => $itemsf));

if($data = $form->get_data()){

	$courseid = $data -> courseid;
	$userid = $data -> userid;
	$itemid = $data -> itemid;
	$itemquantity = $data -> itemquantity;
	$report_page = $data -> report_page;

	//to check courseid
	$form_manager = \block_stash\manager::get($courseid);

	//to check userid
	$form_manager -> require_acquire_items($userid);

	//to check wehter the item belongs to the stash course
	if (!\block_stash\item::is_item_in_stash($itemid, $form_manager->get_stash()->get_id())) {

		throw new coding_exception("Invalid item");
	} 

    //to check itemquantity
	if($itemquantity < 0){

		throw new coding_exception("Invalid item quantity");
	}

    //to delete the item
	if($itemquantity == 0){

		$DB->delete_records(\block_stash\user_item::TABLE, ['itemid' => $itemid, 'userid' => $userid]);
		
	} else {

    	//we check wether the user already has the item
		if($DB -> record_exists('block_stash_user_items', array('itemid' => $itemid, 'userid' => $userid))){

			$user_item_id = $DB -> get_field('block_stash_user_items', 'id', array('itemid' => $itemid, 'userid' => $userid));
			$user_item = new \block_stash\user_item($user_item_id);
			$user_item -> set_quantity($itemquantity);
			$user_item -> update();

		} else{

			$params = ['userid' => $userid, 'itemid' => $itemid]; 
			$user_item = new \block_stash\user_item(null, (object) $params);
			$user_item -> create();
			$user_item -> set_quantity($itemquantity);
			$user_item -> update();
		}

		$relatedusername = $DB->get_field('user','username',['id' => $userid]);
		$item = new \block_stash\item($itemid);
		$itemname = $item->get_name();
		$event = \block_stash\event\item_acquired::create(array(
			'context' => $context,
			'userid' => $USER->id,
			'courseid' => $courseid,
			'objectid' => $item->get_id(),
			'relateduserid' => $userid,
			'other' => array('quantity' => $itemquantity, 'relatedusername' => $relatedusername, 'droportrade' => 'modification', 
				'username' => $USER->username, 'itemname' => $itemname)
			)
		);
		$event->trigger();
	}

	$saveandnext = !empty($data->saveandnext);
	unset($data->saveandnext);

	if ($saveandnext) {

		redirect(new moodle_url('/blocks/stash/handle_items.php', ['userid' => $userid, 'courseid' => $courseid, 'report_page' => $report_page]));
	}

	redirect($returnurl);

} elseif( $form->is_cancelled()){

	redirect($returnurl);
}

//To print user's item inventory
$items = $manager->get_all_user_items_in_stash($userid);
if (!empty($items)) {
	
	$html = '';
	foreach ($items as $item) {
		$exporter = new user_item_summary_exporter([], [
			'context' => $manager->get_context(),
			'item' => $item->item,
			'useritem' => $item->useritem,
			]);
		$data = $exporter->export($renderer);
		$inventory .= $renderer->render_from_template('block_stash/user_item_small', $data);
	}
}


echo $OUTPUT->header();
echo $OUTPUT->heading($title,2);
echo $renderer->navigation($manager, 'report');
$subtitle = $subtitle . $OUTPUT->help_icon('handleitems', 'block_stash');
echo $OUTPUT->heading($subtitle, 3);
echo $OUTPUT->heading(get_string('userinventory', 'block_stash') . ' ' . $inventory, 4);
$form->display();
echo $OUTPUT->footer();
?>