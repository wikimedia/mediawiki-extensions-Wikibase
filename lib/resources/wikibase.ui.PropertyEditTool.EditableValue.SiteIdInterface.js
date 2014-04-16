/**
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater <mediawiki@snater.com>
 */
( function( mw, wb, util, $ ) {
'use strict';
/* jshint camelcase: false */

var PARENT = wb.ui.PropertyEditTool.EditableValue.Interface;

/**
 * Serves the input interface to write a site code or to select one. This will also validate whether the site code is
 * existing and will display the full site name if it is.
 * @constructor
 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface
 * @since 0.1
 */
wb.ui.PropertyEditTool.EditableValue.SiteIdInterface = util.inherit( PARENT, {

	/**
	 * @see wikibase.ui.Base._options
	 * @type {Object}
	 */
	_options: null,

	/**
	 * @type {wikibase.Site[]|null}
	 */
	_sites: null,

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface._init
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
		PARENT.prototype._init.call( this, subject, options );
	},

	/**
	 * Builds siteselector input box.
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface._buildInputElement
	 *
	 * @return {jQuery}
	 */
	_buildInputElement: function() {
		// get basic input box
		var self = this,
			$input = PARENT.prototype._buildInputElement.call( this );

		// extend input element with site selector widget
		if ( this._sites !== null ) {
			$input
			.siteselector( { source: this._sites } )
			.on( 'siteselectorselected siteselectorchange siteselectoropen siteselectorclose', function( event ) {
				// Make sure that when the auto-complete list opens and nothing was considered a
				// valid choice, we just select the first entry as the valid choice (see
				// jQuery.wikibase.siteselector widget for details).
				self._onInputRegistered();
			} );
		}

		return $input;
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
		this._sites = [];
		for ( var siteId in wb.getSites() ) {
			if( $.inArray( siteId, ignoredSites ) === -1 ) {
				this._sites.push( wb.getSite( siteId ) );
			}
		}
		if( this.isInEditMode() ) {
			// set this again if in edit mode, so autocomplete also updates
			this._inputElem.data( 'siteselector' ).option( 'source', this._sites );
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
			this._inputElem.data( 'siteselector' ).setSelectedSite( wb.getSite( value ) );
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
	 * @param {string} value
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
	 * Checks whether a value is in the list of current suggestions (case-insensitive) and returns
	 * the value in the normalized form from the list.
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface.normalize
	 *
	 * @param {string} value String to be normalized
	 * @return {string|null} Actual string found within the result set or null for invalid values.
	 */
	normalize: function( value ) {
		if( this.isInEditMode() &&
			this.getInitialValue() !== '' &&
			this.getInitialValue() === $.trim( value ).toLowerCase()
		) {
			// In edit mode, return initial value if there was one and it matches; This catches the
			// case where _currentResults still is empty but normalization is required.
			return this.getInitialValue();
		}

		// Check against list:
		return this._normalize_fromCurrentResults( value );
	},

	/**
	 * Will return the site ID if any of the site names is given.
	 *
	 * @see wb.ui.PropertyEditTool.EditableValue.Interface._normalize_fromCurrentResults
	 */
	_normalize_fromCurrentResults: function( value ) {
		var siteId = this.getOption( 'siteId' );
		if ( value !== undefined && this._sites !== null ) {
			$.each( this._sites, function( i, site ) {
				if ( value === site.getId() ) {
					siteId = value;
					return false;
				}
			} );
		}
		if ( this.isInEditMode() && siteId === null ) {
			var site = this._inputElem.data( 'siteselector' ).getSelectedSite();
			if( site ) {
				siteId = site.getId();
			}
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
	},

	/**
	 * @see wikibase.utilities.ui.StatableObject._setState
	 *
	 * @param {number} state (see wikibase.ui.PropertyEditTool.EditableValue.STATE)
	 * @return {boolean} Whether the desired state has been applied (or had been applied already).
	 */
	_setState: function( state ) {
		var success = PARENT.prototype._setState.call( this, state );
		if( this._inputElem !== null ) {
			if( state === this.STATE.DISABLED ) {
				this._inputElem.autocomplete( 'disable' );
				this._inputElem.autocomplete( 'close' );
			} else {
				this._inputElem.autocomplete( 'enable' );
			}
		}
		return success;
	}

} );

}( mediaWiki, wikibase, util, jQuery ) );
