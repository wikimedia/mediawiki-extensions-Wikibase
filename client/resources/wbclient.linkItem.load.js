/**
* JavaScript that lazy loads the widget for linking articles on the client
*
* @since 0.4
*
* Author: Marius Hoch hoo@online.de
*/
( function( mw, $ ) {
	/**
	 * Displays the link which loads wbclient.LinkItem
	 *
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
						'wbclient.LinkItem',
						function() {
							$spinner.remove();
							new wikibase.LinkItem();
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
