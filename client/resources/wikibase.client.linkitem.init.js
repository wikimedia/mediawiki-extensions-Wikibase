/**
 * JavaScript that lazy loads the widget for linking articles on the client
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
( function () {
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
				'mw.config.values.wbRepo'
			],
			function () {
				$spinner.remove();

				var repoConfig = mw.config.get( 'wbRepo' ),
					linkItemConfig = require( './config.json' ),
					currentSite = linkItemConfig.currentSite;

				$linkItemLink
				.show()
				.linkitem( {
					pageTitle: ( new mw.Title(
						mw.config.get( 'wgPageName' )
					) ).getPrefixedText(),
					globalSiteId: currentSite.globalSiteId,
					namespaceNumber: mw.config.get( 'wgNamespaceNumber' ),
					repoArticlePath: repoConfig.url + repoConfig.articlePath,
					langLinkSiteGroup: currentSite.langLinkSiteGroup,
					tags: linkItemConfig.tags || []
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
		// TODO: $.support is deprecated
		// eslint-disable-next-line no-jquery/no-support
		if ( !$.support.cors ) {
			// This will fail horribly w/o CORS support on WMF-like setups (different domains for repo and client)
			// Just leave the no-JS edit link in place.
			return;
		}

		$( '.wb-langlinks-link > a' ).eq( 0 )
			.on( 'click', function ( event ) {
				event.preventDefault();
				initLinkItem( this );
			} );
	} );
}() );
