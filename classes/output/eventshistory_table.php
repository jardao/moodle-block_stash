<?php

class eventshistory_table extends table_sql {


	/** @var array list of user fullnames shown in report */
	private $userfullnames = array();


	/** @var stdClass filters parameters */
	private $filterparams;


	public function __construct($uniqueid, $filterparams = null) {
		parent::__construct($uniqueid);

		$this->set_attribute('class', 'reportlog generaltable generalbox');
		$this->filterparams = $filterparams;

		$cols = array();
		$headers = array();

		$this->define_columns(array_merge($cols, array('time', 'fullnameuser', 'relatedfullnameuser', 'description')));
		$this->define_headers(array_merge($headers, array(
			get_string('time'),
			get_string('fullnameuser'),
			get_string('eventrelatedfullnameuser', 'report_log'),
			get_string('description'),
			)
		));
		$this->collapsible(false);
		$this->sortable(false);
		$this->pageable(true);
	}

    protected function get_user_fullname($userid) {
        global $DB;

        if (empty($userid)) {
            return false;
        }

        if (!empty($this->userfullnames[$userid])) {
            return $this->userfullnames[$userid];
        }

        // We already looked for the user and it does not exist.
        if ($this->userfullnames[$userid] === false) {
            return false;
        }

        // If we reach that point new users logs have been generated since the last users db query.
        list($usql, $uparams) = $DB->get_in_or_equal($userid);
        $sql = "SELECT id," . get_all_user_name_fields(true) . " FROM {user} WHERE id " . $usql;
        if (!$user = $DB->get_records_sql($sql, $uparams)) {
            return false;
        }

        $this->userfullnames[$userid] = fullname($user);
        return $this->userfullnames[$userid];
    }

	public function col_time($event) {


		$dateformat = get_string('strftimedatetimeshort', 'core_langconfig');
		return userdate($event->timecreated, $dateformat);
	}


	public function col_fullnameuser($event) {

        // Add username who did the action.
		if (!empty($event->userid) && $username = $this->get_user_fullname($event->userid)) {
			
				$params = array('id' => $event->userid);
				if ($event->courseid) {

					$params['course'] = $event->courseid;
				}
				$username = html_writer::link(new moodle_url('/user/view.php', $params), $username);
			
		} else {
			$username = '-';
		}
		return $username;
	}


	public function col_relatedfullnameuser($event) {
        // Add affected user.
		if (!empty($event->relateduserid) && $username = $this->get_user_fullname($event->relateduserid)) {
			
				$params = array('id' => $event->relateduserid);
				if ($event->courseid) {

					$params['course'] = $event->courseid;
				}
				$username = html_writer::link(new moodle_url('/user/view.php', $params), $username);
			
		} else {
			$username = '-';
		}
		return $username;
	}


	public function col_description($event) {
        // Description.
		return $event->get_description();
	}


	public function query_db($pagesize, $useinitialsbar = true) {
		global $DB;

		$joins = array();
		$params = array();

		//courseid
		$joins[] = "courseid = :courseid";
		$params['courseid'] = $this->filterparams->courseid;

		//userid
		$joins[] = "relateduserid = :relateduserid";
		$params['relateduserid'] = $this->filterparams->userid;

		if (!empty($this->filterparams->date)) {
			$joins[] = "timecreated > :date AND timecreated < :enddate";
			$params['date'] = $this->filterparams->date;
            $params['enddate'] = $this->filterparams->date + DAYSECS; // Show logs only for the selected date.
        }

        //queda pendiente incluir un join para el nombre del evento
        $joins[] = "eventname like \"%block_stash%\"";

        $selector = implode(' AND ', $joins);


        // Get the users and course data.
        $this->rawdata = $this->filterparams->logreader->get_events_select_iterator($selector, $params,
        	$this->filterparams->orderby, $this->get_page_start(), $this->get_page_size());

        // Update list of users which will be displayed on log page.
        $this->update_users_used();

        // Get the events. Same query than before; even if it is not likely, logs from new users
        // may be added since last query so we will need to work around later to prevent problems.
        // In almost most of the cases this will be better than having two opened recordsets.
        $this->rawdata = $this->filterparams->logreader->get_events_select_iterator($selector, $params,
        	$this->filterparams->orderby, $this->get_page_start(), $this->get_page_size());

        // no sé qué hace ésto
        // Set initial bars.
        if ($useinitialsbar && !$this->is_downloading()) {
        	$this->initialbars($total > $pagesize);
        }

    }


    protected function update_users_used() {
    	global $DB;

    	$this->userfullnames = array();
    	$userids = array();

        // For each event cache full username.
        // Get list of userids which will be shown in log report.
    	foreach ($this->rawdata as $event) {
    		$logextra = $event->get_logextra();
    		if (!empty($event->userid) && empty($userids[$event->userid])) {
    			$userids[$event->userid] = $event->userid;
    		}
    		if (!empty($logextra['realuserid']) && empty($userids[$logextra['realuserid']])) {
    			$userids[$logextra['realuserid']] = $logextra['realuserid'];
    		}
    		if (!empty($event->relateduserid) && empty($userids[$event->relateduserid])) {
    			$userids[$event->relateduserid] = $event->relateduserid;
    		}
    	}
    	$this->rawdata->close();

        // Get user fullname and put that in return list.
    	if (!empty($userids)) {
    		list($usql, $uparams) = $DB->get_in_or_equal($userids);
    		$users = $DB->get_records_sql("SELECT id," . get_all_user_name_fields(true) . " FROM {user} WHERE id " . $usql,
    			$uparams);
    		foreach ($users as $userid => $user) {
    			$this->userfullnames[$userid] = fullname($user);
    			unset($userids[$userid]);
    		}

            // We fill the array with false values for the users that don't exist anymore
            // in the database so we don't need to query the db again later.
    		foreach ($userids as $userid) {
    			$this->userfullnames[$userid] = false;
    		}
    	}
    }

}