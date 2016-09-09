// JavaScript Document

jQuery(document).on("change keyup paste keydown","#myaddon_api_key", function(e) {
	var val = jQuery(this).val();
	if( val !== "" )
		jQuery("#auth-myaddon").removeAttr('disabled');
	else
		jQuery("#auth-myaddon").attr('disabled','true');
});

jQuery(document).on( "click", "#auth-myaddon", function(e){
	e.preventDefault();
	jQuery(".smile-absolute-loader").css('visibility','visible');
	var myaddon_api_key = jQuery("#myaddon_api_key").val();
	
	var action = 'update_myaddon_authentication';
	var data = {action:action,myaddon_api_key:myaddon_api_key};
	jQuery.ajax({
		url: ajaxurl,
		data: data,
		type: 'POST',
		dataType: 'JSON',
		success: function(result){
			if(result.status == "success" ){
				jQuery(".bsf-cnlist-mailer-help").hide();
				jQuery("#save-btn").removeAttr('disabled');
				jQuery("#myaddon_api_key").closest('.bsf-cnlist-form-row').hide();
				jQuery("#auth-myaddon").closest('.bsf-cnlist-form-row').hide();
				jQuery(".myaddon-list").html(result.message);

			} else {
				jQuery(".myaddon-list").html('<span class="bsf-mailer-success">'+result.message+'</span>');
			}
			jQuery(".smile-absolute-loader").css('visibility','hidden');
		}
	});
	e.preventDefault();
});

jQuery(document).on( "click", "#disconnect-myaddon", function(){
															
	if(confirm("Are you sure? If you disconnect, your previous campaigns syncing with myaddon will be disconnected as well.")) {
		var action = 'disconnect_myaddon';
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

					jQuery("#myaddon_api_key").val('');
					jQuery(".myaddon-list").html('');
					jQuery("#disconnect-myaddon").replaceWith('<button id="auth-myaddon" class="button button-secondary auth-button" disabled="true">Authenticate myaddon</button><span class="spinner" style="float: none;"></span>');
					jQuery("#auth-myaddon").attr('disabled','true');
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