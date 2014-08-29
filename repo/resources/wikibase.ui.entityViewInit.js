/**
 * JavaScript for 'wikibase' extension, initializing some stuff when ready. This is the main
 * entry point for initializing edit tools for editing entities on entity pages.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner at wikimedia.de >
 *
 * TODO: Refactor this huge single function into smaller pieces of code.
 */

( function( $, mw, wb, dataTypes, experts, getFormatterStore, getParserStore ) {
	'use strict';

	mw.hook( 'wikipage.content' ).add( function() {
		// Edit sections are re-generated with JS functionality further below:
		$( '.wb-editsection' ).parent( 'td' ).not( '.wb-terms td' ).remove();
		$( '.wb-editsection:not(td)' ).remove();

		// remove all infos about empty values which are displayed in non-JS
		$( '.wb-value-empty' ).empty().removeClass( 'wb-value-empty' );

		// Since the DOM is altered for the property edit tools to initialize properly, the
		// following hook informs about these operations having finished.
		// TODO: This hook is not supposed to be permanent. Remove it as soon as no more global DOM
		// adjustments are necessary.
		mw.hook( 'wikibase.domready' ).fire();

		var repoApi = new wb.RepoApi();

		registerEditRestrictionHandlers();

		if( mw.config.get( 'wbEntity' ) !== null ) {
			var $entityview = $( '.wikibase-entityview' ).first();

			initToolbarController( $entityview );

			var entityInitializer = new wb.EntityInitializer( 'wbEntity' );

			entityInitializer.getEntity().done( function( entity ) {
				createEntityDom( entity, $entityview, repoApi );
				triggerEditRestrictionHandlers();
			} );
		}

		$( wb ).on( 'startItemPageEditMode', function( event, origin, options ) {
			// add copyright warning to 'save' button if there is one:
			if( mw.config.exists( 'wbCopyright' ) ) {

				var copyRight = mw.config.get( 'wbCopyright' ),
					copyRightVersion = copyRight.version,
					copyRightMessageHtml = copyRight.messageHtml,
					cookieKey = 'wikibase.acknowledgedcopyrightversion',
					optionsKey = 'wb-acknowledgedcopyrightversion';

				if ( $.cookie( cookieKey ) === copyRightVersion ||
					mw.user.options.get( optionsKey ) === copyRightVersion
				) {
					return;
				}

				var $message = $( '<span><p>' + copyRightMessageHtml + '</p></span>' ),
					edittoolbar = $( origin ).data( 'edittoolbar' );

				if( !edittoolbar ) {
					return;
				}

				var $hideMessage = $( '<a/>', {
						text: mw.msg( 'wikibase-copyrighttooltip-acknowledge' )
					} ).appendTo( $message );

				var gravity = ( options && options.wbCopyrightWarningGravity ) || 'nw';

				// Tooltip gets its own anchor since other elements might have their own tooltip.
				// we don't even have to add this new toolbar element to the toolbar, we only use it
				// to manage the tooltip which will have the 'save' button as element to point to.
				// The 'save' button can still have its own tooltip though.
				var $messageAnchor = $( '<span/>' )
					.appendTo( 'body' )
					.toolbarlabel()
					.wbtooltip( {
						content: $message,
						permanent: true,
						gravity: gravity,
						$anchor: edittoolbar.toolbar.editGroup.getButton( 'save' )
					} );

				$hideMessage.on( 'click', function( event ) {
					event.preventDefault();
					$messageAnchor.data( 'wbtooltip' ).degrade( true );
					if ( mw.user.isAnon() ) {
						$.cookie( cookieKey, copyRightVersion, { 'expires': 365 * 3, 'path': '/' } );
					} else {
						var api = new mw.Api();
						api.get( {
							'action': 'tokens',
							'type': 'options'
						}, function( data ) {
							if ( data.tokens && data.tokens.optionstoken ) {
								api.post( {
									'action': 'options',
									'token': data.tokens.optionstoken,
									'optionname': optionsKey,
									'optionvalue': copyRightVersion
								} );
							}
						} );
					}
				} );

				$messageAnchor.data( 'wbtooltip' ).show();

				// destroy tooltip after edit mode gets closed again:
				$( wb ).one( 'stopItemPageEditMode', function( event, origin ) {
					if( $messageAnchor.data( 'wbtooltip' ) !== undefined ) {
						$messageAnchor.data( 'wbtooltip' ).degrade( true );
					}
				} );
			}
		} );

		// Check if the watch link (star in the Vector skin) needs to be updated after an edit
		$( wb ).on( 'stopItemPageEditMode', function( event, origin, options ) {
			// If save is undefined it should default to true
			var canceled = options && options.save === false;
			var updateWatchLink = mw.page && mw.page.watch ? mw.page.watch.updateWatchLink : null;

			// Skip if module isn't loaded or user doesn't have "watch by default" enabled anyway
			if ( canceled || !updateWatchLink || !mw.user.options.get( 'watchdefault' ) ) {
				return;
			}

			// All four supported skins are using the same ID, the other selectors
			// in mediawiki.page.watch.ajax.js are undocumented and probably legacy stuff
			var $link = $( '#ca-watch a' );

			// Skip if page is already watched and there is no "watch this page" link
			// Note: The exposed function fails for empty jQuery collections
			if ( $link.length ) {
				updateWatchLink( $link, 'watch', 'loading' );
				var api = new mw.Api();
				var pageid = mw.config.get( 'wgArticleId' );
				api.get( {
					'action': 'query',
					'prop': 'info',
					'inprop': 'watched',
					'pageids': pageid
				} ).done( function( data ) {
					var watched = data.query && data.query.pages[pageid] &&
						data.query.pages[pageid].watched !== undefined;
					updateWatchLink( $link, watched ? 'unwatch' : 'watch' );
				} ).fail( function() {
					updateWatchLink( $link, 'watch' );
				} );
			}
		} );

		// remove loading spinner after JavaScript has kicked in
		$( '.wikibase-entityview' ).removeClass( 'loading' );
		$( '.wb-entity-spinner' ).remove();

	} );

	/**
	 * @param {jQuery} $entityview
	 */
	function initToolbarController( $entityview ) {
		// The toolbars (defined per jquery.wikibase.toolbarcontroller.definition) that should
		// be initialized:
		var toolbarControllerConfig = {
			addtoolbar: [
				'claimgrouplistview',
				'claimlistview',
				'claim-qualifiers-snak',
				'references',
				'referenceview-snakview',
				'sitelinklistview'
			],
			edittoolbar: [
				'aliasesview',
				'claimview',
				'descriptionview',
				'labelview',
				'referenceview',
				'sitelinkview',
				'terms-labelview',
				'terms-descriptionview'
			],
			removetoolbar: ['claim-qualifiers-snak', 'referenceview-snakview-remove'],
			movetoolbar: [
				'claimlistview-claimview',
				'claim-qualifiers-snak',
				'statementview-referenceview',
				'referenceview-snakview'
			]
		};

		$entityview.toolbarcontroller( toolbarControllerConfig );
	}

	/**
	 * Creates the entity DOM structure.
	 *
	 * @param {wikibase.datamodel.Entity} entity
	 * @param {jQuery} $entityview
	 * @param {wikibase.RepoApi} repoApi
	 */
	function createEntityDom( entity, $entityview, repoApi ) {
		var abstractedRepoApi = new wb.AbstractedRepoApi( repoApi );
		var entityStore = new wb.store.EntityStore( abstractedRepoApi );
		wb.compileEntityStoreFromMwConfig( entityStore );

		$entityview
		.entityview( {
			value: entity,
			entityStore: entityStore,
			valueViewBuilder: new wb.ValueViewBuilder(
				experts,
				getFormatterStore( repoApi, dataTypes ),
				getParserStore( repoApi ),
				mw
			),
			api: repoApi
		} )
		.on( 'labelviewchange labelviewafterstopediting', function( event ) {
			var $labelview = $( event.target ),
				labelview = $labelview.data( 'labelview' ),
				label = labelview.value().label;

			$( 'title' ).text(
				mw.msg( 'pagetitle', label && label !== '' ? label : mw.config.get( 'wgTitle' ) )
			);
		} );

		$( wb ).on( 'startItemPageEditMode', function( event, origin, options ) {
			// Display anonymous user edit warning:
			if ( mw.user && mw.user.isAnon()
				&& $.find( '.mw-notification-content' ).length === 0
				&& !$.cookie( 'wikibase-no-anonymouseditwarning' )
			) {
				mw.notify(
					mw.msg( 'wikibase-anonymouseditwarning',
						mw.msg( 'wikibase-entity-' + entity.getType() )
					)
				);
			}
		} );

		wb.ui.initTermBox( entity, repoApi );
	}

	function registerEditRestrictionHandlers() {
		$( wb )
			.on( 'restrictEntityPageActions blockEntityPageActions', function( event ) {
				$( '.wikibase-toolbarbutton' ).each( function( i, node ) {
					var toolbarButton = $( node ).data( 'toolbarbutton' );

					toolbarButton.disable();

					var messageId = ( event.type === 'blockEntityPageActions' )
						? 'wikibase-blockeduser-tooltip-message'
						: 'wikibase-restrictionedit-tooltip-message';

					toolbarButton.element.wbtooltip( {
						content: mw.message( messageId ).escaped(),
						gravity: 'nw'
					} );
				} );
			} );
	}

	function triggerEditRestrictionHandlers() {
		if ( mw.config.get( 'wbUserIsBlocked' ) ) {
			$( wb ).triggerHandler( 'blockEntityPageActions' );
		} else if ( !mw.config.get( 'wbUserCanEdit' ) ) {
			$( wb ).triggerHandler( 'restrictEntityPageActions' );
		}

		if( !mw.config.get( 'wbIsEditView' ) ) {
			// no need to implement a 'disableEntityPageActions' since hiding all the toolbars directly like this is
			// not really worse than hacking the Toolbar prototype to achieve this:
			$( '.wikibase-toolbar' ).hide();
			$( 'body' ).addClass( 'wb-editing-disabled' );
			// make it even harder to edit stuff, e.g. if someone is trying to be smart, using
			// firebug to show hidden nodes again to click on them:
			$( wb ).triggerHandler( 'restrictEntityPageActions' );
		}
	}

} )(
	jQuery,
	mediaWiki,
	wikibase,
	wikibase.dataTypes,
	wikibase.experts.store,
	wikibase.formatters.getStore,
	wikibase.parsers.getStore
);
