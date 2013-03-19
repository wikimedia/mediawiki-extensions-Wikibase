/**
* JavaScript that allows linking articles with Wikibase items or creating
* new wikibase items directly in the client wikis
*
* @since 0.4
*
* Author: Marius Hoch hoo@online.de
*/
( function( mw, $ ) {
'use strict';

	/**
	 * Displays the link which shows the dialog after checking whether the user is logged ins
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
					var $e = event;
					mw.loader.using( 'wbclient.linkItem', function( event ) {
						wikibase.AddSiteLinkWidget.checkLoggedin( $e );
					});
				})
			);
		$( '#p-lang' ).show();
	} );

} )( mediaWiki, jQuery );
