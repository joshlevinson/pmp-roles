jQuery(document).ready(function($){
	var html = '';
	var vars = window[key.key];
	html += '<div id="repair_roles_container">';
	html += '<label for="repair_roles" id="repair_roles_label">'+vars.desc+'</label>';
	html += '<input id="repair_roles" name="repair_roles" type="submit" class="button-primary" value="'+vars.repair+'" />';
	html += '<p id="repaired_roles"></p>';
	html += '</div>';
	$('.widefat').after(html);
	$('#repair_roles').click(function(event){
		event.preventDefault();
		$.ajax({
			type: "post",
			url: "admin-ajax.php",
			data: { action: vars.ajaction, _ajax_nonce: vars.nonce },
			beforeSend: function() {$("#repair_roles").val(vars.working);}, //show loading just when link is clicked
			complete: function() { $("#repair_roles").val(vars.done);}, //stop showing loading when the process is complete
			success: function(html){ //so, if data is retrieved, store it in html
				$('#repaired_roles').toggle();
				if(html == 'failed'){
					$('#repaired_roles').text(html);
				}else{
					$('#repaired_roles').text(html + vars.fixed);
					$('#repair_roles').attr('disabled', 'disabled');
				}
			}
		});
	})
});