<?php

require('../../config.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/report/log/locallib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/lib/tablelib.php');
require_once('./classes/output/eventshistory_renderable.php');

$courseid 	= required_param('courseid', PARAM_INT); // Course to display
$userid 	= required_param('userid', PARAM_INT); // User to display
$date       = optional_param('date', 0, PARAM_INT); // Date to display.
$page        = optional_param('page', '0', PARAM_INT);     // Which page to show.
$perpage     = optional_param('perpage', '100', PARAM_INT); // How many per page.
$chooselog   = optional_param('chooselog', false, PARAM_BOOL); // Print the query 
$logreader      = optional_param('logreader', '', PARAM_COMPONENT); // Reader which will be used for displaying logs.


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


$url = new moodle_url("/blocks/stash/events_history.php", $params);


$PAGE->set_url('/blocks/stash/events_history.php', array('courseid' => $courseid, 'userid' => $userid));
$PAGE->set_pagelayout('report');


//get course details
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
require_login($course);
$context = context_course::instance($course->id);


//check for plugin and capabilities
$manager = \block_stash\manager::get($courseid);
$manager->require_enabled();
$manager->require_manage();


if (!empty($page)) {
	$strlogs = get_string('eventshistory'). ": ". get_string('page', 'block_stash', $page + 1);
} else {
	$strlogs = get_string('eventshistory');
}

$PAGE->set_title($course->shortname .': '. $strlogs);
$PAGE->set_heading($course->fullname);


$reportlog = new eventshistory_renderable($logreader, $course, $userid, $chooselog, 
	true, $url, $date, $page, $perpage, 'timecreated DESC');
$readers = $reportlog->get_readers();
$output = $PAGE->get_renderer('block_stash');


if (empty($readers)) {

	//no logstore
	echo $output->header();
	echo $output->heading(get_string('nologreaderenabled', 'block_stash'));

} else {

	if (!empty($chooselog)) {

        // Delay creation of table, till called by user with filter.
		$reportlog->setup_table();

		echo $output->header();

		echo $output->render_eventshistory_table($reportlog);

	} else {

		echo $output->header();
		echo $output->heading(get_string('chooselogs') .':');
		echo $output->render_eventshistory_table($reportlog);
	}
}

echo $output->footer();
