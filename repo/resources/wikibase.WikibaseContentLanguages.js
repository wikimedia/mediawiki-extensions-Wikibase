
/**
 * @licence GNU GPL v2+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function( mw, wb, $ ) {
	'use strict';

var MODULE = wb;
var PARENT = util.ContentLanguages;

/**
 * @constructor
 */
var SELF = MODULE.WikibaseContentLanguages = util.inherit(
	'WbContentLanguages',
	PARENT,
	function() {
		this._languageMap = mw.config.get( 'wgULSLanguages' );
	}
);

$.extend( SELF.prototype, {
	/**
	 * @type {Object|null}
	 * @private
	 */
	_languageMap: null,

	/**
	 * @type {string[]|null}
	 * @private
	 */
	_languageCodes: null,

	/**
	 * @inheritdoc
	 */
	getAll: function() {
		// Cache language codes
		if( !this._languageCodes && this._languageMap ) {
			this._languageCodes = $.map( this._languageMap, function( _, key ) {
				return key;
			} );
		}
		return this._languageCodes;
	},

	/**
	 * @inheritdoc
	 */
	getName: function( code ) {
		return this._languageMap ? this._languageMap[ code ] : null;
	}
} );

}( mediaWiki, wikibase, jQuery ) );
