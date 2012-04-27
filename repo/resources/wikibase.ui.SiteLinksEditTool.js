/**
 * JavasSript for 'Wikibase' edit form for an items site links
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.SiteLinksEditTool.js
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
	/**
	 * Shared prototype for editable site links managed by an instance of this. This allows to
	 * directly manipulate some of all of the values properties as long as they are not overwritten
	 * explicitly.
	 * @see getEditableValuePrototype()
	 * @var Function constructor for window.wikibase.ui.PropertyEditTool.EditableSiteLink
	 *      basically.
	 */
	_editableValuesProto: null,

	_init: function( subject ) {
		window.wikibase.ui.PropertyEditTool.prototype._init.call( this, subject );

		// add colspan+1 because of toolbar td's:
		var th = this._subject.find( 'th' );
		th.attr( 'colspan', parseInt( th.attr( 'colspan' ) ) + 1 );
	},
	
	_initToolbar: function() {
		window.wikibase.ui.PropertyEditTool.prototype._initToolbar.call( this );
		// change message appearing when all language links are represented within the list
		this._toolbar.lblFull.setContent( window.mw.msg( 'wikibase-sitelinksedittool-full' ) );
	},

	_buildSingleValueToolbar: function( editableValue ) {
		var toolbar = window.wikibase.ui.PropertyEditTool.prototype._buildSingleValueToolbar.call( this, editableValue );
		toolbar.editGroup.tooltip.setGravity( 'nw' );
		return toolbar;
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
	
	_initEditToolForValues: function() {
		window.wikibase.ui.PropertyEditTool.prototype._initEditToolForValues.call( this );
		
		// make sure selecting a language in EditableSiteLink only offers languages not yet chosen
		this._editableValuesProto.prototype.ignoredSiteLinks = this.getRepresentedSites();
	},
	
	/**
	 * Full when all languages are represented within
	 * 
	 * @see wikibase.ui.PropertyEditTool.isFull()
	 */
	isFull: function() {
		var allSites = wikibase.getClients();
		var usedSites = this.getRepresentedSites();
		
		// FIXME: in case we have sites in the DB which were removed at some point, this check will
		//        return true for isFull() too early! Make an array diff instead!
		return usedSites.length >= Object.keys( allSites ).length;
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
		// TODO: this system might be useful in other prototypes based on PropertyEditTool, implement
		//       this in PropertyEditTool directly perhaps.
		if( this._editableValuesProto !== null ) {
			return this._editableValuesProto;
		}
		var basePrototype = window.wikibase.ui.PropertyEditTool.EditableSiteLink;
		
		this._editableValuesProto = function() { basePrototype.apply( this, arguments ) };
		this._editableValuesProto.prototype = new basePrototype();
		this._editableValuesProto.prototype.ignoredSiteLinks = this.getRepresentedSites();
		
		return this._editableValuesProto;
	},
	
	_editableValueHandler_onAfterRemove: function( editableValue ) {
		window.wikibase.ui.PropertyEditTool.prototype._editableValueHandler_onAfterRemove.call( this, editableValue );
		
		// remove only site used by removed site link from list of ignored sites:
		var removedSite = editableValue.siteIdInterface.getSelectedClient();
		if( removedSite !== null ) {
			var index = $.inArray( removedSite, this._editableValuesProto.prototype.ignoredSiteLinks );
			if( index > -1 ) {
				this._editableValuesProto.prototype.ignoredSiteLinks.splice( index, 1 );
			}
		}
	},	
	_newValueHandler_afterStopEditing: function( newValue, save, changed, wasPending ) {
		window.wikibase.ui.PropertyEditTool.prototype._newValueHandler_afterStopEditing.call( this, newValue, save );		
		if( save ) {
			// add chosen site to list of sites which can not be chosen by other editable site links
			var addedSite = newValue.siteIdInterface.getSelectedClient();
			this._editableValuesProto.prototype.ignoredSiteLinks.push( addedSite );
		}
	},
	
	/**
	 * Returns a list of sites already represented with a value.
	 * 
	 * @return wikibase.Client[]
	 */
	getRepresentedSites: function() {
		var sites = new Array();
		
		for( var i in this._editableValues ) {
			var editableSiteLink = this._editableValues[ i ];
			var site = editableSiteLink.siteIdInterface.getSelectedClient();
			if( site !== null ) {
				sites.push( site );
			}
		}
		return sites;
	},
	
	/////////////////
	// CONFIGURABLE:
	/////////////////
	
	/**
	 * @see window.wikibase.ui.PropertyEditTool.prototype.allowsMultipleValues
	 */
	allowsMultipleValues: true
} );
