/**
 * JavasSript for a part of an editable property value
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.EditableValue.Interface.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 * @author Daniel Werner
 */
"use strict";

/**
 * Serves the input interface to choose a wiki page from some MediaWiki installation as part of an
 * editable value
 * 
 * @param jQuery subject
 */
window.wikibase.ui.PropertyEditTool.EditableValue.WikiPageInterface = function( subject, editableValue ) {
	window.wikibase.ui.PropertyEditTool.EditableValue.Interface.apply( this, arguments );
};
window.wikibase.ui.PropertyEditTool.EditableValue.WikiPageInterface.prototype = new window.wikibase.ui.PropertyEditTool.EditableValue.Interface();
$.extend( window.wikibase.ui.PropertyEditTool.EditableValue.WikiPageInterface.prototype, {
	
	_buildInputElement: function() {
		// get basic input box:
		var inputElement = window.wikibase.ui.PropertyEditTool.EditableValue.Interface.prototype._buildInputElement.call( this );
		
		// extend input element with autocomplete:
		inputElement.autocomplete( {
			source: $.proxy( function( request, suggest ) {
				var siteId = this._subject.attr('class').match(/wb-language-links-\w+/)[0].split('-').pop();
				var apiLink = 'http://' + siteId + '.wikipedia.org/w/api.php'; // TODO store api references in config and acquire by site id
				$.getJSON( apiLink + '?callback=?', {
					action: 'opensearch',
					search: request.term,
					namespace: 0,
					suggest: ''
				}, $.proxy( function( data ) {
					this._currentResults = data[1];
					suggest( data[1] ); // pass array of returned values to callback
					this._onInputRegistered();
				}, this ) );
			}, this ),
			close: $.proxy( function( event, ui ) {
				this._onInputRegistered();
			}, this )
		} );
		return inputElement;
	},
	
	_getValueContainer: function() {
		return $( this._subject );
	},
	
	stopEditing: function( save ) {
		window.wikibase.ui.PropertyEditTool.EditableValue.Interface.prototype.stopEditing.call( this, save );
		
		var container = $( this._subject.children()[1] );
		var title = container.text();
		var siteId = this._subject.attr('class').match(/wb-languagelinks-link-\w+/)[0].split('-').pop();
		
		container.html( $( '<a/>', {
			href: 'http://' + siteId + '.wikipedia.org/wiki/' + title, // TODO store link references in config
			text: title
		} ) );
	},
	
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
	}	
} );
