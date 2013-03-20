/**
* JavaScript that lazy loads the widget for linking articles on the client
*
* @since 0.4
*
* Author: Marius Hoch hoo@online.de
*/
( function( mw, $ ) {
	'use strict';
	/**
	 * Displays the link which opens the dialog (using jquery.wikibase.linkitem)
	 */
	$( document ).ready( function() {
		$( '#wbc-linkToItem' )
			.empty()
			.append(
				$( '<a>' )
				.attr( {
					href: '#',
					id: 'wbc-linkToItem-link'
				} )
				.text( mw.msg( 'wikibase-linkitem-addlinks' ) )
				.click( function( event ) {
					var $spinner = $.createSpinner(),
						$linkItemLink = $( this );

					$linkItemLink
						.hide()
						.after( $spinner );

					event.preventDefault();

					mw.loader.using(
						'jquery.wikibase.linkitem',
						function() {
							$spinner.remove();
							$linkItemLink.show().linkitem();
						},
						function() {
							// Failure: This isn't very likely, but who knows
							$spinner.remove();
							$linkItemLink.show();
							mw.notify( mw.msg( 'unknown-error' ) );
						}
					);
				} )
			);
		$( '#p-lang' ).show();
	} );
} )( mediaWiki, jQuery );
