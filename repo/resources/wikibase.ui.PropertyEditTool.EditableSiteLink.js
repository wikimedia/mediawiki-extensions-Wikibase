/**
 * JavasSript for managing editable representation of site links.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.EditableSiteLink.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */
"use strict";

/**
 * Serves the input interface for a site link, extends EditableValue.
 *
 * @param jQuery subject
 */
window.wikibase.ui.PropertyEditTool.EditableSiteLink = function( subject ) {
	window.wikibase.ui.PropertyEditTool.EditableValue.call( this, subject );
};
window.wikibase.ui.PropertyEditTool.EditableSiteLink.prototype = new window.wikibase.ui.PropertyEditTool.EditableValue();
$.extend( window.wikibase.ui.PropertyEditTool.EditableSiteLink.prototype, {

	/**
	 * current results received from the api
	 * @var Array
	 */
	_currentResults: null,

	//getInputHelpMessage: function() {
	//	return window.mw.msg( 'wikibase-description-input-help-message', mw.config.get('wbDataLangName') );
	//},

	_initToolbar: function() {
		window.wikibase.ui.PropertyEditTool.EditableValue.prototype._initToolbar.call( this );
		this._toolbar.editGroup.displayRemoveButton = true;
		this._toolbar.draw();
	},

	_getToolbarParent: function() {
		// append toolbar to new td
		return $( '<td/>' ).appendTo( this._subject );
	},

	_getValueContainer: function() {
		return $( this._subject.find( 'td' )[1] );
	},

	_buildInputElement: function() {
		var inputElement = window.wikibase.ui.PropertyEditTool.EditableValue.prototype._buildInputElement.call( this );
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

	stopEditing: function( save ) {
		window.wikibase.ui.PropertyEditTool.EditableValue.prototype.stopEditing.call( this, save );
		var container = $( this._subject.children()[1] );
		var title = container.text();
		var siteId = this._subject.attr('class').match(/wb-language-links-\w+/)[0].split('-').pop();
		container.html( $( '<a/>', {
			href: 'http://' + siteId + '.wikipedia.org/wiki/' + title, // TODO store link references in config
			text: title
		} ) );
	},

	/**
	 * validate current input
	 * @param String current input value
	 */
	validate: function( value ) {
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

	getApiCallParams: function( removeValue ) {
		if ( removeValue === true ) {
			return {
				action: 'wblinksite',
				id: mw.config.values.wbItemId,
				link: 'remove',
				linksite: $( this._subject.children()[0] ).text(),
				linktitle: this.getValue()
			};
		} else {
			return {
				action: 'wblinksite',
				id: mw.config.values.wbItemId,
				link: 'set',
				linksite: $( this._subject.children()[0] ).text(),
				linktitle: this.getValue()
			};
		}
	}
} );