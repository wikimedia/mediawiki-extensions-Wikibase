/**
 * Add a link to the Sitelinks section of the connected Item page to the ULS actions menu.
 *
 * @license GPL-2.0-or-later
 */
( function () {
	'use strict';

	var itemId = mw.config.get( 'wgWikibaseItemId' );
	if ( !itemId || [ null, 'error', 'registered' ].indexOf( mw.loader.getState( 'ext.uls.interface' ) ) !== -1 ) {
		return;
	}

	// wait for ext.uls.interface to be ready,
	// and lazy-load the other dependencies now that we know they're needed
	mw.loader.using( [
		'mw.config.values.wbRepo',
		'mw.config.values.wbSiteDetails',
		'ext.uls.interface',
		'oojs-ui.styles.icons-editing-core'
	], function () {
		var repoConfig = mw.config.get( 'wbRepo' );
		var clientConfig = mw.config.get( 'wbSiteDetails' )[ mw.config.get( 'wgWikiID' ) ];
		var itemUrl = repoConfig.url
			+ repoConfig.articlePath.replace( '$1', 'Special:EntityPage/' + itemId )
			+ '#sitelinks-' + clientConfig.group;

		mw.uls.ActionsMenuItemsRegistry.register( {
			name: 'wikibaseItemLink',
			icon: 'edit',
			text: mw.msg( 'wikibase-editlinkstitle' ),
			href: itemUrl
		} );
	}, function ( e ) {
		// eslint-disable-next-line no-console
		console.error( e );
	} );
}() );
