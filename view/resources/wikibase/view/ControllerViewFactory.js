wikibase.view.ControllerViewFactory = ( function( mw, wb, $ ) {
'use strict';

var PARENT = wikibase.view.ViewFactory;

var SELF = util.inherit(
	PARENT,
	function( toolbarFactory, entityChangersFactory ) {
		this._toolbarFactory = toolbarFactory;
		this._entityChangersFactory = entityChangersFactory;
		PARENT.apply( this, Array.prototype.slice.call( arguments, 2 ) );
	}
);

SELF.prototype.getEntityTermsView = function( startEditingCallback, value, $entitytermsview ) {
	var controller;
	arguments[0] = function() {
		return startEditingCallback().then( function() {
			return controller.startEditing();
		} );
	};
	var view = PARENT.prototype.getEntityTermsView.apply( this, arguments );
	var $container = this._toolbarFactory.getToolbarContainer( view.element );
	$container.sticknode( {
		$container: view.$entitytermsforlanguagelistview,
		autoWidth: true,
		zIndex: 2
	} )
	.on( 'sticknodeupdate', function( event ) {
		if ( !$( event.target ).data( 'sticknode' ).isFixed() ) {
			$container.css( 'width', 'auto' );
		}
	} );

	view.element.on( 'entitytermsviewchange', function() {
		$container.data( 'sticknode' ).refresh();
	} );

	view.element.on( 'entitytermsviewafterstartediting', function() {
		if ( !view.$entitytermsforlanguagelistviewContainer.is( ':visible' ) ) {
			view.$entitytermsforlanguagelistviewContainer.slideDown( {
				complete: function() {
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

	view.element.on( 'entitytermsviewafterstopediting', function() {
		var showEntitytermslistviewValue = mw.user.isAnon()
			? $.cookie( 'wikibase-entitytermsview-showEntitytermslistview' )
			: mw.user.options.get( 'wikibase-entitytermsview-showEntitytermslistview' );
		var showEntitytermslistview = ( showEntitytermslistviewValue === 'true'
			|| showEntitytermslistviewValue === '1'
			|| showEntitytermslistviewValue === null );

		if ( view.$entitytermsforlanguagelistviewContainer.is( ':visible' ) && !showEntitytermslistview ) {
			view.$entitytermsforlanguagelistviewContainer.slideUp( {
				complete: function() {
					view.$entitytermsforlanguagelistviewToggler.data( 'toggler' ).refresh();
				},
				duration: 'fast'
			} );
		}

		$container.data( 'sticknode' ).refresh();
	} );

	var entityTermsChanger = this._entityChangersFactory.getEntityTermsChanger();
	controller = this._getController( $container, view, entityTermsChanger, null, value );
	return view;
};

SELF.prototype.getStatementView = function( startEditingCallback, entityId, propertyId, removeCallback, value, $dom ) {
	var controller;
	arguments[0] = function() {
		return startEditingCallback().then( function() {
			return controller.startEditing();
		} );
	};
	var statementview = PARENT.prototype.getStatementView.apply( this, arguments );
	var statementsChanger = this._entityChangersFactory.getStatementsChanger();
	controller = this._getController(
		this._toolbarFactory.getToolbarContainer( statementview.element ),
		statementview,
		statementsChanger,
		removeCallback.bind( null, statementview ),
		value
	);

	if ( !value ) {
		controller.startEditing().done( $.proxy( statementview, 'focus' ) );
	}
	return statementview;
};

SELF.prototype.getSitelinkGroupView = function( startEditingCallback, groupName, value, $sitelinkgroupview ) {
	var controller;
	arguments[0] = function() {
		return startEditingCallback().then( function() {
			return controller.startEditing();
		} );
	};
	var view = PARENT.prototype.getSitelinkGroupView.apply( this, arguments );
	var siteLinkSetsChanger = this._entityChangersFactory.getSiteLinkSetsChanger();
	controller = this._getController(
		this._toolbarFactory.getToolbarContainer( view.element.find( '.wikibase-sitelinkgroupview-heading-container' ) ),
		view,
		siteLinkSetsChanger,
		null,
		value
	);
	return view;
};

SELF.prototype._getController = function( $container, view, model, onRemove, value ) {
	var edittoolbar = this._toolbarFactory.getEditToolbar(
		{
			$container: $container,
			getHelpMessage: view.getHelpMessage.bind( view )
		},
		view.element
	);

	var controller = new wb.view.ToolbarViewController( model, edittoolbar, view, onRemove );
	edittoolbar.setController( controller );
	controller.setValue( value );

	view.element.on( 'keydown.edittoolbar', function( event ) {
		if ( view.option( 'disabled' ) ) {
			return;
		}
		if ( event.keyCode === $.ui.keyCode.ESCAPE ) {
			controller.stopEditing( true );
		} else if ( event.keyCode === $.ui.keyCode.ENTER ) {
			controller.stopEditing( false );
		}
	} );

	return controller;
};

return SELF;

}( mediaWiki, wikibase, jQuery ) );
