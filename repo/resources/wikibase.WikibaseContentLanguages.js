/**
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( mw, wb, $ ) {
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
				this._languageCodes = $.map( this._languageMap, function ( val, key ) {
					return key;
				} );
				this._languageCodes = $.grep( this._languageCodes, function ( code ) {
					// Make sure this is a subset of the language codes returned by
					// WikibaseRepo::getMonolingualTextLanguages
					// We don't want to have language codes in the suggester that are not
					// supported by the backend. The other way round is currently acceptable,
					// but will be fixed in T124758.
					return [ 'de-formal', 'nl-informal' ].indexOf( code ) === -1;
				} );
			}
			return this._languageCodes;
		},

		/**
		 * @inheritdoc
		 */
		getName: function ( code ) {
			return this._languageMap ? this._languageMap[ code ] : null;
		}
	} );

}( mediaWiki, wikibase, jQuery ) );
