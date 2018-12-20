/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb ) {
	'use strict';

	var MODULE = wb;
	var PARENT = util.ContentLanguages;

	/**
	 * @constructor
	 */
	var SELF = MODULE.WikibaseContentLanguages = util.inherit(
		'WbContentLanguages',
		PARENT,
		function () {
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
			// Cache language codes
			if ( !this._languageCodes && this._languageMap ) {
				this._languageCodes = Object.keys( this._languageMap );
				this._languageCodes = this._languageCodes.filter( function ( code ) {
					// Make sure this is a subset of the language codes returned by
					// WikibaseRepo::getMonolingualTextLanguages
					// We don't want to have language codes in the suggester that are not
					// supported by the backend. The other way round is currently acceptable,
					// but will be fixed in T124758.
					return [ 'de-formal', 'es-formal', 'hu-formal', 'nl-informal' ].indexOf( code ) === -1;
				} );
			}
			return this._languageCodes;
		},

		/**
		 * @inheritdoc
		 */
		getName: function ( code ) {
			return this._languageMap ? this._languageMap[ code ] : null;
		},

		/**
		 * @inheritdoc
		 */
		getAllPairs: function () {
			return this._deepClone( this._languageMap );
		},

		_deepClone: function ( original ) {
			return JSON.parse( JSON.stringify( original ) );
		}
	} );

}( wikibase ) );
