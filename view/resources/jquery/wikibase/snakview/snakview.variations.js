( function ( $, util ) {
	'use strict';

	// TODO: Resolve namespace initialization
	$.wikibase = $.wikibase || {};
	$.wikibase.snakview = $.wikibase.snakview || {};

	// Backup components initialized already to re-apply them below:
	var existingVariations = $.wikibase.snakview.variations || {};

	/**
	 * Map of `Snak` type IDs and related `Variation`s required for representing different kinds of
	 * `Snaks` with `jQuery.wikibase.snakview`.
	 * @property {Object}
	 * @ignore
	 */
	var variations = {};

	/**
	 * Store for `jQuery.wikibase.snakview.variations.Variation` objects.
	 * @class jQuery.wikibase.snakview.variations
	 * @singleton
	 * @license GPL-2.0+
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 */
	var SELF = $.wikibase.snakview.variations = {
		/**
		 * Registers a new `jQuery.wikibase.snakview.variations.Variation` definition to enable
		 * using a a specific `Snak` type within `jQuery.wikibase.snakview`. Acts like the
		 * `jQuery.Widget` factory.
		 *
		 * @param {wikibase.datamodel.Snak} snakConstructor The constructor of the `Snak` the
		 *        `Variation` applies to.
		 * @param {Function|string|Object} baseOrDefinition Constructor or name of the `Variation`
		 *        the new `Variation` should be based on. The parameter may be omitted resulting in
		 *        the it being regarded the `definition` and `base` defaulting to
		 *        `jQuery.snakview.variations.Variation`.
		 * @param {Object} [definition] The new `Variation`'s definition (new members and members
		 *        overwriting the base `Variation`s members).
		 * @return {jQuery.snakview.variations.Variation} The new `Variation`'s constructor.
		 */
		variation: function ( snakConstructor, baseOrDefinition, definition ) {
			if ( !$.isFunction( snakConstructor ) || !snakConstructor.TYPE ) {
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
				if ( variations.hasOwnProperty( key ) ) {
					types.push( key );
				}
			}
			return types;
		},

		/**
		 * Returns whether a `Variation` constructor for representing a particular kind of `Snak`
		 * within a `jQuery.wikibase.snakview` is registered.
		 *
		 * @param {string} snakType
		 * @return {boolean}
		 */
		hasVariation: function ( snakType ) {
			return snakType in variations;
		},

		/**
		 * Returns the constructor of the `Variation` used to represent a particular kind of `Snak`
		 * within a `jQuery.wikibase.snakview`.
		 *
		 * @param {string} snakType
		 * @return {jQuery.wikibase.snakview.variations.Variation|*}
		 */
		getVariation: function ( snakType ) {
			return variations[ snakType ] || null;
		},

		/**
		 * Returns a `Variation` instance used by a `jQuery.wikibase.snakview` for representing a
		 * particular kind of `Snak`.
		 *
		 * @param {string} snakType
		 * @param {jQuery.wikibase.snakview.ViewState} viewState
		 * @param {jQuery} $variationViewPort
		 * @return {jQuery.wikibase.snakview.variations.Variation|null}
		 */
		newFromSnakType: function ( snakType, viewState, $variationViewPort ) {
			if ( typeof snakType !== 'string' ) {
				throw new Error( 'Snak type required for choosing a suitable variation' );
			}
			if ( !SELF.hasVariation( snakType ) ) {
				return null;
			}
			return new ( SELF.getVariation( snakType ) )( viewState, $variationViewPort );
		}
	};

	$.extend( $.wikibase.snakview.variations, existingVariations );

}( jQuery, util ) );
