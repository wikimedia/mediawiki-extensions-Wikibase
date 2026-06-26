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
		'mw.config.values.wbCurrentSiteDetails',
		'ext.uls.interface',
		'oojs-ui.styles.icons-editing-core'
	], () => {
		var repoConfig = mw.config.get( 'wbRepo' );
		var clientConfig = mw.config.get( 'wbCurrentSiteDetails' );
		var itemUrl = repoConfig.url
			+ repoConfig.articlePath.replace( '$1', 'Special:EntityPage/' + itemId );
		if ( clientConfig.group ) {
			itemUrl += '#sitelinks-' + clientConfig.group;
		}

		if ( mw.config.get( 'wgULSLanguageSelectorV2Enabled' ) ) {
			const { cdxIconEdit } = require( './icons.json' );
			mw.loader.using( 'ext.uls.rewrite.entrypoints' ).then( ( require ) => {
				const EntrypointRegistry = require( 'ext.uls.rewrite.entrypoints' );
				const { ENTRYPOINT_TYPE, ULS_MODE } = EntrypointRegistry;
				const quickAction = {
					id: 'wikibase-connected-sitelink',
					shouldShow: () => true,
					getConfig: () => ( {
						label: mw.msg( 'wikibase-editlinkstitle' ),
						icon: cdxIconEdit,
						url: itemUrl
					} )
				};
				EntrypointRegistry.register( ENTRYPOINT_TYPE.QUICK_ACTIONS, quickAction, ULS_MODE.CONTENT );

				const emptyListAction = Object.assign( {}, quickAction );
				emptyListAction.id = 'wikibase-connected-sitelink-emptylist';
				EntrypointRegistry.register( ENTRYPOINT_TYPE.EMPTY_LIST, emptyListAction, ULS_MODE.CONTENT );
			} );
		} else {
			mw.uls.ActionsMenuItemsRegistry.register( {
				name: 'wikibaseItemLink',
				icon: 'edit',
				text: mw.msg( 'wikibase-editlinkstitle' ),
				href: itemUrl
			} );
		}
	}, ( e ) => {
		// eslint-disable-next-line no-console
		console.error( e );
	} );
}() );
