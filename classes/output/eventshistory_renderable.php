<?php

//namespace block_stash\output;

defined('MOODLE_INTERNAL') || die;

require_once('eventshistory_table.php');

class eventshistory_renderable implements renderable {

	/** @var manager log manager */
	protected $logmanager;

	/** @var string selected log reader pluginname */
	public $selectedlogreader = null;

	/** @var int page number */
	public $page;

	/** @var int perpage records to show */
	public $perpage;

	/** @var stdClass course record */
	public $course;

	/** @var moodle_url url of report page */
	public $url;

	/** @var int selected date from which records should be displayed */
	public $date;

	/** @var int selected user id for which logs are displayed */
	public $userid;

	/** @var bool show report */
	public $showreport;

	/** @var bool show selector form */
	public $showselectorform;

	/** @var string order to sort */
	public $order;

	/** @var table_log table log which will be used for rendering logs */
	public $tablelog;

	/** @var string report.php's table page used for redirecting */
	public $report_page;

	public function __construct($logreader = "", $course = 0, $userid = 0, $showreport = true, 
		$showselectorform = true, $url = "", $date = 0, $page = 0, $perpage = 100, $order = "timecreated ASC", $report_page) {

		global $PAGE;

        // Use first reader as selected reader, if not passed.
		if (empty($logreader)) {
			$readers = $this->get_readers();
			if (!empty($readers)) {
				reset($readers);
				$logreader = key($readers);
			} else {
				$logreader = null;
			}
		}
        // Use page url if empty.
		if (empty($url)) {
			$url = new moodle_url($PAGE->url);
		} else {
			$url = new moodle_url($url);
		}
		$this->selectedlogreader = $logreader;
		$url->param('logreader', $logreader);

        // Use site course id, if course is empty.
		if (!empty($course) && is_int($course)) {
			$course = get_course($course);
		}
		$this->course = $course;

		$this->userid = $userid;
		$this->date = $date;
		$this->page = $page;
		$this->perpage = $perpage;
		$this->url = $url;
		$this->order = $order;
		$this->showreport = $showreport;
		$this->showselectorform = $showselectorform;
		$this->report_page = $report_page;
	}

	//para obtener la lista de readers
	public function get_readers($nameonly = false) {
		if (!isset($this->logmanager)) {
			$this->logmanager = get_log_manager();
		}

		$readers = $this->logmanager->get_readers('core\log\sql_reader');
		if ($nameonly) {
			foreach ($readers as $pluginname => $reader) {
				$readers[$pluginname] = $reader->get_name();
			}
		}
		return $readers;
	}

	//para que el renderer obetenga las fechas disponibles
	public function get_date_options() {
		global $SITE;

		$strftimedate = get_string("strftimedate");
		$strftimedaydate = get_string("strftimedaydate");

        // Get all the possible dates.
        // Note that we are keeping track of real (GMT) time and user time.
        // User time is only used in displays - all calcs and passing is GMT.
        $timenow = time(); // GMT.

        // What day is it now for the user, and when is midnight that day (in GMT).
        $timemidnight = usergetmidnight($timenow);

        // Put today up the top of the list.
        $dates = array("$timemidnight" => get_string("today").", ".userdate($timenow, $strftimedate) );

        // If course is empty, get it from frontpage.
        $course = $SITE;
        if (!empty($this->course)) {
        	$course = $this->course;
        }
        if (!$course->startdate or ($course->startdate > $timenow)) {
        	$course->startdate = $course->timecreated;
        }

        $numdates = 1;
        while ($timemidnight > $course->startdate and $numdates < 365) {
        	$timemidnight = $timemidnight - 86400;
        	$timenow = $timenow - 86400;
        	$dates["$timemidnight"] = userdate($timenow, $strftimedaydate);
        	$numdates++;
        }
        return $dates;
    }

    //para preparar la tabla de events_history
    public function setup_table() {
    	$readers = $this->get_readers();

    	$filter = new \stdClass();
    	if (!empty($this->course)) {
    		$filter->courseid = $this->course->id;
    	} else {
    		$filter->courseid = 0;
    	}

    	$filter->userid = $this->userid;
    	$filter->logreader = $readers[$this->selectedlogreader];
    	$filter->date = $this->date;
    	$filter->orderby = $this->order;

    	$this->tablelog = new eventshistory_table('block_stash', $filter);
    	$this->tablelog->define_baseurl($this->url);
    }

}