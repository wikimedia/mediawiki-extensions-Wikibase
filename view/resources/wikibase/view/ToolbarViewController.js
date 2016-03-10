wikibase.view.ToolbarViewController = ( function( wb, mw ) {
'use strict';

/**
 * A view controller implementation for editing wikibase datamodel values
 * through wikibase views using toolbars
 *
 * @class wikibase.view.ToolbarViewController
 * @license GPL-2.0+
 * @since 0.5
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 * @extends wikibase.view.ViewController
 * @constructor
 *
 * @param {Object} model A model-controller interaction object, consisting of a set of functions.
 * @param {Function} model.save A function taking a specific type of wikibase
 * datamodel objects and returning a Promise.
 * @param {Function} model.remove A function taking a specific wikibase datamodel object and
 * returning a Promise.
 * @param {jQuery.wikibase.edittoolbar} toolbar
 * @param {jQuery.ui.EditableTemplatedWidget} view
 */
var SELF = util.inherit(
	wb.view.ViewController,
	function( model, toolbar, view ) {
		this._model = model;
		this._toolbar = toolbar;
		this._view = view;
	}
);

/**
 * @property {Object}
 * @private
 */
SELF.prototype._model = null;

/**
 * @property {jQuery.wikibase.edittoolbar}
 * @private
 */
SELF.prototype._toolbar = null;

/**
 * @property {jQuery.ui.EditableTemplatedWidget}
 * @private
 */
SELF.prototype._view = null;

/**
 * Start editing
 */
SELF.prototype.startEditing = function() {
	this._view.startEditing();
	this._toolbar.toEditMode();

	this._updateSaveButtonState();
	this._view.element.on(
		this._view.widgetEventPrefix + 'change',
		jQuery.proxy( this._updateSaveButtonState, this )
	);
	this._view.element.on(
		this._view.widgetEventPrefix + 'disable',
		jQuery.proxy( this._updateToolbarState, this )
	);
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

/**
 * Stop editing
 *
 * @param {boolean} [dropValue=false] Whether the current value should be kept and
 * persisted or dropped
 */
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

/**
 * Remove the value currently represented in the view
 */
SELF.prototype.remove = function() {
	var self = this;

	// FIXME: Currently done by the edittoolbar itself
	// this._toolbar.disable();

	this.setError();
	this._view.disable();

	// FIXME: Currently done by the edittoolbar itself
	// this._toolbar.toggleActionMessage( mw.msg( 'wikibase-remove-inprogress' ) );
	return this._model.remove( this._view.value() ).done( function() {
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

/**
 * Cancel editing and drop value
 */
SELF.prototype.cancelEditing = function() {
	return this.stopEditing( true );
};

/**
 * Set or clear error
 *
 * @param {mixed} [error] The error or undefined, if error should be
 * cleared
 */
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
