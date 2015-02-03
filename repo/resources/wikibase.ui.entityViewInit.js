/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner < daniel.werner at wikimedia.de >
 */

( function( $, mw, wb, dataTypeStore, getExpertsStore, getFormatterStore, getParserStore ) {
	'use strict';

	mw.hook( 'wikipage.content' ).add( function() {

		if( mw.config.get( 'wbEntity' ) === null ) {
			return;
		}

		var $entityview = $( '.wikibase-entityview' );
		var entityInitializer = new wb.EntityInitializer( 'wbEntity' );

		initToolbarController( $entityview );

		entityInitializer.getEntity().done( function( entity ) {
			var viewName = createEntityView( entity, $entityview.first() );

			attachAnonymousEditWarningTrigger( $entityview, viewName, entity.getType() );

			attachWatchLinkUpdater( $entityview, viewName );

			evaluateRestrictions();

			// Remove loading spinner after JavaScript has kicked in:
			$entityview.removeClass( 'loading' );
			$( '.wb-entity-spinner' ).remove();
		} );

		$entityview.on( 'labelviewchange labelviewafterstopediting', function( event ) {
			var $labelview = $( event.target ),
				labelview = $labelview.data( 'labelview' ),
				label = labelview.value().getText();

			$( 'title' ).text(
				mw.msg( 'pagetitle', label !== '' ? label : mw.config.get( 'wgTitle' ) )
			);
		} );

		attachCopyrightTooltip( $entityview );
	} );

	/**
	 * @param {jQuery} $entityview
	 */
	function initToolbarController( $entityview ) {
		// The toolbars (defined per jquery.wikibase.toolbarcontroller.definition) that should
		// be initialized:
		var toolbarControllerConfig = {
			toolbars: {
				addtoolbar: [
					'statementgrouplistview-statementgroupview',
					'statementlistview-statementview',
					'statementview-snakview',
					'statementview-referenceview',
					'referenceview-snakview'
				],
				edittoolbar: [
					'aliasesview',
					'statementview',
					'descriptionview',
					'labelview',
					'entitytermsview',
					'referenceview',
					'sitelinkgroupview'
				],
				removetoolbar: [
					'statementview-snakview',
					'referenceview-snakview',
					'sitelinkgroupview-sitelinkview'
				]
			}
		};

		$entityview.toolbarcontroller( toolbarControllerConfig );
	}

	/**
	 * @param {jQuery} $entityview
	 */
	function attachCopyrightTooltip( $entityview ) {
		$entityview.on( 'edittoolbarafterstartediting', function( event ) {
			var $target = $( event.target ),
				gravity = 'sw';

			if(
				$target.data( 'labelview' )
				|| $target.data( 'descriptionview' )
				|| $target.data( 'aliasesview' )
				|| $target.data( 'sitelinkgroupview' )
			) {
				gravity = 'nw';
			}

			showCopyrightTooltip( $entityview, $target, gravity );
		} );
	}

	/**
	 * Builds an entity store.
	 * @todo Move to a top-level factory or application scope
	 *
	 * @param {wikibase.api.RepoApi} repoApi
	 * @return {wikibase.store.CombiningEntityStore}
	 */
	function buildEntityStore( repoApi ) {
		// Deserializer for fetched content whose content is a wb.datamodel.Entity:
		var fetchedEntityDeserializer = new wb.store.FetchedContentUnserializer(
				new wb.serialization.EntityDeserializer()
			);

		return new wb.store.CombiningEntityStore( [
			new wb.store.MwConfigEntityStore( fetchedEntityDeserializer ),
			new wb.store.ApiEntityStore(
				repoApi,
				fetchedEntityDeserializer,
				[ mw.config.get( 'wgUserLanguage' ) ]
			)
		] );

	}

	/**
	 * @param {wikibase.datamodel.Entity} entity
	 * @param {jQuery} $entityview
	 * @return {string} The name of the entity view widget class
	 *
	 * @throws {Error} if no widget to render the entity exists.
	 */
	function createEntityView( entity, $entityview ) {
		var repoConfig = mw.config.get( 'wbRepo' );
		var mwApi = wb.api.getLocationAgnosticMwApi( repoConfig.url + repoConfig.scriptPath + '/api.php' );
		var repoApi = new wb.api.RepoApi( mwApi ),
			entityStore = buildEntityStore( repoApi ),
			revisionStore = new wb.RevisionStore( mw.config.get( 'wgCurRevisionId' ) ),
			entityChangersFactory = new wb.entityChangers.EntityChangersFactory(
				repoApi,
				revisionStore,
				entity
			),
			contentLanguages = new wikibase.WikibaseContentLanguages();

		var viewName = entity.getType() + 'view';

		if( !$.wikibase[ viewName ] ) {
			throw new Error( 'View for entity type ' + entity.getType() + ' does not exist' );
		}

		$entityview[ viewName ]( {
			value: entity,
			languages: getUserLanguages(),
			entityChangersFactory: entityChangersFactory,
			entityStore: entityStore,
			valueViewBuilder: new wb.ValueViewBuilder(
				getExpertsStore( dataTypeStore ),
				getFormatterStore( repoApi, dataTypeStore ),
				getParserStore( repoApi ),
				mw.config.get( 'wgUserLanguage' ),
				{
					getMessage: function( key, params ) {
						return mw.msg.apply( mw, [ key ].concat( params ) );
					}
				},
				contentLanguages
			),
			dataTypeStore: dataTypeStore
		} );

		return viewName;
	}

	/**
	 * @return {string[]}
	 */
	function getUserLanguages() {
		var userLanguages = mw.config.get( 'wbUserSpecifiedLanguages' ),
			isUlsDefined = mw.uls && $.uls && $.uls.data,
			languages;

		if( !userLanguages.length && isUlsDefined ) {
			languages = mw.uls.getFrequentLanguageList().slice( 1, 4 );
		} else {
			languages = userLanguages.slice();
			languages.splice( $.inArray( mw.config.get( 'wgUserLanguage' ), userLanguages ), 1 );
		}

		languages.unshift( mw.config.get( 'wgUserLanguage' ) );

		return languages;
	}

	/**
	 * Update the state of the watch link if the user has watchdefault enabled.
	 */
	function attachWatchLinkUpdater( viewName, $entityview ) {
		var update = mw.page && mw.page.watch ? mw.page.watch.updateWatchLink : null;

		if( !update || !mw.user.options.get( 'watchdefault' ) ) {
			return;
		}

		function updateWatchLink() {
			// All four supported skins are using the same ID, the other selectors
			// in mediawiki.page.watch.ajax.js are undocumented and probably legacy stuff
			var $link = $( '#ca-watch a' );

			// Skip if page is already watched and there is no "watch this page" link
			// Note: The exposed function fails for empty jQuery collections
			if( !$link.length ) {
				return;
			}

			update( $link, 'watch', 'loading' );

			var api = new mw.Api(),
				pageId = mw.config.get( 'wgArticleId' );

			api.get( {
				action: 'query',
				prop: 'info',
				inprop: 'watched',
				pageids: pageId
			} ).done( function( data ) {
				var watched = data.query && data.query.pages[pageId]
					&& data.query.pages[pageId].watched !== undefined;
				update( $link, watched ? 'unwatch' : 'watch' );
			} ).fail( function() {
				update( $link, 'watch' );
			} );
		}

		$entityview.on( viewName + 'afterstopediting', function( event, dropValue ) {
			if( !dropValue ) {
				updateWatchLink();
			}
		} );
	}

	/**
	 * @param {jQuery.wikibase.entityview} $entityview
	 * @param {string} viewName
	 * @param {string} entityType
	 */
	function attachAnonymousEditWarningTrigger( $entityview, viewName, entityType ) {
		if( !mw.user || !mw.user.isAnon() ) {
			return;
		}

		$entityview.on( viewName + 'afterstartediting', function() {
			if(
				$.find( '.mw-notification-content' ).length === 0
				&& !$.cookie( 'wikibase-no-anonymouseditwarning' )
			) {
				mw.notify(
					mw.msg( 'wikibase-anonymouseditwarning',
						mw.msg( 'wikibase-entity-' + entityType )
					)
				);
			}
		} );
	}

	/**
	 * @param {jQuery} $entityview
	 * @param {jQuery} $origin
	 * @param {string} gravity
	 */
	function showCopyrightTooltip( $entityview, $origin, gravity ) {
		if( !mw.config.exists( 'wbCopyright' ) ) {
			return;
		}

		gravity = gravity || 'nw';

		var copyRight = mw.config.get( 'wbCopyright' ),
			copyRightVersion = copyRight.version,
			copyRightMessageHtml = copyRight.messageHtml,
			cookieKey = 'wikibase.acknowledgedcopyrightversion',
			optionsKey = 'wb-acknowledgedcopyrightversion';

		if(
			$.cookie( cookieKey ) === copyRightVersion
			|| mw.user.options.get( optionsKey ) === copyRightVersion
		) {
			return;
		}

		var $message = $( '<span><p>' + copyRightMessageHtml + '</p></span>' )
				.addClass( 'wikibase-copyrightnotification-container' ),
			$hideMessage = $( '<a/>', {
				text: mw.msg( 'wikibase-copyrighttooltip-acknowledge' )
			} ).appendTo( $message ),
			editableTemplatedWidget = $origin.data( 'EditableTemplatedWidget' );

		// TODO: Use notification system for copyright messages on all widgets.
		if(
			editableTemplatedWidget
			&& !( editableTemplatedWidget instanceof $.wikibase.statementview )
			&& !( editableTemplatedWidget instanceof $.wikibase.aliasesview )
		) {
			editableTemplatedWidget.notification( $message, 'wb-edit' );

			$hideMessage.on( 'click', function( event ) {
				event.preventDefault();
				editableTemplatedWidget.notification();
				if( mw.user.isAnon() ) {
					$.cookie( cookieKey, copyRightVersion, { 'expires': 365 * 3, 'path': '/' } );
				} else {
					var api = new mw.Api();
					api.postWithToken( 'options', {
						'action': 'options',
						'optionname': optionsKey,
						'optionvalue': copyRightVersion
					} );
				}
			} );
			return;
		}

		var edittoolbar = $origin.data( 'edittoolbar' );

		if( !edittoolbar ) {
			return;
		}

		// Tooltip gets its own anchor since other elements might have their own tooltip.
		// we don't even have to add this new toolbar element to the toolbar, we only use it
		// to manage the tooltip which will have the 'save' button as element to point to.
		// The 'save' button can still have its own tooltip though.
		var $messageAnchor = $( '<span/>' )
			.appendTo( 'body' )
			.toolbaritem()
			.wbtooltip( {
				content: $message,
				permanent: true,
				gravity: gravity,
				$anchor: edittoolbar.getContainer()
			} );

		$hideMessage.on( 'click', function( event ) {
			event.preventDefault();
			$messageAnchor.data( 'wbtooltip' ).degrade( true );
			$( window ).off( '.wbCopyrightTooltip' );
			if( mw.user.isAnon() ) {
				$.cookie( cookieKey, copyRightVersion, { 'expires': 365 * 3, 'path': '/' } );
			} else {
				var api = new mw.Api();
				api.postWithToken( 'options', {
					'action': 'options',
					'optionname': optionsKey,
					'optionvalue': copyRightVersion
				} );
			}
		} );

		$messageAnchor.data( 'wbtooltip' ).show();

		// destroy tooltip after edit mode gets closed again:
		$entityview
		.one( 'entityviewafterstopediting.wbCopyRightTooltip', function( event, origin ) {
			var tooltip = $messageAnchor.data( 'wbtooltip' );
			if( tooltip ) {
				tooltip.degrade( true );
			}
			$( window ).off( '.wbCopyrightTooltip' );
		} );

		$( window ).one(
			'scroll.wbCopyrightTooltip touchmove.wbCopyrightTooltip resize.wbCopyrightTooltip',
			function() {
				var tooltip = $messageAnchor.data( 'wbtooltip' );
				if( tooltip ) {
					$messageAnchor.data( 'wbtooltip' ).hide();
				}
				$entityview.off( '.wbCopyRightTooltip' );
			}
		);
	}

	function evaluateRestrictions() {
		if( mw.config.get( 'wbUserIsBlocked' ) ) {
			restrict( 'blockeduser' );
		} else if( !mw.config.get( 'wbUserCanEdit' ) ) {
			restrict( 'restrictionedit' );
		}
	}

	/**
	 * @param {string} key
	 */
	function restrict( key ) {
		$( ':wikibase-toolbarbutton' ).each( function() {
			var toolbarButton = $( this ).data( 'toolbarbutton' );
			toolbarButton.disable();

			toolbarButton.element.wbtooltip( {
				content: mw.message( 'wikibase-' + key + '-tooltip-message' ).escaped(),
				gravity: 'nw'
			} );
		} );
	}

} )(
	jQuery,
	mediaWiki,
	wikibase,
	wikibase.dataTypeStore,
	wikibase.experts.getStore,
	wikibase.formatters.getStore,
	wikibase.parsers.getStore
);
