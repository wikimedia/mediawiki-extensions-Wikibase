/**
 * JavaScript for a part of an editable property value for the input for a site id
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater <mediawiki@snater.com>
 */
( function( mw, wb, $, undefined ) {
'use strict';
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
	 * initialize input box
	 * @see wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface._initInputElement
	 */
	_initInputElement: function() {
		PARENT.prototype._initInputElement.call( this );
		/*
		 * When leaving the input box, replace current (incomplete) value with first auto-suggested value.
		 * Also make sure pressing the enter key will select the first value in the auto-suggestion.
		 */
		this._inputElem.on( 'blur keypress', $.proxy( function( event ) {
			// 'keypress' event required because pressing enter won't choose first auto-suggested value
			if( event.type === 'keypress' && event.which !== $.ui.keyCode.ENTER ) {
				return;
			}
			if ( this.getSelectedSiteId() !== null ) {
				/*
				 loop through complete result set since the auto suggestion widget's narrowed result set
				 is not reliable / too slow; e.g. do not do this:
				 widget.data( 'menu' ).activate( event, widget.children().filter(':first') );
				 this._inputElem.val( widget.data( 'menu' ).active.data( 'item.autocomplete' ).value );
				 */
				$.each( this._currentResults, $.proxy( function( index, element ) {
					if ( element.site.getId() === this.getSelectedSiteId() ) {
						this._inputElem.val( element.value );
					}
				}, this ) );
				this._onInputRegistered();
				/* make sure to close auto-suggestion menu manually when pressing enter but do not
				overwrite the widget internal blur handling for other (especially mouse) events
				since the menu would also get closed when clicking on its scrollbar taking the focus
				away from the menu; let the internal widget code handle the blur event / closing of
				the menu instead */
				if ( event.type !== 'blur' ) {
					this._inputElem.autocomplete( 'close' );
				}
			}
		}, this ) );
	},

	/**
	 * Build suggester input box and define input handling.
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface._buildInputElement
	 */
	_buildInputElement: function() {
		// get basic input box
		var self = this,
			inputElement = wb.ui.PropertyEditTool.EditableValue.Interface.prototype._buildInputElement.call( this );

		// extend input element with autocomplete
		if ( this._currentResults !== null ) {
			inputElement.siteselector( { resultSet: this._currentResults } );
		}

		inputElement.on( 'siteselectorclose', function( event ) {
			self._onInputRegistered();
		} );

		inputElement.on( 'siteselectoropen', $.proxy( function( event ) {
			// make sure that when the autocomplete list opens and nothing was considered a valid choice, we just
			// select the first entry as the valid choice (see getResultSetMatch() for details)
			this._onInputRegistered();
		}, this ) );

		return inputElement;
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
		if ( this.getOption( 'ignoredSiteLinks' ) !== null
			&& this.getOption( 'ignoredSiteLinks' ) !== undefined ) {
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

		// find out which site ids should be selectable and add them as auto selct choice
		for ( var siteId in wb.getSites() ) {
			var site = wb.getSite( siteId );

			if( $.inArray( siteId, ignoredSites ) == -1 ) {
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
		if ( this.isInEditMode() ) {
			value = this._normalize_fromCurrentResults();
		} else {
			var value = PARENT.prototype.getValue.call( this );
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
			.attr( 'colspan', '0' )
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
			$.each( this._currentResults, function( i, result ) {
				if ( value === result.site.getId() ) {
					siteId = value;
					return false;
				}
			} );
		}
		if ( this.isInEditMode() ) {
			// null in case it doesn't exist!
			siteId = this._inputElem.data( 'siteselector' ).getSelectedSiteId();
		}
		return siteId;
	}

} );

}( mediaWiki, wikibase, jQuery ) );
