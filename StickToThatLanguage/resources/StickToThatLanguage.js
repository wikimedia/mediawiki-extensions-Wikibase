/**
 * JavaScript for 'StickToThatLanguage' extension
 *
 * @since 0.1
 * @file StickToThatLanguage.js
 * @ingroup STTLanguage
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */

( function( $, mw ) {
	'use strict';

	$( document ).ready( function() {

		// place linked separator to have other languages collapse below top 10 languages
		$( '.sttl-toplang' )
		.removeClass( 'sttl-lasttoplang' ) // remove non-JS exclusive class
		.detach()
		.appendTo(
			$( '<ul/>' ).prependTo(
				$( '#p-lang .body' )
			) // put top languages into its own ul and append link to show more languages
			.after(
				$( '<h6/>' )
				.append(
					$( '<span/>' )
					.addClass( 'ui-icon ui-icon-triangle-1-e' )
				)
				.append(
					$( '<a/>' )
					.addClass( 'sttl-languages-more-link' )
					.text( mw.msg( 'sttl-languages-more-link' ) )
					.attr( 'href', 'javascript:void(0);' )
					.click( function( event ) {
						event.preventDefault();
						$( '#p-lang .body h6 span' )
						.toggleClass( 'ui-icon-triangle-1-e' )
						.toggleClass( 'ui-icon-triangle-1-s' );
						$( $( '#p-lang .body ul' )[1] ).slideToggle();
					} )
				)
			)
		);

		// class style initially hides "more" languages
		$( $( '#p-lang .body ul' )[1] ).addClass( 'sttl-languages-more' );

	});

} )( jQuery, mediaWiki );

