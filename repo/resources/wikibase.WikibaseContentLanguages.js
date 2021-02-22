/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb ) {
	'use strict';

	var MODULE = wb;
	var PARENT = util.ContentLanguages;
	var contentLanguages = require( './contentLanguages.json' ).monolingualtext;

	/**
	 * @constructor
	 */
	var SELF = MODULE.WikibaseContentLanguages = util.inherit(
		'WbContentLanguages',
		PARENT,
		function () {
			this._languageCodes = contentLanguages;
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
		getAll: function () {
			return this._languageCodes;
		},

		/**
		 * @inheritdoc
		 */
		getName: function ( code ) {
			return this._languageMap ? this._languageMap[ code ] : null;
		},

		getAllPairs: function () {
			var map = {},
				self = this;

			this._languageCodes.forEach( function ( languageCode ) {
				map[ languageCode ] = self.getName( languageCode );
			} );

			return map;
		}
	} );

}( wikibase ) );
