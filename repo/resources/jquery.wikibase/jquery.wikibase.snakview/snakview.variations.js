/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, wb, $ ) {
	'use strict';

	var MODULE = $.wikibase.snakview.variations;

	/**
	 * Map of snak type IDs and related variations required for representing different kinds of
	 * Snaks with jQuery.snakview.
	 * @type {jQuery.wikibase.snakview.variations.Variation[]}
	 */
	var variations = {};

	/**
	 * @type Object
	 * @since 0.4
	 */
	var SELF = $.wikibase.snakview.variations = {
		/**
		 * Registers a new variation definition to allow usage of a certain Snak type within
		 * jQuery.snakview. This is a factory similar to the jQuery widget factory.
		 *
		 * @since 0.4
		 *
		 * @param {wb.Snak} snakConstructor The constructor of the Snak the variation is made for.
		 * @param {Function|String} [base] Constructor or name of the variation the new variation
		 *        should be based on. By default this is jQuery.snakview.variations.Variation.
		 * @param {Object} variationDefinition of the variation.
		 *
		 * @return {jQuery.snakview.variations.Variation} the new variation constructor.
		 */
		variation: function( snakConstructor, base, variationDefinition ) {
			if( !$.isFunction( snakConstructor ) || !snakConstructor.TYPE ) {
				throw new Error( 'Snak constructor required for registering a snakview variation' );
			}

			if( !variationDefinition ) {
				variationDefinition = base;
				base = MODULE.Variation;
			} else if( typeof base === 'string' ) {
				base = SELF.getVariation( base );
			}

			var snakType = snakConstructor.TYPE,
				variationName = 'WbSnakviewVariations_' + snakType; // name for constructor

			var Variation = wb.utilities.inherit( variationName, base, $.extend(
				{ variationBaseClass: 'wb-snakview-variation-' + snakType + 'snak' },
				variationDefinition,
				{ // we don't want to allow to overwrite this one via the definition
					variationSnakConstructor: snakConstructor
				}
			) );

			// TODO: store them in some public place as well ( have to decide on where exactly)
			variations[ snakType ] = Variation;
			return Variation;
		},

		/**
		 * Returns all Snak types which can be represented by the snakview since there is a
		 * Variation constructor for presenting them.
		 *
		 * @since 0.4
		 *
		 * @return String[]
		 */
		getCoveredSnakTypes: function() {
			var types = [];

			for( var key in variations ) {
				if( variations.hasOwnProperty( key ) ) {
					types.push( key );
				}
			}
			return types;
		},

		/**
		 * Returns whether there is a suitable 'variation' constructor for representing a certain
		 * kind of Snak with a jQuery.snakview.
		 *
		 * @since 0.4
		 *
		 * @param snakType
		 * @return {Boolean}
		 */
		hasVariation: function( snakType ) {
			return snakType in variations;
		},

		/**
		 * Returns the variation constructor required for representing a certain kind of Snak with a
		 * jQuery.snakview.
		 *
		 * @since 0.4
		 *
		 * @param snakType
		 * @return {jQuery.wikibase.snakview.variations.Variation|*}
		 */
		getVariation: function( snakType ) {
			return variations[ snakType ] || null;
		},

		/**
		 * Returns the variation required by a jQuery.snakview for representing a certain kind of
		 * Snak, takes the Snak type as criteria for choosing the related variation.
		 *
		 * @since 0.4
		 *
		 * @param {String} snakType
		 * @param {jQuery.wikibase.snakview.ViewState} viewState
		 * @param {jQuery} $variationViewPort
		 * @return jQuery.wikibase.snakview.variations.Variation|null
		 */
		newFromSnakType: function( snakType, viewState, $variationViewPort ) {
			if( typeof snakType !== 'string' ) {
				throw new Error( 'Snak type required for choosing a suitable variation' );
			}
			if( !SELF.hasVariation( snakType ) ) {
				return null;
			}
			return new ( SELF.getVariation( snakType ) )( viewState, $variationViewPort );
		}
	};

}( mediaWiki, wikibase, jQuery ) );
