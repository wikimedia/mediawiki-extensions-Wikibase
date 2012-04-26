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
 * Serves the input interface to write a site code or to selectone. This will also validate whether
 * the site code is existing and will display the full site name if it is.
 * 
 * @param jQuery subject
 */
window.wikibase.ui.PropertyEditTool.EditableValue.SiteIdInterface = function( subject, editableValue ) {
	window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface.apply( this, arguments );
};
window.wikibase.ui.PropertyEditTool.EditableValue.SiteIdInterface.prototype = new window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface();
$.extend( window.wikibase.ui.PropertyEditTool.EditableValue.SiteIdInterface.prototype, {
	
	_initInputElement: function() {
		/**
		 * when leaving the input box, set displayed value to from any allowed input value to correct display value
		 *
		 * @param event
		 */
		this.onBlur = function( event ) {
			var widget = this._inputElem.autocomplete( 'widget' );
			if ( this.getSelectedSiteId() !== null ) {
				/*
				 loop through complete result set since the autocomplete widget's narrowed result set
				 is not reliable / too slow; e.g. do not do this:
				 widget.data( 'menu' ).activate( event, widget.children().filter(':first') );
				 this._inputElem.val( widget.data( 'menu' ).active.data( 'item.autocomplete' ).value );
				*/
				$.each( this._currentResults, $.proxy( function( index, element ) {
					if ( element.client.getId() == this.getSelectedSiteId() ) {
						this._inputElem.val(element.value );
					}
				}, this ) )
				this._onInputRegistered();
			}
		}
		
		this._initClientList();		
		window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface.prototype._initInputElement.call( this );
	},
	
	/**
	 * Builds a list of clients allowed to choose from
	 */
	_initClientList: function() {
		var clientList = [];
		
		// make sure to allow choosing the currently selected site id even if it is in the list of
		// sites to ignore. This makes sense since it is selected already and it should be possible
		// to select it again.
		var ignoredSites = this.ignoredSiteLinks.slice();
		var ownSite = this.getSelectedClient();
		if( ownSite !== null ) {
			var ownSiteIndex = $.inArray( ownSite, ignoredSites );
			if( ownSiteIndex > -1 ) {
				ignoredSites.splice( ownSiteIndex, 1 );
			}
		}
		
		// find out which site ids should be selectable and add them as auto selct choice
		for ( var siteId in wikibase.getClients() ) {
			var client = wikibase.getClient( siteId );
			
			if( $.inArray( client, ignoredSites ) == -1 ) {
				clientList.push( {
					'label': client.getName() + ' (' + client.getId() + ')',
					'value': client.getShortName() + ' (' + client.getId() + ')',
					'client': client // additional reference to client object for validation
				} );
			}
		}
		this.setResultSet( clientList );
	},

	/**
	 * Returns the selected client site Id from currently specified value
	 * 
	 * @return string|null siteId or null if no valid selection has been made yet.
	 */
	getSelectedSiteId: function() {
		var value = this.getValue();
		if( ! this.isInEditMode() ) {
			return this._getSiteIdFromString( value );
		}		
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
	},
	
	_getSiteIdFromString: function( text ) {
		return text.replace( /^.+\(\s*(.+)\s*\)\s*/, '$1' );
	},
	
	
	/////////////////
	// CONFIGURABLE:
	/////////////////
	
	/**
	 * Allows to specify an array with clients which should not be allowed to choose
	 */
	ignoredSiteLinks: null
} );
