<?php
require_once('../../config.php');
require_once('lib.php');
global $DB, $USER, $SESSION,$OUTPUT;  
$context = context_system::instance();
$managercap = has_capability('local/deptrpts:managerreport',$context);
$PAGE->set_context($context);
$type = optional_param('type','',PARAM_RAW);
$startdt = optional_param('startdate','',PARAM_RAW);
$enddt = optional_param('enddate','',PARAM_RAW);
$siteloc = optional_param('sitelocation','',PARAM_RAW);
$userloc = optional_param('userlocation','',PARAM_RAW);
$courseloc = optional_param('courselocation','',PARAM_RAW);
$sitecat = optional_param('sitecategory','',PARAM_INT);
$siterol = optional_param('siterole','',PARAM_INT);
$sitedept = optional_param('sitedeparment','',PARAM_RAW);
$userselect = optional_param('usersearch','',PARAM_RAW);
$courseselect = optional_param('coursesearch','',PARAM_RAW);
$status = optional_param('status','',PARAM_RAW);
$firstload = optional_param('firstload','',PARAM_RAW);
$allcourse = optional_param('allcourse','',PARAM_RAW);
$alluser = optional_param('allusers','',PARAM_RAW);
// $tblsearching = optional_param('tblsearch','',PARAM_RAW);

$catfirstload=0;
if(!empty($firstload)){
  $catfirstload=1;
}

if(!empty($firstload)){
  echo site_report(null,null,null,null,null,null,$catfirstload);
}

if($type == "usersearch"){
  $userarray = [];
  if(is_siteadmin()){
    $users =$DB->get_records('user',array('deleted'=>'0','suspended'=>'0'));
    foreach ($users as $user) {
      $userarray[]= array(
        'id'=>$user->id,
        'fullname' =>$user->firstname.' '.$user->lastname.'-'.$user->username
      );
    }
  }else if($managercap){
    $userarray[]=array('id'=>'','fullname'=>'');
    $manager=$DB->get_record_sql("SELECT id FROM {user_info_field} WHERE shortname='managersemail'");
    $infodata=$DB->get_records_sql("SELECT userid FROM {user_info_data} WHERE fieldid='$manager->id' AND data='$USER->email'");
    foreach ($infodata as $data1) {
      $username=$DB->get_record_sql("SELECT firstname,lastname FROM {user} WHERE id='$data1->userid'");
      $userarray[]=array(
        'id'=>$data1->userid,
        'fullname'=>$username->firstname.' '.$username->lastname
      );
    }
  }else{
  }
  echo json_encode($userarray);
}

if($type == 'coursesearch'){
  $coursearray=[];
  if(is_siteadmin()){
    $courses =$DB->get_records('course',array('visible'=>'1'));
    foreach ($courses as $course) {
      $coursearray[] = array(
               'id' =>$course->id,
             'name'=>$course->fullname
      );
    }
  }else if($managercap){
    $coursearray[]=array('id'=>'','fullname'=>'');
    $manager=$DB->get_record_sql("SELECT id FROM {user_info_field} WHERE shortname='managersemail'");
    $infodata=$DB->get_records_sql("SELECT userid FROM {user_info_data} WHERE fieldid='$manager->id' AND data='$USER->email'");
    $counter=1;
    $instring="";
    foreach ($infodata as $coursekey => $coursevalue) {
        if($counter == 1){
          $instring="'".$coursekey."'";
        }else{
          $instring = $instring.","."'".$coursekey."'";
        }
        $counter++;
    }
    $managercourse="SELECT DISTINCT c.fullname,c.id
    FROM mdl_course AS c
    JOIN mdl_context AS ctx ON c.id = ctx.instanceid
    JOIN mdl_role_assignments AS ra ON ra.contextid = ctx.id
    JOIN mdl_user AS u ON u.id = ra.userid
    JOIN mdl_course_categories AS cc ON cc.id = c.category
    WHERE u.id in (".$instring.")";
    $usercourse = $DB->get_records_sql($managercourse);
    foreach ($usercourse as $mngcoursekey => $mngcoursevalue) {
          $coursearray[] =array(
            'id'=>$mngcoursevalue->id,
            'name'=>$mngcoursevalue->fullname
          );
    }
  }
    echo json_encode($coursearray);  
}

if($status == "startdate" || $status == "enddate" && empty($siteloc) && empty($userloc) && empty($courseloc) && ($userselect <= 1) && ($courseselect <= 1) && empty($tblsearching)){
  echo site_report(strtotime($startdt),strtotime($enddt),null,null,null,null,null);
}

if ($status == "sitelocation" || $status == "sitecategory" || $status == "siterole" || $status == "sitedepartment") {
  echo site_report(strtotime($startdt),strtotime($enddt),$siteloc,$sitecat,$siterol,$sitedept,null);
}

if($status == "userlocation" || $status == "usersearch"){
echo user_report(strtotime($startdt),strtotime($enddt),$userloc,$userselect);
}

if($status == "courselocation" || $status == "coursesearch"){
    echo course_report(strtotime($startdt),strtotime($enddt),$courseloc,$courseselect);
}

if($status == "allcourses"){
  echo allcourses_report(strtotime($startdt),strtotime($enddt));
}

if($status == "allusers"){
  echo allusers_report(strtotime($startdt),strtotime($enddt));
}