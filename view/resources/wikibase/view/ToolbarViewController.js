wikibase.view.ToolbarViewController = ( function( $, wb, mw ) {
'use strict';

/**
 * A view controller implementation for editing wikibase datamodel values
 * through wikibase views using toolbars
 *
 * @class wikibase.view.ToolbarViewController
 * @license GPL-2.0+
 * @since 0.5
 * @author Adrian Heine <adrian.heine@wikimedia.de>
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
	function( model, toolbar, view, removeView ) {
		this._model = model;
		this._toolbar = toolbar;
		this._view = view;
		this._removeView = removeView;
	}
);

/**
 * @property {Object|null}
 * @private
 */
SELF.prototype._value = null;

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

SELF.prototype.setValue = function( value ) {
	this._value = value;
	this._toolbar.option(
		'onRemove',
		( value && this._model.remove ) ? $.proxy( this, 'remove' ) : null
	);
};

/**
 * Start editing
 */
SELF.prototype.startEditing = function() {
	var result = this._view.startEditing();
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
	return result;
};

SELF.prototype._updateToolbarState = function() {
	var disable = this._view.option( 'disabled' );

	this._toolbar.option( 'disabled', disable );
	if ( !disable ) {
		this._updateSaveButtonState();
	}
};

SELF.prototype._viewHasNewGoodValue = function() {
	var viewValue = this._view.value();
	return viewValue !== null && ( this._value === null || !this._value.equals( viewValue ) );
};

SELF.prototype._updateSaveButtonState = function() {
	var btnSave = this._toolbar.getButton( 'save' ),
		enableSave = this._viewHasNewGoodValue();

	btnSave[enableSave ? 'enable' : 'disable']();
};

/**
 * Stop editing
 *
 * @param {boolean} [dropValue=false] Whether the current value should be kept and
 * persisted or dropped
 */
SELF.prototype.stopEditing = function( dropValue ) {
	if ( !dropValue && !this._viewHasNewGoodValue() ) {
		return;
	}

	this._toolbar.disable();

	this.setError();
	this._view.disable();

	if ( dropValue ) {
		this._view.value( this._value );
		this._leaveEditMode( dropValue );
		return;
	}

	var self = this;
	this._toolbar.toggleActionMessage( mw.msg( 'wikibase-save-inprogress' ) );
	this._model.save( this._view.value(), this._value ).done( function( savedValue ) {
		self.setValue( savedValue );
		self._view.value( savedValue );
		self._toolbar.toggleActionMessage();
		self._leaveEditMode( dropValue );
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
	var promise;
	if ( this._value ) {
		promise = this._model.remove( this._value );
	} else {
		promise = $.Deferred().resolve().promise();
	}
	return promise.done( function() {
		self._value = null;
		self._toolbar.toggleActionMessage();
		self._leaveEditMode( true );
	} ).fail( function( error ) {
		self._view.enable();
		self.setError( error );
	} );
};

SELF.prototype._leaveEditMode = function( dropValue ) {
	if ( dropValue && !this._value ) {
		this._removeView();
	} else {
		this._view.enable();
		this._view.stopEditing( dropValue );

		this._toolbar.enable();
		var self = this;
		// FIXME: The toolbar has a race condition
		window.setTimeout( function() {
			self._toolbar.toNonEditMode();
		} );
	}
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
	var viewParam = error ? ( error.context ? { context: error.context } : true ) : false;

	this._view.setError( viewParam );

	if ( !viewParam ) {
		return;
	}

	if ( !( error instanceof wb.api.RepoApiError ) ) {
		error = {
			code: true, // Used by wbtooltip to detect errors
			message: 'Unknown error' // FIXME: translate?
		};
	}

	if ( this._view.doErrorNotification ) {
		this._view.doErrorNotification( error );
		this._toolbar.enable();
		this._toolbar.toggleActionMessage();
	} else {
		// Use the toolbar for notification
		var $anchor;

		if ( error.action === 'save' ) {
			$anchor = this._toolbar.getButton( 'save' ).element;
		} else if ( error.action === 'remove' ) {
			$anchor = this._toolbar.getButton( 'remove' ).element;
		}

		this._toolbar.enable();
		var self = this;
		this._toolbar.toggleActionMessage( function() {
			self._toolbar.displayError( error, $anchor );
		} );
	}
};

return SELF;

} )( jQuery, wikibase, mediaWiki );
