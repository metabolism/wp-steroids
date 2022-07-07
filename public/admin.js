;(function($) {

	function disableACFLayoutReorder(){
		$('.acf-flexible-content > .values').sortable( "disable" );
		$('.acf-flexible-content .ui-sortable-handle').removeAttr( "title" );
	}

	function initTranslation(){

		$('#wp-content-wrap, #titlewrap, #wp-advanced_description-wrap, #postexcerpt .inside, #menu-to-edit .menu-item-settings label, #link-selector .wp-link-text-field label').append('<a class="wps-translate wps-translate--'+window.enable_translation+'" title="Translate with '+window.enable_translation+'"></a>')
		$('#tag-post-content #name').wrap('<div class="input-wrapper"></div>')
		$('#tag-post-content #name').after('<a class="wps-translate wps-translate--'+window.enable_translation+'" title="Translate with '+window.enable_translation+'"></a>')
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
			var wysiwyg = $input.hasClass('wp-editor-area');
			var value = wysiwyg ? tinymce.editors[$input.attr('id')].getContent() : $input.val();

			if( value.length <= 2)
				return;

			$self.addClass('loading')

			$.post('https://translation.googleapis.com/language/translate/v2?key='+window.translate_key, {q:value, format:(wysiwyg?'html':'text'), target:document.documentElement.lang.split('-')[0]}, function (response){

				$self.removeClass('loading')

				if( response.data.translations.length ){

					var translations = response.data.translations[0].translatedText;

					if( wysiwyg )
						tinymce.editors[$input.attr('id')].setContent(translations)
					else
						$input.val(translations)
				}
			})
		})
	}

	$(document).ready(function(){

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
			$(this).addClass('loading');

			$.get( $(this).attr('href') ).then(function (){

				setTimeout(window.location.reload, 500);
			})
		})

		if( window.enable_translation )
			initTranslation();
	});


})(jQuery);
