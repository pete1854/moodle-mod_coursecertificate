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
 * Display information about all the mod_coursecertificate modules in the requested course.
 *
 * @package     mod_coursecertificate
 * @copyright   2020 Mikel Martín <mikel@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

$id = required_param('id', PARAM_INT);

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_course_login($course);

$coursecontext = context_course::instance($course->id);

$event = \mod_coursecertificate\event\course_module_instance_list_viewed::create(['context' => $coursecontext]);
$event->add_record_snapshot('course', $course);
$event->trigger();

$PAGE->set_url('/mod/certificate/index.php', ['id' => $id]);
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);

echo $OUTPUT->header();

$modulenameplural = get_string('modulenameplural', 'mod_coursecertificate');
echo $OUTPUT->heading($modulenameplural);

$certificates = get_all_instances_in_course('coursecertificate', $course);

if (empty($certificates)) {
    notice(get_string('thereareno', 'moodle'), new moodle_url('/course/view.php', ['id' => $course->id]));
    exit;
}

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

$align = ['center', 'left'];
if ($course->format == 'weeks') {
    $table->head  = [get_string('week'), get_string('name')];
    $table->align = ['center', 'left'];
} else if ($course->format == 'topics') {
    $table->head  = [get_string('topic'), get_string('name')];
    $table->align = ['center', 'left'];
} else {
    $table->head  = [get_string('name')];
    $table->align = ['left'];
}

foreach ($certificates as $certificate) {
    $attributes = [];
    if (!$certificate->visible) {
        $attributes['class'] = 'dimmed';
    }
    $link = html_writer::link(
        new moodle_url('/mod/certificate/view.php', ['id' => $certificate->coursemodule]),
        format_string($certificate->name, true),
        $attributes);

    if ($course->format == 'weeks' or $course->format == 'topics') {
        $table->data[] = [$certificate->section, $link];
    } else {
        $table->data[] = [$link];
    }
}

echo html_writer::table($table);
echo $OUTPUT->footer();