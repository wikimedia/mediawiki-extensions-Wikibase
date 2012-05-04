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
window.wikibase.ui.PropertyEditTool.EditableValue.SiteIdInterface = function( subject ) {
	window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface.apply( this, arguments );
};
window.wikibase.ui.PropertyEditTool.EditableValue.SiteIdInterface.prototype = new window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface();
$.extend( window.wikibase.ui.PropertyEditTool.EditableValue.SiteIdInterface.prototype, {

	_init: function( subject ) {
		window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface.prototype._init.call( this, subject );
		this._initSiteList();
	},

	_initInputElement: function() {
		window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface.prototype._initInputElement.call( this );
		/**
		 * when leaving the input box, set displayed value to from any allowed input value to correct display value
		 *
		 * @param event
		 */
		this._inputElem.on( 'blur', $.proxy( function( event ) {
			if ( this.getSelectedSiteId() !== null ) {
				/*
				 loop through complete result set since the autocomplete widget's narrowed result set
				 is not reliable / too slow; e.g. do not do this:
				 widget.data( 'menu' ).activate( event, widget.children().filter(':first') );
				 this._inputElem.val( widget.data( 'menu' ).active.data( 'item.autocomplete' ).value );
				 */
				$.each( this._currentResults, $.proxy( function( index, element ) {
					if ( element.site.getId() == this.getSelectedSiteId() ) {
						this._inputElem.val(element.value );
					}
				}, this ) );
				this._onInputRegistered();
			}
		}, this ) );
	},

	/**
	 * Builds a list of sites allowed to choose from
	 */
	_initSiteList: function() {
		var siteList = [];
		
		// make sure to allow choosing the currently selected site id even if it is in the list of
		// sites to ignore. This makes sense since it is selected already and it should be possible
		// to select it again.
		var ignoredSites = [];
		if ( this.ignoredSiteLinks !== null ) {
			ignoredSites = this.ignoredSiteLinks.slice();
		}
		var ownSite = this.getSelectedSite();
		if( ownSite !== null ) {
			var ownSiteIndex = $.inArray( ownSite, ignoredSites );
			if( ownSiteIndex > -1 ) {
				ignoredSites.splice( ownSiteIndex, 1 );
			}
		}
		
		// find out which site ids should be selectable and add them as auto selct choice
		for ( var siteId in wikibase.getSites() ) {
			var site = wikibase.getSite( siteId );
			
			if( $.inArray( site, ignoredSites ) == -1 ) {
				siteList.push( {
					'label': site.getName() + ' (' + site.getId() + ')',
					'value': site.getShortName() + ' (' + site.getId() + ')',
					'site': site // additional reference to site object for validation
				} );
			}
		}
		this.setResultSet( siteList );
	},

	/**
	 * Returns the selected site
	 *
	 * @return wikibase.Site
	 */
	getSelectedSite: function() {
		var siteId = this.getSelectedSiteId();
		if( siteId === null ) {
			return null;
		}
		return wikibase.getSite( siteId );
	},

	/**
	 * Returns the selected sites site Id from currently specified value.
	 * 
	 * @return string|null siteId or null if no valid selection has been made yet.
	 */
	getSelectedSiteId: function() {
		var value = this.getValue();
		return this.getSiteIdByValue( value );
	},
	
	/**
	 * Tries to get a site id by analyzing a given string
	 *
	 * @param String value
	 */
	getSiteIdByValue: function( value ) {
		// normalize value first (trim and stuff)
		value = this.normalize( value );

		for( var i in this._currentResults ) {
			var currentItem = this._currentResults[i];
			if(    value == currentItem.site.getId()
				|| value == currentItem.site.getShortName()
				|| value == currentItem.value
				|| value == currentItem.label
			) {
				return currentItem.site.getId();
			}
		}
		return null;
	},

	/**
	 * @see window.wikibase.ui.PropertyEditTool.EditableValue.Interface
	 */
	validate: function( value ) {
		// check whether current input is in the list of values returned by the wikis API
		return this.getSiteIdByValue( value ) !== null;
	},
/*
	normalize: function( value ) {
		value = window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface.prototype.normalize.call( this, value );
		return value;
	},
*/
	
	/////////////////
	// CONFIGURABLE:
	/////////////////
	
	/**
	 * Allows to specify an array with sites which should not be allowed to choose
	 */
	ignoredSiteLinks: null
} );
