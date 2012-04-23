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
			source:	$.proxy( function( request, suggest ) {
				var siteId = this._subject.attr('class').match(/wb-language-links-\w+/)[0].split('-').pop();
				var apiLink = 'http://' + siteId + '.wikipedia.org/w/api.php'; // TODO store api references in config and acquire by site id
				$.getJSON( apiLink + '?callback=?', {
					action: 'opensearch',
					search: request.term,
					namespace: 0,
					suggest: ''
				}, function( data ) {
					suggest( data[1] ); // pass array of returned values to callback
				} );
			}, this )
		} );
		return inputElement;
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