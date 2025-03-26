/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb ) {
	'use strict';

	var MODULE = wb;
	var PARENT = util.ContentLanguages;
	var monolingualTextLanguages = require( './contentLanguages.json' ).monolingualtext;
	var termLanguages = require( './contentLanguages.json' ).term;

	MODULE.WikibaseContentLanguages = class WbContentLanguages extends PARENT {
		/**
		 * @param {string[]|null} contentLanguages
		 * @param {Function} getName
		 * @private
		 */
		constructor( contentLanguages, getName ) {
			super();
			if ( !Array.isArray( contentLanguages ) ) {
				throw new Error( 'Required parameter "contentLanguages" is not specified properly.' );
			}
			if ( typeof getName !== 'function' ) {
				throw new Error( 'Required parameter "getName" is not specified properly.' );
			}

			/**
			 * @type {string[]|null}
			 * @private
			 */
			this._languageCodes = contentLanguages;
			/**
			 * @type {Function}
			 * @private
			 */
			this._getName = getName;
		}

		/**
		 * @inheritdoc
		 */
		getAll() {
			return this._languageCodes;
		}

		/**
		 * @inheritdoc
		 */
		getName( code ) {
			return this._getName( code );
		}

		getLanguageNameMap() {
			var map = {},
				self = this;

			this._languageCodes.forEach( ( languageCode ) => {
				map[ languageCode ] = self.getName( languageCode );
			} );

			return map;
		}

		static getMonolingualTextLanguages() {
			return new this( monolingualTextLanguages, wb.getLanguageNameByCode );
		}

		static getTermLanguages() {
			return new this( termLanguages, wb.getLanguageNameByCodeForTerms );
		}
	};

}( wikibase ) );
