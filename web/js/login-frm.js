var sb = sb || {};

(function($) {

	var ns = sb;                    //our namespace
	
	function show_login() {
		
		$('#signinModal').modal('show');
		$('#signin').on('click', function () {
			var $msg = $("#loginMsg");
			$msg.hide();
			$msg.removeClass('alert-success');
			$msg.removeClass('alert-error');
			$("#username").tooltip('hide');
			$("#password").tooltip('hide');
			if ( $("#username").val() == '' ) {
				$("#username").tooltip('show');
				return false;
			}
			if ( $("#password").val() == '' ) {
				$("#password").tooltip('show');
				return false;
			}
		    var $btn = $(this).button('Please wait');
		    $.post( "/login", $("#signinForm").serialize())
			    .done(function( data ) {
			    	$btn.button('reset');
			    	if ( data ) {
				    	if ( data.success ) {
							$msg.addClass('alert-success');
				    	} else {
							$msg.addClass('alert-error');
				    	}
				    	if ( data.message )  {
				    		$msg.find('p').text(data.message);
				    		$msg.show();
				    	}
				    	if ( data.redirect ) {
				    		window.location.hash = '';
				    		window.location.pathname = data.redirect;
				    	}
			    	} else {
			    		$msg.addClass('alert-error');
			    		$msg.find('p').text('empty response');
			    		$msg.show();			    	
			    	}
			    })
			    .always(function() {
			    	$btn.button('reset');
			    });
		  })
	}

	function show_portal() {
		$("#header").find(".active").removeClass("active")
		$("#header").find("a").off('click.onePageNav');
		$("#header").removeClass("transparent-header");
		$("#main_navigation").addClass("shrinking-nav shrinked");

	}
	
//public methods
ns.show_login			= show_login;
ns.show_portal			= show_portal;

})(jQuery);

