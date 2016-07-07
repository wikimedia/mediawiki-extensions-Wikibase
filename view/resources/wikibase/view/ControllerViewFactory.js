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
