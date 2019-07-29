<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * Block Stash renderer.
 *
 * @package    block_stash
 * @copyright  2016 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_stash\output;
defined('MOODLE_INTERNAL') || die();

use context;
use html_writer;
use moodle_url;
use plugin_renderer_base;
use renderable;
use tabobject;
use block_stash\drop as dropmodel;
use block_stash\item;
use block_stash\external\drop_exporter;
use block_stash\external\item_exporter;


/**
 * Block Stash renderer class.
 *
 * @package    block_stash
 * @copyright  2016 Adrian Greeve
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Renderer to output the snippet UI.
     *
     * @param drop $drop The drop.
     * @param item $item The item.
     * @param context $context The context of the drop.
     * @return string
     */
    public function drop_snippet_ui(dropmodel $drop, item $item, context $context) {
        $data = (object) [];
        $exporter = new drop_exporter($drop, ['context' => $context]);
        $data->drop = $exporter->export($this);
        $data->dropjson = json_encode($data->drop);
        $exporter = new item_exporter($item, ['context' => $context]);
        $data->item = $exporter->export($this);
        $data->itemjson = json_encode($data->item);

        list($altsnippetmaker, $warning) = \block_stash\helper::get_alternate_amd_snippet_maker($context);
        $data->altsnippetmaker = $altsnippetmaker->drop;
        $data->warnings = [$warning];
        $data->haswarnings = !empty($warning);

        return parent::render_from_template('block_stash/drop_snippet_ui', $data);
    }

    /**
     * Explanation on what to do with a drop snippet.
     *
     * @return string
     */
    public function drop_snippet_whatsnext() {
        $o = '';
        $o .= html_writer::start_div('alert alert-info');
        $o .= html_writer::tag('strong', get_string('whatsnext', 'block_stash'));
        $o .= ' ';
        $o .= get_string('aftercreatinglocationhelp', 'block_stash');
        $o .= html_writer::end_div();
        return $o;
    }

    /**
     * Explanation on what a drop/location is.
     *
     * @return string
     */
    public function drop_whats_that() {
        $o = '';
        $o .= html_writer::start_div('alert alert-info');
        $o .= html_writer::tag('strong', get_string('whatsthis', 'block_stash'));
        $o .= ' ';
        $o .= get_string('whatisadrophelp', 'block_stash');
        $o .= html_writer::end_div();
        return $o;
    }

    /**
     * Explanation on what a trade drop/location is.
     *
     * @return string
     */
    public function tradedrop_whats_that() {
        $o = '';
        $o .= html_writer::start_div('alert alert-info');
        $o .= html_writer::tag('strong', get_string('whatsthis', 'block_stash'));
        $o .= ' ';
        $o .= get_string('whatisatradedrophelp', 'block_stash');
        $o .= html_writer::end_div();
        return $o;
    }

    /**
     * Describes what drops are.
     *
     * @return string
     */
    public function drops_fullpage_help() {
        $data = (object) [];
        $data->heading = get_string('whataredrops', 'block_stash');
        $data->helptext = get_string('drops_help', 'block_stash');
        return parent::render_from_template('block_stash/fullpage_help', $data);
    }

    /**
     * Outputs the navigation.
     *
     * @param block_xp_manager $manager The manager.
     * @param string $page The page we are on.
     * @return string The navigation.
     */
    public function navigation($manager, $page) {
        $tabs = [];
        $courseid = $manager->get_courseid();

        if ($manager->can_manage()) {
            $tabs[] = new tabobject(
                'items',
                new moodle_url('/blocks/stash/items.php', ['courseid' => $courseid]),
                get_string('navitems', 'block_stash')
            );

            // Presently we hide the drops page by default.
            if ($page == 'drops') {
                $tabs[] = new tabobject(
                    'drops',
                    new moodle_url('/blocks/stash/drops.php', ['courseid' => $courseid]),
                    get_string('navdrops', 'block_stash')
                );
            }

            // I want to hide this depending on the block filter being enabled and there being at least one item defined.
            $tabs[] = new tabobject(
                'trade',
                new moodle_url('/blocks/stash/trade.php', ['courseid' => $courseid]),
                get_string('navtrade', 'block_stash')
            );

            $tabs[] = new tabobject(
                'report',
                new moodle_url('/blocks/stash/report.php', ['courseid' => $courseid]),
                get_string('navreport', 'block_stash')
            );
        }

        // If there is only one page, then that is the page we are on.
        if (count($tabs) == 1) {
            return '';
        }

        return $this->tabtree($tabs, $page);
    }

    public function render_block_content(renderable $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('block_stash/main_content', $data);
    }

    public function render_drop_image(drop_image $renderable) {
        $data = $renderable->export_for_template($this);
        return parent::render_from_template('block_stash/drop_image', $data);
    }

    public function render_drop_text(drop_text $renderable) {
        $data = $renderable->export_for_template($this);
        return parent::render_from_template('block_stash/drop_text', $data);
    }

    public function render_item_xsmall(renderable $renderable) {
        $data = $renderable->export_for_template($this);
        return parent::render_from_template('block_stash/item_xsmall', $data);
    }

    public function render_trade(renderable $renderable) {
        $data = $renderable->export_for_template($this);
        return parent::render_from_template('block_stash/trade', $data);
    }

    public function render_settings_page(renderable $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('block_stash/settings', $data);
    }

    public function render_user_inventory(renderable $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('block_stash/user_inventory', $data);
    }

    public function render_trade_form(renderable $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('block_stash/trade_form', $data);
    }

    public function render_event_history_table(\event_history_renderable $reportlog){

        if (empty($reportlog->selectedlogreader)) {
            echo $this->output->notification(get_string('nologreaderenabled', 'report_log'), 'notifyproblem');
            return;
        }
        if ($reportlog->showselectorform) {
            $this->report_selector_form($reportlog);
        }

        if ($reportlog->showreport) {
            $reportlog->tablelog->out($reportlog->perpage, true);
        }
    }

    // To print event_history.php's search options
    public function report_selector_form(\event_history_renderable $reportlog) {
        echo html_writer::start_tag('form', array('class' => 'logselecform', 'action' => $reportlog->url, 'method' => 'get'));
        echo html_writer::start_div();

        // To show the query
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'chooselog', 'value' => '1'));

        // courseid
        $courseid = $reportlog->course->id;
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'courseid', 'value' => $courseid ));

        // userid
        $userid = $reportlog->userid;
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'userid', 'value' => $userid));

        // Page of report.php's table to redirect to
        $report_page = $reportlog->report_page;
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'report_page', 'value' => $report_page));


        // Add date selector.
        $dates = $reportlog->get_date_options();
        echo html_writer::label(get_string('date'), 'menudate', false, array('class' => 'accesshide'));
        echo html_writer::select($dates, "date", $reportlog->date, get_string("alldays"));


        // Add reader option.
        // If there is some reader available then only show submit button.
        $readers = $reportlog->get_readers(true);
        if (!empty($readers)) {
            if (count($readers) == 1) {
                $attributes = array('type' => 'hidden', 'name' => 'logreader', 'value' => key($readers));
                echo html_writer::empty_tag('input', $attributes);
            } else {
                echo html_writer::label(get_string('selectlogreader', 'report_log'), 'menureader', false,
                    array('class' => 'accesshide'));
                echo html_writer::select($readers, 'logreader', $reportlog->selectedlogreader, false);
            }
            echo html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('eventhistoryget','block_stash'),
                'class' => 'btn btn-secondary'));
        }
        echo html_writer::end_div();
        echo html_writer::end_tag('form');
    }

}
