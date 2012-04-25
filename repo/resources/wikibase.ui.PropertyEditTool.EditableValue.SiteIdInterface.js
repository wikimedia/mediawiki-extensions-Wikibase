/**
 * JavasSript for a part of an editable property value for the input for a site id
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.EditableValue.SiteIdInterface.js
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
	
	_initInputElement: function() {
		var clientList = [];

		this.onKeyDown = function( event ) {
			// when hitting tab, select the first element of the current result set an jump into title input box
			if ( event.keyCode == 9 ) {
				var widget = this._inputElem.autocomplete( 'widget' );
				widget.data( 'menu' ).activate( event, widget.children().filter(':first') );
				widget.data( 'menu' ).select( event );
			}
		}

		for ( var siteId in wikibase.getClients() ) {
			var client = wikibase.getClient( siteId );
			clientList.push( {
				'label': client.getName() + ' (' + client.getId() + ')',
				'value': client.getShortName() + ' (' + client.getId() + ')',
				'client': client } // additional reference to client object for validation
			);
		}
		this.setResultSet( clientList );

		window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface.prototype._initInputElement.call( this );
	},
	
	_onInputRegistered: function() {
		window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface.prototype._onInputRegistered.call( this );
		var siteId = this.getSelectedSiteId();
		var isValid = this.validate( this.getValue() );
		/*
		if ( isValid && wikibase.hasClient( siteId ) ) {
			this._editableValue._interfaces.pageName.url = wikibase.getClient( siteId ).getApi();
		}
		*/
		this._editableValue._interfaces.pageName.setDisabled( !isValid );
	},


	/**
	 * Returns the selected client site Id
	 * 
	 * @return string|null siteId or null if no valid selection has been made yet.
	 */
	getSelectedSiteId: function() {
		var value = this.getValue();
		for( var i in this._currentResults ) {
			if(
				   value == this._currentResults[i].client.getId()
				|| value == this._currentResults[i].client.getShortName()
				|| value == this._currentResults[i].value
			) {
				return this._currentResults[i].client.getId();
			}
		}
		return null;
	},
	
	/**
	 * Returns the selected client
	 * 
	 * @return wikibase.Client
	 */
	getSelectedClient: function() {
		var siteId = this.getSelectedSiteId();
		if( siteId === null ) {
			return null;
		}
		return wikibase.getClient( siteId );
	},

	/**
	 * validate input
	 * @param String value
	 */
	validate: function( value ) {
		// check whether current input is in the list of values returned by the wikis API
		window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface.prototype.validate.call( this, value );
		return ( this.getSelectedSiteId() === null ) ? false : true;
	}

} );
