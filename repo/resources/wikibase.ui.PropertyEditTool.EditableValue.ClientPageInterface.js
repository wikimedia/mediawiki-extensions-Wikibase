/**
 * JavasSript for a part of an editable property value
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.EditableValue.ClientPageInterface.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */
"use strict";

/**
 * Serves the input interface to choose a wiki page from some MediaWiki installation as part of an
 * editable value
 * 
 * @param jQuery subject
 */
window.wikibase.ui.PropertyEditTool.EditableValue.ClientPageInterface = function( subject, editableValue, url, ajaxParams ) {
	window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface.apply( this, arguments );
};
window.wikibase.ui.PropertyEditTool.EditableValue.ClientPageInterface.prototype = new window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface();
$.extend( window.wikibase.ui.PropertyEditTool.EditableValue.ClientPageInterface.prototype, {

	stopEditing: function( save ) {
		window.wikibase.ui.PropertyEditTool.EditableValue.Interface.prototype.stopEditing.call( this, save );

		var title = this._subject.text();
		if ( this._subject.attr('class').match(/wb-sitelinks-link-[\w-]+/) !== null ) {
			var siteId = this._subject.attr('class').replace(/wb-sitelinks-link-([\w-]+)/, '$1');
			this._subject.html( $( '<a/>', {
				href: 'http://' + siteId + '.wikipedia.org/wiki/' + title, // TODO store link references in config
				text: title
			} ) );
		}
	},

	/**
	 * validate input
	 * @param String value
	 */
	validate: function( value ) {
		window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface.prototype.validate.call( this, value );
		for ( var i in this._currentResults ) {
			if ( value === this._currentResults[i] ) {
				return true;
			}
		}
	}

} );
