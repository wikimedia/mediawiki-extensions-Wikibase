( function () {
	'use strict';
	/**
	 * Map of `Snak` type IDs and related `Variation`s required for representing different kinds of
	 * `Snaks` with `jQuery.wikibase.snakview`.
	 *
	 * @property {Object}
	 * @ignore
	 */
	var variations = {};

	/**
	 * Store for `jQuery.wikibase.snakview.variations.Variation` objects.
	 *
	 * @class jQuery.wikibase.snakview.variations
	 * @singleton
	 * @license GPL-2.0-or-later
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 */
	module.exports = {
		/**
		 * Registers a new `jQuery.wikibase.snakview.variations.Variation` definition to enable
		 * using a specific `Snak` type within `jQuery.wikibase.snakview`. Acts like the
		 * `jQuery.Widget` factory.
		 *
		 * @param {string} snakType The type of the snak
		 * @param {Variation} variation The constructor / class definition for the variation
		 */
		registerVariation( snakType, variation ) {
			variations[ snakType ] = variation;
		},

		/**
		 * Returns all `Snak` types which can be represented by the `snakview` since there is a
		 * `Variation` constructor for presenting them.
		 *
		 * @return {string[]}
		 */
		getCoveredSnakTypes: function () {
			var types = [];

			for ( var key in variations ) {
				if ( Object.prototype.hasOwnProperty.call( variations, key ) ) {
					types.push( key );
				}
			}
			return types;
		},

		/**
		 * Returns the constructor of the `Variation` used to represent a particular kind of `Snak`
		 * within a `jQuery.wikibase.snakview`.
		 *
		 * @param {string} snakType
		 * @return {Variation|*}
		 */
		getVariation: function ( snakType ) {
			return variations[ snakType ] || null;
		}
	};

}() );
