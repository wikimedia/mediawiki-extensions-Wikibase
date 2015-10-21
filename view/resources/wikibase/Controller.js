( function( mw ) {
'use strict';

wikibase.Controller = function( options ) {
	this._model = options.model;
	this._toolbar = options.toolbar;
	this._view = options.view;
};

wikibase.Controller.prototype.startEditing = function() {
	this._view.startEditing();
	this._toolbar.toEditMode();

	this._updateSaveButtonState();
	this._view.element.on( this._view.widgetEventPrefix + 'change', jQuery.proxy( this._updateSaveButtonState, this ) );
};

wikibase.Controller.prototype._updateSaveButtonState = function() {
	var btnSave = this._toolbar.getButton( 'save' ),
		enableSave = this._view.isValid() && !this._view.isInitialValue();

	btnSave[enableSave ? 'enable' : 'disable']();
};

wikibase.Controller.prototype.stopEditing = function( dropValue ) {
	var view = this._view,
		self = this;

	if ( ( !view.isValid() || view.isInitialValue() ) && !dropValue ) {
		return;
	}

	this._toolbar.disable();

	this.setError();
	view.disable();

	if ( dropValue ) {
		// FIXME: Shouldn't we re-set the value here?
		this._leaveEditMode();
		return;
	}

	this._toolbar.toggleActionMessage( mw.msg( 'wikibase-save-inprogress' ) );
	this.saveValue( view.value() ).done( function( savedValue ) {
		view.value( savedValue );
		self._toolbar.toggleActionMessage();
		self._leaveEditMode();
	} ).fail( function( error ) {
		view.enable();
		this.setError( error );
	} );
};

wikibase.Controller.prototype._leaveEditMode = function() {
	this._view.enable();
	this._view.stopEditing();

	this._toolbar.enable();
	this._toolbar.toNonEditMode();
};

wikibase.Controller.prototype.cancelEditing = function() {
	return this.stopEditing( true );
};

wikibase.Controller.prototype.saveValue = function( value ) {
	return this._model.save( value );
};

wikibase.Controller.prototype.setError = function( error ) {
	this._view.setError( Boolean( error ) );
	// FIXME: trigger toggleerror
};

} )( mediaWiki );
