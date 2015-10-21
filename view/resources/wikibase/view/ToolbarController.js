wkibase.view.ToolbarController = ( function( wb, mw ) {
'use strict';

var SELF = function( options ) {
	this._model = options.model;
	this._toolbar = options.toolbar;
	this._view = options.view;
};

SELF.prototype.startEditing = function() {
	this._view.startEditing();
	this._toolbar.toEditMode();

	this._updateSaveButtonState();
	this._view.element.on( this._view.widgetEventPrefix + 'change', jQuery.proxy( this._updateSaveButtonState, this ) );
	this._view.element.on( this._view.widgetEventPrefix + 'disable', jQuery.proxy( this._updateToolbarState, this ) );
};

SELF.prototype._updateToolbarState = function() {
	var disable = this._view.option( 'disabled' );

	this._toolbar.option( 'disabled', disable );
	if ( !disable ) {
		this._updateSaveButtonState();
	}
};

SELF.prototype._updateSaveButtonState = function() {
	var btnSave = this._toolbar.getButton( 'save' ),
		enableSave = this._view.isValid() && !this._view.isInitialValue();

	btnSave[enableSave ? 'enable' : 'disable']();
};

SELF.prototype.stopEditing = function( dropValue ) {
	var self = this;

	if ( ( !this._view.isValid() || this._view.isInitialValue() ) && !dropValue ) {
		return;
	}

	this._toolbar.disable();

	this.setError();
	this._view.disable();

	if ( dropValue ) {
		// FIXME: Shouldn't we re-set the value here?
		this._leaveEditMode();
		return;
	}

	this._toolbar.toggleActionMessage( mw.msg( 'wikibase-save-inprogress' ) );
	this._model.save( this._view.value() ).done( function( savedValue ) {
		self._view.value( savedValue );
		self._toolbar.toggleActionMessage();
		self._leaveEditMode();
	} ).fail( function( error ) {
		self._view.enable();
		self.setError( error );
	} );
};

SELF.prototype._leaveEditMode = function() {
	this._view.enable();
	this._view.stopEditing();

	this._toolbar.enable();
	this._toolbar.toNonEditMode();
};

SELF.prototype.cancelEditing = function() {
	return this.stopEditing( true );
};

SELF.prototype.setError = function( error ) {
	this._view.setError( Boolean( error ) );
	if ( error instanceof wb.api.RepoApiError ) {
		var toolbar = this._toolbar,
			$anchor;

		if ( error.action === 'save' ) {
			$anchor = toolbar.getButton( 'save' ).element;
		} else if ( error.action === 'remove' ) {
			$anchor = toolbar.getButton( 'remove' ).element;
		}

		toolbar.enable();
		toolbar.toggleActionMessage( function() {
			toolbar.displayError( error, $anchor );
		} );
	}
};

return SELF;

} )( wikibase, mediaWiki );
