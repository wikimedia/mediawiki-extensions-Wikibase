/**
 * JavasSript for a part of an editable property value offering auto complete functionality
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */
"use strict";

/**
 * Serves an autocomplete supported input interface as part of an EditableValue
 *
 * @param jQuery subject
 */
window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface = function( subject ) {
	window.wikibase.ui.PropertyEditTool.EditableValue.Interface.apply( this, arguments );
};
window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface.prototype = new window.wikibase.ui.PropertyEditTool.EditableValue.Interface();
$.extend( window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface.prototype, {

	/**
	 * current result set of strings used for validation
	 * @var Array
	 */
	_currentResults: null,

	_init: function( subject ) {
		window.wikibase.ui.PropertyEditTool.EditableValue.Interface.prototype._init.call( this, subject );
		this._currentResults = new Array();
	},
	
	_buildInputElement: function() {
		// get basic input box:
		var inputElement = window.wikibase.ui.PropertyEditTool.EditableValue.Interface.prototype._buildInputElement.call( this );

		// extend input element with autocomplete:
		if ( this.ajaxParams !== null ) {
			inputElement.autocomplete( {
				source: $.proxy( function( request, suggest ) {
					$.ajax( {
						url: this.url,
						dataType: 'jsonp',
						data:  $.extend( {}, this.ajaxParams, { 'search': request.term } ),
						timeout: 8000,
						success: $.proxy( function( response ) {
							this._currentResults = response[1];
							suggest( response[1] ); // pass array of returned values to callback
							this._onInputRegistered();
						}, this ),
						error: $.proxy( function( jqXHR, textStatus, errorThrown ) {
							this._inputElem.data('autocomplete')._response(); // remove spinner
							if ( textStatus != 'abort' ) {
								var error = {
									code: textStatus,
									shortMessage: window.mw.msg( 'wikibase-error-autocomplete-connection' ),
									message: window.mw.msg( 'wikibase-error-autocomplete-response', errorThrown )
								};
								this.setTooltip( new window.wikibase.ui.Tooltip( this._inputElem, error, { gravity: 'nw' }, this ) );
								this.tooltip.showMessage( true );
								this.setFocus(); // re-focus input
							}
						}, this )
					} );
				}, this ),
				close: $.proxy( function( event, ui ) {
					this._onInputRegistered();
				}, this )
			} );
		} else if ( this._currentResults !== null ) {
			inputElement.autocomplete( {
				source: this._currentResults,
				close: $.proxy( function( event, ui ) {
					this._onInputRegistered();
				}, this )
			} );
			inputElement.on( 'keyup', $.proxy( function( event ) {
				this._onInputRegistered();
			}, this ) );
		}

		// make autocomplete results list strech from the right side of the input box in rtl
		if ( document.documentElement.dir == 'rtl' ) {
			inputElement.data( 'autocomplete' ).options.position.my = 'right top';
			inputElement.data( 'autocomplete' ).options.position.at = 'right bottom';
		}

		// since results list does not reposition automatically on resize, just close it
		$( window ).on( 'resize', $.proxy( function() {
			this._inputElem.data( 'autocomplete' ).close( {} );
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
	 * validate input
	 * @param String value
	 */
	validate: function( value ) {
		for( var i in this._currentResults ) {
			if( $.trim( this._currentResults[i] ) === $.trim( value ) ) {
				return true;
			}
		}
		return false;
	},
		
	_disableInputElement: function() {
		window.wikibase.ui.PropertyEditTool.EditableValue.Interface.prototype._disableInputElement.call( this );
		this._inputElem.autocomplete( "disable" );
		this._inputElem.autocomplete( "close" );
	},
	
	_enableInputelement: function() {
		window.wikibase.ui.PropertyEditTool.EditableValue.Interface.prototype._enableInputelement.call( this );
		this._inputElem.autocomplete( "enable" );
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

