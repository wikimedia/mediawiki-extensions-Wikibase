/**
 * JavaScript for a part of an editable property value
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 * @author Daniel Werner
 */
( function( mw, wb, $, undefined ) {
'use strict';
var $PARENT = wb.ui.PropertyEditTool.EditableValue.AutocompleteInterface;

/**
 * Serves the input interface to choose a wiki page from some MediaWiki installation as part of an
 * editable value
 * @constructor
 * @see wb.ui.PropertyEditTool.EditableValue.AutocompleteInterface
 * @since 0.1
 *
 * @param jQuery subject
 */
wb.ui.PropertyEditTool.EditableValue.SitePageInterface = wb.utilities.inherit( $PARENT,
	function() {
		delete this.setResultSet; // no use calling that function since result set is retrieved via AJAX
		$PARENT.apply( this, arguments );
	}, {
	/**
	 * Information for which site this autocomplete interface should serve input suggestions
	 * @var wikibase.Site
	 */
	_site: null,

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface._init
	 *
	 * @param site wikibase.Site as source for the page suggestions
	 */
	_init: function( subject, site ) {
		$PARENT.prototype._init.apply( this, arguments );
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
		this._site = site;

		this._currentResults = []; // empty current suggestions...
		if( this.isInEditMode() ) {
			this._inputElem.autocomplete( "search" ); // ...and get new suggestions

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
	updateLanguageAttributes: function( language ) {
		$PARENT.prototype.updateLanguageAttributes.call( this, language );
		if ( this._isInEditMode ) {
			// updating language attributes of auto-suggestion menu
			this._inputElem.data( 'autocomplete' ).menu.element
				.attr( 'lang', language.code ).attr( 'dir',language.dir );
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
		$PARENT.prototype._destroy.call( this );
		this._site = null;
	}

} );

} )( mediaWiki, wikibase, jQuery );
