<?php
// This file is part of the Certificate module for Moodle - http://moodle.org/
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
 * Handles uploading files
 *
 * @package    local_deptrpts
 * @copyright  mallamma<mallamma@elearn10.com>
 * @copyright  Dhruv Infoline Pvt Ltd <lmsofindia.com>
 * @license    http://www.lmsofindia.com 2017 or later
 */
require_once('../../config.php');
require_once('lib.php');
global $DB, $USER, $SESSION,$OUTPUT;  
$categoryid = optional_param('id','',PARAM_INT);
require_login(true);
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_url($CFG->wwwroot . '/local/deptrpts/subcategoryview.php');
$title = get_string('title','local_deptrpts');
$PAGE->navbar->add($title);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->requires->jquery();
$PAGE->requires->js(new moodle_url($CFG->wwwroot.'/local/deptrpts/js/chart.js'),true);
echo $OUTPUT->header();
$counter=1;
$sitecategory="";
$sitecategory.=html_writer::start_div('container');
$sitecategory.=html_writer::start_div('row text-center');
$sitecategory.=html_writer::start_div('col-md-12 ');
//getting main-categoryname to passing categoryid.
$maincategory_name=$DB->get_field('course_categories','name', array('id'=>$categoryid));
$sitecategory.="<h4>".$maincategory_name."</h4>";
$sitecategory .= html_writer::end_div();
$sitecategory .= html_writer::end_div();
$sitecategory .= html_writer::end_div();
$sitecategory.=html_writer::start_div('container');
$sitecategory.=html_writer::start_div('row');
//here spitting the categoryid using with '/'.
$subcat="/".$categoryid."/";
//here getting all subcategories.
$subcategories=$DB->get_records_sql("SELECT * FROM {course_categories} c WHERE  c.path LIKE  '%$subcat%'");
foreach ($subcategories as $subcategory) {
  	if(!empty($subcategory)){
  	$site=course_completion_stats('','',$subcategory->id,'','');
	$catdata=$site[0].','.$site[1].','.$site[2];
    $catname=$site[3];

    if(!empty($site[0]) || !empty($site[1]) || !empty($site[2])){
    $sitecategory.= filter_categorywise_chart($counter,$catdata,$catname,$subcategory->id);
      $counter++;
    }
 }	
}
$sitecategory .= html_writer::end_div();
$sitecategory .= html_writer::end_div();
echo $sitecategory;
echo $OUTPUT->footer();