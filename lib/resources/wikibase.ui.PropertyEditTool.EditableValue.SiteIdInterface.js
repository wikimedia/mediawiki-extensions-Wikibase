/**
 * JavaScript for a part of an editable property value for the input for a site id
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater <mediawiki@snater.com>
 */
( function( mw, wb, $, undefined ) {
'use strict';
/* jshint camelcase: false */

var PARENT = wb.ui.PropertyEditTool.EditableValue.AutocompleteInterface;

/**
 * Serves the input interface to write a site code or to select one. This will also validate whether the site code is
 * existing and will display the full site name if it is.
 * @constructor
 * @see wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface
 * @since 0.1
 */
wb.ui.PropertyEditTool.EditableValue.SiteIdInterface = wb.utilities.inherit( PARENT, {

	/**
	 * @see wikibase.ui.Base._options
	 * @type {Object}
	 */
	_options: null,

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface._init
	 */
	_init: function( subject, options ) {

		// default options
		$.extend( this._options, {
			/**
			 * The site id cached for retrieving in non-edit mode.
			 * @type {String}
			 */
			siteId: null,
			/**
			 * Allows to specify an array with sites which should not be allowed to choose
			 * @type {Array}
			 */
			ignoredSiteLinks: null
		} );

		// overwrite default options
		if ( options !== undefined ) {
			$.extend( this._options, options );
		}

		this._initSiteList();

		// NOTE: this isn't too nice since we don't call the PARENT.prototype._init() which would override the list we
		//       create with _initSiteList() which we also can't call afterwards since the original init() already
		//       requires it when normalizing the initial value.
		wb.ui.PropertyEditTool.EditableValue.Interface.prototype._init.call( this, subject, options );
	},

	/**
	 * Builds siteselector input box.
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface._buildInputElement
	 *
	 * @return {jQuery} input element
	 */
	_buildInputElement: function() {
		// get basic input box
		var self = this,
			inputElement = wb.ui.PropertyEditTool.EditableValue.Interface.prototype._buildInputElement.call( this );

		// extend input element with site selector widget
		if ( this._currentResults !== null ) {
			inputElement
			.siteselector( { resultSet: this._currentResults } )
			.on( 'siteselectoropen siteselectorclose siteselectorautocomplete', function( event ) {
				// make sure that when the autocomplete list opens and nothing was considered a valid choice, we just
				// select the first entry as the valid choice (see getResultSetMatch() for details)
				self._onInputRegistered();
			} );
		}

		return inputElement;
	},

	/**
	 * Builds a list of sites allowed to choose from
	 */
	_initSiteList: function() {
		// make sure to allow choosing the currently selected site id even if it is in the list of
		// sites to ignore. This makes sense since it is selected already and it should be possible
		// to select it again.
		var ignoredSites = [];
		if ( this.getOption( 'ignoredSiteLinks' ) !== null
			&& this.getOption( 'ignoredSiteLinks' ) !== undefined
		) {
			ignoredSites = this.getOption( 'ignoredSiteLinks' ).slice();
		}
		var ownSite = this.getSelectedSite();
		if( ownSite !== null ) {
			// make sure currently selected site can still be selected even if on ignore list
			var ownSiteIndex = $.inArray( ownSite, ignoredSites );
			if( ownSiteIndex > -1 ) {
				ignoredSites.splice( ownSiteIndex, 1 );
			}
		}

		// find out which site ids should be selectable and add them as auto select choice
		this._currentResults = [];
		for ( var siteId in wb.getSites() ) {
			if( $.inArray( siteId, ignoredSites ) === -1 ) {
				this._currentResults.push( wb.getSite( siteId ) );
			}
		}
		if( this.isInEditMode() ) {
			// set this again if in edit mode, so autocomplete also updates
			this._inputElem.data( 'siteselector' ).setResultSet( this._currentResults );
		}
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
		return wb.getSite( siteId );
	},

	/**
	 * Returns the selected sites site Id from currently specified value.
	 *
	 * @return string|null siteId or null if no valid selection has been made yet.
	 */
	getSelectedSiteId: function() {
		var id = this.getValue();
		if( id === '' ) {
			return null;
		}
		return id;
	},

	/**
	 * @see wb.ui.PropertyEditTool.EditableValue.Interface.setValue
	 */
	setValue: function( value ) {
		if ( this._inputElem !== null ) {
			this._inputElem.val( value );
		}
		return PARENT.prototype.setValue.call( this, value );
	},

	/**
	 * Returns the current value
	 *
	 * @return string
	 */
	getValue: function() {
		var value;
		if ( this.isInEditMode() ) {
			value = this._normalize_fromCurrentResults();
		} else {
			value = PARENT.prototype.getValue.call( this );
			value = this.normalize( value ); // return id instead of actual value...
		}
		return value ? value : ''; // ... but make sure this won't be null!
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface._setValue_inNonEditMode
	 *
	 * @param string value
	 * @return bool whether the value has been changed
	 */
	_setValue_inNonEditMode: function( value ) {
		// the actual value is the site id, displayed value though should be the whole site name and id in parentheses.
		var site = wb.getSite( value );
		// site is null in case it was initialized empty and destroy() is called... so we just handle this
		if( site !== null ) {
			// split interface subject into two columns, one holding the site name, the other the site id
			this._subject
			.removeAttr( 'colspan' )
			.attr( 'class', '' )
			.addClass( 'wb-sitelinks-sitename wb-sitelinks-sitename-' + site.getId() )
			.text( site.getShortName() )
			.after(
				$( '<td/>' ) // TODO interface should be independent from any DOM structure
				.addClass( 'wb-sitelinks-siteid wb-sitelinks-siteid-' + site.getId() )
				.attr({ 'dir' : 'ltr' })
				.text( site.getId() )
			);
			this.setOption( 'siteId', site.getId() );
			return true;
		} else {
			return false;
		}
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface.validate
	 */
	validate: function( value ) {
		return this.normalize( value ) !== null;
	},

	/**
	 * Will return the site ID if any of the site names is given.
	 *
	 * @see wb.ui.PropertyEditTool.EditableValue.AutocompleteInterface._normalize_fromCurrentResults
	 */
	_normalize_fromCurrentResults: function( value ) {
		var siteId = this.getOption( 'siteId' );
		if ( value !== undefined && this._currentResults !== null ) {
			$.each( this._currentResults, function( i, site ) {
				if ( value === site.getId() ) {
					siteId = value;
					return false;
				}
			} );
		}
		if ( this.isInEditMode() && siteId === null ) {
			// null in case it doesn't exist!
			siteId = this._inputElem.data( 'siteselector' ).getSelectedSiteId();
		}
		return siteId;
	},

	/**
	 * @see wb.ui.PropertyEditTool.EditableValue.Interface.valueCompare
	 *
	 * This method performs the same operations like the corresponding method in the parent class
	 * just without additional normalization of the values since an empty value would be normalized
	 * to the fallback site id just like the the other value might have been passed as fallback site
	 * id. While overwriting this method is not required for other browsers, Firefox will trigger
	 * the key events registered in wb.ui.PropertyEditTool.EditableValue.Interface
	 * ._buildInputElement before any other attached event.
	 *
	 * @param {String} value1
	 * @param {String} [value2] When not given, this will check whether value1 is empty
	 * @return {Boolean} True for equal/empty, false if not
	 */
	valueCompare: function( value1, value2 ) {
		if( value2 === undefined || value2 === null ) {
			// check for empty value1
			return value1 === '' || value1 === null;
		}
		return value1 === value2;
	}

} );

}( mediaWiki, wikibase, jQuery ) );
