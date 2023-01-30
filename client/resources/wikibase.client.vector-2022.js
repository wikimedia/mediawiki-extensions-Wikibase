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

	mw.loader.using( [
		'mw.config.values.wbRepo',
		'ext.uls.interface'
	], function () {
		var repoConfig = mw.config.get( 'wbRepo' );
		var itemUrl = repoConfig.url + repoConfig.articlePath.replace( '$1', 'Special:EntityPage/' + itemId ) + '#sitelinks';

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
