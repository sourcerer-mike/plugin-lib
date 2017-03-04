/*!
 * plugin-lib (https://github.com/felixarntz/plugin-lib)
 * By Felix Arntz (https://leaves-and-love.net)
 * Licensed under GPL-3.0
 */
( function( $, pluginLibData ) {
	'use strict';

	$( '.nav-tab' ).on( 'click', function( e ) {
		var $this = $( this );
		var $all  = $this.parent().children( '.nav-tab' );

		e.preventDefault();

		if ( 'true' === $this.attr( 'aria-selected' ) ) {
			return;
		}

		$all.each( function() {
			$( this ).attr( 'aria-selected', 'false' );
			$( $( this ).attr( 'href' ) ).attr( 'aria-hidden', 'true' );
		});

		$this.attr( 'aria-selected', 'true' );
		$( $this.attr( 'href' ) ).attr( 'aria-hidden', 'false' );
	});

	var $realSlug    = $( '#post_name' );
	var $editSlugBox = $( '#edit-slug-box' );

	var $full;
	var slug;
	var slugEdited;
	var slugDependencies = [];
	var blockAJAX = false;

	function generateSlug() {
		if ( blockAJAX ) {
			return;
		}

		blockAJAX = true;

		var requestData = {};

		requestData.nonce = pluginLibData.generate_slug_nonce;
		requestData[ pluginLibData.primary_property ] = pluginLibData.primary_property_value;

		for ( var i in slugDependencies ) {
			requestData[ slugDependencies[ i ].propertyName ] = slugDependencies[ i ].$elem.val();
		}

		wp.ajax.post( pluginLibData.ajax_prefix + 'model_generate_slug', requestData )
			.done( function( response ) {
				if ( response.verified && response.verified.length ) {
					slug = response.verified;

					$realSlug.val( slug );
					$full.text( slug );

					if ( 'none' === $editSlugBox.css( 'display' ) ) {
						$editSlugBox.fadeIn( 'slow' );
					}
				}

				blockAJAX = false;
			})
			.fail( function( message ) {
				console.error( message );

				blockAJAX = false;
			});
	}

	function editSlug() {
		if ( blockAJAX ) {
			return;
		}

		blockAJAX = true;

		var $buttons = $( '#edit-slug-buttons' );
		var buttonsOrig = $buttons.html();
		var fullOrig = $full.html();

		$buttons.html( '<button type="button" class="save button button-small">' + pluginLibData.i18n.ok + '</button> <button type="button" class="cancel button-link">' + pluginLibData.i18n.cancel + '</button>' );

		$buttons.children( '.save' ).one( 'click', function() {
			var newSlug = $full.children( 'input' ).val();

			if ( newSlug == slug ) {
				$buttons.children( '.cancel' ).trigger( 'click' );
				return;
			}

			var requestData = {};

			requestData.nonce = pluginLibData.verify_slug_nonce;
			requestData[ pluginLibData.primary_property ] = pluginLibData.primary_property_value;
			requestData[ pluginLibData.slug_property ] = newSlug;

			wp.ajax.post( pluginLibData.ajax_prefix + 'model_verify_slug', requestData )
				.done( function( response ) {
					if ( response.verified && response.verified.length ) {
						slug = response.verified;

						$buttons.html( buttonsOrig );
						$realSlug.val( slug );
						$full.text( slug );

						$( '.edit-slug' ).focus();

						if ( ! slugEdited ) {
							slugEdited = true;

							for ( var i in slugDependencies ) {
								slugDependencies[ i ].$elem.off( 'change', generateSlug );
							}
						}

						blockAJAX = false;
					} else {
						$buttons.children( '.cancel' ).trigger( 'click' );
					}
				})
				.fail( function( message ) {
					console.error( message );

					$buttons.children( '.cancel' ).trigger( 'click' );
				});
		});

		$buttons.children( '.cancel' ).one( 'click', function() {
			$buttons.html( buttonsOrig );
			$realSlug.val( slug );
			$full.html( fullOrig );

			blockAJAX = false;
		});

		$full.html( '<input type="text" id="new-post-slug" value="' + slug + '" autocomplete="off" />' )
			.children( 'input' )
				.keydown( function( e ) {
					var key = e.which;

					if ( 13 === key ) {
						e.preventDefault();
						$buttons.children( '.save' ).trigger( 'click' );
					} else if ( 27 === key ) {
						$buttons.children( '.cancel' ).trigger( 'click' );
					}
				})
				.keyup( function() {
					$realSlug.val( this.value );
				}).focus();
	}

	if ( $realSlug.length && $editSlugBox.length ) {
		$full = $( '#editable-post-name-full' );

		slug = $realSlug.val();
		slugEdited = slug.length ? true : false;

		if ( ! slugEdited && pluginLibData.slug_dependencies ) {
			for ( var i in pluginLibData.slug_dependencies ) {
				var $elem = $( '[name="' + pluginLibData.slug_dependencies[ i ] + '"]:input' );

				if ( $elem.length ) {
					slugDependencies.push({
						propertyName: pluginLibData.slug_dependencies[ i ],
						$elem: $elem
					});

					$elem.on( 'change', generateSlug );
				}
			}
		}

		$( '#titlediv' ).on( 'click', '.edit-slug', function() {
			editSlug();
		});
	}

}( jQuery, pluginLibEditModelData ) );
