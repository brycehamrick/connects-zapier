// JavaScript Document

jQuery(document).on("change keyup paste keydown","#zapier_api_key", function(e) {
	var val = jQuery(this).val();
	if( val !== "" )
		jQuery("#auth-zapier").removeAttr('disabled');
	else
		jQuery("#auth-zapier").attr('disabled','true');
});

jQuery(document).on( "click", "#auth-zapier", function(e){
	e.preventDefault();
	jQuery(".smile-absolute-loader").css('visibility','visible');
	var zapier_api_key = jQuery("#zapier_api_key").val();

	var action = 'update_zapier_authentication';
	var data = {action:action,zapier_api_key:zapier_api_key};
	jQuery.ajax({
		url: ajaxurl,
		data: data,
		type: 'POST',
		dataType: 'JSON',
		success: function(result){
			if(result.status == "success" ){
				jQuery(".bsf-cnlist-mailer-help").hide();
				jQuery("#save-btn").removeAttr('disabled');
				jQuery("#zapier_api_key").closest('.bsf-cnlist-form-row').hide();
				jQuery("#auth-zapier").closest('.bsf-cnlist-form-row').hide();
				jQuery(".zapier-list").html(result.message);

			} else {
				jQuery(".zapier-list").html('<span class="bsf-mailer-success">'+result.message+'</span>');
			}
			jQuery(".smile-absolute-loader").css('visibility','hidden');
		}
	});
	e.preventDefault();
});

jQuery(document).on( "click", "#disconnect-zapier", function(){

	if(confirm("Are you sure? If you disconnect, your previous campaigns syncing with zapier will be disconnected as well.")) {
		var action = 'disconnect_zapier';
		var data = {action:action};
		jQuery(".smile-absolute-loader").css('visibility','visible');
		jQuery.ajax({
			url: ajaxurl,
			data: data,
			type: 'POST',
			dataType: 'JSON',
			success: function(result){

				jQuery("#save-btn").attr('disabled','true');
				if(result.message == "disconnected" ){

					jQuery("#zapier_api_key").val('');
					jQuery(".zapier-list").html('');
					jQuery("#disconnect-zapier").replaceWith('<button id="auth-zapier" class="button button-secondary auth-button" disabled="true">Authenticate zapier</button><span class="spinner" style="float: none;"></span>');
					jQuery("#auth-zapier").attr('disabled','true');
				}

				jQuery('.bsf-cnlist-form-row').fadeIn('300');
				jQuery(".bsf-cnlist-mailer-help").show();
				jQuery(".smile-absolute-loader").css('visibility','hidden');
			}
		});
	}
	else {
		return false;
	}
});
