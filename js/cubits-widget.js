jQuery(document).ready(function($) {

	function cubitsToggle() {
		var openText = "Hide Advanced Options";
		var closedText = "Show Advanced Options";

		$('.cubits-toggle').click(function() {
			var parent = $(this).parent();
			if ($(this).hasClass('open')) {
				$(this).removeClass('open');
				$(this).text(closedText);
				$(parent).children('.cubits-advanced').hide();
			} else {
				$(this).addClass('open');
				$(this).text(openText);
				$(parent).children('.cubits-advanced').show();

			}
		});
	}

	$('.widget').hover(cubitsToggle);

});