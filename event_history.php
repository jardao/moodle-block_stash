<?php

//  mfernandriu
//  This script allows the user to check the block_stash plugin's events related to some course user

require('../../config.php');

require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/report/log/locallib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/lib/tablelib.php');

require_once('./classes/output/event_history_renderable.php');

$courseid 	= required_param('courseid', PARAM_INT); // User's course
$userid 	= required_param('userid', PARAM_INT); // User to display
$date       = optional_param('date', 0, PARAM_INT); // Date to display.
$page        = optional_param('page', '0', PARAM_INT);     // Which page to show.
$perpage     = optional_param('perpage', '100', PARAM_INT); // How many per page.
$chooselog   = optional_param('chooselog', false, PARAM_BOOL); // Print the query 
$logreader      = optional_param('logreader', '', PARAM_COMPONENT); // Reader which will be used for displaying logs
$report_page 	= optional_param('report_page', '0', PARAM_INT); // Page of report.php's table to redirect to  


$params = array();

$params['courseid'] = $courseid;
$params['userid'] = $userid;

if ($date !== 0) {
	$params['date'] = $date;
}
if ($page !== '0') {
	$params['page'] = $page;
}
if ($perpage !== '100') {
	$params['perpage'] = $perpage;
}
if ($chooselog) {
	$params['chooselog'] = $chooselog;
}
if ($logreader !== '') {
	$params['logreader'] = $logreader;
}
if ($report_page !== '0') {
	$params['report_page'] = $report_page; 
}

$title = get_string('report','block_stash');
$subtitle = get_string('eventhistory','block_stash');

$url = new moodle_url("/blocks/stash/event_history.php", $params);

$PAGE->set_url('/blocks/stash/event_history.php', array('courseid' => $courseid, 'userid' => $userid));
$PAGE->set_pagelayout('report');


// Get course details
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
require_login($course);
$context = context_course::instance($course->id);


// Check for plugin and capabilities
$manager = \block_stash\manager::get($courseid);
$manager->require_enabled();
$manager->require_manage();


if (!empty($page)) {
	$strlogs = get_string('eventhistory'). ": ". get_string('page', 'block_stash', $page + 1);
} else {
	$strlogs = get_string('eventhistory');
}

$PAGE->set_context($context);
$PAGE->set_title($title);
$PAGE->set_heading($manager->get_context()->get_context_name());

$returnurl = new moodle_url('/blocks/stash/report.php', ['courseid' => $courseid]);
if($report_page != '0'){

	$returnurl = new moodle_url('/blocks/stash/report.php', ['courseid' => $courseid, 'page' => $report_page]);
}

$PAGE->navbar->add(get_string('stash', 'block_stash'));
$PAGE->navbar->add($title, $returnurl);
$PAGE->navbar->add($subtitle);

// Get render and prepare renderable
$renderer = $PAGE->get_renderer('block_stash');

$reportlog = new event_history_renderable($logreader, $course, $userid, $chooselog, 
	true, $url, $date, $page, $perpage, 'timecreated DESC', $report_page);
$readers = $reportlog->get_readers();
$output = $PAGE->get_renderer('block_stash');


if (empty($readers)) {

	// No logstore
	echo $output->header();
	echo $output->heading(get_string('nologreaderenabled', 'block_stash'));

} else {

	if (!empty($chooselog)) {

        // Delay creation of table, till called by user with filter.
		$reportlog->setup_table();

		echo $output->header();
		echo $OUTPUT->heading($title,2);		
		echo $renderer->navigation($manager, 'report');
		$subtitle = $subtitle . $OUTPUT->help_icon('eventhistory', 'block_stash');
		echo $OUTPUT->heading($subtitle, 3);
		echo $output->render_event_history_table($reportlog);

	} else {

		echo $output->header();
		echo $OUTPUT->heading($title,2);		
		echo $renderer->navigation($manager, 'report');
		$subtitle = $subtitle . $OUTPUT->help_icon('eventhistory', 'block_stash');
		echo $OUTPUT->heading($subtitle, 3);
		echo $output->render_event_history_table($reportlog);
	}
}

echo $output->footer();
