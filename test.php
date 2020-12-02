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
require_login(true);
$context = context_system::instance();
$managercap = has_capability('local/deptrpts:managerreport',$context);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_url($CFG->wwwroot . '/local/deptrpts/test.php');
$title = get_string('title','local_deptrpts');
$PAGE->navbar->add($title);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->requires->jquery();
echo $OUTPUT->header();
// if(is_siteadmin()){
// 	$categories=$DB->get_records('course_categories');
// }else if($managercap){
// //checking if the logged in user is manager or not.
// //getting all the users under this manager.
//   	//gettin the managersemail field id.
// $manager=$DB->get_record_sql("SELECT id FROM {user_info_field} WHERE shortname='managersemail'");
// //getting all the userid having curently logged in managers email id.
// $infodata=$DB->get_records_sql("SELECT userid FROM {user_info_data} WHERE fieldid='$manager->id' AND data='$USER->email'");
//   	//gettingall the categories related to these users.
// $counter=1;
// $instring="";
// foreach ($infodata as $catkey => $catval) {
// 	if($counter == 1){
// 		$instring="'".$catkey."'";
// 	}else{
// 		$instring = $instring.","."'".$catkey."'";
// 	}
// 	$counter++;
// }
// $categories="SELECT DISTINCT(cc.name),cc.id,cc.parent
// FROM mdl_course AS c
// JOIN mdl_context AS ctx ON c.id = ctx.instanceid
// JOIN mdl_role_assignments AS ra ON ra.contextid = ctx.id
// JOIN mdl_user AS u ON u.id = ra.userid
// JOIN mdl_course_categories AS cc ON cc.id = c.category
// WHERE u.id in (".$instring.")";
// $categories = $DB->get_records_sql($categories);
// }
// $catarray=[];
// foreach ($categories as $catagory) {
// 	$cparent=$catagory->parent;
// 	if($cparent == 0){
// 		$catarray[$catagory->id]=$catagory->name;
// 	}else {
// 		$cpath=$catagory->path;
// 		$catexplode=explode('/',$cpath);
// 		$counter=1;
// 		$instring="";
// 		foreach ($catexplode as $ckey => $cvalue) {
// 			if(!empty($cvalue)){
// 			$catname=$DB->get_record('course_categories',array('id'=>$cvalue));
// 			$cname=$catname->name;
// 			if($counter == 1){
// 				$instring=$cname;
// 			}else {
// 				$instring = $instring.'/'.$cname;
// 			}
// 			$counter++;
// 			}	
// 		}
// 		$catarray[$catagory->id]=$instring;
// 	}
// }
// return $catarray;

echo $OUTPUT->footer();
