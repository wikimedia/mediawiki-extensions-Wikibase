/**
 * JavaScript for an list interface for EditableValue
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( mw, wb, $, undefined ) {
'use strict';
/* jshint camelcase:false */

var PARENT = wb.ui.PropertyEditTool.EditableValue.Interface;

/**
 * Serves the input interface for a list of strings and handles the conversion between the pure html representation
 * and the interface itself in both directions. All values of the list belong together and must be edited at the same
 * time.
 * @constructor
 * @see wb.ui.PropertyEditTool.EditableValue.Interface
 * @since 0.1
 */
wb.ui.PropertyEditTool.EditableValue.ListInterface = wb.utilities.inherit( PARENT, {
	/**
	 * Css class which will be attached to all pieces of a value set with this interface.
	 * @const
	 */
	UI_VALUE_PIECE_CLASS: 'wb-ui-propertyedittool-editablevaluelistinterface-piece',

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface._initInputElement
	 */
	_initInputElement: function() {
		PARENT.prototype._initInputElement.call( this );
		/*
		applying auto-expansion mechanism has to be done after tagadata's tagList has been placed within
		the DOM since no css rules of the specified css classes are applied by then - respectively jQuery's
		.css() function would return unexpected results
		*/
		var self = this;
		if ( $.fn.inputAutoExpand ) {
			var expansionOptions = {
				expandOnResize: false,
				comfortZone: 16, // width of .ui-icon
				maxWidth: function() {
					// TODO/FIXME: figure out why this requires at least -17, can't be because of padding + border
					// which is only 6 for both sides
					return self._inputElem.width() - 20;
				}
				/*
				// TODO/FIXME: both solutions are not perfect, when tag larger than available space either the
				// input will be auto-resized and not show the whole text or we still show the whole tag but it
				// will break the site layout. A solution would be replacing input with textarea.
				maxWidth: function() {
					var tagList = self._getTagadata().tagList;
					var origCssDisplay = tagList.css( 'display' );
					tagList.css( 'display', 'block' );
					var width = tagList.width();
					tagList.css( 'display', origCssDisplay );
					return width;
				}
				 */
			};

			// calculate size for all input elements initially:
			this._getTagadata().tagList.children( 'li' ).find( 'input' ).inputAutoExpand( expansionOptions );

			// also make sure that new helper tags will calculate size correctly:
			this._inputElem.on( 'tagadatahelpertagadded', function( e, tag ) {
				$( tag ).find( 'input' ).inputAutoExpand( expansionOptions );
			} );
		}
	},

	/**
	 * create input element and initialize autocomplete
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface._buildInputElement
	 *
	 * @return jQuery
	 */
	_buildInputElement: function() {
		// nodes will be removed and replaced with genereated input interface, so we clone them for initialization:
		var inputElement = this._subject.children( 'ul:first' ).clone(),
			self = this;

		// additional UI class
		this._subject.addClass( 'wb-ui-propertyedittool-editablevaluelistinterface' );

		// Get events from all input elements of tagadata and register them here.
		// NOTE: not yet all events registered, register on demand.
		// ToDo: this is not nice, we should use use proper event-delegation instead
		inputElement
		.on( 'tagadatataginserted', function( e, tag ) {
			$( tag ).find( 'input' )
			.on( 'keypress',function( event ) {
				self._onKeyPressed( event );
			} )
			.on( 'keyup', function( event ) {
				self._onKeyUp( event );
			} );
		} );

		inputElement.tagadata( {
			animate: false, // FIXME: when animated set to true, something won't work in there, fails silently then
			placeholderText: this.getOption( 'inputPlaceholder' ),
			tagRemoved: $.proxy( this._onInputRegistered, this )
		} )
		// register event after initial tags were added on tag-a-data initialization!
		.on( 'tagadatatagadded tagadatatagchanged', $.proxy( function( e, tag ) {
			this._onInputRegistered();
		}, this ) );

		return inputElement;
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface._destroyInputElement
	 */
	_destroyInputElement: function() {
		this._getTagadata().destroy();
		this._subject.children( 'li' ).removeClass( this.UI_VALUE_PIECE_CLASS + '-new' );
		this._inputElem = null;
	},

	/**
	 * Convenience function for getting the 'tagadata' jQuery plugin data related to the _inputElem
	 *
	 * @return wikibase.utilities.jQuery.ui.tagadata|null
	 */
	_getTagadata: function() {
		if( ! this._inputElem ) {
			return null;
		}
		return this._inputElem.data( 'tagadata' );
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface._getValue_inEditMode
	 *
	 * @param string[]
	 */
	_getValue_inEditMode: function() {
		var tagadata = this._getTagadata(),
			labels = [];

		if ( tagadata !== undefined ) {
			var values = tagadata.getTags();
			for( var i in values ) {
				labels.push( tagadata.getTagLabel( values[i] ) );
			}
		}
		return labels;
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface._getValue_inNonEditMode
	 *
	 * @param string[]
	 */
	_getValue_inNonEditMode: function() {
		var values = [],
			valList = this._subject.children( 'ul:first' );

		valList.children( 'li' ).each( function() {
			values.push( $( this ).text() );
		} );

		return values;
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface._setValue_inEditMode
	 *
	 * @param string[] value
	 * @return bool
	 */
	_setValue_inEditMode: function( value ) {
		this._getTagadata().removeAll();
		var self = this;
		$.each( value, function( i, val ) {
			self._getTagadata().createTag( val, self.UI_VALUE_PIECE_CLASS );
		} );
		return false; // onInputRegistered event will be thrown by tagadata.onTagAdded
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface._setValue_inNonEditMode
	 *
	 * @param string[] value
	 * @return bool
	 */
	_setValue_inNonEditMode: function( value ) {
		var valList = this._subject.children( 'ul:first' ),
			self = this;

		valList.empty();

		$.each( value, function( i, val ) {
			valList.append( $( '<li>', {
				'class': self.UI_VALUE_PIECE_CLASS,
				'text': val
			} ) );
		} );

		return true; // trigger onInputRegistered event
	},

	/**
	 * Returns all items added to the list since edit mode has been entered.
	 * If not in edit mode, this will simply return an empty array.
	 *
	 * @return Array
	 */
	getNewItems: function() {
		return $( this.getValue() ) // current items...
			.not( this.getInitialValue() ) // ...without initial items
			.toArray();
	},

	/**
	 * Returns all items removed from the list since edit mode has been entered.
	 * If not in edit mode, this will simply return an empty array.
	 *
	 * @return Array
	 */
	getRemovedItems: function() {
		return $( this.getInitialValue() ) // initial items...
			.not( this.getValue() ) // ...without current items
			.toArray();
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.setFocus
	 */
	setFocus: function() {
		if( this._getTagadata() !== null ) {
			this._getTagadata().getHelperTag().find( 'input' ).focus();
		}
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.removeFocus
	 */
	removeFocus: function() {
		if( this._getTagadata() !== null ) {
			this._getTagadata().getHelperTag().find( 'input' ).blur();
		}
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface.valueCompare
	 *
	 * Compares all values of the two lists, normalizes the lists first. This means the values can be in random and
	 * still be considered equal.
	 *
	 * @param String[] value1
	 * @param String[] value2 [optional] if not given, this will check whether value1 is empty
	 * @return bool true for equal/empty, false if not
	 */
	valueCompare: function( value1, value2 ) {
		var normalVal1 = this.normalize( value1 );

		if( !$.isArray( value2 ) ) {
			// check for empty value1
			return normalVal1.length < 1;
		}

		var normalVal2 = this.normalize( value2 );

		if( normalVal1.length !== normalVal2.length ) {
			return false;
		}

		for( var i in normalVal1 ) {
			if( normalVal1[ i ] !== normalVal2[ i ] ) {
				return false;
			}
		}
		return true;
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface.isEmpty
	 *
	 * @return bool whether this interface is empty
	 */
	isEmpty: function() {
		return this.getValue().length === 0;
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface.validate
	 *
	 * @return bool whether this interface is valid
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
	 * @return String[] all parts of the value which are valid, can be an empty array
	 */
	normalize: function( value ) {
		var validValue = [],
			self = this;

		$.each( value, function( i, val ) {
			val = self.normalizePiece( val );
			if( val !== null ) {
				// add valid values to result
				validValue.push( val );
			}
		} );
		return validValue;
	},

	/**
	 * Validates a piece of a list value.
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
		var normalized = PARENT.prototype.normalize.call( this, value );
		if( normalized === '' ) {
			return null;
		}
		return normalized;
	},

	/**
	 * @see wb.utilities.ui.StatableObject._setState
	 */
	_setState: function( state ) {
		if ( this._getTagadata() !== null ) {
			if ( state === this.STATE.DISABLED ) {
				this._getTagadata().disable();
			} else {
				this._getTagadata().enable();
			}
		}
		return true;
	}

} );

} )( mediaWiki, wikibase, jQuery );
