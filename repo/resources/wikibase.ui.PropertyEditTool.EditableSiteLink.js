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
 * @author Daniel Werner
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

	_buildInterfaces: function( subject ) {
		var interfaces = new Array();
		var tableCells = subject.children( 'td' );
		var ev = window.wikibase.ui.PropertyEditTool.EditableValue;
		
		// interface for choosing the source site:
		interfaces.siteId = new ev.SiteIdInterface( tableCells[0], this );
		interfaces.push( interfaces.siteId );
		interfaces.siteId.setActive( this.isPending() ); // site ID will remain once set!

		// interface for choosing a page (from the source site):
		interfaces.pageName = new ev.WikiPageInterface( tableCells[1], this );
		interfaces.pageName.ajaxParams = {
			action: 'opensearch',
			namespace: 0,
			suggest: ''
		};
		// url can only be set when site id is known (when adding a site link, url will be passed on that event)
		if ( this._subject.attr('class').match(/wb-language-links-\w+/) !== null ) {
			var siteId = this._subject.attr('class').match(/wb-language-links-\w+/)[0].split('-').pop();
			interfaces.pageName.url = wikibase.getClient( siteId ).getApi();
		}
		interfaces.push( interfaces.pageName );

		return interfaces;
	},

	_getToolbarParent: function() {
		// append toolbar to new td
		return $( '<td/>' ).appendTo( this._subject );
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