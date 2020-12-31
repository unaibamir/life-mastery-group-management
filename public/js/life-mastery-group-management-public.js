(function( $ ) {
	'use strict';

	var selected_lessons = [];

	$('.lesson_date').datepicker({
		dateFormat: 'mm/dd/yy'
	});

	$('.lesson_review_date').datepicker({
		dateFormat: 'mm/dd/yy'
	});

	var $lesson_select = $('.lesson_select').select2({
		width: '100%'
		
	});

	var $student_select = $('.student_select').select2({
		width: '100%',
		maximumSelectionLength: 2,
	});

	$('.lm-user-select').select2();

	$(".lm-group-details").tabs({                
        beforeLoad: function(event, ui) {
            ui.panel.html('Loading... ')
        }
    });

	/*$lesson_select.on("select2:select", function (e) {

		var vals = $(this).select2("val");
		
		//selected_lessons.push(vals);
		selected_lessons.indexOf(vals) === -1 && selected_lessons.push(vals);

		// selects contains all the OTHER select forms
	    var selects = $('select.lesson_select').not('#'+$(this).attr('id'));

	    // loop trough all the selects
	    for (var i = 0; i < selects.length; i++) {
	        //re-enable all options before
	        $(selects[i]).find('option').removeAttr('disabled');
	        // loop trough all the values
	        selected_lessons.forEach(function( item, index ){
	        	if( Array.isArray( item ) ) {
	        		item.forEach(function( sub_item, sub_index ){
	        			$(selects[i]).find('option[value='+sub_item+']').attr('disabled', 'disabled');
	        		});
	        	} else {
		        	$(selects[i]).find('option[value='+item+']').attr('disabled', 'disabled');
		        }
	        });
	    }
	});


	$lesson_select.on("select2:unselect", function (e) {
		var data = e.params.data;
		selected_lessons = $.grep(selected_lessons, function(value) {
		  return value != data.id;
		});

		var selects = $('select.lesson_select').not('#'+$(this).attr('id'));
		

		// loop trough all the selects
	    for (var i = 0; i < selects.length; i++) {

	    	$(selects[i]).trigger('change.select2');

	        //re-enable all options before
	        $(selects[i]).find('option').removeAttr('disabled');

	        selected_lessons.forEach(function( item, index ){
	        	if( Array.isArray( item ) ) {
	        		item.forEach(function( sub_item, sub_index ){
	        			$(selects[i]).find('option[value='+sub_item+']').attr('disabled', 'disabled');
	        		});
	        	} else {
		        	$(selects[i]).find('option[value='+item+']').attr('disabled', 'disabled');
		        }
	        });
	    }
	});
*/
})( jQuery );
