/**
 * JavasSript for 'Wikibase' edit form for an items site links
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner at wikimedia.de >
 * @author H. Snater
 */
"use strict";

/**
 * Module for 'Wikibase' extensions user interface functionality for editing the site links of an item.
 */
window.wikibase.ui.SiteLinksEditTool = function( subject ) {
	if( typeof subject != 'undefined' ) {
		this._init( subject );
	}
};

window.wikibase.ui.SiteLinksEditTool.prototype = new window.wikibase.ui.PropertyEditTool();
$.extend( window.wikibase.ui.SiteLinksEditTool.prototype, {

	_init: function( subject ) {
		window.wikibase.ui.PropertyEditTool.prototype._init.call( this, subject );

		// add colspan+1 because of toolbar td's:
		var th = this._subject.find( 'th' );
		th.attr( 'colspan', parseInt( th.attr( 'colspan' ) ) + 1 );
	},

	_getToolbarParent: function() {
		// take content (table), put it into a div and also add the toolbar into the div
		return $( '<td/>', { colspan: '3' } )
			.appendTo( $( '<tr/>' )
				.appendTo( $( '<tfoot/>' )
					.appendTo( this._subject )
				)
			);
	},

	/**
	 * @see wikibase.ui.PropertyEditTool._getValueElems()
	 * @return jQuery[]
	 */
	_getValueElems: function() {
		// select all rows but not heading!
		return this._subject.find( 'tr:not(:has(th))' );
	},

	_newEmptyValueDOM: function() {
		return $( '<tr> <td></td> <td></td> </tr>' );
	},
	
	getEditableValuePrototype: function() {
		return window.wikibase.ui.PropertyEditTool.EditableSiteLink;
	},
	
	allowsMultipleValues: true
} );
