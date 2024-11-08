/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb, performance ) {
	'use strict';

	var getExpertsStore = require( './experts/getStore.js' ),
		getParserStore = require( './parsers/getStore.js' ),
		DefaultViewFactoryFactory = require( '../../view/resources/wikibase/view/ViewFactoryFactory.js' ),
		RevisionStore = require( '../../view/resources/wikibase/wikibase.RevisionStore.js' ),
		ApiValueFormatterFactory = require( './formatters/ApiValueFormatterFactory.js' ),
		StructureEditorFactory = require( '../../view/resources/wikibase/view/StructureEditorFactory.js' ),
		CachingEntityStore = require( '../../view/resources/wikibase/store/store.CachingEntityStore.js' ),
		ApiEntityStore = require( '../../view/resources/wikibase/store/store.ApiEntityStore.js' ),
		dataTypeStore = require( './dataTypes/wikibase.dataTypeStore.js' ),
		CachingEntityIdHtmlFormatter = require( '../../view/resources/wikibase/entityIdFormatter/CachingEntityIdHtmlFormatter.js' ),
		DataValueBasedEntityIdHtmlFormatter = require( '../../view/resources/wikibase/entityIdFormatter/DataValueBasedEntityIdHtmlFormatter.js' ),
		CachingEntityIdPlainFormatter = require( '../../view/resources/wikibase/entityIdFormatter/CachingEntityIdPlainFormatter.js' ),
		DataValueBasedEntityIdPlainFormatter = require( '../../view/resources/wikibase/entityIdFormatter/DataValueBasedEntityIdPlainFormatter.js' ),
		ToolbarFactory = require( '../../view/resources/wikibase/view/ToolbarFactory.js' ),
		PropertyDataTypeStore = require( './wikibase.PropertyDataTypeStore.js' ),
		config = require( './config.json' ),
		datamodel = require( 'wikibase.datamodel' ),
		serialization = require( 'wikibase.serialization' );

	/**
	 * @return {boolean}
	 */
	function isEditable() {
		return mw.config.get( 'wbIsEditView' )
			&& mw.config.get( 'wgRelevantPageIsProbablyEditable' );
	}

	/**
	 * @param {wikibase.api.RepoApi} repoApi
	 * @param {string} languageCode The language code of the ui language
	 * @return {CachingEntityStore}
	 */
	function buildEntityStore( repoApi, languageCode ) {
		return new CachingEntityStore(
			new ApiEntityStore(
				repoApi,
				new serialization.EntityDeserializer(),
				[ languageCode ]
			)
		);
	}

	/**
	 * @param {datamodel.Entity} entity
	 * @param {jQuery} $entityview
	 * @return {string} The name of the entity view widget class
	 *
	 * @throws {Error} if no widget to render the entity exists.
	 */
	function createEntityView( entity, $entityview ) {
		var currentRevision, revisionStore, entityChangersFactory,
			viewFactoryArguments, ViewFactoryFactory, viewFactory, entityView,
			repoConfig = mw.config.get( 'wbRepo' ),
			repoApiUrl = repoConfig.url + repoConfig.scriptPath + '/api.php',
			mwApi = wb.api.getLocationAgnosticMwApi( repoApiUrl ),
			repoApi = new wb.api.RepoApi( mwApi, mw.config.get( 'wgUserLanguage' ), config.tags ),
			userLanguages = wb.getUserLanguages(),
			entityStore = buildEntityStore( repoApi, userLanguages[ 0 ] ),
			monolingualTextLanguages = wikibase.WikibaseContentLanguages.getMonolingualTextLanguages(),
			formatterFactory = new ApiValueFormatterFactory(
				new wb.api.FormatValueCaller(
					repoApi,
					dataTypeStore
				),
				userLanguages[ 0 ]
			),
			parserStore = getParserStore( repoApi ),
			htmlDataValueEntityIdFormatter = formatterFactory.getFormatter( null, null, 'text/html' ),
			plaintextDataValueEntityIdFormatter = formatterFactory.getFormatter( null, null, 'text/plain' ),
			entityIdParser = new ( parserStore.getParser( datamodel.EntityId.TYPE ) )( { lang: userLanguages[ 0 ] } ),
			toolbarFactory = new ToolbarFactory(),
			structureEditorFactory = new StructureEditorFactory( toolbarFactory ),
			startEditingCallback = function () {
				return $.Deferred().resolve().promise();
			},
			entityNamespace = entity.getType(),
			wbCurRev = mw.config.get( 'wbCurrentRevision' );

		if ( wbCurRev === null ) {
			currentRevision = mw.config.get( 'wgCurRevisionId' );
		} else {
			currentRevision = wbCurRev;
		}

		revisionStore = new RevisionStore( currentRevision );

		entityChangersFactory = new wb.entityChangers.EntityChangersFactory(
			repoApi,
			revisionStore,
			entity,
			function ( hookName ) {
				var hook = mw.hook( hookName );
				hook.fire.apply( hook, Array.prototype.slice.call( arguments, 1 ) );
			}
		);

		viewFactoryArguments = [
			toolbarFactory,
			entityChangersFactory,
			structureEditorFactory,
			monolingualTextLanguages,
			dataTypeStore,
			new CachingEntityIdHtmlFormatter(
				new DataValueBasedEntityIdHtmlFormatter( entityIdParser, htmlDataValueEntityIdFormatter )
			),
			new CachingEntityIdPlainFormatter(
				new DataValueBasedEntityIdPlainFormatter( entityIdParser, plaintextDataValueEntityIdFormatter )
			),
			new PropertyDataTypeStore( mw.hook( 'wikibase.entityPage.entityLoaded' ), entityStore ),
			getExpertsStore( dataTypeStore ),
			formatterFactory,
			{
				getMessage: function ( key, params ) {
					return mw.msg.apply( mw, [ key ].concat( params || [] ) );
				}
			},
			parserStore,
			userLanguages,
			repoApiUrl,
			config.geoShapeStorageApiEndpoint
		];
		var hookResults = [];
		mw.hook( 'wikibase.entityPage.entityView.viewFactoryFactory.required' ).fire(
			entityNamespace,
			( promise ) => {
				hookResults.push( promise );
			}
		);

		return $.when.apply( $, hookResults ).then( () => {
			ViewFactoryFactory = wb[ entityNamespace ] && wb[ entityNamespace ].view
				&& wb[ entityNamespace ].view.ViewFactoryFactory
				|| DefaultViewFactoryFactory;

			viewFactory = ( new ViewFactoryFactory() ).getViewFactory( isEditable(), viewFactoryArguments );

			entityView = viewFactory.getEntityView( startEditingCallback, entity, $entityview );

			return entityView.widgetName;
		} );

	}

	/**
	 * @param {jQuery.wikibase.entityview} $entityview
	 * @param {string} viewName
	 * @param {string} entityType
	 */
	function attachAnonymousEditWarningTrigger( $entityview, viewName, entityType ) {
		if ( config.tempUserEnabled || !mw.user || !mw.user.isAnon() ) {
			return;
		}

		$entityview.on( viewName + 'afterstartediting', () => {
			if ( !$.find( '.mw-notification-content' ).length
				&& !mw.cookie.get( 'wikibase-no-anonymouseditwarning' )
			) {
				var currentPage = mw.config.get( 'wgPageName' );
				var userLoginUrl = mw.util.getUrl( 'Special:UserLogin', { returnto: currentPage } );
				var createAccountUrl = mw.util.getUrl( 'Special:CreateAccount', { returnto: currentPage } );
				var message = mw.message( 'wikibase-anonymouseditwarning', userLoginUrl, createAccountUrl );
				mw.notify( message, { autoHide: false, type: 'warn', tag: 'wikibase-anonymouseditpopup' } );
			}
		} );
	}

	/**
	 * Update the state of the watch link if the user has watchdefault enabled.
	 */
	function attachWatchLinkUpdater( $entityview, viewName ) {
		var update;

		if ( mw.loader.getState( 'mediawiki.page.watch.ajax' ) !== 'ready' || !mw.user.options.get( 'watchdefault' ) ) {
			return;
		}

		update = require( 'mediawiki.page.watch.ajax' ).updateWatchLink;

		function updateWatchLink() {
			// All four supported skins are using the same ID, the other selectors
			// in mediawiki.page.watch.ajax.js are undocumented and probably legacy stuff
			var $link = $( '#ca-watch' ).find( 'a' );

			// Skip if page is already watched and there is no "watch this page" link
			// Note: The exposed function fails for empty jQuery collections
			if ( !$link.length ) {
				return;
			}

			update( $link, 'watch', 'loading' );

			var api = new mw.Api();

			api.get( {
				formatversion: 2,
				action: 'query',
				prop: 'info',
				inprop: 'watched',
				pageids: mw.config.get( 'wgArticleId' )
			} ).done( ( data ) => {
				var watched = data.query && data.query.pages[ 0 ]
					&& data.query.pages[ 0 ].watched;
				update( $link, watched ? 'unwatch' : 'watch' );
			} ).fail( () => {
				update( $link, 'watch' );
			} );
		}

		$entityview.on( viewName + 'afterstopediting', ( event, dropValue ) => {
			if ( !dropValue ) {
				updateWatchLink();
			}
		} );
	}

	/**
	 * @param {jQuery} $entityview
	 * @param {jQuery} $origin
	 * @param {string} gravity
	 * @param {string} viewName
	 */
	function showCopyrightTooltip( $entityview, $origin, gravity, viewName ) {
		if ( !mw.config.exists( 'wbCopyright' ) ) {
			return;
		}

		gravity = gravity || 'nw';

		var copyRight = mw.config.get( 'wbCopyright' ),
			copyRightVersion = copyRight.version,
			copyRightMessageHtml = copyRight.messageHtml,
			cookieKey = 'wikibase.acknowledgedcopyrightversion',
			optionsKey = 'wb-acknowledgedcopyrightversion';

		if ( mw.cookie.get( cookieKey ) === copyRightVersion
			|| mw.user.options.get( optionsKey ) === copyRightVersion
		) {
			return;
		}

		var $message = $( '<span><p>' + copyRightMessageHtml + '</p></span>' )
				.addClass( 'wikibase-copyrightnotification-container' ),
			$hideMessage = $( '<a>' )
				.text( mw.msg( 'wikibase-copyrighttooltip-acknowledge' ) )
				.appendTo( $message ),
			editableTemplatedWidget = $origin.data( 'EditableTemplatedWidget' );

		// TODO: Use editableTemplatedWidget's notification system for copyright messages on all widgets
		if ( editableTemplatedWidget
			&& !( editableTemplatedWidget instanceof $.wikibase.statementview )
			&& !( editableTemplatedWidget instanceof $.wikibase.entitytermsview )
			&& editableTemplatedWidget.widgetName !== 'lexemeformview'
			&& editableTemplatedWidget.widgetName !== 'senseview'
		) {
			editableTemplatedWidget.notification( $message, 'wb-edit' );

			$hideMessage.on( 'click', ( event ) => {
				event.preventDefault();
				editableTemplatedWidget.notification();
				if ( mw.user.isAnon() ) {
					mw.cookie.set( cookieKey, copyRightVersion, { expires: 3 * 365 * 24 * 60 * 60, path: '/' } );
				} else {
					var api = new mw.Api();
					api.saveOption( optionsKey, copyRightVersion );
				}
			} );
			return;
		}

		// Tooltip gets its own anchor since other toolbar elements might have their own tooltip.
		var $edittoolbarContainer, $tooltipAnchor;
		if ( $origin.data( 'edittoolbar' ) ) {
			$edittoolbarContainer = $origin.data( 'edittoolbar' ).getContainer();
			$tooltipAnchor = $( '<span>' )
				.appendTo( $edittoolbarContainer.children( ':wikibase-toolbar' ) );
		} else if ( $origin.find( '.lemma-widget_controls' ).length ) {
			// HACK: WikibaseLexeme's lemma widget is implemented in Vue, thus doesn't feature a legacy edittoolbar (T343999)
			$edittoolbarContainer = $origin.find( '.lemma-widget_controls' );
			$tooltipAnchor = $( '<span>' )
				.appendTo( $edittoolbarContainer );
		} else {
			return;
		}

		var $messageAnchor = $( '<span>' )
			.appendTo( document.body )
			.toolbaritem()
			.wbtooltip( {
				content: $message,
				permanent: true,
				gravity: gravity,
				$anchor: $tooltipAnchor
			} );

		var eventNamespace = '.wbCopyrightTooltip' + Math.random().toString( 36 ).slice( 2 );

		// Remove the no longer needed tooltip anchor
		$messageAnchor.one( 'wbtooltipafterhide', () => {
			$tooltipAnchor.remove();
		} );

		$hideMessage.on( 'click', ( event ) => {
			event.preventDefault();
			$messageAnchor.data( 'wbtooltip' ).degrade( true );
			$( window ).off( eventNamespace );
			if ( mw.user.isAnon() ) {
				mw.cookie.set( cookieKey, copyRightVersion, { expires: 3 * 365 * 24 * 60 * 60, path: '/' } );
			} else {
				var api = new mw.Api();
				api.saveOption( optionsKey, copyRightVersion );
			}
		} );

		$messageAnchor.data( 'wbtooltip' ).show();

		// Record the initial edit toolbar offset.
		var initialToolbarOffset = $edittoolbarContainer.offset().top;
		// Destroy tooltip after edit mode gets closed again or if the position of the toolbar changed.
		$entityview.on(
			`${ viewName }afterstopediting${ eventNamespace } ${ viewName }afterstartediting${ eventNamespace }`,
			( event, origin ) => {
				var tooltip = $messageAnchor.data( 'wbtooltip' );
				// Don't close this copyright notice if we're still in edit mode (another widget left edit mode)
				// and the position of the toolbar is unchanged (we don't bother re-positioning the tooltip).
				var isInEditMode = event.type === viewName + 'afterstartediting' || ( editableTemplatedWidget && editableTemplatedWidget.isInEditMode() );
				if ( $edittoolbarContainer.offset().top === initialToolbarOffset && isInEditMode ) {
					return;
				}
				if ( tooltip ) {
					tooltip.degrade( true );
				}
				$( window ).off( eventNamespace );
			}
		);

		$( window ).one(
			`scroll${ eventNamespace } touchmove${ eventNamespace } resize${ eventNamespace }`,
			() => {
				var tooltip = $messageAnchor.data( 'wbtooltip' );
				if ( tooltip ) {
					$messageAnchor.data( 'wbtooltip' ).hide();
				}
				$entityview.off( eventNamespace );
			}
		);
	}

	/**
	 * @param {jQuery} $entityview
	 * @param {string} viewName
	 */
	function attachCopyrightTooltip( $entityview, viewName ) {
		var startEditingEvents = [
			'entitytermsviewafterstartediting',
			'sitelinkgroupviewafterstartediting',
			'statementviewafterstartediting'
		];
		if ( viewName === 'lexemeview' ) {
			// WikibaseLexeme specific events. These are handled here, as this legacy code can't be nicely extended/ re-used without a bigger refactoring.
			startEditingEvents.push(
				'senseviewafterstartediting',
				'lexemeformviewafterstartediting',
				'lexemeheaderafterstartediting'
			);
		}
		$entityview.on(
			startEditingEvents.join( ' ' ),
			( event ) => {
				var $target = $( event.target ),
					gravity = 'sw';

				if ( $target.data( 'sitelinkgroupview' ) ) {
					gravity = 'nw';
				} else if ( $target.data( 'entitytermsview' ) ) {
					gravity = 'ne';
				} else if ( $target.find( '.lemma-widget_controls' ).length ) {
					// WikibaseLexeme's lemma widget
					gravity = 'nw';
				}

				// Break out of stack to make sure this runs only after the editing toolbar is fully initialized.
				// This is needed as showCopyrightTooltip manipulates the toolbar's DOM.
				setTimeout( () => {
					showCopyrightTooltip( $entityview, $target, gravity, viewName );
				}, 0 );
			}
		);
	}

	mw.hook( 'wikipage.content' ).add( () => {
		// This is copied from startup.js in MediaWiki core.
		var mwPerformance = window.performance && performance.mark ? performance : {
			mark: function () {}
		};
		mwPerformance.mark( 'wbInitStart' );

		var $entityview = $( '.wikibase-entityview' );
		var canEdit = isEditable();

		wb.EntityInitializer.newFromEntityLoadedHook().getEntity().then( ( entity ) => {
			var viewNamePromise = createEntityView( entity, $entityview.first() );
			return viewNamePromise.then( ( viewName ) => {
				if ( canEdit ) {
					attachAnonymousEditWarningTrigger( $entityview, viewName, entity.getType() );
					attachWatchLinkUpdater( $entityview, viewName );
					attachCopyrightTooltip( $entityview, viewName );
				}

				mw.hook( 'wikibase.entityPage.entityView.rendered' ).fire();

				mwPerformance.mark( 'wbInitEnd' );
			} );
		} ).catch( mw.log.error );

		if ( canEdit ) {
			$entityview
			.on( 'entitytermsviewchange entitytermsviewafterstopediting', ( event, lang ) => {
				var userLanguage = mw.config.get( 'wgUserLanguage' );

				var $entitytermsview = $( event.target ),
					entitytermsview = $entitytermsview.data( 'entitytermsview' ),
					fingerprint = entitytermsview.value(),
					label = wb.view.termFallbackResolver.getTerm( fingerprint.getLabels(), userLanguage ),
					isEmpty = !label || label.getText() === '';

				if ( isEmpty ) {
					$( 'h1' ).find( '.wikibase-title' )
						.toggleClass( 'wb-empty', true )
						.find( '.wikibase-title-label' )
						.text( mw.msg( 'wikibase-label-empty' ) );
				} else {
					var indicator = wb.view.languageFallbackIndicator.getHtml( label, userLanguage );
					$( 'h1' ).find( '.wikibase-title' )
						.toggleClass( 'wb-empty', false )
						.find( '.wikibase-title-label' )
						.html( mw.html.escape( label.getText() ) + indicator );
				}

				if ( event.type === 'entitytermsviewafterstopediting' ) {
					$( 'title' ).text( mw.msg( 'pagetitle', isEmpty ? mw.config.get( 'wgTitle' ) : label.getText() ) );
				}
			} );
		}
	} );

}(
	wikibase,
	window.performance
) );
