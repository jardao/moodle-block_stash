<?php

require_once(__DIR__ . '/../../config.php');
require_once('./classes/form/handleitem_form.php');

global $PAGE;

$courseid = required_param('courseid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

require_login($courseid);

$manager = \block_stash\manager::get($courseid);
$manager->require_enabled();
$manager->require_manage();

$context = context_course::instance($courseid);
$url = new moodle_url('/blocks/stash/handle_items.php', array('userid' => $userid, 'courseid' => $courseid));

//estas lineas las extraigo del page_helper.php
$title = 'Report';
$subtitle = 'Handling items';

$PAGE->set_context($context);
$PAGE->set_pagelayout('course');
$PAGE->set_title($title);
$PAGE->set_heading($manager->get_context()->get_context_name());
$PAGE->set_url($url);

$returnurl = new moodle_url('/blocks/stash/report.php', ['courseid' => $manager->get_courseid()]);

$PAGE->navbar->add(get_string('stash', 'block_stash'));
$PAGE->navbar->add($title, $returnurl);
$PAGE->navbar->add($subtitle);

$renderer = $PAGE->get_renderer('block_stash');
//$form = new \block_stash\form\handleitem_form();
$form = new handleitem_form(null, array('courseid' => $courseid, 'userid' => $userid));

if($data = $form->get_data()){

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