;(function($) {

	var allowInterfaceResizeInterval;

	function disableACFLayoutReorder(){
		$('.acf-flexible-content > .values').sortable( "disable" );
		$('.acf-flexible-content .ui-sortable-handle').removeAttr( "title" );
	}

	function allowInterfaceResize(){

		if( $('.interface-interface-skeleton__sidebar').length ) {

			clearInterval(allowInterfaceResizeInterval);

			$('.interface-interface-skeleton__sidebar').width(localStorage.getItem('toast_rs_personal_sidebar_width'))
			$('.interface-interface-skeleton__sidebar').resizable({
				handles: 'w',
				resize: function (event, ui) {
					$(this).css({'left': 0});
					localStorage.setItem('toast_rs_personal_sidebar_width', $(this).width());
				}
			});
		}
	}

	function ucfirst(string) {
		return string.charAt(0).toUpperCase() + string.slice(1);
	}

	function initTranslation(){

		$('#wp-content-wrap, #titlewrap, #wp-advanced_description-wrap, #postexcerpt .inside, #menu-to-edit .menu-item-settings label, #link-selector .wp-link-text-field label').append('<a class="wps-translate wps-translate--'+wps.enable_translation+'" title="Translate with '+ucfirst(wps.enable_translation)+'"></a>')
		$('#tag-post-content #name').wrap('<div class="input-wrapper"></div>')
		$('#tag-post-content #name').after('<a class="wps-translate wps-translate--'+wps.enable_translation+'" title="Translate with '+ucfirst(wps.enable_translation)+'"></a>')
		$('#menu-to-edit span.description').remove()

		$(document).on('click', '.wps-translate', function (){

			var $self = $(this);
			var $inputs = $(this).parent().find('.acf-input-wrap > input, > textarea, textarea.wp-editor-area, .field, #title, #name, #excerpt')

			if( !$inputs.length ){

				$inputs = $(this).prev('input, textarea')

				if( !$inputs.length )
					return;
			}

			var $input = $inputs.first()
			var $editable = $input.prev('[contenteditable]');
			var wysiwyg = $input.hasClass('wp-editor-area');
			var value = wysiwyg ? tinymce.editors[$input.attr('id')].getContent() : $input.val();

			if( value.length <= 2)
				return;

			$self.addClass('loading')

			if( wps.enable_translation ){

				$.post(wps.ajax_url, {action: 'translate', q:value, format:(wysiwyg?'html':'text')}, function (response){

					$self.removeClass('loading')

					if( response.text.length ){

						var translations = response.text;

						if( wysiwyg ){

							tinymce.editors[$input.attr('id')].setContent(translations)
						}
						else{

							if( $editable.length )
								$editable.html(translations)

							$input.val(translations).change()
						}
					}
				}).fail(function(response) {

					alert( response.message )
					$self.removeClass('loading')
				})
			}
		})
	}

	$(document).ready(function(){

		allowInterfaceResizeInterval = setInterval(allowInterfaceResize, 500);

		if( $('body').hasClass('no-acf_edit_layout') ){

			disableACFLayoutReorder();
			setInterval(disableACFLayoutReorder, 1000);
		}

		$('.postbox-container [data-wp-lists]').each(function(){

			if( $(this).find('.children').length )
				$(this).addClass('has-children');

			$(this).find('input[type="checkbox"]').click(function(){

				if( !$(this).is(':checked') )
					$(this).closest('li').find('input[type="checkbox"]').attr('checked', false)
				else
					$(this).parents('li').find('> label input[type="checkbox"]').attr('checked', true)
			})
		})

		$('.acf-label').each(function(){

			if( $(this).text().length < 2 )
				$(this).remove()
		})

		$('#wp-admin-bar-build a').click(function(e){

			e.preventDefault();
			var $el = $(this);

			$el.addClass('loading');

			$.get( $el.attr('href') ).then(function (){

				var refresh = setInterval(function (){

					$('#wps-build-badge').attr('src', $('#wps-build-badge').data('url')+'&v='+Date.now())

				}, 1000);

				setTimeout(function (){

					clearInterval(refresh);
					$el.removeClass('loading');

				}, 10000)
			})
		})

		if( wps.enable_translation )
			initTranslation();
	});


})(jQuery);
