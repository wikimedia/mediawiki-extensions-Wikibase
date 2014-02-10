/**
 * JavaScript that lazy loads the widget for linking articles on the client
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author: Marius Hoch < hoo@online.de >
 */
( function( mw, $ ) {
	'use strict';

	function initLinkItem( elem ) {
		var $spinner = $.createSpinner(),
			$linkItemLink = $( elem );

		$linkItemLink
		.hide()
		.after( $spinner );

		mw.loader.using(
			'jquery.wikibase.linkitem',
			function() {
				$spinner.remove();

				$linkItemLink
				.show()
				.linkitem();

				var widgetName = $linkItemLink.data( 'linkitem' ).widgetName;

				$linkItemLink
				.on( 'linkitemdialogclose.' + widgetName, function( event ) {
					$linkItemLink.data( 'linkitem' ).destroy();
				} )
				.on( 'linkitemsuccess.' + widgetName, function( event ) {
					// Don't reshow the "Add links" link but reload the page on dialog close:
					$linkItemLink
					.off( 'linkitemdialogclose' )
					.on( 'linkitemdialogclose.' + widgetName, function() {
						window.location.reload( true );
					} );
				} );
			},
			function() {
				// Failure: This isn't very likely, but who knows
				$spinner.remove();
				$linkItemLink.show();
				mw.notify( mw.msg( 'unknown-error' ) );
			}
		);
	}

	/**
	 * Displays the link which opens the dialog (using jquery.wikibase.linkitem)
	 */
	$( document ).ready( function() {
		if ( !$.support.cors ) {
			// This will fail horribly w/o CORS support on WMF-like setups (different domains for repo and client)
			return;
		}

		// @note .wbc-editpage is deprecated
		$( '.wbc-editpage, .wb-langlinks-add' ).eq( 0 )
		.empty()
		.append(
			$( '<a>' )
			.attr( {
				href: '#',
				id: 'wbc-linkToItem-link'
			} )
			.text( mw.msg( 'wikibase-linkitem-addlinks' ) )
			.click( function( event ) {
				event.preventDefault();
				initLinkItem( this );
			} )
		);
		$( '#p-lang' ).show();
	} );
} )( mediaWiki, jQuery );
