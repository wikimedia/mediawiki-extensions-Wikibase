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
		interfaces.siteId.setActive( this.isPending() ); // site ID will remain once set!
		interfaces.siteId.inputPlaceholder = mw.msg( 'wikibase-sitelink-site-edit-placeholder' );

		// interface for choosing a page (from the source site):
		interfaces.pageName = new ev.ClientPageInterface(
				tableCells[1], this, interfaces.siteId.getSelectedClient()
		);
		interfaces.pageName.inputPlaceholder = mw.msg( 'wikibase-sitelink-page-edit-placeholder' );
		interfaces.pageName.ajaxParams = {
			action: 'opensearch',
			namespace: 0,
			suggest: ''
		};
		
		interfaces.push( interfaces.siteId );
		interfaces.push( interfaces.pageName );
		return interfaces;
	},
	
	_interfaceHandler_onInputRegistered: function() {
		window.wikibase.ui.PropertyEditTool.EditableValue.prototype._interfaceHandler_onInputRegistered.call( this );
		
		var idInterface = this._interfaces.siteId;
		var pageInterface = this._interfaces.pageName;
		
		// set up necessary communication between both interfaces:
		var client = idInterface.getSelectedClient();
		if( client !== pageInterface.getClient() && client !== null ) {
			// FIXME: this has to be done on idInterface.onInputRegistered only but that
			//        is not really possible with the current 'event' system since this function is
			//        registered there.
			pageInterface.setClient( client );
			
			var siteId = idInterface.getSelectedSiteId();
			
			// change class names:
			this._removeClassByRegex( this._subject, /^wb-sitelinks-.+/ );
			this._subject.addClass( 'wb-sitelinks-' + siteId );
			
			this._removeClassByRegex( idInterface._getValueContainer(), /^wb-sitelinks-site-.+/ );
			idInterface._getValueContainer().addClass( 'wb-sitelinks-' + siteId );
			
			this._removeClassByRegex( pageInterface._getValueContainer(), /^wb-sitelinks-link-.+/ );
			pageInterface._getValueContainer().addClass( 'wb-sitelinks-site-' + siteId );
		}
		
		// only enable client page selector if there is a valid client id selected
		pageInterface.setDisabled( ! idInterface.isValid() );
	},

	_getToolbarParent: function() {
		// append toolbar to new td
		return $( '<td/>' ).appendTo( this._subject );
	},
	
	/**
	 * Helper function to remove a css class matching a regular expression.
	 * 
	 * @param subject jQuery
	 * @param RegExp classNameRegex
	 */
	_removeClassByRegex: function( subject, classNameRegex ) {
		if ( typeof subject.attr( 'class' ) == 'undefined' ) {
			return
		}
		$.each( subject.attr( 'class' ).split( ' ' ), $.proxy( function( index, className ) {
			if ( className.match( classNameRegex ) ) {
				subject.removeClass( className );
			}
		}, this ) );
	},

	stopEditing: function( save ) {
		var changed = window.wikibase.ui.PropertyEditTool.EditableValue.prototype.stopEditing.call( this, save );
		
		// make sure the interface for entering the clients id can't be edited after created
		this._interfaces.siteId.setActive( this.isPending() );
		
		return changed;
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
