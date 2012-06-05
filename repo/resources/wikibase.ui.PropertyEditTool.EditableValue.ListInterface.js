/**
 * JavasSript for an list interface for EditableValue
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.EditableValue.ListInterface.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
'use strict';

/**
 * Serves the input interface for a list of strings and handles the conversion between the pure html representation
 * and the interface itself in both directions. All values of the list belong together and must be edited at the same
 * time.
 *
 * @param jQuery subject
 */
window.wikibase.ui.PropertyEditTool.EditableValue.ListInterface = function( subject ) {
	window.wikibase.ui.PropertyEditTool.EditableValue.Interface.apply( this, arguments );
};
window.wikibase.ui.PropertyEditTool.EditableValue.ListInterface.prototype
	= Object.create( window.wikibase.ui.PropertyEditTool.EditableValue.Interface.prototype );
$.extend( window.wikibase.ui.PropertyEditTool.EditableValue.ListInterface.prototype, {
	/**
	 * Css class which will be attached to all pieces of a value set with this interface.
	 * @const
	 */
	UI_VALUE_PIECE_CLASS: 'wb-ui-propertyedittool-editablevaluelistinterface-piece',

	/**
	 * create input element and initialize autocomplete
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface.prototype._buildInputElement
	 */
	_buildInputElement: function() {
		var inputElement = this._subject.children( 'ul:first' ).clone()
			.addClass( this.UI_CLASS )
			.addClass( 'wb-ui-propertyedittool-editablevaluelistinterface' ); // additional UI class

		return inputElement.tagit( {
			animate: false, // FIXME: when animated set to true, something won't work in there, fails silently then
			allowSpaces: true,
			removeConfirmation: true, // only two times backspace will remove tag
			placeholderText: this.inputPlaceholder,
			onTagAdded: $.proxy( function( e, tag ) {
				tag.addClass( this.UI_VALUE_PIECE_CLASS );
				this._onInputRegistered();
			}, this ),
			onTagRemoved: $.proxy( this._onInputRegistered, this )
		} );
	},

	/**
	 * $see wikibase.ui.PropertyEditTool.EditableValue.Interface.prototype._destroyInputElement
	 */
	_destroyInputElement: function() {
		this._getTagit().destroy();
		this._inputElem = null;
	},

	/**
	 * Convenience function for getting the 'tagit' jQuery plugin data related to the _inputElem
	 */
	_getTagit: function() {
		if( ! this._inputElem ) {
			return null;
		}
		return this._inputElem.data( 'tagit' );
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface.prototype._getValue_inEditMode
	 *
	 * @param string[]
	 */
	_getValue_inEditMode: function() {
		return this._getTagit().assignedTags();
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface.prototype._getValue_inNonEditMode
	 *
	 * @param string[]
	 */
	_getValue_inNonEditMode: function() {
		var values = new Array();
		var valList = this._subject.children( 'ul:first' );

		valList.children( 'li' ).each( function() {
			values.push( $( this ).text() );
		} );

		return values;
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface.prototype.setValue
	 *
	 * @param string[] value
	 * @return string[]|null same as value but normalized, null in case the value was invalid
	 */
	setValue: function( value ) {
		value.sort(); // sort values. NOTE: could be made configurable!
		window.wikibase.ui.PropertyEditTool.EditableValue.Interface.prototype.setValue.call( this, value );
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface.prototype._setValue_inEditMode
	 *
	 * @param string[] value
	 */
	_setValue_inEditMode: function( value ) {
		var self = this;
		$.each( value, function( i, val ) {
			self._getTagit().createTag( val, self.UI_VALUE_PIECE_CLASS );
		} );
		return false; // onInputRegistered event will be thrown by tagit.onTagAdded
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface.prototype._setValue_inNonEditMode
	 *
	 * @param string[] value
	 */
	_setValue_inNonEditMode: function( value ) {
		var valList = this._subject.children( 'ul:first' );
		valList.empty();

		var self = this;
		$.each( value, function( i, val ) {
			valList.append( $( '<li>', {
				'class': self.UI_VALUE_PIECE_CLASS,
				'text': val
			} ) );
		} );

		return true; // trigger onInputRegistered event
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface.prototype._disableInputElement
	 */
	_disableInputElement: function() {
		this._getTagit().disable();
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface.prototype._enableInputelement
	 */
	_enableInputelement: function() {
		this._getTagit().enable();
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface.prototype.isEmpty
	 */
	isEmpty: function() {
		return this.getValue().length == 0;
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface.prototype._enableInputelement
	 */
	validate: function( value ) {
		var normalized = this.normalize( value );
		return normalized.length > 0;
	},

	/**
	 * Normalizes a set of values. If any of the values pieces is invalid, the piece will be removed.
	 * If in the end no piece is left because all pieces were invalid, an empty array will be returned.
	 *
	 * @param String[] value
	 * @return String[] all parts of the value which are valid
	 */
	normalize: function( value ) {
		var validValue = new Array();
		var self = this;
		$.each( value, function( i, val ) {
			val = self.normalizePiece( val );
			if( val !== null ) {
				// add valid values to result
				validValue.push( val );
			}
		} );
		validValue.sort() // TODO: make this configurable or move to somewhere else perhaps
		return validValue;
	},

	/**
	 * Validates a piece of a value.
	 *
	 * @param String value
	 * @return Bool
	 */
	validatePiece: function( value ) {
		var normalized = this.normalizePiece( value );
		return  normalized !== null;
	},

	/**
	 * Normalizes a string so it is sufficient for setting it as value for this interface.
	 * This will be done automatically when using setValue().
	 * In case the given value is invalid, null will be returned.
	 *
	 * @param String value
	 * @return String|null
	 */
	normalizePiece: function( value ) {
		var normalized = window.wikibase.ui.PropertyEditTool.EditableValue.Interface.prototype.normalize.call( this, value );
		if( normalized === '' ) {
			return null;
		}
		return normalized;
	}

} );

