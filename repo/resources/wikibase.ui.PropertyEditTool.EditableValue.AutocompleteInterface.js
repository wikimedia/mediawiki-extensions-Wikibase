/**
 * JavaScript for a part of an editable property value offering auto complete functionality
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */
( function( mw, wb, $, undefined ) {
'use strict';
var $PARENT = wb.ui.PropertyEditTool.EditableValue.Interface;

/**
 * Serves an autocomplete supported input interface as part of an EditableValue
 * @constructor
 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface
 * @since 0.1
 */
wb.ui.PropertyEditTool.EditableValue.AutocompleteInterface = wb.utilities.inherit( $PARENT, {
	/**
	 * timeout if auto-complete AJAX request in milliseconds
	 * @const int
	 */
	TIMEOUT: 8000,

	/**
	 * current result set of strings used for validation
	 * @var Array
	 */
	_currentResults: null,

	/**
	 * to prevent text highlighting when pressing backspace, keyCode is stored onKeyDown event
	 * @var int
	 */
	_lastKeyDown: null,

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface._init
	 */
	_init: function( subject ) {
		this._currentResults = [];
		$PARENT.prototype._init.call( this, subject );
	},

	/**
	 * create input element and initialize autocomplete
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface._buildInputElement
	 */
	_buildInputElement: function() {
		// get basic input box:
		var inputElement = $PARENT.prototype._buildInputElement.call( this );

		// extend input element with autocomplete
		if ( this.ajaxParams !== null ) {
			inputElement.wikibaseAutocomplete( {
				source: $.proxy( this._handleResponse, this ),
				close: $.proxy( function( event, ui ) {
					this._inputElem.data( 'autocomplete' ).element.removeClass( 'ui-autocomplete-loading' );
					this._onInputRegistered();
				}, this )
			} );
		} else if ( this._currentResults !== null ) {
			inputElement.wikibaseAutocomplete( {
				source: this._currentResults,
				close: $.proxy( function( event, ui ) {
					this._onInputRegistered();
				}, this )
			} );
			inputElement.on( 'keyup', $.proxy( function( event ) {
				this._onInputRegistered();
			}, this ) );
		}

		return inputElement;
	},

	/**
	 * handles AJAX response for jquery.ui.autocomplete filling auto-complete result set on success
	 *
	 * @param object request contains request parameters
	 * @param function suggest callback putting results into auto-complete menu
	 */
	_handleResponse: function( request, suggest ) {
		$.ajax( {
			url: this.url,
			dataType: 'jsonp',
			data:  $.extend( {}, this.ajaxParams, { 'search': request.term } ),
			timeout: this.TIMEOUT,
			success: $.proxy( function( response ) {
				if ( ! this.isInEditMode() ) {
					// in a few rare cases this could happen. For example when just switching a char from lower
					// to upper case, which will still be considered valid but require another suggestion list
					return;
				}
				if ( response[0] === this._inputElem.val() ) {
					this._currentResults = response[1];
					suggest( response[1] ); // pass array of returned values to callback

					/*
					 set value to first suggestion but select text of additional characters automatically placed
					 allowing the first value to be selected directly but be overwritten as well;
					 because of the API call lag, this is avoided when hitting backspace, since the value would
					 be resetted too slow
					 */
					if ( this._lastKeyDown !== 8 && response[1].length > 0 ) {
						/*
						 following if-statement is a work-around for a technically unexpected search
						 behaviour: e.g. in English Wikipedia opensearch for "Allegro " returns "Allegro"
						 as first result instead of "Allegro (music)", so auto-completion should probably
						 be prevented here
						 */
						if (
							response[1][0].toLowerCase().indexOf(
								this._inputElem.val().toLowerCase()
							) !== -1
						) {
							this.setValue( response[1][0] );
							var start = response[0].length;
							var end = response[1][0].length;
							var node = this._inputElem[0];
							if( node.createTextRange ) {
								var selRange = node.createTextRange();
								selRange.collapse( true );
								selRange.moveStart( 'character', start);
								selRange.moveEnd( 'character', end);
								selRange.select();
							} else if( node.setSelectionRange ) {
								node.setSelectionRange( start, end );
							} else if( node.selectionStart ) {
								node.selectionStart = start;
								node.selectionEnd = end;
							}
						}
					}
					this._inputElem.data( 'autocomplete' ).element.removeClass( 'ui-autocomplete-loading' );
				}
				this._onInputRegistered();
			}, this ),
			error: $.proxy( function( jqXHR, textStatus, errorThrown ) {
				this._inputElem.data( 'autocomplete' ).element.removeClass( 'ui-autocomplete-loading' );
				if ( textStatus !== 'abort' ) {
					var error = {
						code: textStatus,
						shortMessage: mw.msg( 'wikibase-error-autocomplete-connection' ),
						message: mw.msg( 'wikibase-error-autocomplete-response', errorThrown )
					};
					this.setTooltip( new wb.ui.Tooltip(
						this._inputElem,
						error,
						{ gravity: 'nw' }
					) );
					this.getTooltip().show( true );
					this.setFocus(); // re-focus input
				}
			}, this )
		} );
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
	 * custom onKeyDown event extension
	 *
	 * @param jQuery.event event
	 */
	onKeyDown: function( event ) {
		this._lastKeyDown = event.keyCode;
	},

	/**
	 * @see wikibase.ui.StateExtension.setDisabled
	 *
	 * @param Boolean disable true to disable, false to enable the element
	 */
	setDisabled: function( disable ) {
		var success = $PARENT.prototype.setDisabled.call( this, disable );
		if ( this._inputElem !== null ) {
			if ( disable ) {
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
