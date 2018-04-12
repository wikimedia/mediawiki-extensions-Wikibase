/**
 * @license GPL-2.0-or-later
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
			if ( !this._languageCodes ) {
				this._languageCodes = this._languageMap ? Object.keys( this._languageMap ) : [];

				// FIXME: This must be kept in sync with WikibaseRepo::getMonolingualTextLanguages()
				// in the backend! Introduce a ResourceLoader module for this!
				this._languageCodes = this._languageCodes.concat( [
					// Special ISO 639-2 codes
					'und', 'mis', 'mul', 'zxx',

					// Other valid codes without MediaWiki localization
					'abe', 'ami', 'bnn', 'brx', 'chn', 'cnr', 'cop', 'ett', 'eya', 'fkv', 'fos',
					'fr-ca', 'frm', 'fro', 'fuf', 'gez', 'hai', 'kjh', 'koy', 'lag', 'lkt', 'lld',
					'mnc', 'moe', 'non', 'nr', 'nxm', 'ood', 'otk', 'pjt', 'ppu', 'pwn', 'pyu',
					'quc', 'sjd', 'sju', 'smn', 'sms', 'ssf', 'trv', 'tzl', 'umu', 'uun', 'xpu',
					'yap', 'zun'
				] );

				// FIXME: This does not remove all codes WikibaseRepo::getMonolingualTextLanguages()
				// removes in the backend!
				$.grep( this._languageCodes, function ( code ) {
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
