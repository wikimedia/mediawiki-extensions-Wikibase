
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
	 */
	_languageMap: null,

	/**
	 * @inheritdoc
	 */
	getAll: function() {
		return this._languageMap ? Object.keys( this._languageMap ) : null;
	},

	/**
	 * @inheritdoc
	 */
	getName: function( code ) {
		return this._languageMap ? this._languageMap[ code ] : null;
	}
} );

}( mediaWiki, wikibase, jQuery ) );
