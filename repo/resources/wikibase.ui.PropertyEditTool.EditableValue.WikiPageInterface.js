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
 */
"use strict";

/**
 * Serves the input interface to choose a wiki page from some MediaWiki installation as part of an
 * editable value
 * 
 * @param jQuery subject
 */
window.wikibase.ui.PropertyEditTool.EditableValue.WikiPageInterface = function( subject, editableValue, url, ajaxParams ) {
	window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface.apply( this, arguments );
};
window.wikibase.ui.PropertyEditTool.EditableValue.WikiPageInterface.prototype = new window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface();
$.extend( window.wikibase.ui.PropertyEditTool.EditableValue.WikiPageInterface.prototype, {

	stopEditing: function( save ) {
		window.wikibase.ui.PropertyEditTool.EditableValue.Interface.prototype.stopEditing.call( this, save );

		var container = $( this._subject.children()[1] );
		var title = container.text();
		var siteId = this._subject.attr('class').match(/wb-languagelinks-link-\w+/)[0].split('-').pop(); // TODO get site id from config

		container.html( $( '<a/>', {
			href: 'http://' + siteId + '.wikipedia.org/wiki/' + title, // TODO store link references in config
			text: title
		} ) );
	}

} );
