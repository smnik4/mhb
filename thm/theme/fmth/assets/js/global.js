//console.log("global_js");

(function($) {

	$(function() {

		var	$window = $(window),
			$body = $('body');
			
			$("a.submit").on('click', function(event){
				var href = $(this).attr('href');
				var text = $(this).attr('text');
				var message = 'Выполнить?';
				event.stopPropagation();
				event.PreventDefault;
				if(text){
					message = text;
				}
				if(confirm(message)){
					window.location.href = href;
				}
				return false;
			});
			
		// Menu.
			var $menu = $('#menu');

			$menu.wrapInner('<div class="inner"></div>');

			$menu._locked = false;

			$menu._lock = function() {

				if ($menu._locked)
					return false;

				$menu._locked = true;

				window.setTimeout(function() {
					$menu._locked = false;
				}, 350);

				return true;

			};

			$menu._show = function() {

				if ($menu._lock())
					$body.addClass('is-menu-visible');

			};

			$menu._hide = function() {

				if ($menu._lock())
					$body.removeClass('is-menu-visible');

			};

			$menu._toggle = function() {

				if ($menu._lock())
					$body.toggleClass('is-menu-visible');

			};

			$menu
				.appendTo($body)
				.on('click', function(event) {
					event.stopPropagation();
				})
				.on('click', 'a', function(event) {

					var href = $(this).attr('href');

					event.preventDefault();
					event.stopPropagation();

					// Hide.
						$menu._hide();

					// Redirect.
						if (href == '#menu')
							return;

						window.setTimeout(function() {
							window.location.href = href;
						}, 350);

				})
				.append('<a class="close" href="#menu">Close</a>');

			$body
				.on('click', 'a[href="#menu"]', function(event) {

					event.stopPropagation();
					event.preventDefault();

					// Toggle.
						$menu._toggle();

				})
				.on('click', function(event) {

					// Hide.
						$menu._hide();

				})
				.on('keydown', function(event) {

					// Hide on escape.
						if (event.keyCode == 27)
							$menu._hide();

				});
			$body.on('click', '.field.number .add', function(event){
				var el = $(".field.number .data").html();
				$(".field.number").append(el);
				console.log(el);
			});

	});
})(jQuery);
function view_detail(val,selector){
	$("#"+selector).val(val)
	console.log($("#"+selector).val());
	var form = $("#"+selector).parents("form");
	if(form.is("form")){
		form.submit();
	}
	//
}