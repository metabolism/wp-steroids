;(function($) {

	//todo: rewrite all

	function disableACFLayoutReorder(){
		$('.acf-flexible-content > .values').sortable( "disable" );
		$('.acf-flexible-content .ui-sortable-handle').removeAttr( "title" );
	}

	function ucfirst(string) {
		return string.charAt(0).toUpperCase() + string.slice(1);
	}

	function initTranslation(){

		if( !wps.enable_translation )
			return

		var selector = '#edittag .term-name-wrap td, #edittag .term-slug-wrap td, #edittag .term-description-wrap td, #wp-content-wrap, #titlewrap, #wp-advanced_description-wrap, #postexcerpt .inside, #menu-to-edit .menu-item-settings label, #link-selector .wp-link-text-field label, .edit-post-visual-editor__post-title-wrapper'

		$(selector).append('<a class="wps-translate wps-translate--'+wps.enable_translation+'" title="Translate with '+ucfirst(wps.enable_translation)+'"></a>')
		$('#tag-post-content #name').wrap('<div class="input-wrapper"></div>')
		$('#tag-post-content #name').after('<a class="wps-translate wps-translate--'+wps.enable_translation+'" title="Translate with '+ucfirst(wps.enable_translation)+'"></a>')
		$('#menu-to-edit span.description').remove()

		$(document).on('mouseenter', '.editor-post-excerpt', function (){

			if( !$(this).find('.wps-translate').length )
				$(this).find('.components-base-control__field').append('<a class="wps-translate wps-translate--'+wps.enable_translation+'" title="Translate with '+ucfirst(wps.enable_translation)+'"></a>')
		})

		$(document).on('click', '.wps-translate', function (){

			var $self = $(this);

			var is_title = $(this).prev('.editor-post-title').length;
			var is_excerpt = $(this).closest('.editor-post-excerpt').length;

			if( !is_title && !is_excerpt ){

				var $inputs = $(this).parent().find('.acf-input-wrap > input, > textarea, textarea.wp-editor-area, .field, #title, #name, #excerpt')

				if( !$inputs.length ){

					$inputs = $(this).prev('input, textarea')

					if( !$inputs.length ){

						$inputs = $(this).prev().prev('input, textarea')

						if( !$inputs.length )
							return;
					}
				}

				var $input = $inputs.first()
				var $editable = $input.prev('[contenteditable]');
				var is_editor = $input.hasClass('wp-editor-area');
			}

			var value = ''

			if( is_title )
				value = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'title' );
			else if (is_excerpt )
				value = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'excerpt' );
			else
				value = is_editor ? tinymce.editors[$input.attr('id')].getContent() : $input.val();

			if( value.length <= 2)
				return;

			$self.addClass('loading')

			$.post(wps.ajax_url, {action: 'translate', q:value, format:(is_editor?'html':'text')}, function (response){

				$self.removeClass('loading')

				if( response.text.length ){

					var translations = response.text;

					if( is_title ){

						wp.data.dispatch( 'core/editor' ).editPost( { title: translations } );
					}
					else if( is_excerpt ){

						wp.data.dispatch( 'core/editor' ).editPost( { excerpt: translations } );
					}
					else if( is_editor ){

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
		})
	}

	function columnAddToMenu(){

		$('#submit-columndiv').click(function(){

			$( '.columndiv .spinner' ).addClass( 'is-active' );

			let menu = {
				'-1': {
					'menu-item-type': 'custom',
					'menu-item-url': $('.url-columndiv').val(),
					'menu-item-title': $('.title-columndiv').val()
				}
			};

			window.wpNavMenu.addItemToMenu( menu, window.wpNavMenu.addMenuItemToBottom, function() {
				// Remove the Ajax spinner.
				$( '.columndiv .spinner' ).removeClass( 'is-active' );
			});
		})
	}

	function buildClick(){

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
	}

	function ajaxClick(){

		$('a.wp-ajax').click(function(e){

			function ajaxCall(){

				$.get( $el.attr('href') ).then(function (data){

					if( data.continue ){

						ajaxCall($el)
						$el.text(continue_text.replace(/\[(.+?)\]/g, (_, match) => data[match] || match));
					}
					else{

						$el.removeClass('loading');
						$el.text('Done!')
						document.location.reload()
					}
				})
			}

			e.preventDefault();
			var $el = $(this);
			var continue_text = $el.attr('data-continue');

			$el.addClass('loading');

			ajaxCall($el)
		})
	}

	function setupACF(){

		if( $('body').hasClass('no-acf_edit_layout') ){

			disableACFLayoutReorder();
			setInterval(disableACFLayoutReorder, 1000);
		}

		$('.acf-label').each(function(){

			if( $(this).text().length < 2 )
				$(this).remove()
		})
	}

	function makeTitleRequired(){

		if( $('#title').length )
            $('#title').attr('required', 'required');
	}

	$(document).ready(function(){

		makeTitleRequired()
		setupACF()
		columnAddToMenu()
		buildClick()
		ajaxClick()
	});

	$(window).load(initTranslation);

})(jQuery);
