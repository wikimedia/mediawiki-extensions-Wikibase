/**
 * JavaScript for a part of an editable property value
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
 * @author Daniel Werner
 */
( function( mw, wb, $ ) {
'use strict';
/* jshint camelcase:false */

var PARENT = wb.ui.PropertyEditTool.EditableValue.AutocompleteInterface;

/**
 * Serves the input interface to choose a wiki page from some MediaWiki installation as part of an
 * editable value
 * @constructor
 * @see wb.ui.PropertyEditTool.EditableValue.AutocompleteInterface
 * @since 0.1
 *
 * @param jQuery subject
 */
wb.ui.PropertyEditTool.EditableValue.SitePageInterface = wb.utilities.inherit( PARENT,
	function() {
		delete this.setResultSet; // no use calling that function since result set is retrieved via AJAX
		PARENT.apply( this, arguments );
	}, {
	/**
	 * Information for which site this autocomplete interface should serve input suggestions
	 * @var wikibase.Site
	 */
	_site: null,

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface._init
	 *
	 * @param {jQuery} subject
	 * @param {Object} options
	 * @param {wikibase.Site} site as source for the page suggestions
	 */
	_init: function( subject, options, site ) {
		PARENT.prototype._init.apply( this, arguments );
		if( site ) {
			this.setSite( site );
		}
	},

	/**
	 * Allows to set the site, the pages should be selected from.
	 *
	 * @param wb.Site site
	 */
	setSite: function( site ) {
		if( this._site !== null && this._site.getId() === site.getId() ) {
			return; // no change
		}

		this.url = site.getApi();
		if ( this._inputElem !== null ) { // notify widget (if it exists already) of url change
			this._inputElem.data( 'suggester' ).options.ajax.url = site.getApi();
		}
		this._site = site;

		this._currentResults = []; // empty current suggestions...
		if( this.isInEditMode() ) {
			this._inputElem.autocomplete( 'search' ); // ...and get new suggestions

			/* // TODO: this should be done after "search" is finished, apparently, there is no callback for that currently...
			if( ! this.isValid() ) {
				this.setValue( '' );
			}
			*/
		}
	},

	/**
	 * @see wb.ui.PropertyEditTool.EditableValue.Interface.updateLanguageAttributes
	 *
	 * @param object language
	 */
	updateLanguageAttributes: function() {
		PARENT.prototype.updateLanguageAttributes.call( this );
		// apply input's language attributes or attributes according to user language
		if ( this._inputElem !== null ) {
			var lang = this._inputElem.attr( 'lang' );
			if ( lang === undefined ) {
				lang = mw.config.get( 'wgUserLanguage' );
			}
			var dir = this._inputElem.attr( 'dir' );
			if ( dir === undefined ) {
				if ( wb.getLanguages()[lang] !== undefined ) {
					dir = $.uls.data.getDir( lang );
				}
			}
			if ( dir === undefined ) {
				dir = 'auto'; // Shouldn't happen, but go figure
			}
			this._inputElem.data( 'suggester' ).menu.element
				.attr( 'lang', lang ).attr( 'dir', dir );
		}
	},

	/**
	 * Just a dummy to override the validation of the page
	 * For now the validation is done by the API only
	 * @param value String page
	 * @return String page
	 */
	validate: function( value ) {
		return value;
	},

	/**
	 * Returns the site set to select pages from.
	 *
	 * @return wb.Site site
	 */
	getSite: function() {
		return this._site;
	},

	_setValue_inNonEditMode: function( value ) {
		this._getValueContainer()
		.empty()
		.append( // insert link to site in site
			this._site && value !== '' ? this._site.getLinkTo( value ) : ''
		);
	},

	/**
	 * @see wb.ui.PropertyEditTool.EditableValue.AutocompleteInterface._destroy
	 */
	_destroy: function() {
		PARENT.prototype._destroy.call( this );
		this._site = null;
	}

} );

} )( mediaWiki, wikibase, jQuery );
