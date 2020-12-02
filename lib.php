<?php
global $CFG,$DB,$USER;
require_once($CFG->libdir.'/completionlib.php');
require_once("$CFG->libdir/gradelib.php");
require_once("$CFG->dirroot/lib/completionlib.php");
require_once($CFG->dirroot . '/grade/querylib.php');

/**
*Rachitha:this function will give site report.
* @param string $startdt startdate.
* @param string $enddt enddate.
* @param string $siteloc selected location from the filter.
* @param string $sitecat selected category from the filter.
* @return array $fhtml returning it contains cards,piecharts,graphs and datatable.
*/
function site_report($startdt,$enddt,$siteloc,$sitecat,$siterol,$sitedept,$catfirstload){
  global $DB,$OUTPUT;
  $carddata = get_course_from_category($startdt,$enddt,$siteloc,$sitecat,$siterol,$sitedept);
  $data='';
  $counter=0;
  $title=array(get_string('enrolledcourses','local_deptrpts'),
    get_string('coursecompletion','local_deptrpts'),
    get_string('badgesearned','local_deptrpts'),
    get_string('certificateearned','local_deptrpts'));
  $icon=array("<i class='fa fa-check-square-o' aria-hidden='true'></i>",
    "<i class='fa fa-certificate' aria-hidden='true'></i>",
    "<i class='fa fa-list' aria-hidden='true'></i>",
    "<i class='fa fa-address-card-o' aria-hidden='true'></i>");
  $color=array("gradient-deepblue","gradient-orange","gradient-ohhappiness","gradient-ibiza");
  if(!empty($carddata)){
    foreach ($carddata as $card) {
      $data.=filter_top_cards($icon[$counter],$title[$counter],$card,$color[$counter]);
      $counter++;
    }
  }
  if(!empty($sitecat)){
    $categories = $DB->get_records('course_categories',array('id'=>$sitecat));
  }else{
    //getting all main categories where parent is '0'.
    if(!empty($catfirstload)){
      $categories=$DB->get_records('course_categories',array('parent'=>0));
    }else{
      $categories = $DB->get_records('course_categories',array('visible'=>1));
    }
  }
  $sitecategory="";
  $counter=1;
  foreach ($categories as $category) {
    $site=course_completion_stats($startdt,$enddt,$category->id,$siteloc,$catfirstload);
    $catdata=$site[0].','.$site[1].','.$site[2];
    $catname=$site[3];
    
    if(!empty($site[0]) || !empty($site[1]) || !empty($site[2])){
      $sitecategory.= filter_categorywise_chart($counter,$catdata,$catname,$category->id);
      $counter++;
    }
  }
  $yeargraph = get_yearwisecategory_info($startdt,$enddt,$siteloc,$sitecat,$siterol,$sitedept);
  $enrolllabel="";
  $enrolldata="";
  $counter=1;
  if(!empty($yeargraph['enrol'])){
    foreach ($yeargraph['enrol'] as $enkey => $envalue) {
      if($counter==1){
        $enrolllabel="'".$enkey."'";
        $enrolldata=$envalue;
      }else{
        $enrolllabel=$enrolllabel.','."'".$enkey."'";
        $enrolldata=$enrolldata.','.$envalue;
      }
      $counter++;
    }
  }
  $completelabel="";
  $completedata="";
  $counter=1;
  if(!empty($yeargraph['complete'])){
    foreach ($yeargraph['complete'] as $cmkey => $cmvalue) {
      if($counter==1){
        $completelabel="'".$cmkey."'";
        $completedata=$cmvalue;
      }else{
        $completelabel=$completelabel.','."'".$cmkey."'";
        $completedata=$completedata.','.$cmvalue;
      }
      $counter++;
    }
  }
  $graph=filter_yearly_enrol_completiongraph($enrolllabel,$enrolldata,$completelabel,$completedata);
  $datatablearray = site_report_datatable_deptrpts($startdt,$enddt,$siteloc,$sitecat);
  $retarray=[];
  $retarray['tabledata']=$datatablearray;
  $retarray['siteheading']=get_string('sitehead','local_deptrpts');
  $retarray['sitehelptxt']=get_string('sitehelptext','local_deptrpts');
  $retarray['dynlink']="/local/deptrpts/downloadexcel.php?strtdate=".$startdt."&endate=".$enddt."&slocation=".$siteloc."&scategory=".$sitecat."&status=site";
  $sitetable=site_datatable($retarray);
  $fhtml="";
  $fhtml.=$data;
  $fhtml.=$sitecategory;
  $fhtml.=$graph;
  $fhtml.=$sitetable;
  return $fhtml;
}

/**
*Rachitha:this function will give user report.
* @param string $startdt startdate.
* @param string $enddt enddate.
* @param string $siteloc selected location from the filter.
* @param string $userid userid.
* @return array $fhtml returning it contains cards,piecharts,graphs and datatable.
*/
function user_report($startdt,$enddt,$userloc,$userid){
  global $DB;
  $coursecount = user_course_count($userid,$startdt,$enddt,$userloc);
  $badgecount= user_badge_count($userid,$startdt,$enddt,$userloc);
  $certificatecount=user_course_certificate($userid,$startdt,$enddt,$userloc);
  $completioncount=user_course_completion($userid,$startdt,$enddt,$userloc);
  $title=array(get_string('enrolledcourses','local_deptrpts'),
    get_string('coursecompletion','local_deptrpts'),
    get_string('badgesearned','local_deptrpts'),
    get_string('certificateearned','local_deptrpts'));
  $icon=array("<i class='fa fa-check-square-o' aria-hidden='true'></i>",
    "<i class='fa fa-certificate' aria-hidden='true'></i>",
    "<i class='fa fa-list' aria-hidden='true'></i>",
    "<i class='fa fa-address-card-o' aria-hidden='true'></i>");
  $color=array("gradient-deepblue","gradient-orange","gradient-ohhappiness","gradient-ibiza");
  $html='';
  $html.=filter_top_cards($icon[0],$title[0],$coursecount,$color[0]);
  $html.=filter_top_cards($icon[1],$title[1],$completioncount,$color[1]);
  $html.=filter_top_cards($icon[2],$title[2],$badgecount,$color[2]);
  $html.=filter_top_cards($icon[3],$title[3],$certificatecount,$color[3]);
  
  $ycomplete=get_user_yearly_completion($startdt,$enddt,$userloc,$userid);
  $temp1=1;
  $ydata='';
  $ylabel='';
  foreach ($ycomplete as $ykey => $yvalue) {
    if($temp1 == 1){
      $ydata = $yvalue;
      $ylabel = "'".$ykey."'";
    }else{
      $ydata = $ydata.','.$yvalue;
      $ylabel = $ylabel.','."'".$ykey."'";
    }
    $temp1++;
  }

  $yenrol=get_enrolled_course_yearly($startdt,$enddt,$userloc,$userid);
  $temp2=1;
  $crdata='';
  $crlabel='';
  if(!empty($yenrol)){
    foreach ($yenrol as $crkey => $crvalue) {
      if($temp2 == 1){
        $crdata = $crvalue;
        $crlabel = "'".$crkey."'";

      }else{
        $crdata = $crdata.','.$crvalue;
        $crlabel = $crlabel.','."'".$crkey."'";
      }
      $temp2++;
    }
  }
  $yearlygraph=filter_yearly_enrol_completiongraph($crlabel,$crdata,$ylabel,$ydata);

  $infoenroll = get_course_enrolled_info($startdt,$enddt,$userloc,$userid);
  $count = 1;
  $menrol = '';
  foreach ($infoenroll as $mkey => $mvalue) {
    if($count == 1){
      $menrol = $mvalue;
    }else{
      $menrol =$menrol.','.$mvalue;
    }
    $count++;
  }

  $completion=get_course_completion($startdt,$enddt,$userloc,$userid);
  $temp=1;
  $mcomplete='';
  foreach ($completion as $ckey => $cvalue) {
    if($temp == 1){
      $mcomplete = $cvalue;
    }else{
      $mcomplete =$mcomplete.','.$cvalue;
    }
    $temp++;
  }
  $montlygraph=filter_monthly_enrol_completiongraph($menrol,$mcomplete);
  $usertabledata=userdatatable_report($startdt,$enddt,$userloc,$userid);
  $usertabledata['siteheading']=get_string('usertableheading','local_deptrpts');
  $usertabledata['sitehelptxt']=get_string('usertabletxt','local_deptrpts');
  $usertable=site_datatable($usertabledata);
  $fhtml="";
  $fhtml.=$html;
  $fhtml.=$yearlygraph;
  $fhtml.=$montlygraph;
  $fhtml.=$usertable;
  echo $fhtml;
}

/**
*Rachitha:this function will give course report.
* @param string $startdt startdate.
* @param string $enddt enddate.
* @param string $courseloc selected location from the filter.
* @param string $courseid courseid.
* @return array $fhtml returning it contains cards,piecharts,graphs and datatable.
*/
function course_report($startdt,$enddt,$courseloc,$courseid){
  global $DB;
  $enrolluser = enrolled_users_count_course($startdt,$enddt,$courseloc,$courseid);
  $badgecount= get_badges_earned($startdt,$enddt,$courseloc,$courseid);
  $certificatecount =get_course_cerficatescount($startdt,$enddt,$courseloc,$courseid);
  $completioncount =course_complition_count($startdt,$enddt,$courseloc,$courseid);
  $title=array(get_string('enrolledusers','local_deptrpts'),
    get_string('userscompleted','local_deptrpts'),
    get_string('badges','local_deptrpts'),
    get_string('certificates','local_deptrpts'));
  $icon=array("<i class='fa fa-check-square-o' aria-hidden='true'></i>",
    "<i class='fa fa-certificate' aria-hidden='true'></i>",
    "<i class='fa fa-list' aria-hidden='true'></i>",
    "<i class='fa fa-address-card-o' aria-hidden='true'></i>");
  $color=array("gradient-deepblue","gradient-orange","gradient-ohhappiness","gradient-ibiza");
  $html='';
  $html.=filter_top_cards($icon[0],$title[0],$enrolluser,$color[0]);
  $html.=filter_top_cards($icon[1],$title[1],$completioncount,$color[1]);
  $html.=filter_top_cards($icon[2],$title[2],$badgecount,$color[2]);
  $html.=filter_top_cards($icon[3],$title[3],$certificatecount,$color[3]);
  
  $yearlygraph=yearwise_course_enrollment_data($startdt,$enddt,$courseloc,$courseid);
  $cyearlygraph=filter_yearly_enrol_completiongraph($yearlygraph[0],$yearlygraph[1],$yearlygraph[2],$yearlygraph[3]);

  $monthlygraph=monthwise_course_enrollment_data($startdt,$enddt,$courseloc,$courseid);

  $cmontlygraph=filter_monthly_enrol_completiongraph($monthlygraph[0],$monthlygraph[1]);
  $cousrsedatatable=course_report_datatable($startdt,$enddt,$courseloc,$courseid);
  // $coursetable=course_datatable($cousrsedatatable);

  course_header($courseid);
  $coursetable=course_data_table($startdt,$enddt,$courseloc,$courseid);
  // course_data($startdt,$enddt,$courseloc,$courseid);
  //echo $coursetable;

  $fhtml="";
  $fhtml.=$html;
  $fhtml.=$cyearlygraph;
  $fhtml.=$cmontlygraph;
  $fhtml.=$coursetable;
  $fhtml.=course_script();
  echo $fhtml;
}

/**
*Rachitha:this function will give All course report.
* @param string $startdt startdate.
* @param string $enddt enddate.
*/
//allcourse  button report.
function allcourses_report($startdt,$enddt){
  //getting all user count.
  global $DB;
  $html = "";
  $html.= allcourse_top_cards_report($startdt,$enddt,null,null);
  $yearlygraph=yearwise_course_enrollment_data($startdt,$enddt,null,null);
  $html.= filter_yearly_enrol_completiongraph($yearlygraph[0],$yearlygraph[1],$yearlygraph[2],$yearlygraph[3]);
  $monthlygraph=monthwise_course_enrollment_data($startdt,$enddt,null,null);
  $html.= filter_monthly_enrol_completiongraph($monthlygraph[0],$monthlygraph[1]);
  $cousrsedatatable = allcoursedatatable_report($startdt,$enddt);
  $cousrsedatatable['siteheading']=get_string('allcoursetableheading','local_deptrpts');
  $cousrsedatatable['sitehelptxt']=get_string('allcoursetabletxt','local_deptrpts');
  $html.= site_datatable($cousrsedatatable);
  echo $html;
}

/**
*Rachitha:this function will give All user report.
* @param string $startdt startdate.
* @param string $enddt enddate.
*/
//allusers button report.
function allusers_report($startdt,$enddt){
  global $DB;
  $html = "";
  //getting top 4 cards here.
  $html.= allusers_topcards_report($startdt,$enddt,null,null);
  //getting yearwise graphs here.
  $ycomplete = get_user_yearly_completion($startdt,$enddt,null,null);
  $temp1=1;
  $ydata='';
  $ylabel='';
  foreach ($ycomplete as $ykey => $yvalue) {
    if($temp1 == 1){
      $ydata = $yvalue;
      $ylabel = "'".$ykey."'";
    }else{
      $ydata = $ydata.','.$yvalue;
      $ylabel = $ylabel.','."'".$ykey."'";
    }
    $temp1++;
  }
  $yenrol=get_enrolled_course_yearly($startdt,$enddt,null,null);
  $temp2=1;
  $crdata='';
  $crlabel='';
  if(!empty($yenrol)){
    foreach ($yenrol as $crkey => $crvalue) {
      if($temp2 == 1){
        $crdata = $crvalue;
        $crlabel = "'".$crkey."'";

      }else{
        $crdata = $crdata.','.$crvalue;
        $crlabel = $crlabel.','."'".$crkey."'";
      }
      $temp2++;
    }
  }
  $html.= filter_yearly_enrol_completiongraph($crlabel,$crdata,$ylabel,$ydata);

  $infoenroll = get_course_enrolled_info($startdt,$enddt,null,null);
  $count = 1;
  $menrol = '';
  foreach ($infoenroll as $mkey => $mvalue) {
    if($count == 1){
      $menrol = $mvalue;
    }else{
      $menrol =$menrol.','.$mvalue;
    }
    $count++;
  }
  $completion=get_course_completion($startdt,$enddt,null,null);
  $temp=1;
  $mcomplete='';
  foreach ($completion as $ckey => $cvalue) {
    if($temp == 1){
      $mcomplete = $cvalue;
    }else{
      $mcomplete =$mcomplete.','.$cvalue;
    }
    $temp++;
  }
  $html.= filter_monthly_enrol_completiongraph($menrol,$mcomplete);
  $usertabledata=userdatatable_report($startdt,$enddt,null,null);
  $usertabledata['siteheading']=get_string('allusertableheading','local_deptrpts');
  $usertabledata['sitehelptxt']=get_string('allusertabletxt','local_deptrpts');
  $html.= site_datatable($usertabledata);
  echo $html;
}

/**
*Rachitha:this function will give html code for leftside filter.
* @return array $filterarray returning html code for leftside.
*/
function filter_ajax_page(){
  global $DB,$OUTPUT,$USER;
  $context = context_system::instance();
  $managercap = has_capability('local/deptrpts:managerreport',$context);
//creating city dropdown
  $locationarray=[];
  if(is_siteadmin()){
  	$locations = $DB->get_records_sql('SELECT  DISTINCT city FROM {user} WHERE city!=" "');
  	foreach ($locations as $location) {
  		$locationarray[]=$location->city;
  	}
  }else if($managercap){
  	//gettin the managersemail field id.
  	$manager=$DB->get_record_sql("SELECT id FROM {user_info_field} WHERE shortname='managersemail'");
  	//getting all the userid having curently logged in managers email id.
  	$infodata=$DB->get_records_sql("SELECT userid FROM {user_info_data} WHERE fieldid='$manager->id' AND data='$USER->email'");

  	foreach ($infodata as $userloc) {
  		$username=$DB->get_record_sql("SELECT city FROM {user} WHERE id='$userloc->userid'");
  		if($username->city!==""){
  			$locationarray[]=$username->city;
  		}
  	}
  }
  $city='';
  $city .= html_writer::start_tag('option',array('value'=>''));
  $city .= get_string('selectlocation','local_deptrpts');
  $city .= html_writer::end_tag('option');
  if(!empty($locationarray)){
  	foreach ($locationarray as $locs) {
  		$city .= html_writer::start_tag('option',array('value'=>$locs));
  		$city .= $locs;
  		$city .= html_writer::end_tag('option');
  	}
  }
//city dropdown ends.
//creating category dropdown.
//checking if the logged in user is admin or not.
  $category = "";
  $category .= html_writer::start_tag('select', array('id'=>'site-category','class'=>'custom-select'));
  $category .= html_writer::start_tag('option');
  $category .= get_string('selectcategory','local_deptrpts');
  $category .= html_writer::end_tag('option');
  $categories = all_category_subacatgeory();
    foreach ($categories as $managerkey => $managervalue) {
      $category .= html_writer::start_tag('option',array('value'=>$managerkey));
      $category .= $managervalue;
      $category .= html_writer::end_tag('option');
    }
  $category .= html_writer::end_tag('select');

  //creating role dropdown.
  $roles=$DB->get_records_sql('SELECT id,shortname FROM {role}');
  $role='';
  $role .= html_writer::start_tag('select', array('id'=>'site-role','class'=>'custom-select'));
  $role .= html_writer::start_tag('option',array('value'=>''));
  $role .= get_string('selectrole','local_deptrpts');
  $role .= html_writer::end_tag('option');
  if(!empty($roles)){
    foreach ($roles as $rol) {
      $role .= html_writer::start_tag('option',array('value'=>$rol->id));
      $role .= $rol->shortname;
      $role .= html_writer::end_tag('option');
    }
  }
  $role .= html_writer::end_tag('select');
  //end the role dropdown.

  $departments=$DB->get_records_sql('SELECT DISTINCT(department) FROM {user}');
  $department='';
  $department=html_writer::start_tag('select', array('id'=>'site-dept','class'=>'custom-select'));
  $department .= html_writer::start_tag('option',array('value'=>''));
  $department .= get_string('selectdepartment','local_deptrpts');
  $department .= html_writer::end_tag('option');
  $count=1;
  foreach ($departments as $dept) {
    if($dept->department != '' ){
      $department .= html_writer::start_tag('option',array('value'=>$count));
      $department .= $dept->department;
      $department .= html_writer::end_tag('option');
      $count++;
    }
  }
  $department .= html_writer::end_tag('select');


    //creating usersearch dropdown.
  $uhtml='';
  $uhtml .= html_writer::start_tag('select', array('id'=>'usersearch'));
  $uhtml .= html_writer::end_tag('select');
    //usersearch dropdown end.

    //creating coursesearch dropdown.
  $chtml='';
  $chtml .= html_writer::start_tag('select', array('id'=>'coursesearch'));
  $chtml .= html_writer::end_tag('select');
    //coursesearch dropdown end.
  $bhtml='';
  $bhtml.=html_writer::start_tag('button');
  $bhtml.=html_writer::end_tag('button');
  //checking capabilities.
  $userreporthtml=array("hasfilter"=>1,
    'filtertitle'=>get_string('userreport','local_deptrpts'),
    'headingid'=>"headingTwo",
    'href'=>"collapseTwo",
    'icon'=>'<i class="fa fa-user-circle-o" aria-hidden="true"></i>',
    'input1title'=>get_string('selectlocation','local_deptrpts'),
    'input1html'=>$city,
    'input1id'=>'user_location',
    'input2title'=>get_string('selectuser','local_deptrpts'),
    'input2html'=>$uhtml,
    'input3title'=>'',
    'input3html'=>'',
    'input4title'=>'',
    'input4html'=>'',
    'button1title'=>'',
    'buttonid1'=>'',
    'sectionhelptxt'=>get_string('userrpt','local_deptrpts')
  );
  $sitereporthtml=array("hasfilter"=>1,
    'filtertitle'=>get_string('sitereport','local_deptrpts'),
    'headingid'=>"headingOne",
    'href'=>"collapseOne",
    'icon'=>'<i class="fa fa-bars" aria-hidden="true"></i>',
    'input1title'=>get_string('selectlocation','local_deptrpts'),
    'input1html'=>$city,
    'input1id'=>'site_location',
    'input2title'=>get_string('selectcategory','local_deptrpts'),
    'input2html'=>$category,
    'input3title'=>get_string('selectrole','local_deptrpts'),
    'input3html'=>$role,
    'input4title'=>get_string('selectdepartment','local_deptrpts'),
    'input4html'=>$department,
    'button1title'=>'',
    'buttonid1'=>'',
    'sectionhelptxt'=>get_string('siterpt','local_deptrpts')

  );
  $coursereporthtml=array("hasfilter"=>1,
    'filtertitle'=>get_string('coursereport','local_deptrpts'),
    'headingid'=>"headingThree",
    'href'=>"collapseThree",
    'icon'=>'<i class="fa fa-book" aria-hidden="true"></i>',
    'input1title'=>get_string('selectlocation','local_deptrpts'),
    'input1html'=>$city,
    'input1id'=>'course_location',
    'input2title'=>get_string('selectcourse','local_deptrpts'),
    'input2html'=>$chtml,
    'input3title'=>'',
    'input3html'=>'',
    'input4title'=>'',
    'input4html'=>'',
    'button1title'=>'',
    'buttonid1'=>'',
    'sectionhelptxt'=>get_string('courserpt','local_deptrpts')
  );
  $alluserreporthtml=array("hasfilter"=>1,
    'filtertitle'=>get_string('allusers','local_deptrpts'),
    'headingid'=>"headingFive",
    'href'=>"collapseFive",
    'icon'=>'<i class="fa fa-users" aria-hidden="true"></i>',
    'input1title'=>'',
    'input1html'=>'',
    'input1id'=>'',
    'input2title'=>'',
    'input2html'=>'',
    'input3title'=>'',
    'input3html'=>'',
    'input4title'=>'',
    'input4html'=>'',
    'button1title'=>get_string('allusersreport','local_deptrpts'),
    'buttonid1'=>'all_users',
    'sectionhelptxt'=>get_string('user','local_deptrpts')
  );
  $allcoursereporthtml=array("hasfilter"=>1,
    'filtertitle'=>get_string('allcourses','local_deptrpts'),
    'headingid'=>"headingFour",
    'href'=>"collapseFour",
    'icon'=>'<i class="fa fa-book" aria-hidden="true"></i>',
    'input1title'=>'',
    'input1html'=>'',
    'input1id'=>'',
    'input2title'=>'',
    'input2html'=>'',
    'input3title'=>'',
    'input3html'=>'',
    'input4title'=>'',
    'input4html'=>'',
    'button1title'=>get_string('allcoursesreport','local_deptrpts'),
    'buttonid1'=>'all_course',
    'sectionhelptxt'=>get_string('course','local_deptrpts')
  );
  if(is_siteadmin() || !empty($managercap)){
    $filterarray =['filters' =>array($sitereporthtml,$userreporthtml,$coursereporthtml,$alluserreporthtml,$allcoursereporthtml)];
  }else{
    $filterarray =['filters' =>array($coursereporthtml,$allcoursereporthtml)];
  }
  return $OUTPUT->render_from_template('local_deptrpts/filter_page', $filterarray);
}
/**
*Rachitha:this function will give top-cards.
* @param string $icon icon.
* @param string $cardtitle card-title.
* @param string $data card-data.
* @param string $color color.
* @return array $cardarray returning it contains icon,cardtitle,data,color.
*/
function filter_top_cards($icon,$cardtitle,$data,$color){
  global $DB, $OUTPUT;
  $cardarray=[
    'cards' =>array("hascard"=>1,
      'icon' =>$icon,
      'cardtitle' =>strtoupper($cardtitle),
      'data' =>$data,
      'color' =>$color)];

    return $OUTPUT->render_from_template('local_deptrpts/card', $cardarray);
  }

/**
*Rachitha:this function will give category wise charts.
* @param string $counter counter.
* @param string $categorydata categorydata.
* @param string $categoryname categoryname.
* @return array $cardarray returning it contains counter,categorydata,categoryname.
*/
function filter_categorywise_chart($counter,$categorydata,$categoryname,$categoryid){
  global $DB, $OUTPUT,$CFG;
  $chartarray=[
    'charts' =>array("counter"=>$counter,
      "chartdata"=>$categorydata,
      "categoryname"=>htmlspecialchars_decode($categoryname),
      "catid"=>$CFG->wwwroot.'/local/deptrpts/subcategoryview.php?id='.$categoryid
    )];
    return $OUTPUT->render_from_template('local_deptrpts/category', $chartarray);
  }

/**
*Rachitha:this function will give year wise enrollment and completion graphs.
* @param string $enrolllabel enrolllabel.
* @param string $enrolldata enrolldata.
* @param string $completelabel completelabel.
* @param string $completedata completedata.
* @return array $grapharray returning it contains enrolllabel,enrolldata,completelabel,completedata.
*/
function filter_yearly_enrol_completiongraph($enrolllabel,$enrolldata,$completelabel,$completedata){
  global $DB, $OUTPUT;
  $grapharray=[
    'graph' =>array("hasgraph" =>1,
      "enrollabel" =>$enrolllabel,
      "enroldata" =>$enrolldata,
      "completionlabel" =>$completelabel,
      "completiondata" =>$completedata,
      "heading"=>get_string('graphheadtext','local_deptrpts')
    )];
    return $OUTPUT->render_from_template('local_deptrpts/year_graphs', $grapharray);
  }

/**
*Rachitha:this function will give month wise enrollment and completion graphs.
* @param string $enrolldata enrolldata.
* @param string $completiondata completiondata.
* @return array $monthlyarray returning it contains enrolldata,completiondata.
*/
function filter_monthly_enrol_completiongraph($enrolldata,$completiondata){
  global $DB, $OUTPUT;

  $monthlyarray=[
    'graph' =>array("hasgraph" =>1,
      "enroldata" =>$enrolldata,
      "completiondata" =>$completiondata,
      "mheading"=>get_string('mgraphheadtext','local_deptrpts')
    )];
    return $OUTPUT->render_from_template('local_deptrpts/month_graphs', $monthlyarray);
  }

/**
*Rachitha:this function will give site datatable.
* @param string $sitedatatable sitedatatable.
* @return array $sitedatatable returning datatable.
*/
function site_datatable($sitedatatable){
  global $DB, $OUTPUT;
  return $OUTPUT->render_from_template('local_deptrpts/sitedatatable', $sitedatatable);
}

/**
*Rachitha:this function will give site datatable.
* @param string $cousrsedatatable cousrsedatatable.
* @return array $cousrsedatatable returning course-datatable.
*/
function course_datatable($cousrsedatatable){
  global $DB, $OUTPUT;
  $sitedatatable['courseheader']=get_string('courseheading','local_deptrpts');  
  $sitedatatable['coursehlptext']=get_string('coursehelptext','local_deptrpts');  
  return $OUTPUT->render_from_template('local_deptrpts/coursedatatable', $cousrsedatatable);
}

// function course_button(){
//   global $DB, $OUTPUT;
//   return $OUTPUT->render_from_template()
// }
/**
*Rachitha:this function will give data to four top cards.
* @param string $startdt startdate.
* @param string $enddt enddate.
* @param string $siteloc selected location from the filter.
* @param string $sitecat selected category from the filter.
* @return array $returnarray returning count of enrollment,completion,badges,certificates.
*/
function get_course_from_category($startdt,$enddt,$siteloc,$sitecat,$siterol,$sitedept){
  global $DB,$CFG,$USER;
  require_once($CFG->libdir.'/completionlib.php');
  $context = context_system::instance();
  $managercap = has_capability('local/deptrpts:managerreport',$context);
  $sql='';
  if(is_siteadmin()){
    $sql.="SELECT DISTINCT (c.id) 
    FROM {course} c
    JOIN {context} ct ON c.id = ct.instanceid
    JOIN {role_assignments} ra ON ra.contextid = ct.id
    JOIN {user} u ON u.id = ra.userid
    JOIN {role} r ON r.id = ra.roleid 
    WHERE c.visible = 1 AND c.id != 1";
  }else if($managercap){
  	$manager=$DB->get_record_sql("SELECT id FROM {user_info_field} WHERE shortname='managersemail'");
    $infodata=$DB->get_records_sql("SELECT userid FROM {user_info_data} WHERE fieldid='$manager->id' AND data='$USER->email'");
    $counter=1;
    $instring="";
    foreach ($infodata as $enkey => $envalue) {
    	if($counter == 1){
    		$instring = "'".$enkey."'";
      }else{
       $instring =$instring.","."'".$enkey."'";
     }
     $counter++;
   }
   $sql.="SELECT DISTINCT (c.id) 
   FROM {course} c
   JOIN {context} ct ON c.id = ct.instanceid
   JOIN {role_assignments} ra ON ra.contextid = ct.id
   JOIN {user} u ON u.id = ra.userid
   JOIN {role} r ON r.id = ra.roleid 
   WHERE c.visible = 1 AND c.id != 1 AND u.id in (".$instring.")";
 }
 if(!empty($sitecat)){
  $sql.=" AND c.category = '$sitecat'";
}
if(!empty($siteloc)){
  $sql.=" AND u.city = '$siteloc'";
}
  //here checking role.
if(!empty($siterol)){
  $sql.=" AND r.id = '$siterol'";
}
  //checking department.
if(!empty($sitedept)){
  $sql.=" AND u.department = '$sitedept'";
}
$courses = $DB->get_records_sql($sql);
  //here i am getting all courses from this category.
  //here intializing counts of completion,enrolled,badges,certificates.  
$totalcomplition=0;
$totalenrolled=0;
$totalbadges=0;
$totalcertificates=0;
if(!empty($courses)){
  foreach ($courses as $course) {
      //here i am getting total user enroll into this course.
    $courseenrolcount=enrolled_users_count_course($startdt,$enddt,$siteloc,$course->id,$siterol,$sitedept);
    $totalenrolled = $totalenrolled + $courseenrolcount;
    $enrolled = $DB->get_records_sql("
      SELECT DISTINCT (c.id), u.id
      FROM {course} c
      JOIN {context} ct ON c.id = ct.instanceid
      JOIN {role_assignments} ra ON ra.contextid = ct.id
      JOIN {user} u ON u.id = ra.userid
      JOIN {role} r ON r.id = ra.roleid
      where c.id = ".$course->id."");
        //here i am getting single user using foreach loop.
    foreach ($enrolled as $user) {
        //here getting complition status.
              //calculating completed users.
      $completesql='';
      $completesql.="SELECT cc.* FROM {course_completions} cc
      JOIN {user} u ON cc.userid = u.id
      JOIN {course} c ON c.id = cc.course 
      WHERE c.visible = 1 AND c.id != 1 AND u.id = '$user->id' AND c.id = '$course->id' AND cc.timecompleted IS NOT NULL";

      if(!empty($startdt) && !empty($enddt)){
        $completesql.=" AND cc.timecompleted BETWEEN ".$startdt." AND ".$enddt." ";
      }
      if(!empty($siteloc)){
        $completesql.=" AND u.city = '$siteloc'";
      }
      $completed=$DB->get_records_sql($completesql);
      $completedcount=count($completed);
      $totalcomplition = $totalcomplition + $completedcount;
        //here i am getting all badge count related to this category.
      $sql='';
      $sql.="SELECT b.* FROM {badge} b
      JOIN {badge_issued} bi ON bi.badgeid=b.id
      JOIN {user} u ON u.id=bi.userid  
      WHERE bi.userid='$user->id' AND b.courseid='$course->id'";
      if(!empty($startdt) && !empty($enddt)){
        $sql.=" AND bi.dateissued BETWEEN ".$startdt." AND ".$enddt." ";
      }
      if(!empty($siteloc)){
        $sql.=" AND u.city = '$siteloc'";
      }
      $badgecount = count($DB->get_records_sql($sql));
      $totalbadges = $totalbadges+$badgecount;
        // here i am getting all certificate count related to this category.
      $certificate='';
      $certificate.="SELECT * FROM {simplecertificate_issues} si 
      JOIN {simplecertificate} s ON s.id=si.certificateid 
      JOIN {user} u ON u.id=si.userid
      WHERE si.userid='$user->id' AND s.course ='$course->id'";
      if(!empty($startdt) && !empty($enddt)){
        $certificate.=" AND si.timecreated BETWEEN ".$startdt." AND ".$enddt." ";
      }
      if(!empty($siteloc)){
        $certificate.=" AND u.city = '$siteloc'";
      }
      $certificates=$DB->get_records_sql($certificate);
      $certificatecount = count($certificates);
      $totalcertificates = $totalcertificates+$certificatecount;
    }
  }
}
$returnarray = array('enrolcount'=>$totalenrolled,'completioncount'=>$totalcomplition,'badgecount'=>$totalbadges,'certificatecount'=>$totalcertificates);
return $returnarray ;
}

/**
*Rachitha:This function will return number of users enrolled into this course.- parameters with returning function.
* @param string $courseid icon.
* @return array $listofusers returning list of user.
*/
function all_enrolled_usersdata($courseid){
  global $DB,$CFG;
  $allenrolleduser=enrol_get_course_users($courseid);
  $listofusers =[];
  foreach ($allenrolleduser as $user) {
    $listofusers[] = $user->id;
  }
  sort($listofusers);
  return  $listofusers;
}

/**
*Rachitha:This function will return count of users enrolled into this course.- parameters with returning function.
* @param string $userid userid.
* @param string $startdate startdate.
* @param string $enddate enddate.
* @param string $city city.
* @return count $course returning count of user.
*/
function user_course_count($userid,$startdate,$enddate,$city){
  global $DB,$USER;
  $context = context_system::instance();
  $managercap = has_capability('local/deptrpts:managerreport',$context);
  $sql='';
  if(is_siteadmin()){
    $sql.="SELECT  DISTINCT(c.id)
    FROM {course} c
    JOIN {context} ct ON c.id = ct.instanceid
    JOIN {role_assignments} ra ON ra.contextid = ct.id
    JOIN {user} u ON u.id = ra.userid
    JOIN {role} r ON r.id = ra.roleid
    where c.visible = 1 ";
  }else if($managercap){
  	//gettin the managersemail field id.
  	$manager=$DB->get_record_sql("SELECT id FROM {user_info_field} WHERE shortname='managersemail'");
  	//getting all the userid having curently logged in managers email id.
  	$infodata=$DB->get_records_sql("SELECT userid FROM {user_info_data} WHERE fieldid='$manager->id' AND data='$USER->email'");
  	$counter=1;
  	$instring="";
  	foreach ($infodata as $userkey => $uservalue) {
     if($counter == 1){
       $instring ="'".$userkey."'";
     }else{
      $instring = $instring.","."'".$userkey."'";
    }
    $counter++;
  }
  $sql.="SELECT  DISTINCT(c.id)
  FROM {course} c
  JOIN {context} ct ON c.id = ct.instanceid
  JOIN {role_assignments} ra ON ra.contextid = ct.id
  JOIN {user} u ON u.id = ra.userid
  JOIN {role} r ON r.id = ra.roleid
  where c.visible = 1 AND u.id in (".$instring.")";
}
if($userid > 1){
  $sql.=" AND u.id = '$userid'";
}
if(!empty($startdate) && !empty($enddate)){
  $sql.=" AND ra.timemodified BETWEEN ".$startdate." AND ".$enddate." ";
}
if(!empty($city)){
  $sql.=" AND u.city = '$city'";
}
$courses = $DB->get_records_sql($sql);
if(!empty($courses)){
 return count($courses); 
}else{
  return 0;
} 
}

/**
*Rachitha:This function will return the no of the counting badge.
* @param string $userid userid.
* @param string $startdate startdate.
* @param string $enddate enddate.
* @param string $city city.
* @return count $badge returning count of badge.
*/
function user_badge_count($userid,$startdate,$enddate,$city){
  global $DB,$USER;
  //checking for manager cabilty.
  $sql="";
  $sql.="SELECT bi.id FROM {badge_issued} bi
  INNER JOIN {user} u ON u.id = bi.userid
  WHERE u.deleted = 0";
  if(!empty($userid) && $userid > 1){
    $sql.=" AND bi.userid = '$userid'";
  }
  if(!empty($startdate) && !empty($enddate)){
    $sql.= " AND bi.dateissued BETWEEN '$startdate' AND '$enddate'";
  }
  if(!empty($city)){
    $sql.=" AND u.city = '$city'";
  }
  $badges = $DB->get_records_sql($sql);
  if(!empty($badges)){
    return count($badges);
  }else{
    return 0;
  }
}

/**
*Rachitha:This function will return the no of the counting certificates.
* @param string $userid userid.
* @param string $startdate startdate.
* @param string $enddate enddate.
* @param string $city city.
* @return count $certificates returning count of certificates.
*/
function user_course_certificate($userid,$startdate,$enddate,$city){
  global $DB;
  if($userid > 1){
    $sql='';
    $sql="SELECT u.id,c.userid FROM {user} AS u INNER JOIN 
    {simplecertificate_issues} AS c ON u.id=c.userid WHERE u.id='$userid'";
    if(!empty($startdate) && !empty($enddate)){
      $sql.= " AND c.timecreated BETWEEN '$startdate' AND '$enddate'";
    }
    //here checking userlocation is empty or not.
    if(!empty($city)){
    //here adding this query to main query.
      $sql.=" AND u.city = '$city'";
    }
    $certificates=$DB->get_records_sql($sql);
    return count($certificates);
  }else if(!empty($city)){
    $sql="SELECT u.id,c.userid FROM {user} AS u INNER JOIN 
    {simplecertificate_issues} AS c ON u.id=c.userid WHERE u.city='$city'";
    if(!empty($startdate) && !empty($enddate)){
      $sql.= " AND c.timecreated BETWEEN '$startdate' AND '$enddate'";
    }
    $certificates=$DB->get_records_sql($sql);
    if(!empty($certificates)){
      return count($certificates);     
    }
    return 0;
  }
}

/**
*Rachitha:This function will return the no of the counting course completion.
* @param string $userid userid.
* @param string $startdate startdate.
* @param string $enddate enddate.
* @param string $city city.
* @return count $ret returning count of completion of course.
*/
function user_course_completion($userid,$startdate,$enddate,$city){
  global $DB;
  $sql='';
  $sql.="SELECT cc.id
  FROM {course_completions} cc
  INNER JOIN {user} u ON u.id = cc.userid
  WHERE u.deleted != 1 AND cc.timecompleted IS NOT NULL";
  if($userid > 1){
    $sql.=" AND u.id = '$userid'";
  }
  if(!empty($startdate) && !empty($enddate)){
    $sql.=" AND cc.timecompleted BETWEEN ".$startdate." AND ".$enddate." ";
  }
  if(!empty($city)){
    $sql.=" AND u.city = '$city'";
  }
  $courses=$DB->get_records_sql($sql);
  if(!empty($courses)){
    return count($courses); 
  }else{
    return 0;
  }
}

/**
*Rachitha:this function will give count of badges.
* @param string $startdt startdate.
* @param string $enddt enddate.
* @param string $courseloc selected location from the filter.
* @param string $courseid selected course from the filter.
* @return returning count of badges. 
**/
function get_badges_earned($startdt,$enddt,$courseloc,$courseid){
  global $DB;
  $sql='';
  $sql.="SELECT c.id,u.id 
  FROM {course} AS c 
  JOIN {badge} AS b ON c.id=b.courseid
  JOIN {badge_issued} AS bi ON bi.badgeid=b.id
  JOIN {user} AS u ON u.id=bi.userid
  WHERE c.visible = 1";
  if($courseid > 1){
    $sql.=" AND u.id = '$courseid'";
  }
  if(!empty($startdt) && !empty($enddt)){
    $sql.= " AND b.timecreated BETWEEN '$startdt' AND '$enddt'";
  }
  if(!empty($courseloc)){
  //here adding this query to main query.
    $sql.=" AND u.city = '$courseloc'";
  }
  $badges=$DB->get_records_sql($sql);
  if(!empty($badges)){
    return count($badges);
  }else{

   return 0;
 }
}

/**
*Rachitha:this function will give count of certificates.
* @param string $startdt startdate.
* @param string $enddt enddate.
* @param string $courseloc selected location from the filter.
* @param string $courseid selected course from the filter.
* @return returning count of certificates. 
**/
function get_course_cerficatescount($startdt,$enddt,$courseloc,$courseid){
  global $DB;
  if(!empty($courseid)){
    $sql='';
    $sql.="SELECT c.id,u.id 
    FROM {course} AS c 
    JOIN {simplecertificate} AS sc ON c.id=sc.course
    JOIN {simplecertificate_issues} AS si ON si.certificateid=sc.id
    JOIN {user} AS u ON u.id=si.userid
    WHERE c.visible=1";
    if($courseid > 1){
      $sql.=" AND u.id = '$courseid'";
    }

    if(!empty($startdt) && !empty($enddt)){
      $sql.= " AND si.timecreated BETWEEN '$startdt' AND '$enddt'";
    }
    if(!empty($courseloc)){
  //here adding this query to main query.
      $sql.=" AND u.city = '$courseloc'";
    }
    $certificates=$DB->get_records_sql($sql);
    if(!empty($certificates)){
      return count($certificates);
    }else{
      return 0;
    }
  }
}
/**
*Rachitha:this function will give count of course completion.
* @param string $courseid selected category from the filter.
* @return $totalcomplition returning count of course completion. 
**/
function course_complition_count($startdt,$enddt,$courseloc,$courseid){
  global $DB;
  $sql='';
  $sql.="SELECT cc.id
  FROM {course_completions} cc
  INNER JOIN {user} u ON u.id = cc.userid
  WHERE u.deleted != 1 AND cc.timecompleted IS NOT NULL";
  if($courseid > 1){
    $sql.=" AND cc.course = '$courseid'";
  }
  if(!empty($startdate) && !empty($enddate)){
    $sql.=" AND cc.timecompleted BETWEEN ".$startdate." AND ".$enddate." ";
  }
  if(!empty($courseloc)){
    $sql.=" AND u.city = '$courseloc'";
  }
  $courses=$DB->get_records_sql($sql);
  if(!empty($courses)){
    return count($courses); 
  }else{
    return 0;
  }  
}

/**
* Rachitha:This function will return no of completed,inprogress, not started users of all courses present in particular category.
* @param string $startdt startdate.
* @param string $enddt enddate.
* @param string $sitecat selected category from the filter.
* @param string $siteloc selected location from the filter.
* @return array returning $completed,$inprogress,$notstarted,$categoryname. 
**/
function course_completion_stats($startdt=null,$enddt=null,$sitecat=null,$siteloc=null,$catfirstload){
  global $DB;
  $sql="";
  $categoryname='';
  $sql.="SELECT DISTINCT (c.id) 
  FROM {course} c
  JOIN {context} ct ON c.id = ct.instanceid
  JOIN {role_assignments} ra ON ra.contextid = ct.id
  JOIN {user} u ON u.id = ra.userid
  JOIN {role} r ON r.id = ra.roleid 
  WHERE c.visible = 1 AND c.id != 1";
  if(!empty($sitecat)){
    $sql.=" AND c.category = '$sitecat'";
    $categoryname=$DB->get_field('course_categories', 'name', array('id'=>$sitecat));
  }
  if(!empty($siteloc)){
    $sql.=" AND u.city = '$siteloc'";
  }
  $courses=$DB->get_records_sql($sql);
  $compcount=0;
  $inprocount=0;
  $notstcount=0;
  if(!empty($courses)){
    foreach ($courses as $course) {
      //calculating not started users.
     $sql='';
     $sql.="SELECT cc.* FROM {course_completions} cc
     JOIN {user} u ON cc.userid = u.id
     JOIN {course} c ON c.id = cc.course 
     WHERE c.visible = 1 AND c.id != 1 AND c.id = '$course->id' AND cc.timeenrolled IS NOT NULL AND cc.timestarted = 0 AND cc.timecompleted is null ";

     if(!empty($startdt) && !empty($enddt)){
      $sql.=" AND cc.timeenrolled BETWEEN ".$startdt." AND ".$enddt." ";
    }
    if(!empty($siteloc)){
      $sql.=" AND u.city = '$siteloc'";
    }
    $notstarted=$DB->get_records_sql($sql);
    $notstartedcount=count($notstarted);
      //calculating inprogress users.
    $inprogresssql='';
    $inprogresssql.="SELECT cc.* FROM {course_completions} cc
    JOIN {user} u ON cc.userid = u.id
    JOIN {course} c ON c.id = cc.course 
    WHERE c.visible = 1 AND c.id != 1 AND c.id = '$course->id' AND cc.timeenrolled IS NOT NULL AND cc.timestarted != 0 AND cc.timecompleted is null";

    if(!empty($startdt) && !empty($enddt)){
      $inprogresssql.=" AND cc.timeenrolled BETWEEN ".$startdt." AND ".$enddt." ";
    }
    if(!empty($siteloc)){
      $inprogresssql.=" AND u.city = '$siteloc'";
    }
    $inprogress=$DB->get_records_sql($inprogresssql);
    $inprogresscount=count($inprogress);
      //calculating completed users.
    $completesql='';
    $completesql.="SELECT cc.* FROM {course_completions} cc
    JOIN {user} u ON cc.userid = u.id
    JOIN {course} c ON c.id = cc.course 
    WHERE c.visible = 1 AND c.id != 1 AND c.id = '$course->id' AND cc.timecompleted IS NOT NULL";

    if(!empty($startdt) && !empty($enddt)){
      $completesql.=" AND cc.timecompleted BETWEEN ".$startdt." AND ".$enddt." ";
    }
    if(!empty($siteloc)){
      $completesql.=" AND u.city = '$siteloc'";
    }
    $completed=$DB->get_records_sql($completesql);
    $completedcount=count($completed);

    $compcount = $compcount + $completedcount;
    $inprocount= $inprocount + $inprogresscount;
    $notstcount=$notstcount + $notstartedcount;
  }
  return array($compcount,$inprocount,$notstcount,$categoryname);
}
}

/**
* Rachitha:This function will return year wise enrolment and completion of user's info.
* @param string $courseid selected course from the filter.
* @return array returning $enrolled,$complition. 
**/
function get_yearwisecategory_info($startdt,$enddt,$siteloc,$categoryid,$siterol,$sitedept){
  global $DB;
  $sql="";
  $sql.="SELECT DISTINCT (c.id) 
  FROM {course} c
  JOIN {context} ct ON c.id = ct.instanceid
  JOIN {role_assignments} ra ON ra.contextid = ct.id
  JOIN {user} u ON u.id = ra.userid
  JOIN {role} r ON r.id = ra.roleid 
  WHERE c.visible = 1 AND c.id != 1";
  if(!empty($categoryid)){
    $sql.=" AND c.category = '$categoryid'";
  }
  if(!empty($siteloc)){
    $sql.=" AND u.city = '$siteloc'";
  }
  if(!empty($siterol)){
    $sql.=" AND r.id = '$siteloc'";
  }
  if(!empty($sitedept)){
    $sql.=" AND u.department = '$siteloc'";
  }
  $courses = $DB->get_records_sql($sql);
  $enrolled=[];
  $complition=[];
  if(!empty($courses)){
    foreach ($courses as $course) {
      if(!empty($course)){
        $result = get_yearwisegraph($course->id, $startdt, $enddt, $siteloc);
      }
      foreach ($result as $rkey => $rvalue) {
        if($rkey=='enrolldata'){
          foreach ($rvalue as $rrkey => $rrvalue) {
            if(array_key_exists($rrkey, $enrolled)){
              $enrolled[$rrkey] += $rrvalue;
            }else{
              $enrolled[$rrkey]=$rrvalue;

            }

          }

        }
        if($rkey=='completiondata'){
          foreach ($rvalue as $ckey => $cvalue) {
            if(array_key_exists($ckey, $complition)){
              $complition[$ckey] += $cvalue;
            }else{
              $complition[$ckey]=$cvalue;
            }

          }
        }
      }

    }
  }
  return array('enrol'=>$enrolled,'complete'=>$complition);
}

/**
* Rachitha:This function will return year wise enrollment and completion of graphs.
* @param string $course_id courseid.
* @param string $start_date startdate.
* @param string $end_date enddate.
* @param string $city selected location from the filter.
* @param string $institution selected institution from the filter.
* @param string $department selected department from the filter.
* @return array returning $returnarray. 
**/
function get_yearwisegraph($courseid, $startdt, $enddt, $courseloc){
  global $DB;
  //here getting enroll course data.
  $enrollsql='';
  $enrollsql.="SELECT cc.id,cc.timeenrolled FROM {course_completions} cc
  INNER JOIN {user} u ON cc.userid = u.id WHERE cc.timeenrolled IS NOT NULL";
  if($courseid > 1){
    $enrollsql.=" AND cc.course = '$courseid' ";   
  }

  if(!empty($startdt) && !empty($enddt)){
    $enrollsql.=" AND cc.timeenrolled BETWEEN ".$startdt." AND ".$enddt." ";   
  }

  if(!empty($courseloc)){
    $enrollsql.=" AND u.city = '$courseloc'";   
  }
  $enrolldates=$DB->get_records_sql($enrollsql);
  $convdate=[];
  if(!empty($enrolldates)){
    foreach ($enrolldates as $enrolldate) { 
      if(!empty($enrolldate->timeenrolled)){
        $convdate[]=date('Y', $enrolldate->timeenrolled);
      }
    }
  }
  $userenrolconvyear = array_count_values($convdate);

//getting completion dates.
  $sql='';
  $sql.="SELECT cc.id,cc.timecompleted FROM {course_completions} cc
  INNER JOIN {user} u ON cc.userid = u.id WHERE cc.timecompleted IS NOT NULL";
  if($courseid > 1){
    $sql.=" AND cc.course = '$courseid' ";   
  }

  if(!empty($startdt) && !empty($enddt)){
    $sql.=" AND cc.timecompleted BETWEEN ".$startdt." AND ".$enddt." ";   
  }

  if(!empty($courseloc)){
    $sql.=" AND u.city = '$courseloc'";   
  }
  $completiondata=$DB->get_records_sql($sql);
  $cmpletiondate=[];
  if(!empty($completiondata)){
    foreach ($completiondata as $compledata) {
      if(!empty($compledata)){
        $cmpletiondate[]=date('Y',$compledata->timecompleted);
      }  
    }
  }
  $usercompleconvyear = array_count_values($cmpletiondate);
  $returnarray = array('enrolldata'=>$userenrolconvyear,
    'completiondata'=>$usercompleconvyear);
  return $returnarray;
}

/**
* Rachitha:This function will return number of users enrolled into this course parameters with returning function.
* @param string $course_id courseid.
* @return array returning $listofusers. 
**/
function all_enrolled_usersdata_date($courseid){
  global $DB,$CFG;
  $allenrolleduser=enrol_get_course_users($courseid);
  $listofusers =[];
  foreach ($allenrolleduser as $user) {
    $listofusers[] = $user->uetimecreated;
  }
  sort($listofusers);
  return  $listofusers;
}

/**
* Rachitha:this function is used to year wise course completion data.
* @param string $userid userid.
* @return array returning $years. 
**/
function get_user_yearly_completion($startdt,$enddt,$userloc,$userid){
  global $DB;
  $sql='';
  $sql.="SELECT cc.id,cc.timecompleted FROM {course_completions} cc
  INNER JOIN {user} u ON cc.userid = u.id WHERE cc.timecompleted IS NOT NULL";
  if($userid > 1){
    $sql.=" AND cc.userid = '$userid' ";   
  }

  if(!empty($startdt) && !empty($enddt)){
    $sql.=" AND cc.timecompleted BETWEEN ".$startdt." AND ".$enddt." ";   
  }

  if(!empty($userloc)){
    $sql.=" AND u.city = '$userloc'";   
  }

  $year=$DB->get_records_sql($sql);
  $emptyarray=[];
  foreach ($year as  $yearcompleted) {
    if(!empty($yearcompleted->timecompleted)){
      $singleyear=$yearcompleted->timecompleted;
      $emptyarray[] = date('Y',$singleyear);
    }
  }
  $years = array_count_values($emptyarray);
  return $years;
}

/**
* Rachitha:this function is used to month wise course completion data..
* @param string $userid userid.
* @return array returning $years. 
**/
function get_enrolled_course_yearly($startdt,$enddt,$userloc,$userid){
  global $DB;
  $sql='';
  $sql.="SELECT cc.id,cc.timeenrolled FROM {course_completions} cc
  INNER JOIN {user} u ON cc.userid = u.id WHERE cc.timeenrolled IS NOT NULL";
  if($userid > 1){
    $sql.=" AND cc.userid = '$userid' ";   
  }

  if(!empty($startdt) && !empty($enddt)){
    $sql.=" AND cc.timeenrolled BETWEEN ".$startdt." AND ".$enddt." ";   
  }

  if(!empty($userloc)){
    $sql.=" AND u.city = '$userloc'";   
  }
  $year=$DB->get_records_sql($sql);
  $emptyarray=[];
  foreach ($year as  $yearenrolled) {
    if(!empty($yearenrolled->timeenrolled)){   
      $singleyear=$yearenrolled->timeenrolled;
      $emptyarray[] = date('Y',$singleyear);
    }
  }
  $years = array_count_values($emptyarray);
  return $years;  
}

/**
* Rachitha: This function is used to detail of enrolled course.
* @param string $userid userid.
* @return array returning $montharray. 
**/
function get_course_enrolled_info($startdt,$enddt,$userloc,$userid){
  global $DB;
  $sql='';
  $sql.="SELECT cc.id,cc.timeenrolled FROM {course_completions} cc
  INNER JOIN {user} u ON cc.userid = u.id WHERE cc.timeenrolled IS NOT NULL";
  if($userid > 1){
    $sql.=" AND cc.userid = '$userid' ";   
  }

  if(!empty($startdt) && !empty($enddt)){
    $sql.=" AND cc.timeenrolled BETWEEN ".$startdt." AND ".$enddt." ";   
  }

  if(!empty($userloc)){
    $sql.=" AND u.city = '$userloc'";   
  }
  // here getting enrolement time.  
  $information=$DB->get_records_sql($sql);
  $abc=[];
  //here getting single value.
  foreach($information as $info){
    $singleinfo=$info->timeenrolled;
    $abc[]=date("m",$singleinfo);
  }
  // this function is used to count the array value.
  $months = array_count_values($abc);
  $montharray=[];
  $marray = array('Jan','Feb','Mar','Apl','May','Jun','Jul','Aug','Sep','Act','Nov','Dec');
  for ($i=01; $i < 13; $i++) { 
    if($i <= 9){
      $i = '0'.$i;
    }
  // this function is used to check whether a specified key is present in an array or not. 
    if (array_key_exists($i,$months)){
      $montharray[$marray[$i-1]] = $months[$i];
    }else{
      $montharray[$marray[$i-1]] = 0;
    }
  }
  return $montharray;
}

/**
* Rachitha: this function is used to completion of course.
* @param string $userid userid.
* @return array returning $montharray. 
**/
function get_course_completion($startdt,$enddt,$userloc,$userid){
  global $DB;
  $sql='';
  $sql.="SELECT cc.id,cc.timecompleted FROM {course_completions} cc
  INNER JOIN {user} u ON cc.userid = u.id WHERE cc.timecompleted IS NOT NULL";
  if($userid > 1){
    $sql.=" AND cc.userid = '$userid' ";   
  }

  if(!empty($startdt) && !empty($enddt)){
    $sql.=" AND cc.timecompleted BETWEEN ".$startdt." AND ".$enddt." ";   
  }

  if(!empty($userloc)){
    $sql.=" AND u.city = '$userloc'";   
  }

  // here getting enrolement time.  
  $information=$DB->get_records_sql($sql);
  $abc=[];
  //here getting single value.
  foreach($information as $info){
    $singleinfo=$info->timecompleted ;
    $abc[]=date("m",$singleinfo);
  }
  // this function is used to count the array value.
  $months = array_count_values($abc);
  $montharray=[];
  $marray = array('Jan','Feb','Mar','Apl','May','Jun','Jul','Aug','Sep','Act','Nov','Dec');
  for ($i=01; $i < 13; $i++) { 
    if($i <= 9){
      $i = '0'.$i;
    }
    // this function is used to check whether a specified key is present in an array or not. 
    if (array_key_exists($i,$months)){
      $montharray[$marray[$i-1]] = $months[$i];
    }else{
      $montharray[$marray[$i-1]] = 0;
    }
  }
  return $montharray;
}

/**
* Rachitha: this function will give year wise enrollment and completion data.
* @param string $courseid courseid.
* @return array returning $montharray. 
**/
function yearwise_course_enrollment_data($startdt,$enddt,$courseloc,$courseid){
 global $DB;
 $sql='';
 $sql.="SELECT cc.id,cc.timecompleted FROM {course_completions} cc
 INNER JOIN {user} u ON cc.userid = u.id WHERE cc.timecompleted IS NOT NULL";
 if($courseid > 1){
  $sql.=" AND cc.course = '$courseid' ";   
}

if(!empty($startdt) && !empty($enddt)){
  $sql.=" AND cc.timecompleted BETWEEN ".$startdt." AND ".$enddt." ";   
}

if(!empty($courseloc)){
  $sql.=" AND u.city = '$courseloc'";   
}
$completiondata = $DB->get_records_sql($sql);
$completiondate=[];
foreach ($completiondata as $comkey => $comvalue) {
  if(!empty($comvalue->timecompleted)){
    $completiondate[]=date('Y', $comvalue->timecompleted);
  }
  
}
$completionyear = array_count_values($completiondate);
$complabel="";
$compdata="";
$counter=1;
if(!empty($completionyear)){
  foreach ($completionyear as $cmkey => $cmvalue) {
    if($counter==1){
      $complabel="'".$cmkey."'";
      $compdata=$cmvalue;
    }else{
      $complabel=$enrolllabel.','."'".$cmkey."'";
      $compdata=$enrolldata.','.$cmvalue;
    }
    $counter++;
  }
}
//enrolled data for year wise graph.
$enrollsql='';
$enrollsql.="SELECT cc.id,cc.timeenrolled FROM {course_completions} cc
INNER JOIN {user} u ON cc.userid = u.id WHERE cc.timeenrolled IS NOT NULL";
if($courseid > 1){
  $enrollsql.=" AND cc.course = '$courseid' ";   
}

if(!empty($startdt) && !empty($enddt)){
  $enrollsql.=" AND cc.timeenrolled BETWEEN ".$startdt." AND ".$enddt." ";   
}

if(!empty($courseloc)){
  $enrollsql.=" AND u.city = '$courseloc'";   
}

$enrolldates=$DB->get_records_sql($enrollsql);
$convdate=[];
if(!empty($enrolldates)){
  foreach ($enrolldates as $condate) { 
    if(!empty($condate->timeenrolled)){
      $convdate[]=date('Y', $condate->timeenrolled);
    }
  }
}

$userenrolconvyear = array_count_values($convdate);
$enrolllabel ="";
$enrolldata ="";
$counter=1;
if(!empty($userenrolconvyear)){
  foreach ($userenrolconvyear as $enkey => $envalue) {
    if($counter==1){
      $enrolllabel="'".$enkey."'";
      $enrolldata=$envalue;
    }else{
      $enrolllabel=$enrolllabel.','."'".$enkey."'";
      $enrolldata=$enrolldata.','.$envalue;
    }
    $counter++;
  }
}
return array($enrolllabel,$enrolldata,$complabel,$compdata);
}

/**
* Rachitha: this function will give list of enrollement users inside courses.
* @param string $courseid courseid.
* @return array returning $listofusers. 
**/
function get_enroled_userdata($courseid){
  global $DB,$CFG;
  $allenrolleduser= enrol_get_course_users($courseid);

  foreach ($allenrolleduser as $user) {
    $listofusers[] = array($user->id,$user->uetimecreated);
  }
  if(!empty($listofusers)){
    sort($listofusers);
    return  $listofusers;
  }
}

/**
* Rachitha: this function will give user's datatable.
* @param string $startdt startdate selecting startdate from the filter..
* @param string $enddt enddate selecting enddate from the filter..
* @param string $userloc selecting location from the filter.
* @param string $userid userid.
* @return array returning $retarray. 
**/
function userdatatable_report($startdt,$enddt,$userloc,$userid){
  global $DB,$USER;
  $context = context_system::instance();
  $managercap = has_capability('local/deptrpts:managerreport',$context);
  $sql='';
  //checking for admin capability.
  if(is_siteadmin()){
    $sql.="SELECT ra.id as roleid,u.id as userid, c.id,u.firstname, u.lastname, c.fullname, u.email
    FROM mdl_course AS c
    JOIN mdl_context AS ctx ON c.id = ctx.instanceid
    JOIN mdl_role_assignments AS ra ON ra.contextid = ctx.id
    JOIN mdl_user AS u ON u.id = ra.userid
    WHERE c.visible = 1 ";
  }else if($managercap){
    $manager=$DB->get_record_sql("SELECT id FROM {user_info_field} WHERE shortname='managersemail'");
    $infodata=$DB->get_records_sql("SELECT userid FROM {user_info_data} WHERE fieldid='$manager->id' AND data='$USER->email'");
    $counter=1;
    $instring="";
    foreach ($infodata as $tkey => $tvalue) {
      if($counter == 1){
        $instring="'".$tkey."'";
      }else{
        $instring = $instring.","."'".$tkey."'";
      }
      $counter++;
    }
    $sql.="SELECT ra.id as roleid,u.id as userid, c.id,u.firstname, u.lastname, c.fullname, u.email
    FROM mdl_course AS c
    JOIN mdl_context AS ctx ON c.id = ctx.instanceid
    JOIN mdl_role_assignments AS ra ON ra.contextid = ctx.id
    JOIN mdl_user AS u ON u.id = ra.userid
    WHERE c.visible = 1 AND u.id in (".$instring.")";
  }else{
    $sql.="SELECT ra.id as roleid,u.id as userid, c.id,u.firstname, u.lastname, c.fullname, u.email
    FROM mdl_course AS c
    JOIN mdl_context AS ctx ON c.id = ctx.instanceid
    JOIN mdl_role_assignments AS ra ON ra.contextid = ctx.id
    JOIN mdl_user AS u ON u.id = ra.userid
    WHERE c.visible = 1 AND u.id = '$userid'";
  }
  //here checking startdate and enddate are empty or not.
  if(!empty($startdt) && !empty($enddt)){
      //here adding this query to main query.
    $sql.=" AND ra.timemodified BETWEEN '$startdt' AND '$enddt'";
  }
  //here checking userlocation is empty or not.
  if(!empty($userloc)){
      //here adding this query to main query.
    $sql.=" AND u.city = '$userloc'";
  }
  //here getting all enrolled courses from userid.
  $courses = $DB->get_records_sql($sql);
  //here creating data for table.
  if(!empty($courses)){
    $counter=1;
    foreach ($courses as $course) {
      //userfullname
      $userfullname=$course->firstname.' '.$course->lastname;
      $email=$course->email;
      $coursename=$course->fullname;
      $enroldate=get_enrolement_date_user($course->id,$course->userid);
      if(!empty($enroldate)){
        $enroltime=date('d-m-Y', $enroldate);
      }else{
        $enroltime="-";
      }
      //here i am finding course complition date.
      $completion=$DB->get_record_sql("SELECT timecompleted FROM {course_completions} WHERE course=".$course->id." AND userid=".$course->userid." AND timecompleted IS NOT NULL");
      if(!empty($completion)){
        $completiondate=date('d-m-Y',$completion->timecompleted);
      }else{
        $completiondate="-";
      }
      $courseobject=$DB->get_record('course',array('id'=>$course->id));
      $cinfo = new completion_info($courseobject);
      $iscomplete = $cinfo->is_course_complete($course->userid);
      if(!empty($iscomplete)){

        $status=get_string('complet','local_deptrpts');
      }else
      {
        $status=get_string('notcomplete','local_deptrpts');
      }
      $grade = grade_get_course_grades($course->id, $course->userid);
      $grd = $grade->grades[$course->userid]; 
      $cgrade=$grd->str_grade;
      $html='';
      $html.=html_writer::start_tag('a',array('data-toggle'=>'modal', 'data-target'=>'#exampleModal'));
      $html.='<i class="fa fa-envelope" aria-hidden="true"></i>';
      $html.=html_writer::end_tag('a');
      $datatablearray[]=array("counter"=>$counter,"username"=>$userfullname,"emailid"=>$email,"coursefullname"=>$coursename,"enrolledtime"=>$enroltime,"completiontime"=>$completiondate,"completionstatus"=>$status,"coursegrade"=>$cgrade,"action"=>$html);
      $counter++;
    }
    $retarray=[];
    $retarray['tabledata']=$datatablearray;
    $retarray['dynlink']="/local/deptrpts/downloadexcel.php?strtdate=".$startdt."&endate=".$enddt."&ulocation=".$userloc."&userselect=".$course->userid."&status=user";
    return  $retarray;
  }
}

/**
* Rachitha: this function will give user enrollment  date in a course.
* @param string $courseid userid.
* @param string $userid userid.
* @return array returning $user->uetimecreated. 
**/
function get_enrolement_date_user($courseid,$userid){
  global $DB,$CFG;
  $allenrolleduser= enrol_get_course_users($courseid);

  foreach ($allenrolleduser as $user) {
    if($userid ==$user->id){
      return $user->uetimecreated;
    }
  }
}

/**
* Rachitha: this function will give course datatable.
* @param string $startdt startdate.
* @param string $enddt enddate.
* @param string $courseloc selecting location from the filter part.
* @param string $courseid courseid.
* @return array returning $retarray. 
**/
function course_report_datatable($startdt,$enddt,$courseloc,$courseid){
  global $DB;
  //getting all enrolled user-data from course.
  //$enrolusers=all_enrolled_usersdata($courseid);
  //here getting list of activities given courseid.
  $activities=get_fast_modinfo($courseid);
  $activity=$activities->get_cms();
  //here getting all list of activities grade.
  //here I am getting all the course in a course.
  $activities=list_all_activities_grade($courseid);
  $activityname=[];
  $gradevalue=[];
  foreach ($activities as $akey => $avalue) {
    $activityname[]=$avalue['activityname'];
    $gradevalue[]=$avalue['gradeval'];
  } 
  $header1=array(get_string('serial','local_deptrpts'),get_string('fullname','local_deptrpts'),get_string('email','local_deptrpts'));
  $header2=$activityname;
  $header3=array(get_string('enrolmentdate','local_deptrpts'),get_string('completiondate','local_deptrpts'),get_string('completionstatus','local_deptrpts'),get_string('coursegrade','local_deptrpts'));
  $tableheader =array_merge($header1,$header2,$header3);
  $counter=1;
  $course=$DB->get_record('course',array('id'=>$courseid));
  $enrolldata=get_enroled_userdata($courseid);
  if(!empty($enrolldata)){
    foreach ($enrolldata as $enkey => $envalue) {
      $user=$DB->get_record('user',array('id'=>$envalue[0]));
      if(!empty($user)){

        if(!empty($envalue[1])){
          $enroldate=date('d-m-Y', $envalue[1]);

        }else{
          $enroldate="-";
        }
        $fullname=$user->firstname.' '.$user->lastname;
        $email=$user->email;
        $coursename=$course->fullname;
        $completion=$DB->get_record_sql("SELECT timecompleted FROM {course_completions} WHERE course=".$courseid." AND userid=".$envalue[0]." AND timecompleted IS NOT NULL");
        if(!empty($completion)){
          $completiondate=date('d-m-Y',$completion->timecompleted);
        }else{
          $completiondate="-";
        }

        $course=$DB->get_record('course',array('id'=>$courseid));
        $cinfo = new completion_info($course);
        $iscomplete = $cinfo->is_course_complete($user->id);
        if(!empty($iscomplete)){

          $status=get_string('complet','local_deptrpts');
        }else
        {
          $status=get_string('notcomplete','local_deptrpts');
        }
        $actgrades=list_all_activities_grade($courseid,$user->id);
        $gradevalue=[];
        foreach ($actgrades as $gkey => $gvalue) {
          if(!empty($gvalue['gradeval'])){
            $gradevalue[]=$gvalue['gradeval'];
          }else{
            $gradevalue[]='-';
          }
        }
        $grade = grade_get_course_grades($courseid, $envalue[0]);
        $grd = $grade->grades[$envalue[0]];
        $cgrade=$grd->str_grade;
        $html='';
        $html.=html_writer::start_tag('a',array('data-toggle'=>'modal', 'data-target'=>'#exampleModal'));
        $html.='<i class="fa fa-envelope" aria-hidden="true"></i>';
        $html.=html_writer::end_tag('a');
        $data1=array($counter,$fullname,$email);
        $data2=$gradevalue;
        $data3=array($enroldate,$completiondate,$status,$cgrade,$html);
        $tabledata[]['tdata']=array_merge($data1,$data2,$data3);
        $counter++;
      }
    }
    $retarray=[];
    $retarray['tableheader']=$tableheader;
    $retarray['tabledata']=$tabledata;
        // print_object($retarray);
    return  $retarray;
  }
}

/**
* Rachitha: this function will give list of activities grade.
* @param string $courseid courseid.
* @param string $userid userid.
* @return array returning $returnarray. 
**/
function list_all_activities_grade($courseid, $userid = null){
  global $DB,$CFG,$USER;
  if(is_null($userid)){
    $userid = $USER->id;
  }
  $course = $DB->get_record('course', array('id' => $courseid));
  $notgrade = [];
  $modinfo = get_fast_modinfo($course);
  $returnarray=[];
  $activitygrade=[];
  foreach($modinfo->get_cms() as $cm) {
    $grades = grade_get_grades($courseid, 'mod', $cm->modname, $cm->instance,$userid);
    if(!empty($grades->items)){
      foreach ($grades->items as $gradekey => $gradevalue) {
        if($gradevalue->locked != 1){
          $activityname = $gradevalue->name;
          foreach ($gradevalue->grades as $gkey => $gvalue) {
            $activitygrade = $gvalue->grade;
          }
          $returnarray[] = array('activityname'=>$activityname,'gradeval'=>$activitygrade);
        }
      }
    }
  }
  return $returnarray;
}


/**
*Rachitha:this function will give count of badges.
* @param string $startdt startdate.
* @param string $enddt enddate.
* @param string $courseloc selected location from the filter.
* @param string $courseid selected course from the filter.
* @return returning count of badges. 
**/
function enrolled_users_count_course($startdate,$enddate,$location,$courseid){
  global $DB;
  $enrollsql='';
  $enrollsql.="SELECT cc.id,cc.timeenrolled FROM {course_completions} cc
  INNER JOIN {user} u ON cc.userid = u.id WHERE cc.timeenrolled IS NOT NULL";
  if($courseid > 1){
    $enrollsql.=" AND cc.course = '$courseid' ";   
  }

  if(!empty($startdate) && !empty($enddate)){
    $enrollsql.=" AND cc.timeenrolled BETWEEN ".$startdate." AND ".$enddate." ";   
  }

  if(!empty($location)){
    $enrollsql.=" AND u.city = '$location'";   
  }

  $courses = $DB->get_records_sql($enrollsql);
  if(!empty($courses)){
   return count($courses); 
 }else {
  return false;
}
}

/**
*Rachitha:this function will give month wise course enrollment.
* @param string $startdt startdate.
* @param string $enddt enddate.
* @param string $courseloc courselocation.
* @param  string $courseid courseid.
* @return $menroll,$mcomplete return month wise enrollment and completion data.
*/
function monthwise_course_enrollment_data($startdt,$enddt,$courseloc,$courseid){
  global $DB;
  $sql='';
  $sql.="SELECT cc.id,cc.timecompleted FROM {course_completions} cc
  INNER JOIN {user} u ON cc.userid = u.id WHERE cc.timecompleted IS NOT NULL";
  if($courseid > 1){
    $sql.=" AND cc.course = '$courseid' ";   
  }

  if(!empty($startdt) && !empty($enddt)){
    $sql.=" AND cc.timecompleted BETWEEN ".$startdt." AND ".$enddt." ";   
  }

  if(!empty($courseloc)){
    $sql.=" AND u.city = '$courseloc'";   
  }
  $completiondata=$DB->get_records_sql($sql);
  $completeddate=[];
  foreach ($completiondata as $ckey => $cvalue) {
    $completeddate[]=$cvalue->timecompleted;    
  }

  $completearray=[];
  foreach ($completeddate as $singledate) {
    $completearray[]=date("m",$singledate);

  }
  $months = array_count_values($completearray);

  $montharray=[];
  $marray = array('Jan','Feb','Mar','Apl','May','Jun','Jul','Aug','Sep','Act','Nov','Dec');
  for ($i=01; $i < 13; $i++) { 
    if ($i <= 9) {
      $i = '0'.$i;
    }
    if(array_key_exists($i, $months)){
      $montharray[$marray[$i-1]] = $months[$i];
    }else{
      $montharray[$marray[$i-1]] = 0;
    }
  }

  //here getting enroll course data.
  $enrollsql='';
  $enrollsql.="SELECT cc.id,cc.timeenrolled FROM {course_completions} cc
  INNER JOIN {user} u ON cc.userid = u.id WHERE cc.timeenrolled IS NOT NULL";
  if($courseid > 1){
    $enrollsql.=" AND cc.course = '$courseid' ";   
  }

  if(!empty($startdt) && !empty($enddt)){
    $enrollsql.=" AND cc.timeenrolled BETWEEN ".$startdt." AND ".$enddt." ";   
  }

  if(!empty($courseloc)){
    $enrollsql.=" AND u.city = '$courseloc'";   
  }
  $enrolldate=$DB->get_records_sql($enrollsql);

  $emptyarray=[];
  if (!empty($enrolldate)) {
    foreach($enrolldate as $singledate){
      $enrol=$singledate->timeenrolled;
      $emptyarray[]=date('m', $enrol);
    }
  }
  $enrolcourse = array_count_values($emptyarray);
  $enrollarray=[];
  $enrolldate = array('Jan','Feb','Mar','Apl','May','Jun','Jul','Aug','Sep','Act','Nov','Dec');
  for($i=01; $i< 13; $i++){
    if($i <= 9){
      $i = '0'.$i;
    }
    if(array_key_exists($i, $enrolcourse)){
      $enrollarray[$enrolldate[$i-1]] = $enrolcourse[$i];
    }else{
      $enrollarray[$enrolldate[$i-1]] = 0;
    }
  }
  $count =1;
  $menroll = '';
  foreach ($enrollarray as $mkey => $mvalue) {
    if($count == 1){
      $menroll = $mvalue;
    }else{
      $menroll = $menroll.','.$mvalue;
    }
    $count++;
  }
  $temp = 1;
  $mcomplete ='';
  foreach ($montharray as $ckey => $cvalue) {
    if($temp == 1){
      $mcomplete = $cvalue;
    }else{
      $mcomplete = $mcomplete.','.$cvalue;

    }
    $temp++;
  }
  return array($menroll,$mcomplete);
}

/**
*Rachitha:this function will give month wise course enrollment.
* @param string $startdt startdate.
* @param string $enddt enddate.
* @param string $courseloc courselocation.
* @param  string $sitecat sitecategory.
* @return $datatablearray return datatable of site report.
*/
function site_report_datatable_deptrpts($startdt,$enddt,$siteloc,$sitecat){
  global $DB,$USER;
  $context = context_system::instance();
  $managercap = has_capability('local/deptrpts:managerreport',$context);
  $datatablearray=[];
  $counter=1;
  $sql="";
  if(is_siteadmin()){
  	$sql.="SELECT DISTINCT (c.id) 
  	FROM {course} c
  	JOIN {context} ct ON c.id = ct.instanceid
  	JOIN {role_assignments} ra ON ra.contextid = ct.id
  	JOIN {user} u ON u.id = ra.userid
  	JOIN {role} r ON r.id = ra.roleid 
  	WHERE c.visible = 1 AND c.id != 1";
  }else if($managercap){
  	$manager=$DB->get_record_sql("SELECT id FROM {user_info_field} WHERE shortname='managersemail'");
  	$infodata=$DB->get_records_sql("SELECT userid FROM {user_info_data} WHERE fieldid='$manager->id' AND data='$USER->email'");
  	$counter=1;
  	$instring="";
  	foreach ($infodata as $sitekey => $sitevalue) {
  		if($counter == 1){
  			$instring ="'".$sitekey."'";
  		}else{
  			$instring = $instring.","."'".$sitekey."'";
  		}
  		$counter++;
  	}
    $sql.="SELECT DISTINCT (c.id) 
    FROM {course} c
    JOIN {context} ct ON c.id = ct.instanceid
    JOIN {role_assignments} ra ON ra.contextid = ct.id
    JOIN {user} u ON u.id = ra.userid
    JOIN {role} r ON r.id = ra.roleid 
    WHERE c.visible = 1 AND c.id != 1 AND u.id in (".$instring.")";
  }
  if(!empty($sitecat)){
    $sql.=" AND c.category = '$sitecat'";
  }
  if(!empty($siteloc)){
    $sql.=" AND u.city = '$siteloc'";
  }
  // if(!empty($tblsearching)){
  //   $sql.=" AND c.fullname LIKE '%$tblsearching%'";
  // }
  $courses = $DB->get_records_sql($sql);
  //creating datatable to site-report.
  foreach ($courses as $scourse) {
    //here getting enroll course data.
    $course = $DB->get_record('course',array('id'=>$scourse->id));
    $enrollsql='';
    $enrollsql.="SELECT cc.id,cc.userid,cc.timeenrolled FROM {course_completions} cc
    INNER JOIN {user} u ON cc.userid = u.id WHERE cc.timeenrolled IS NOT NULL";
    if($course->id){
      $enrollsql.=" AND cc.course = '$course->id' ";   
    }
    if(!empty($startdt) && !empty($enddt)){
      $enrollsql.=" AND cc.timeenrolled BETWEEN ".$startdt." AND ".$enddt." ";   
    }
    if(!empty($siteloc)){
      $enrollsql.=" AND u.city = '$siteloc'";   
    }
    $enroled = $DB->get_records_sql($enrollsql);
    
    if(!empty($enroled)){
      foreach ($enroled as $enkey => $envalue) {
        $user=$DB->get_record('user',array('id'=>$envalue->userid));
        if(!empty($user)){
          if(!empty($envalue->timeenrolled)){
            $enroldate=date('d-m-Y', $envalue->timeenrolled);
          }else{
            $enroldate="-";
          }
          $fullname=$user->firstname.' '.$user->lastname;
          $email=$user->email;
          $coursename=$course->fullname;
          $completiondata='';
          $completiondata.="SELECT cc.timecompleted FROM {course_completions} cc WHERE course=".$course->id." AND userid=".$envalue->userid." AND cc.timecompleted IS NOT NULL";
          if(!empty($startdt) && !empty($enddt)){
            $completiondata.=" AND cc.timecompleted BETWEEN ".$startdt." AND ".$enddt." ";   
          }

          $completion = $DB->get_record_sql($completiondata);

          if(!empty($completion)){
            $completiondate=date('d-m-Y',$completion->timecompleted);
          }else
          {
            $completiondate="-";
          }

          $cinfo = new completion_info($course);
          $iscomplete = $cinfo->is_course_complete($user->id);
          if(!empty($iscomplete)){

            $status=get_string('complet','local_deptrpts');
          }else
          {
            $status=get_string('notcomplete','local_deptrpts');
          }

          $grade = grade_get_course_grades($course->id, $envalue->userid);
          $grd = $grade->grades[$envalue->userid]; 
          $cgrade=$grd->str_grade;
          $html='';
          $html.=html_writer::start_tag('a',array('data-toggle'=>'modal', 'data-target'=>'#exampleModal'));
          $html.='<i class="fa fa-envelope" aria-hidden="true"></i>';
          $html.=html_writer::end_tag('a');
          $datatablearray[]=array("counter"=>$counter,"username"=>$fullname,"emailid"=>$email,"coursefullname"=>$coursename,"enrolledtime"=>$enroldate,"completiontime"=>$completiondate,"completionstatus"=>$status,"coursegrade"=>$cgrade,"action"=>$html);
          $counter++;                
        }
      }
    }
  }
  return $datatablearray;
}

/**
*Rachitha:this function will give all course top cards report.
* @param string $startdt startdate.
* @param string $enddt enddate.
*/
function allcourse_top_cards_report($startdt,$enddt){
  global $DB,$USER;
  $context = context_system::instance();
  $managercap = has_capability('local/deptrpts:managerreport',$context);
  $totalcount=0;
  $totalbadgecount=0;
  $totalcertificates=0;
  $totalcompletion=0;
  //checking if user as admin.
  if(is_siteadmin()){
      $courses=$DB->get_records('course');
  }else if($managercap){
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
    $courses = $DB->get_records_sql($managercourse);
  }
  foreach ($courses as $course) {
    if($course->id != 1){
      $listusers = enrolled_users_count_course($startdt,$enddt,null,$course->id);
      $totalcount=$totalcount+$listusers;
      $badgecount= get_badges_earned($startdt,$enddt,null,$course->id);
      $totalbadgecount=$totalbadgecount+$badgecount;
      $certificatecount =get_course_cerficatescount($startdt,$enddt,null,$course->id);
      $totalcertificates=$totalcertificates+$certificatecount;
      $completioncount =course_complition_count($startdt,$enddt,null,$course->id);
      $totalcompletion =$totalcompletion+$completioncount;
    }
  }
  $title=array(get_string('enrolledusers','local_deptrpts'),
    get_string('userscompleted','local_deptrpts'),
    get_string('badges','local_deptrpts'),
    get_string('certificates','local_deptrpts'));
  $icon=array("<i class='fa fa-check-square-o' aria-hidden='true'></i>",
    "<i class='fa fa-certificate' aria-hidden='true'></i>",
    "<i class='fa fa-list' aria-hidden='true'></i>",
    "<i class='fa fa-address-card-o' aria-hidden='true'></i>");
  $color=array("gradient-deepblue","gradient-orange","gradient-ohhappiness","gradient-ibiza");
  $html='';
  $html.=filter_top_cards($icon[0],$title[0],$totalcount,$color[0]);
  $html.=filter_top_cards($icon[1],$title[1],$totalcompletion,$color[1]);
  $html.=filter_top_cards($icon[2],$title[2],$totalbadgecount,$color[2]);
  $html.=filter_top_cards($icon[3],$title[3],$totalcertificates,$color[3]);
  echo $html;
}

/**
*Rachitha:this function will give all users top cards report.
* @param string $startdt startdate.
* @param string $enddt enddate.
*/
function allusers_topcards_report($startdt,$enddt){
  global $DB,$USER;
  $context = context_system::instance();
  $managercap = has_capability('local/deptrpts:managerreport',$context);
  $allusercount=0;
  $allbadgecount=0;
  $allcertificatecount=0;
  $allcompletioncount=0;
  //checking for admin.
  if(is_siteadmin()){
    $users=$DB->get_records('user');
    //checking for manager.
  }elseif ($managercap) {
    $users =[];
    $manager=$DB->get_record_sql("SELECT id FROM {user_info_field} WHERE shortname='managersemail'");
    $infodata=$DB->get_records_sql("SELECT userid FROM {user_info_data} WHERE fieldid='$manager->id' AND data='$USER->email'");
    foreach ($infodata as $data1) {
      $username=$DB->get_record_sql("SELECT firstname,lastname FROM {user} WHERE id='$data1->userid'");
      $uobject = new stdClass();
      $uobject->id = $data1->userid;
      $users[] = $uobject;
    }
  }
  foreach ($users as $user) {
    if($user->id != 1){
      $coursecount = user_course_count($user->id,$startdt,$enddt,null);
      $allusercount=$allusercount+$coursecount;
      $badgecount= user_badge_count($user->id,$startdt,$enddt,null);
      $allbadgecount=$allbadgecount+$badgecount;
      $certificatecount=user_course_certificate($user->id,$startdt,$enddt,null);
      $allcertificatecount=$allcertificatecount+$certificatecount;
      $completioncount=user_course_completion($user->id,$startdt,$enddt,null);
      $allcompletioncount=$allcompletioncount+$completioncount;
    }
  }
  $title=array(get_string('enrolledcourses','local_deptrpts'),
    get_string('coursecompletion','local_deptrpts'),
    get_string('badgesearned','local_deptrpts'),
    get_string('certificateearned','local_deptrpts'));
  $icon=array("<i class='fa fa-check-square-o' aria-hidden='true'></i>",
    "<i class='fa fa-certificate' aria-hidden='true'></i>",
    "<i class='fa fa-list' aria-hidden='true'></i>",
    "<i class='fa fa-address-card-o' aria-hidden='true'></i>");
  $color=array("gradient-deepblue","gradient-orange","gradient-ohhappiness","gradient-ibiza");
  $html='';
  $html.=filter_top_cards($icon[0],$title[0],$allusercount,$color[0]);
  $html.=filter_top_cards($icon[1],$title[1],$allcompletioncount,$color[1]);
  $html.=filter_top_cards($icon[2],$title[2],$allbadgecount,$color[2]);
  $html.=filter_top_cards($icon[3],$title[3],$allcertificatecount,$color[3]);
  return $html;
}

/**
*Rachitha:this function will give site datatable.
* @param string $strtdate startdate.
* @param string $endate enddate.
* @param string $siteloc sitelocation.
* @param  string $sitecat sitecategory.
* @return $datatablearray return datatable of site report.
*/
function site_export($strtdate,$endate,$siteloc,$sitecat){
  global $DB,$CFG,$USER;
  $datatablearray=[];
  $counter=1;

  $sql="";
  $sql.="SELECT DISTINCT (c.id) 
  FROM {course} c
  JOIN {context} ct ON c.id = ct.instanceid
  JOIN {role_assignments} ra ON ra.contextid = ct.id
  JOIN {user} u ON u.id = ra.userid
  JOIN {role} r ON r.id = ra.roleid 
  WHERE c.visible = 1 AND c.id != 1";
  if(!empty($sitecat)){
    $sql.=" AND c.category = '$sitecat'";
  }
  if(!empty($siteloc)){
    $sql.=" AND u.city = '$siteloc'";
  }
  $courses = $DB->get_records_sql($sql);
  $headers=array(get_string('serial','local_deptrpts'),
    get_string('fullname','local_deptrpts'),
    get_string('email','local_deptrpts'),
    get_string('coursename','local_deptrpts'),
    get_string('enrolmentdate','local_deptrpts'),
    get_string('completiondate','local_deptrpts'),
    get_string('completionstatus','local_deptrpts'),
    get_string('coursegrade','local_deptrpts'),
    get_string('action','local_deptrpts'));
  $datatablearray[]=$headers;


  //creating datatable to site-report.
  foreach ($courses as $scourse) {
    //here getting enroll course data.
    $course = $DB->get_record('course',array('id'=>$scourse->id));
    $enrollsql='';
    $enrollsql.="SELECT cc.id,cc.userid,cc.timeenrolled FROM {course_completions} cc
    INNER JOIN {user} u ON cc.userid = u.id WHERE cc.timeenrolled IS NOT NULL";
    if($course->id){
      $enrollsql.=" AND cc.course = '$course->id' ";   
    }
    if(!empty($startdt) && !empty($enddt)){
      $enrollsql.=" AND cc.timeenrolled BETWEEN ".$startdt." AND ".$enddt." ";   
    }
    if(!empty($siteloc)){
      $enrollsql.=" AND u.city = '$siteloc'";   
    }
    $enroled = $DB->get_records_sql($enrollsql);
    
    if(!empty($enroled)){
      foreach ($enroled as $enkey => $envalue) {
        $user=$DB->get_record('user',array('id'=>$envalue->userid));
        if(!empty($user)){
          if(!empty($envalue->timeenrolled)){
            $enroldate=date('d-m-Y', $envalue->timeenrolled);
          }else{
            $enroldate="-";
          }
          $fullname=$user->firstname.' '.$user->lastname;
          $email=$user->email;
          $coursename=$course->fullname;
          $completiondata='';
          $completiondata.="SELECT cc.timecompleted FROM {course_completions} cc WHERE course=".$course->id." AND userid=".$envalue->userid." AND cc.timecompleted IS NOT NULL";
          if(!empty($startdt) && !empty($enddt)){
            $completiondata.=" AND cc.timecompleted BETWEEN ".$startdt." AND ".$enddt." ";   
          }

          $completion = $DB->get_record_sql($completiondata);

          if(!empty($completion)){
            $completiondate=date('d-m-Y',$completion->timecompleted);
          }else
          {
            $completiondate="-";
          }

          $cinfo = new completion_info($course);
          $iscomplete = $cinfo->is_course_complete($user->id);
          if(!empty($iscomplete)){

            $status=get_string('complet','local_deptrpts');
          }else
          {
            $status=get_string('notcomplete','local_deptrpts');
          }

          $grade = grade_get_course_grades($course->id, $envalue->userid);
          $grd = $grade->grades[$envalue->userid]; 
          $cgrade=$grd->str_grade;
          $html1='';
          $html1.=html_writer::start_tag('a',array('data-toggle'=>'modal', 'data-target'=>'#exampleModal'));
          $html1.='<i class="fa fa-envelope" aria-hidden="true"></i>';
          $html1.=html_writer::end_tag('a');
          $datatablearray[]=array($counter,$fullname,$email,$coursename,$enroldate,$completiondate,$status,$cgrade,$html1);
          $counter++;                
        }
      }
    }
  }
  return $datatablearray;
}

/**
*Rachitha:this function will give excel_sheet for user-datatable.
* @param string $startdt startdate.
* @param string $enddt enddate.
* @param string $userloc sitelocation.
* @param  string $userid user's id.
* @return $datatablearray return datatable of user report.
*/
function user_exceldownload($startdt,$enddt,$userloc,$userid){
  global $DB;
  //We are getting default 1 as a userid when no user is selected.
  if($userid > 1){
    $sql='';
  //here writing sql query passing userid.
    $sql.="SELECT c.id,u.firstname, u.lastname, c.fullname, u.email
    FROM mdl_course AS c
    JOIN mdl_context AS ctx ON c.id = ctx.instanceid
    JOIN mdl_role_assignments AS ra ON ra.contextid = ctx.id
    JOIN mdl_user AS u ON u.id = ra.userid
    WHERE u.id = '$userid'";
    //here checking startdate and enddate are empty or not.
    if(!empty($startdt) && !empty($enddt)){
      //here adding this query to main query.
      $sql.=" AND ra.timemodified BETWEEN ".$startdt." AND ".$enddt." ";
    }
    //here checking userlocation is empty or not.
    if(!empty($userloc)){
      //here adding this query to main query.
      $sql.=" AND u.city = '$userloc'";
    }
    //here getting all enrolled courses from userid.
    $courses = $DB->get_records_sql($sql);
    //here checking userlocation is empty or not.
  }else if(!empty($userloc)){
    $sql='';
    //here writing query passing userlocation.
    $sql.="SELECT c.id, u.firstname, u.lastname, c.fullname, u.email
    FROM mdl_course AS c
    JOIN mdl_context AS ctx ON c.id = ctx.instanceid
    JOIN mdl_role_assignments AS ra ON ra.contextid = ctx.id
    JOIN mdl_user AS u ON u.id = ra.userid
    WHERE u.city = '$userloc'";
    if(!empty($startdt) && !empty($enddt)){
      //adding this query to main query.
      $sql.=" AND ra.timemodified BETWEEN ".$startdt." AND ".$enddt." ";
    }
    //getting all enrolled courses from userlocation.
    $courses = $DB->get_records_sql($sql);
  }
  $headers=array(get_string('serial','local_deptrpts'),
    get_string('fullname','local_deptrpts'),
    get_string('email','local_deptrpts'),
    get_string('coursename','local_deptrpts'),
    get_string('enrolmentdate','local_deptrpts'),
    get_string('completiondate','local_deptrpts'),
    get_string('completionstatus','local_deptrpts'),
    get_string('coursegrade','local_deptrpts'),
    get_string('action','local_deptrpts'));
  $datatablearray[]=$headers;
  //here creating data for table.
  if(!empty($courses)){
    $counter=1;
    foreach ($courses as $course) {
      //userfullname
      $userfullname=$course->firstname.' '.$course->lastname;
      $email=$course->email;
      $coursename=$course->fullname;
      $enroldate=get_enrolement_date_user($course->id,$userid);
      if(!empty($enroldate)){
        $enroltime=date('d-m-Y', $enroldate);
      }else{
        $enroltime="-";
      }
      //here i am finding course complition date.
      $completion=$DB->get_record_sql("SELECT timecompleted FROM {course_completions} WHERE course=".$course->id." AND userid=".$userid." AND timecompleted IS NOT NULL");
      if(!empty($completion)){
        $completiondate=date('d-m-Y',$completion->timecompleted);
      }else{
        $completiondate="-";
      }
      $courseobject=$DB->get_record('course',array('id'=>$course->id));
      $cinfo = new completion_info($courseobject);
      $iscomplete = $cinfo->is_course_complete($userid);
      if(!empty($iscomplete)){

        $status=get_string('complet','local_deptrpts');
      }else
      {
        $status=get_string('notcomplete','local_deptrpts');
      }
      $grade = grade_get_course_grades($course->id, $userid);
      $grd = $grade->grades[$userid]; 
      $cgrade=$grd->str_grade;
      $datatablearray[]=array($counter,$userfullname,$email,$coursename,$enroldate,$completiondate,$status,$cgrade);
      $counter++;
    }
  }
  return $datatablearray;
}

/**
*Rachitha:this function will give header-part to course datatable.
* @param string $courseid startdate.
* @return $tableheader return tableheader for course report table.
*/
function course_header($courseid){
  global $DB;
  $activities=get_fast_modinfo($courseid);
  $activity=$activities->get_cms();
  //here getting all list of activities grade.
  //here I am getting all the course in a course.
  $activities=list_all_activities_grade($courseid);
  $activityname=[];
  $gradevalue=[];
  foreach ($activities as $akey => $avalue) {
    $activityname[]=$avalue['activityname'];
    $gradevalue[]=$avalue['gradeval'];
  } 
  $header1=array(get_string('serial','local_deptrpts'),
    get_string('fullname','local_deptrpts'),
    get_string('email','local_deptrpts'));
  $header2=$activityname;
  $header3=array(get_string('enrolmentdate','local_deptrpts'),
    get_string('completiondate','local_deptrpts'),
    get_string('completionstatus','local_deptrpts'),
    get_string('coursegrade','local_deptrpts'),
    get_string('action','local_deptrpts'));
  $tableheader =array_merge($header1,$header2,$header3);
  return $tableheader;  
}

/**
*Rachitha:this function will give course datatable.
* @param string $strtdt startdate.
* @param string $enddt enddate.
* @param string $courseloc course location.
* @param  string $courseid courseid.
* @return $html return html part.
*/
function course_data_table($startdt,$enddt,$courseloc,$courseid){
  global $DB, $CFG;
  $courseheader=course_header($courseid);
  $coursedatatable=course_report_datatable($startdt,$enddt,$courseloc,$courseid);
  $html='';
  $html.=html_writer::start_div('row pt-5');
  $html.=html_writer::start_div('col-md-12');
  $html.=html_writer::start_tag('b');
  $html.=html_writer::start_tag('h4');
  $html.=get_string('courseheading','local_deptrpts');
  $html.=html_writer::end_tag('h4');
  $html.=html_writer::end_tag('b');
  $html.=html_writer::end_div();
  $html.=html_writer::end_div();
  $html.=html_writer::start_div('row');
  $html.=html_writer::start_div('col-md-12');
  $html.=html_writer::start_tag('small');
  $html.=html_writer::start_tag('span',array('class'=>'text-danger'));
  $html.=get_string('noties','local_deptrpts');
  $html.=html_writer::end_tag('span');
  $html.=get_string('coursehelptext','local_deptrpts');
  $html.=html_writer::end_tag('small');
  $html.=html_writer::end_div();
  $html.=html_writer::end_div();
  $html.=html_writer::start_div('pt-3');
  $link=$CFG->wwwroot."/local/deptrpts/downloadexcel.php?strtdate=".$startdt."&endate=".$enddt."&clocation=".$courseloc."&coursename=".$courseid."&status=course";
  $html.=html_writer::start_tag('a',array('href'=>$link, 'class'=>'btn btn-success'));
  $html.=get_string('exceldownload','local_deptrpts');
  $html.=html_writer::end_tag('a');
  $html.=html_writer::end_div();
  $html.=html_writer::start_div('form-group pt-3');
  $html.=html_writer::start_tag('select',array('class'=>'form-control','name'=>'state','id'=>'maxRows'));
  $html.=html_writer::start_tag('option',array('value'=>'5000'));
  $html.=get_string('showallrows','local_deptrpts');
  $html.=html_writer::end_tag('option');
  $optioarray=array(5,10,15,20,50,70,100);
  foreach ($optioarray as $optionval) {
    $html.=html_writer::start_tag('option',array('value'=>$optionval));
    $html.=$optionval;
    $html.=html_writer::end_tag('option');
  }
  $html.=html_writer::end_tag('select');
  $html.=html_writer::end_div();
  $html.=html_writer::end_div();
  $html.=html_writer::start_div('table-responsive');
  $html.=html_writer::start_tag('table', array('class'=>'table table-striped','id'=>'course_table1'));
  $html.=html_writer::start_tag('thead');
  $html.=html_writer::start_tag('tr');
  foreach ($courseheader as $single => $value) {
    if($single != 0){
      $html.=html_writer::start_tag('th');
      $html.=$value;
      $html.=html_writer::end_tag('th');
    }
  }
  $html.=html_writer::end_tag('tr');
  $html.=html_writer::end_tag('thead');
  $html.=html_writer::start_tag('tbody');
  
  foreach ($coursedatatable as $cdatakey => $cvalue) {
    if($cdatakey !="tableheader"){
      foreach($cvalue as $ckey => $crvalue){
        foreach ($crvalue as $crkey => $coursevalue) {
          $html.=html_writer::start_tag('tr');
          foreach ($coursevalue as $cckey => $coursedatavalue) {
            if($cckey != 0){
              $html.=html_writer::start_tag('td');
              $html.=$coursedatavalue;
              $html.=html_writer::end_tag('td');
            }
          }
          $html.=html_writer::end_tag('tr');
        }
      }
    }
  }
  
  $html.=html_writer::end_tag('tbody');
  $html.=html_writer::end_tag('table');
  $html.=html_writer::end_div();
  $html.=html_writer::start_div('pagination-container');
  $html.=html_writer::start_tag('nav');
  $html.=html_writer::start_tag('ul',array('class'=>'pagination'));
  $html.=html_writer::start_tag('li',array('data-page'=>'prev'));
  $html.=html_writer::start_tag('button');
  $html.=get_string('preview','local_deptrpts');
  $html.=html_writer::start_tag('button',array('class'=>'sr-only'));
  $html.=get_string('current','local_deptrpts');
  $html.=html_writer::end_tag('button');
  $html.=html_writer::end_tag('button');
  $html.=html_writer::end_tag('li');
  $html.=html_writer::start_tag('li',array('data-page'=>'next','id'=>'prev'));
  $html.=html_writer::start_tag('button');
  $html.=get_string('next','local_deptrpts');
  $html.=html_writer::start_tag('button',array('class'=>'sr-only'));
  $html.=get_string('current','local_deptrpts');
  $html.=html_writer::end_tag('button');
  $html.=html_writer::end_tag('button');
  $html.=html_writer::end_tag('li');
  $html.=html_writer::end_tag('ul');
  $html.=html_writer::end_tag('nav');
  $html.=html_writer::end_div();
  return $html;
}

/**
*Rachitha:this function will give site datatable.
* @param string $coursescript coursescript.
* @return array $coursescript returning coursescript.
*/
function course_script(){
  global $DB, $OUTPUT;
  $coursescript=[];
  return $OUTPUT->render_from_template('local_deptrpts/coursescript', $coursescript);
}

/**
*Rachitha:this function will give course datatable.
* @param string $strtdt startdate.
* @param string $endt enddate.
* @param string $courseloc courselocation.
* @param  string $courseid courseid.
* @return $retarray return datatable of course report.
*/
function course_data_tbl($startdt,$enddt,$courseloc,$courseid){
  global $DB;
  $retarray=[];
  $headers=course_header($courseid);
  $retarray[] = $headers;
  $counter=1;
  $course=$DB->get_record('course',array('id'=>$courseid));
  $enrolldata=get_enroled_userdata($courseid);
  if(!empty($enrolldata)){
    foreach ($enrolldata as $enkey => $envalue) {
      $user=$DB->get_record('user',array('id'=>$envalue[0]));
      if(!empty($user)){

        if(!empty($envalue[1])){
          $enroldate=date('d-m-Y', $envalue[1]);

        }else{
          $enroldate="-";
        }
        $fullname=$user->firstname.' '.$user->lastname;
        $email=$user->email;
        $coursename=$course->fullname;
        $completion=$DB->get_record_sql("SELECT timecompleted FROM {course_completions} WHERE course=".$courseid." AND userid=".$envalue[0]." AND timecompleted IS NOT NULL");
        if(!empty($completion)){
          $completiondate=date('d-m-Y',$completion->timecompleted);
        }else{
          $completiondate="-";
        }

        $course=$DB->get_record('course',array('id'=>$courseid));
        $cinfo = new completion_info($course);
        $iscomplete = $cinfo->is_course_complete($user->id);
        if(!empty($iscomplete)){

          $status=get_string('complet','local_deptrpts');
        }else
        {
          $status=get_string('notcomplete','local_deptrpts');
        }
        $actgrades=list_all_activities_grade($courseid,$user->id);
        $gradevalue=[];
        foreach ($actgrades as $gkey => $gvalue) {
          if(!empty($gvalue['gradeval'])){
            $gradevalue[]=$gvalue['gradeval'];
          }else{
            $gradevalue[]='-';
          }
        }
        $grade = grade_get_course_grades($courseid, $envalue[0]);
        $grd = $grade->grades[$envalue[0]];
        $cgrade=$grd->str_grade;
        $data1=array($counter,$fullname,$email);
        $data2=$gradevalue;
        $data3=array($enroldate,$completiondate,$status,$cgrade);
        $retarray[]=array_merge($data1,$data2,$data3);
        $counter++;
      }
    }
    return  $retarray;
  }  
}


/**
* Rachitha: this function will give all course datatable.
* @param string $startdt startdate selecting startdate from the filter..
* @param string $enddt enddate selecting enddate from the filter..
* @param string $userloc selecting location from the filter.
* @param string $userid userid.
* @return array returning $retarray. 
**/
function allcoursedatatable_report($startdt,$enddt){
  global $DB,$USER;
  $context = context_system::instance();
  $managercap = has_capability('local/deptrpts:managerreport',$context);
  $sql='';
  if(is_siteadmin()){
  //We are getting default 1 as a userid when no user is selected.
  $sql.="SELECT ra.id as roleid,u.id as userid, c.id,u.firstname, u.lastname, c.fullname, u.email
  FROM mdl_course AS c
  JOIN mdl_context AS ctx ON c.id = ctx.instanceid
  JOIN mdl_role_assignments AS ra ON ra.contextid = ctx.id
  JOIN mdl_user AS u ON u.id = ra.userid
  WHERE c.visible = 1 ";  
  }else if($managercap){
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
  $sql.="SELECT ra.id as roleid,u.id as userid, c.id,u.firstname, u.lastname, c.fullname, u.email
  FROM mdl_course AS c
  JOIN mdl_context AS ctx ON c.id = ctx.instanceid
  JOIN mdl_role_assignments AS ra ON ra.contextid = ctx.id
  JOIN mdl_user AS u ON u.id = ra.userid
  WHERE c.visible = 1 AND u.id in (".$instring.")"; 
  }
    //here checking startdate and enddate are empty or not.
  if(!empty($startdt) && !empty($enddt)){
      //here adding this query to main query.
    $sql.=" AND ra.timemodified BETWEEN ".$startdt." AND ".$enddt." ";
  }
    //here getting all enrolled courses from userid.
  $courses = $DB->get_records_sql($sql);
  //here creating data for table.
  if(!empty($courses)){
    $counter=1;
    foreach ($courses as $course) {
      //userfullname
      $userfullname=$course->firstname.' '.$course->lastname;
      $email=$course->email;
      $coursename=$course->fullname;
      $enroldate=get_enrolement_date_user($course->id,$course->userid);
      if(!empty($enroldate)){
        $enroltime=date('d-m-Y', $enroldate);
      }else{
        $enroltime="-";
      }
      //here i am finding course complition date.
      $completion=$DB->get_record_sql("SELECT timecompleted FROM {course_completions} WHERE course=".$course->id." AND userid=".$course->userid." AND timecompleted IS NOT NULL");
      if(!empty($completion)){
        $completiondate=date('d-m-Y',$completion->timecompleted);
      }else{
        $completiondate="-";
      }
      $courseobject=$DB->get_record('course',array('id'=>$course->id));
      $cinfo = new completion_info($courseobject);
      $iscomplete = $cinfo->is_course_complete($course->userid);
      if(!empty($iscomplete)){

        $status=get_string('complet','local_deptrpts');
      }else
      {
        $status=get_string('notcomplete','local_deptrpts');
      }
      $grade = grade_get_course_grades($course->id, $course->userid);
      $grd = $grade->grades[$course->userid]; 
      $cgrade=$grd->str_grade;
      $html1='';
      $html1.=html_writer::start_tag('a',array('data-toggle'=>'modal', 'data-target'=>'#exampleModal'));
      $html1.='<i class="fa fa-envelope" aria-hidden="true"></i>';
      $html1.=html_writer::end_tag('a');
      $datatablearray[]=array("counter"=>$counter,"username"=>$userfullname,"emailid"=>$email,"coursefullname"=>$coursename,"enrolledtime"=>$enroltime,"completiontime"=>$completiondate,"completionstatus"=>$status,"coursegrade"=>$cgrade,"action"=>$html1);
      $counter++;
    }
    $retarray=[];
    $retarray['tabledata']=$datatablearray;
    $retarray['dynlink']="/local/deptrpts/downloadexcel.php?strtdate=".$startdt."&endate=".$enddt."&status=allcourse";
     return  $retarray;
  }
}

/**
*Rachitha:this function will getting all category and subcategory.
* @return $catarray return array of category.
*/
function all_category_subacatgeory(){
  global $DB,$OUTPUT,$USER;
  $context = context_system::instance();
  $managercap = has_capability('local/deptrpts:managerreport',$context);
  if(is_siteadmin()){
  $categories=$DB->get_records('course_categories');
}else if($managercap){
//checking if the logged in user is manager or not.
//getting all the users under this manager.
    //gettin the managersemail field id.
$manager=$DB->get_record_sql("SELECT id FROM {user_info_field} WHERE shortname='managersemail'");
//getting all the userid having curently logged in managers email id.
$infodata=$DB->get_records_sql("SELECT userid FROM {user_info_data} WHERE fieldid='$manager->id' AND data='$USER->email'");
    //gettingall the categories related to these users.
$counter=1;
$instring="";
foreach ($infodata as $catkey => $catval) {
  if($counter == 1){
    $instring="'".$catkey."'";
  }else{
    $instring = $instring.","."'".$catkey."'";
  }
  $counter++;
}
$categories="SELECT DISTINCT(cc.name),cc.id,cc.parent
FROM mdl_course AS c
JOIN mdl_context AS ctx ON c.id = ctx.instanceid
JOIN mdl_role_assignments AS ra ON ra.contextid = ctx.id
JOIN mdl_user AS u ON u.id = ra.userid
JOIN mdl_course_categories AS cc ON cc.id = c.category
WHERE u.id in (".$instring.")";
$categories = $DB->get_records_sql($categories);
}

$catarray=[];
foreach ($categories as $catagory) {
  $cparent=$catagory->parent;
  if($cparent == 0){
    $catarray[$catagory->id]=$catagory->name;
  }else {
    $cpath=$catagory->path;
    $catexplode=explode('/',$cpath);
    $counter=1;
    $instring="";
    foreach ($catexplode as $ckey => $cvalue) {
      if(!empty($cvalue)){
      $catname=$DB->get_record('course_categories',array('id'=>$cvalue));
      $cname=$catname->name;
      if($counter == 1){
        $instring=$cname;
      }else {
        $instring = $instring.'/'.$cname;
      }
      $counter++;
      } 
    }
    $catarray[$catagory->id]=$instring;
  }
}
return $catarray;
}