wikibase.view.ControllerViewFactory = ( function ( wb ) {
	'use strict';

	var ViewFactory = require( './ViewFactory.js' ),
		ToolbarViewController = require( './ToolbarViewController.js' );

	var SELF = util.inherit(
		ViewFactory,
		function (
			toolbarFactory,
			entityChangersFactory,
			structureEditorFactory,
			contentLanguages,
			dataTypeStore,
			entityIdHtmlFormatter,
			entityIdPlainFormatter,
			propertyDataTypeStore,
			expertStore,
			formatterFactory,
			messageProvider,
			parserStore,
			userLanguages,
			vocabularyLookupApiUrl,
			commonsApiUrl
		) {
			this._toolbarFactory = toolbarFactory;
			this._entityChangersFactory = entityChangersFactory;
			ViewFactory.apply(
				this,
				[
					structureEditorFactory,
					contentLanguages,
					dataTypeStore,
					entityIdHtmlFormatter,
					entityIdPlainFormatter,
					propertyDataTypeStore,
					expertStore,
					formatterFactory,
					messageProvider,
					parserStore,
					userLanguages,
					vocabularyLookupApiUrl,
					commonsApiUrl
				]
			);
		}
	);

	SELF.prototype.getEntityTermsView = function ( startEditingCallback, value, $entitytermsview ) {
		var controller;
		var startEditingController = function () {
			return controller.startEditing();
		};
		var view = ViewFactory.prototype.getEntityTermsView.call( this, startEditingController, value, $entitytermsview );
		var $container = this._toolbarFactory.getToolbarContainer( view.element );

		view.element.on( 'entitytermsviewafterstartediting', function () {
			// eslint-disable-next-line no-jquery/no-sizzle
			if ( !view.$entitytermsforlanguagelistviewContainer.is( ':visible' ) ) {
				// TODO: Use CSS animation
				// eslint-disable-next-line no-jquery/no-slide
				view.$entitytermsforlanguagelistviewContainer.slideDown( {
					complete: function () {
						view.$entitytermsforlanguagelistview
							.data( 'entitytermsforlanguagelistview' ).updateInputSize();
						view.$entitytermsforlanguagelistviewToggler.data( 'toggler' )
							.refresh();
					},
					duration: 'fast'
				} );
			}

			view.focus();
		} );

		view.element.on( 'entitytermsviewafterstopediting', function () {
			var showEntitytermslistviewValue = mw.user.isAnon()
				? mw.cookie.get( 'wikibase-entitytermsview-showEntitytermslistview' )
				: mw.user.options.get( 'wikibase-entitytermsview-showEntitytermslistview' );
			var showEntitytermslistview = ( showEntitytermslistviewValue === 'true'
				|| showEntitytermslistviewValue === '1'
				|| showEntitytermslistviewValue === null );

			// eslint-disable-next-line no-jquery/no-sizzle
			if ( view.$entitytermsforlanguagelistviewContainer.is( ':visible' ) && !showEntitytermslistview ) {
				// TODO: Use CSS animation
				// eslint-disable-next-line no-jquery/no-slide
				view.$entitytermsforlanguagelistviewContainer.slideUp( {
					complete: function () {
						view.$entitytermsforlanguagelistviewToggler.data( 'toggler' ).refresh();
					},
					duration: 'fast'
				} );
			}
		} );

		var entityTermsChanger = this._entityChangersFactory.getEntityTermsChanger();
		controller = this._getController( $container, view, entityTermsChanger, null, value, startEditingCallback );
		return view;
	};

	SELF.prototype.getStatementView = function ( startEditingCallback, entityId, propertyId, removeCallback, value, $dom ) {
		var controller;
		var startEditingController = function () {
			return controller.startEditing();
		};
		var statementview = ViewFactory.prototype.getStatementView.call(
			this,
			startEditingController,
			entityId,
			propertyId,
			removeCallback,
			value,
			$dom
		);

		var statementsChanger = this._entityChangersFactory.getStatementsChanger();
		controller = this._getController(
			this._toolbarFactory.getToolbarContainer( statementview.element ),
			statementview,
			statementsChanger,
			removeCallback.bind( null, statementview ),
			value,
			startEditingCallback
		);

		// Empty statementviews (added with the "add" button) should start in edit mode
		if ( !value ) {
			controller.startEditing()
				.done( statementview.focus.bind( statementview ) );
		}

		// Always focus the statementview that switched to edit mode last
		statementview.element.on( 'statementviewafterstartediting', function () {
			statementview.focus();
		} );

		return statementview;
	};

	SELF.prototype.getSitelinkGroupView = function ( startEditingCallback, groupName, value, $sitelinkgroupview ) {
		var controller;
		var startEditingController = function () {
			return controller.startEditing();
		};
		var view = ViewFactory.prototype.getSitelinkGroupView.call( this, startEditingController, groupName, value, $sitelinkgroupview );
		var siteLinkSetsChanger = this._entityChangersFactory.getSiteLinkSetsChanger();
		controller = this._getController(
			this._toolbarFactory.getToolbarContainer( view.element.find( '.wikibase-sitelinkgroupview-heading-container' ) ),
			view,
			siteLinkSetsChanger,
			null,
			value,
			startEditingCallback
		);
		return view;
	};

	SELF.prototype._getController = function ( $container, view, model, onRemove, value, startEditingCallback ) {
		var edittoolbar = this._toolbarFactory.getEditToolbar(
			{
				$container: $container,
				getHelpMessage: view.getHelpMessage.bind( view )
			},
			view.element
		);

		var controller = new ToolbarViewController( model, edittoolbar, view, onRemove, startEditingCallback );
		edittoolbar.setController( controller );
		controller.setValue( value );

		view.element.on( 'keydown.edittoolbar', function ( event ) {
			if ( view.option( 'disabled' ) ) {
				return;
			}
			if ( SELF._isCancelEvent( event ) ) {
				controller.stopEditing( true );
			} else if ( SELF._isSaveEvent( event ) ) {
				controller.stopEditing( false );
			}
		} );

		return controller;
	};

	SELF._isCancelEvent = function ( event ) {
		return event.keyCode === $.ui.keyCode.ESCAPE;
	};

	SELF._isSaveEvent = function ( event ) {
		// do not save the whole statement if Enter is pressed on e.g. an "add qualifier" link;
		// only save if Enter is pressed on elements of the following types:
		var savingNodeNames = [
			'input',
			'textarea'
		];
		var eventNodeName = event.target.nodeName.toLowerCase();

		return event.keyCode === $.ui.keyCode.ENTER &&
			savingNodeNames.indexOf( eventNodeName ) !== -1;
	};

	// dynamically load wikibase.tainted-ref, originally added as dependency of this module in change I9a244a36fe (commit 5fed25ce15);
	// it’s not directly related to ControllerViewFactory, but we want to load it at the same time,
	// and the dependency can’t be in resources.php because then TR’s es6-ness “infects” regular Wikibase (T298001);
	// however, don’t load TR during QUnit tests, we don’t need it there and it causes problems (T330293)
	if ( !window.QUnit ) {
		mw.loader.using( 'wikibase.tainted-ref' ).catch( function () {
			// ignore errors (but avoid unhandled promise rejection)
		} );
	}

	return SELF;

}( wikibase ) );
