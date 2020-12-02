$(document).ready(function () {
 	var filter1 = document.getElementById("collapseOne");
 	  	filter1.classList.add("show");
 	var filter2 = document.getElementById("collapseTwo");
 	    filter2.classList.add("show");
 	var filter3 = document.getElementById("collapseThree");
 	    filter3.classList.add("show");
    var filter4 = document.getElementById("collapseFour");
        filter4.classList.add("show");
    var filter5 = document.getElementById("collapseFive");
        filter5.classList.add("show");
 });

 $(document).ready(function() {
        $.ajax({ 
        url: M.cfg.wwwroot+"/local/deptrpts/ajax.php",
        data:{firstload:'yes'},
        type: "POST",
        success: function(data){
            $('#ajaxresult').html(data);
        }
    });
 });

$(document).ready(function() {
	var startdate ='';
    $("#start-date-input").change(function(event) {
    var status = "startdate";
    filter_data(status);
    });

	var enddate ='';
    $("#end-date-input").change(function(event) {
    var status = "enddate";
    filter_data(status);
    });

	var sitelocation ='';
    $("#site_location").change(function(event) {
    var status = "sitelocation";
    filter_data(status);
    });

	var userlocation ='';
    $("#user_location").change(function(event) {
    var status = "userlocation";
    filter_data(status);
    });

	var courselocation ='';
    $("#course_location").change(function(event) {
    var status = "courselocation";
    filter_data(status);
    });

	var sitecategory ='';
    $("#site-category").change(function(event) {
    var status = "sitecategory";
    filter_data(status);
    });

  var siterole ='';
    $("#site-role").change(function(event) {
    var status = "siterole";
    filter_data(status);
    });

  var sitedepartment ='';
    $("#site-dept").change(function(event) {
    var status = "sitedepartment";
    filter_data(status);
    });

	var usersearch ='';
    $("#usersearch").change(function(event) {
    var status = "usersearch";
    filter_data(status);
    });

	var coursesearch ='';
    $("#coursesearch").change(function(event) {
    var status = "coursesearch";
    filter_data(status);
    });

    //checking which filter is calling.
    var allcourses ='';
    $("#all_course").click(function(event){
    var status = "allcourses";
    filter_data(status);
    });

    var allusers ='';
    $("#all_users").click(function(event){
    var status = "allusers";
    filter_data(status);
    });
 
});


// function searchfun(){
//   var status = "search";
//   filter_data(status);
// }

function filter_data(status){

    var startdate ='';
    var startdate = $("#start-date-input").val();
  
    var enddate ='';
    var enddate = $("#end-date-input").val();
 

    var sitelocation ='';
    var sitelocation = $("#site_location").val();


    var userlocation ='';
    var userlocation = $("#user_location").val();


    var courselocation ='';
    var courselocation = $("#course_location").val();


    var sitecategory ='';
    var sitecategory = $("#site-category").val();
 
    var siterole ='';
    var siterole = $("#site-role").val();

    var sitedepartment ='';
    var sitedepartment = $("#site-dept").val();

    var usersearch ='';
    var usersearch = $("#usersearch").val();


    var coursesearch ='';
    var coursesearch = $("#coursesearch").val();

    // var tblsearch ='';
    // var tblsearch = $("#tablesearch").val();

    $.ajax({ 
        url: M.cfg.wwwroot+"/local/deptrpts/ajax.php",
        data:{status:status,startdate:startdate,enddate:enddate,sitelocation:sitelocation,userlocation:userlocation,
        courselocation:courselocation,sitecategory:sitecategory,usersearch:usersearch, coursesearch:coursesearch, siterole:siterole, sitedepartment:sitedepartment},
        type: "POST",
        success: function(data){
            $('#ajaxresult').html(data);
        }
    });
}



getPagination('#course_table1');
function getPagination(table) {
  var lastPage = 1;
  $('#maxRows').on('change', function(evt) {
      lastPage = 1;
      $('.pagination').find('li').slice(1, -1).remove();
      var trnum = 0;
      var maxRows = parseInt($(this).val());
      if (maxRows == 5000) {
        $('.pagination').hide();
    } else {
        $('.pagination').show();
    }

      var totalRows = $(table + ' tbody tr').length;
      if(totalRows == 0){
        $('.pagination').hide();
      }

      $(table + ' tr:gt(0)').each(function() {
        trnum++;
        if (trnum > maxRows) {
          $(this).hide();
      }
      if (trnum <= maxRows) {
          $(this).show();
      } 
  });

      if (totalRows > maxRows) {
        var pagenum = Math.ceil(totalRows / maxRows);
        for (var i = 1; i <= pagenum; ) {
          $('.pagination #prev').before('<li data-page="' + i +'">\\<span>' +
              i++ +'<span class="sr-only">(current)</span></span>\\</li>').show();
        }
      }
      $('.pagination [data-page="1"]').addClass('active');
      $('.pagination li').on('click', function(evt) {
        evt.stopImmediatePropagation();
        evt.preventDefault();
        var pageNum = $(this).attr('data-page');
        var maxRows = parseInt($('#maxRows').val());
        if (pageNum == 'prev') {
          if (lastPage == 1) {
            return;
        }
        pageNum = --lastPage;
    }
    if (pageNum == 'next') {
      if (lastPage == $('.pagination li').length - 2) {
        return;
    }
    pageNum = ++lastPage;
}
lastPage = pageNum;
        var trIndex = 0; 
        $('.pagination li').removeClass('active'); 
        $('.pagination [data-page="' + lastPage + '"]').addClass('active');              
        limitPagging();
        $(table + ' tr:gt(0)').each(function() {
          trIndex++; 
          if (
            trIndex > maxRows * pageNum ||
            trIndex <= maxRows * pageNum - maxRows
            ) {
            $(this).hide();
    } else {
        $(this).show();
          } 
        }); 
      });
      limitPagging();
  })
  .val(5)
  .change();
}

function limitPagging() {
  if ($('.pagination li').length > 7) {
    if ($('.pagination li.active').attr('data-page') <= 3) {
      $('.pagination li:gt(5)').hide();
      $('.pagination li:lt(5)').show();
      $('.pagination [data-page="next"]').show();
  }
  if ($('.pagination li.active').attr('data-page') > 3) {
      $('.pagination li:gt(0)').hide();
      $('.pagination [data-page="next"]').show();
      for (
        let i = parseInt($('.pagination li.active').attr('data-page')) - 2;
        i <= parseInt($('.pagination li.active').attr('data-page')) + 2;
        i++
        ) {
        $('.pagination [data-page="' + i + '"]').show();
}
}
}
}

$(function() {
  $('table tr:eq(0)').prepend('<th> ID </th>');
  var id = 0;
  $('table tr:gt(0)').each(function() {
    id++;
    $(this).prepend('<td>' + id + '</td>');
});
});


$('#myModal').on('shown.bs.modal', function () {
  $('#myInput').trigger('focus')
})


