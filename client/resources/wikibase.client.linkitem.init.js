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

		mw.loader.using( [
				'jquery.wikibase.linkitem',
				'mediawiki.api',
				'mediawiki.Title',
				'user.tokens',
				'mw.config.values.wbRepo',
				'wikibase.RepoApi',
			],
			function() {
				var wb = wikibase;

				$spinner.remove();

				var repoConfig = mw.config.get( 'wbRepo' );
				var repoScriptUrl = repoConfig.url + repoConfig.scriptPath;
				var localScriptUrl = mw.config.get( 'wgServer' ) + mw.config.get( 'wgScriptPath' );
				var repoIsLocal = repoScriptUrl === localScriptUrl;
				var originForCors = null;

				if( !repoIsLocal ) {
					originForCors = mw.config.get( 'wgServer' );
					if ( originForCors.indexOf( '//' ) === 0 ) {
						// The origin parameter musn't be protocol relative
						originForCors = document.location.protocol + originForCors;
					}
				}

				$linkItemLink
				.show()
				.linkitem( {
					repoApi: new wb.RepoApi(
						new mw.Api(),
						repoScriptUrl + '/api.php',
						repoIsLocal && mw.user.tokens.get( 'editToken' ),
						originForCors
					),
					pageTitle: ( new mw.Title(
						mw.config.get( 'wgTitle' ),
						mw.config.get( 'wgNamespaceNumber' )
					) ).getPrefixedText(),
					globalSiteId: mw.config.get( 'wbCurrentSite' ).globalSiteId,
					namespaceNumber: mw.config.get( 'wgNamespaceNumber' ),
					repoArticlePath: repoConfig.url + repoConfig.articlePath,
					langLinkSiteGroup: mw.config.get( 'wbCurrentSite' ).langLinkSiteGroup
				} );

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

		$( '.wb-langlinks-edit, .wb-langlinks-add' ).eq( 0 )
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
