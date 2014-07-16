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

admin_externalpage_setup('reportkentplayer');

$PAGE->set_title(get_string('pluginname', 'report_kentplayer'));
$PAGE->requires->js_init_call('M.report_kentplayer.init', array(), false, array(
    'name' => 'report_kentplayer',
    'fullpath' => '/report/kentplayer/module.js'
));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'report_kentplayer'));

// Allow restriction by role.
$ar = \block_panopto\util::get_role('panopto_academic');
$nar = \block_panopto\util::get_role('panopto_non_academic');
echo html_writer::select(array(
    0 => "All",
    $ar->id => "Academic",
    $nar->id => "Non-Academic"
), 'role', $role);

$wheresql = '';
$params = array();

if ($role == $ar->id || $role == $nar->id) {
    $wheresql = '= :roleid';
    $params['roleid'] = $role;
} else {
    list($wheresql, $params) = $DB->get_in_or_equal(array($ar->id, $nar->id), SQL_PARAMS_NAMED, 'roleid');
}

$data = $DB->get_records_sql("SELECT *
	FROM {role_assignments} ra
	INNER JOIN {user} u ON u.id=ra.userid
	WHERE roleid $wheresql
", $params, $page * $perpage, $perpage);

echo $OUTPUT->footer();