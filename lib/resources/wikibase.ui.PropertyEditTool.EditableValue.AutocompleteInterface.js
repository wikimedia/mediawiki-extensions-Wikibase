/**
 * JavaScript for a part of an editable property value offering auto complete functionality
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
 */
( function( mw, wb, $ ) {
'use strict';
/* jshint camelcase: false */

var PARENT = wb.ui.PropertyEditTool.EditableValue.Interface;

/**
 * Serves an autocomplete supported input interface as part of an EditableValue
 * @constructor
 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface
 * @since 0.1
 */
wb.ui.PropertyEditTool.EditableValue.AutocompleteInterface = wb.utilities.inherit( PARENT, {
	/**
	 * current result set of strings used for validation
	 * @var Array
	 */
	_currentResults: null,

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface._init
	 */
	_init: function( subject, options ) {
		this._currentResults = [];
		PARENT.prototype._init.call( this, subject, options );
	},

	/**
	 * create input element and initialize autocomplete
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface._buildInputElement
	 */
	_buildInputElement: function() {
		// get basic input box:
		var inputElement = PARENT.prototype._buildInputElement.call( this );

		// extend input element with autocomplete
		inputElement.suggester( {
			ajax: {
				url: this.url,
				params: this.ajaxParams
			},
			adaptLetterCase: 'first',
			source: ( this.ajaxParams === null ) ? this._currentResults : null
		} );

		inputElement.on( 'keyup close', $.proxy( function( event ) {
			// the input element might have been removed already with the event still being in the loop
			if ( !this.isDisabled() && this._inputElem !== null ) {
				this._onInputRegistered();
			}
		}, this ) );

		inputElement.on( 'suggesterresponse suggesterselect', $.proxy( function( event, results ) {
			this._currentResults = results;
			if ( !this.isDisabled() ) {
				this._onInputRegistered();
			}
		}, this ) );

		inputElement.on( 'suggestererror', $.proxy( function( event, textStatus, errorThrown ) {
			if ( textStatus !== 'abort' ) {
				var error = {
					code: textStatus,
					message: mw.msg( 'wikibase-error-autocomplete-connection' ),
					detailedMessage: mw.msg( 'wikibase-error-autocomplete-response', errorThrown )
				};
				this.setTooltip( new wb.ui.Tooltip(
					this._inputElem,
					{},
					error,
					{ gravity: 'nw' }
				) );
				this.getTooltip().show( true );
			}
		}, this ) );

		return inputElement;
	},

	/**
	 * set set of results that may be chosen from
	 * @param Array resultSet
	 */
	setResultSet: function( resultSet ) {
		this._currentResults = resultSet;
		if( this.isInEditMode() ) {
			// set this again if in edit mode, so autocomplete also updates
			this._inputElem.autocomplete( 'option', 'source', resultSet );
		}
	},

	/**
	 * Returns an value from the result set if it equals the one given.
	 * null in case the value doesn't exist within the result set.
	 *
	 * @return string|null
	 */
	getResultSetMatch: function( value ) {
		// trim and lower...
		value = $.trim( value ).toLowerCase();

		for( var i in this._currentResults ) {
			if( $.trim( this._currentResults[i] ).toLowerCase() === value ) {
				return this._currentResults[i]; // ...but return the original from suggestions
			}
		}
		return null;
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface.validate
	 */
	validate: function( value ) {
		return this.getResultSetMatch( value );
	},

	/**
	 * Takes the value and checks whether it is in the list of current suggestions (case-insensitive) and returns the
	 * value in the normalized form from the list.
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface.normalize
	 *
	 * @param string value string to be normalized
	 * @return string|null actual string found within the result set or null for invalid values
	 */
	normalize: function( value ) {
		if( this.isInEditMode() &&
			this.getInitialValue() !== '' &&
			this.getInitialValue() === $.trim( value ).toLowerCase()
		) {
			// in edit mode, return initial value if there was one and it matches
			// this catches the case where _currentResults still is empty but normalization is required
			return this.getInitialValue();
		}

		// check against list:
		return this._normalize_fromCurrentResults( value );
	},

	/**
	 * Checks whether the value is in the list of current suggestions (case-insensitive)
	 *
	 * @param value string
	 * @return string|null
	 */
	_normalize_fromCurrentResults: function( value ) {
		var match = this.getResultSetMatch( value );

		return ( match === null )
			? value // not found, return string "unnormalized" but don't return null since it could still be valid!
			: match;
	},

	/**
	 * @see wb.utilities.ui.StatableObject._setState
	 *
	 * @param Number state see wb.ui.EditableValue.STATE
	 * @return Boolean whether the desired state has been applied (or had been applied already)
	 */
	_setState: function( state ) {
		var success = PARENT.prototype._setState.call( this, state );
		if ( this._inputElem !== null ) {
			if ( state === this.STATE.DISABLED ) {
				this._inputElem.autocomplete( 'disable' );
				this._inputElem.autocomplete( 'close' );
			} else {
				this._inputElem.autocomplete( 'enable' );
			}
		}
		return success;
	},


	/////////////////
	// CONFIGURABLE:
	/////////////////

	/**
	 * url the AJAX request will point to (if ajax should be used to define a result set)
	 * @var String
	 */
	url: null,

	/**
	 * additional params for the AJAX request (if ajax should be used to define a result set)
	 * @var Object
	 */
	ajaxParams: null

} );

} )( mediaWiki, wikibase, jQuery );
