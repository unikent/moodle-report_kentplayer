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
 * Home page for the report.
 * 
 * @package    report
 * @subpackage kentplayer
 * @copyright  Skylar Kelty <S.Kelty@kent.ac.uk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$page     = optional_param('page', 0, PARAM_INT);
$perpage  = optional_param('perpage', 25, PARAM_INT);
$role = optional_param('role', 0, PARAM_INT);
$format = optional_param('format', '', PARAM_ALPHA);

admin_externalpage_setup('reportkentplayer');

$PAGE->set_title(get_string('pluginname', 'report_kentplayer'));
$PAGE->requires->js_init_call('M.report_kentplayer.init', array(), false, array(
    'name' => 'report_kentplayer',
    'fullpath' => '/report/kentplayer/module.js'
));

$ar = \block_panopto\util::get_role('panopto_academic');
$nar = \block_panopto\util::get_role('panopto_non_academic');

$wheresql = '';
$params = array();

if ($role == $ar->id || $role == $nar->id) {
    $wheresql = '= :roleid';
    $params['roleid'] = $role;
} else {
    list($wheresql, $params) = $DB->get_in_or_equal(array($ar->id, $nar->id), SQL_PARAMS_NAMED, 'roleid');
}

$sql = <<<SQL
    SELECT u.id, u.username, u.firstname, u.lastname, ra.roleid
    FROM {role_assignments} ra
    INNER JOIN {user} u ON u.id=ra.userid
    WHERE roleid $wheresql
    GROUP BY u.id
SQL;

$data = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

// Setup the table.
$table = new html_table();
$table->head  = array("Username", "First name", "Last Name", "Role");
$table->colclasses = array('mdl-left username', 'mdl-left firstname', 'mdl-left lastname', 'mdl-left role');
$table->attributes = array('class' => 'admintable kentplayerreport generaltable');
$table->id = 'kentplayerreporttable';
$table->data  = array();

if ($format == 'csv') {
    require_once($CFG->libdir . "/csvlib.class.php");

    $export = new csv_export_writer();
    $export->set_filename('PanoptoReport-');
    $export->add_data($table->head);
}

foreach ($data as $datum) {
    $user = new \html_table_cell(\html_writer::tag('a', $datum->username, array(
        'href' => $CFG->wwwroot . '/user/view.php?id=' . $datum->id,
        'target' => '_blank'
    )));

    $row = array(
        $user,
        $datum->firstname,
        $datum->lastname,
        $datum->roleid == $ar->id ? 'Academic' : 'Non-Academic'
    );
    $table->data[] = $row;

    if ($format == 'csv') {
        $row[0] = $datum->username;
        $export->add_data($row);
    }
}

if ($format == 'csv') {
    $export->download_file();
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'report_kentplayer'));

$baseurl = new moodle_url('/report/kentplayer/index.php', array('page' => $page, 'perpage' => $perpage, 'role' => $role));

// Allow restriction by role.
echo html_writer::select(array(
    0 => "All",
    $ar->id => "Academic",
    $nar->id => "Non-Academic"
), 'role', $role);

echo html_writer::table($table);

$count = $DB->count_records_select('role_assignments', 'roleid ' . $wheresql, $params);
echo $OUTPUT->paging_bar($count, $page, $perpage, $baseurl);

$link = new \moodle_url($baseurl);
$link->param('perpage', 999999);
$link->param('format', 'csv');
$link = \html_writer::tag('a', 'Download as CSV', array(
    'href' => $link
));
echo '<p>'.$link.'</p>';

echo $OUTPUT->footer();