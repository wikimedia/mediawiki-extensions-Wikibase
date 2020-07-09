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
	var SELF = {
		/**
		 * Registers a new `jQuery.wikibase.snakview.variations.Variation` definition to enable
		 * using a specific `Snak` type within `jQuery.wikibase.snakview`. Acts like the
		 * `jQuery.Widget` factory.
		 *
		 * @param {datamodel.Snak} snakConstructor The constructor of the `Snak` the
		 *        `Variation` applies to.
		 * @param {Function|string|Object} baseOrDefinition Constructor or name of the `Variation`
		 *        the new `Variation` should be based on. The parameter may be omitted resulting in
		 *        the it being regarded the `definition` and `base` defaulting to
		 *        `jQuery.snakview.variations.Variation`.
		 * @param {Object} [definition] The new `Variation`'s definition (new members and members
		 *        overwriting the base `Variation`s members).
		 * @return {Variation} The new `Variation`'s constructor.
		 */
		variation: function ( snakConstructor, baseOrDefinition, definition ) {
			if ( typeof snakConstructor !== 'function' || !snakConstructor.TYPE ) {
				throw new Error( 'Snak constructor required for registering a snakview variation' );
			}

			if ( !definition ) {
				definition = baseOrDefinition;
				baseOrDefinition = SELF.Variation;
			} else if ( typeof baseOrDefinition === 'string' ) {
				baseOrDefinition = SELF.getVariation( baseOrDefinition );
			}

			var snakType = snakConstructor.TYPE,
				variationName = 'WbSnakviewVariations_' + snakType; // name for constructor

			var Variation = util.inherit( variationName, baseOrDefinition, $.extend(
				{ variationBaseClass: 'wikibase-snakview-variation-' + snakType + 'snak' },
				definition,
				{ // we don't want to allow to overwrite this one via the definition
					variationSnakConstructor: snakConstructor
				}
			) );

			// TODO: store them in some public place as well ( have to decide on where exactly)
			variations[ snakType ] = Variation;
			return Variation;
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

	module.exports = SELF;

}() );
