/**
 * JavasSript for a part of an editable property value
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
 * Serves an autocomplete supported input interface
 *
 * @param jQuery subject
 */
window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface = function( subject, editableValue ) {
	window.wikibase.ui.PropertyEditTool.EditableValue.Interface.apply( this, arguments );
};
window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface.prototype = new window.wikibase.ui.PropertyEditTool.EditableValue.Interface();
$.extend( window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface.prototype, {

	/**
	 * current result set of strings used for validation
	 * @var Array
	 */
	_currentResults: null,

	_init: function( subject, editableValue ) {
		window.wikibase.ui.PropertyEditTool.EditableValue.Interface.prototype._init.call( this, subject, editableValue );
	},

	_buildInputElement: function() {
		// get basic input box:
		var inputElement = window.wikibase.ui.PropertyEditTool.EditableValue.Interface.prototype._buildInputElement.call( this );

		// extend input element with autocomplete:
		if ( this.ajaxParams !== null ) {
			inputElement.autocomplete( {
				source: $.proxy( function( request, suggest ) {
					$.getJSON( this.url + '?callback=?', $.extend( {}, this.ajaxParams, { 'search': request.term } ), $.proxy( function( data ) {
						this._currentResults = data[1];
						suggest( data[1] ); // pass array of returned values to callback
						this._onInputRegistered();
					}, this ) );
				}, this ),
				close: $.proxy( function( event, ui ) {
					this._onInputRegistered();
				}, this )
			} );
		} else if ( this._resultSet !== null ) {
			inputElement.autocomplete( {
				source: this._currentResults,
				close: $.proxy( function( event, ui ) {
					this._onInputRegistered();
				}, this )
			} );
		}
		return inputElement;
	},

	/**
	 * set set of results that may be chosen from
	 * @param Array resultSet
	 */
	setResultSet: function( resultSet ) {
		this._currentResults = resultSet;
	},

	/**
	 * validate input
	 * @param String value
	 */
	validate: function( value ) {
		// check whether current input is in the list of values returned by the wikis API
		if ( this._currentResults === null ) {
			return false;
		}
		for ( var i in this._currentResults ) {
			if ( value === this._currentResults[i] ) {
				return true;
			}
		}
		return false;
	},


	/////////////////
	// CONFIGURABLE:
	/////////////////

	/**
	 * url the AJAX request will point to
	 * @var String
	 */
	url: null,

	/**
	 * additional params for the AJAX request
	 * @var Object
	 */
	ajaxParams: null

} );
