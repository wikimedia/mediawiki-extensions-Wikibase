/**
 * JavasSript for a part of an editable property value for the input for a site id
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.EditableValue.Interface.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater
 */
"use strict";

/**
 * Serves the input interface to write a site code to select, this will validate whether the site
 * code is existing and will display the full site name if it is.
 * 
 * @param jQuery subject
 */
window.wikibase.ui.PropertyEditTool.EditableValue.SiteIdInterface = function( subject, editableValue ) {
	window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface.apply( this, arguments );
};
window.wikibase.ui.PropertyEditTool.EditableValue.SiteIdInterface.prototype = new window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface();
$.extend( window.wikibase.ui.PropertyEditTool.EditableValue.SiteIdInterface.prototype, {
	
	_resolvedSiteName: null,
	
	_initInputElement: function() {
		var arrayClients = [];

		this.onKeyDown = function( event ) {
			// when hitting tab, select the first element of the current result set an jump into title input box
			if ( event.keyCode == 9 ) {
				var widget = this._inputElem.autocomplete( 'widget' );
				widget.data( 'menu' ).activate( event, widget.children().filter(':first') );
				widget.data( 'menu' ).select( event );
			}
		}

		for ( var siteId in mw.config.get('wbSiteDetails') ) {
			arrayClients.push(  mw.config.get( 'wbSiteDetails' )[ siteId ].shortName + ' (' + siteId + ')' );
		}
		this.setResultSet( arrayClients );

		window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface.prototype._initInputElement.call( this );
		
		this._resolvedSiteName = $( '<span/>', {
			'class': this.UI_CLASS + '-siteid'
		} );
		this._inputElem.after( this._resolvedSiteName );
	},
	
	_onInputRegistered: function() {
		window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface.prototype._onInputRegistered.call( this );
		var siteId = this._getSiteIdFromValue();
		var isValid = this.validate( this.getValue() );
		if ( isValid ) {
			this._editableValue._interfaces[1].setUrl( wikibase.getClient( this._getSiteIdFromValue() ).getApi() );
		}
		this._editableValue._interfaces.pageName.setDisabled( !isValid );
	},

	_getSiteIdFromValue: function() {
		return this.getValue().replace( /[^(]+\(([^()]+)\)/, '$1' );
	}

} );
