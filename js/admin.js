;(function($) {
	$(document).ready(function() {
		$(".wrap-cpe2-google-spreadsheet").each(function() {
			var container = $(this);
			var mForm = container.find("form");

			mForm.on("submit", function(e) {
				e.preventDefault();
				if(container.hasClass("doing-ajax")) return false;
				container.addClass("doing-ajax");

				$.ajax({
					type: "POST",
					url: CPE2GSAdminData.ajaxUrl + "?action=cpe2gs_export",
					cache: false,
					dataType: "json",
					timeout: 300000,
					data: mForm.serialize()
				}).done(function(response) {
					if(response.success) {
						mForm.find(".input-field input").val("");
						alert(CPE2GSAdminData.i18n.done);
					} else {
						alert(response.data);
					}
					container.removeClass("doing-ajax");
				}).fail(function() {
					alert(CPE2GSAdminData.i18n.NSError);
					container.removeClass("doing-ajax");
				});
			});
		});
	});
})(jQuery);