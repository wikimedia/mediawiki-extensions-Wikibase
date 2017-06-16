/**
 * JavaScript that lazy loads the widget for linking articles on the client
 *
 * @license GPL-2.0+
 * @author Marius Hoch < hoo@online.de >
 */
( function ( mw, $ ) {
	'use strict';

	function initLinkItem( elem ) {
		var $spinner = $.createSpinner(),
			$linkItemLink = $( elem );

		$linkItemLink
		.hide()
		.after( $spinner );

		mw.loader.using(
			[
				'jquery.wikibase.linkitem',
				'mediawiki.Title',
				'mw.config.values.wbRepo',
				'wikibase.client.getMwApiForRepo'
			],
			function () {
				$spinner.remove();

				var repoConfig = mw.config.get( 'wbRepo' );

				$linkItemLink
				.show()
				.linkitem( {
					mwApiForRepo: wikibase.client.getMwApiForRepo(),
					pageTitle: ( new mw.Title(
						mw.config.get( 'wgPageName' )
					) ).getPrefixedText(),
					globalSiteId: mw.config.get( 'wbCurrentSite' ).globalSiteId,
					namespaceNumber: mw.config.get( 'wgNamespaceNumber' ),
					repoArticlePath: repoConfig.url + repoConfig.articlePath,
					langLinkSiteGroup: mw.config.get( 'wbCurrentSite' ).langLinkSiteGroup
				} );

				var widgetName = $linkItemLink.data( 'linkitem' ).widgetName;

				$linkItemLink
				.on( 'linkitemdialogclose.' + widgetName, function ( event ) {
					$linkItemLink
					.off( '.' + widgetName )
					.data( 'linkitem' ).destroy();
				} )
				.on( 'linkitemsuccess.' + widgetName, function ( event ) {
					// Don't reshow the "Add links" link but reload the page on dialog close:
					$linkItemLink
					.off( '.' + widgetName )
					.on( 'linkitemdialogclose.' + widgetName, function () {
						window.location.reload( true );
					} );
				} );
			},
			function () {
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
	$( function () {
		if ( !$.support.cors ) {
			// This will fail horribly w/o CORS support on WMF-like setups (different domains for repo and client)
			// Just leave the no-JS edit link in place.
			return;
		}

		$( '.wb-langlinks-link > a' ).eq( 0 )
		.click( function ( event ) {
			event.preventDefault();
			initLinkItem( this );
		} );
	} );
}( mediaWiki, jQuery ) );
