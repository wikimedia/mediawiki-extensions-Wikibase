
/**
 * @licence GNU GPL v2+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function( wb ) {
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
	 * @type {Object}
	 */
	_languageMap: null,

	/**
	 * @inheritdoc
	 */
	getAll: function() {
		return Object.keys( this._languageMap );
	},

	/**
	 * @inheritdoc
	 */
	getName: function( code ) {
		return this._languageMap[ code ];
	}
} );

}( wikibase, jQuery ) );
