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
 * @see window.wikibase.ui.PropertyEditTool.EditableValue
 */
window.wikibase.ui.PropertyEditTool.EditableSiteLink = function( subject, toolbar ) {
	window.wikibase.ui.PropertyEditTool.EditableValue.call( this, subject, toolbar );
};
window.wikibase.ui.PropertyEditTool.EditableSiteLink.prototype = new window.wikibase.ui.PropertyEditTool.EditableValue();
$.extend( window.wikibase.ui.PropertyEditTool.EditableSiteLink.prototype, {
	/**
	 * The part of the editable site link representing the site the selected page belongs to
	 * @var wikibase.ui.PropertyEditTool.EditableValue.SiteIdInterface
	 */
	siteIdInterface: null,
	
	/**
	 * The part of the editable site link representing the link to the site
	 * @var wikibase.ui.PropertyEditTool.EditableValue.SitePageInterface
	 */
	pageNameInterface: null,

	/**
	 * current results received from the api
	 * @var Array
	 */
	_currentResults: null,

	_initToolbar: function() {
		window.wikibase.ui.PropertyEditTool.EditableValue.prototype._initToolbar.call( this );
		this._toolbar.editGroup.displayRemoveButton = true;
		this._toolbar.draw();
	},

	_initInterfaces: function() {
		window.wikibase.ui.PropertyEditTool.EditableValue.prototype._initInterfaces.call( this );
		// make interfaces available for public since they contain interesting data:
		this.siteIdInterface = this._interfaces.siteId;
		this.pageNameInterface = this._interfaces.pageName;
	},

	_buildInterfaces: function( subject ) {
		var interfaces = new Array();
		var tableCells = subject.children( 'td' );
		var ev = window.wikibase.ui.PropertyEditTool.EditableValue;
		
		// interface for choosing the source site:
		interfaces.siteId = new ev.SiteIdInterface();
		interfaces.siteId.inputPlaceholder = mw.msg( 'wikibase-sitelink-site-edit-placeholder' );
		interfaces.siteId.ignoredSiteLinks = this.ignoredSiteLinks;
		interfaces.siteId._init( tableCells[0] );

		interfaces.siteId.setActive( this.isPending() ); // site ID will remain once set!

		// interface for choosing a page (from the source site):
		interfaces.pageName = new ev.SitePageInterface(
				tableCells[1], interfaces.siteId.getSelectedSite()
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
	
	_interfaceHandler_onInputRegistered: function( relatedInterface ) {
		window.wikibase.ui.PropertyEditTool.EditableValue.prototype._interfaceHandler_onInputRegistered.call( this, relatedInterface );
		
		var idInterface = this._interfaces.siteId;
		var pageInterface = this._interfaces.pageName;
		
		// set up necessary communication between both interfaces:
		var site = idInterface.getSelectedSite();
		if( site !== pageInterface.getSite() && site !== null ) {
			// FIXME: this has to be done on idInterface.onInputRegistered only but that
			//        is not really possible with the current 'event' system since this function is
			//        registered there.
			pageInterface.setSite( site );
			
			var siteId = idInterface.getSelectedSiteId();
			
			// change class names:
			this._removeClassByRegex( this._subject, /^wb-sitelinks-.+/ );
			this._subject.addClass( 'wb-sitelinks-' + siteId );
			
			this._removeClassByRegex( idInterface._getValueContainer(), /^wb-sitelinks-site-.+/ );
			idInterface._getValueContainer().addClass( 'wb-sitelinks-' + siteId );
			
			this._removeClassByRegex( pageInterface._getValueContainer(), /^wb-sitelinks-link-.+/ );
			pageInterface._getValueContainer().addClass( 'wb-sitelinks-site-' + siteId );
		}
		
		// only enable site page selector if there is a valid site id selected
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
	
	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.prototype.startEditing
	 */
	startEditing: function() {
		// set ignored site links again since they could have changed
		this._interfaces.siteId.ignoredSiteLinks = this.ignoredSiteLinks;
		return window.wikibase.ui.PropertyEditTool.EditableValue.prototype.startEditing.call( this );
	},
	
	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.prototype.stopEditing
	 */
	stopEditing: function( save ) {
		var changed = window.wikibase.ui.PropertyEditTool.EditableValue.prototype.stopEditing.call( this, save );
		
		// make sure the interface for entering the sites id can't be edited after created
		this._interfaces.siteId.setActive( this.isPending() );
		
		return changed;
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.prototype.getInputHelpMessage
	 */
	getInputHelpMessage: function() {
		return window.mw.msg( 'wikibase-sitelinks-input-help-message' );
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.prototype.getApiCallParams
	 */
	getApiCallParams: function( removeValue ) {
		var linkValues = this.getValue();
		var siteId = this.siteIdInterface.getSelectedSiteId();
		if ( removeValue === true ) {
			return {
				action: 'wblinksite',
				id: mw.config.values.wbItemId,
				link: 'remove',
				linksite: siteId,
				linktitle: linkValues[1]
			};
		} else {
			return {
				action: 'wblinksite',
				id: mw.config.values.wbItemId,
				link: 'set',
				linksite: siteId,
				linktitle: linkValues[1]
			};
		}
	},
	
	/////////////////
	// CONFIGURABLE:
	/////////////////
	
	/**
	 * Allows to specify an array with sites which should not be allowed to choose
	 * @var wikibase.Site[]
	 */
	ignoredSiteLinks: null
} );
