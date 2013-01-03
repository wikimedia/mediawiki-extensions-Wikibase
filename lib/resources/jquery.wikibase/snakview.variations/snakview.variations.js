/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, wb, $ ) {
	'use strict';

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
		 * 1. label in Snak type selector
		 * 2. Variation constructor
		 */
		registerVariation: function( forSnakType, variationConstructor ) {
			if( typeof forSnakType !== 'string' ) {
				throw new Error( 'Snak type required for registering a snakview variation' );
			}
			if( !$.isFunction( variationConstructor ) ) {
				throw new Error( 'A Variation constructor has to be given' );
			}
			variations[ forSnakType ] = variationConstructor;
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
		 * @param {jQuery} $variationViewPort
		 * @return jQuery.wikibase.snakview.variations.Variation|null
		 */
		newFromSnakType: function( snakType, $variationViewPort ) {
			if( typeof snakType !== 'string' ) {
				throw new Error( 'Snak type required for choosing a suitable variation' );
			}
			if( !SELF.hasVariation( snakType ) ) {
				return null;
			}
			return new ( SELF.getVariation( snakType ) )( $variationViewPort );
		}
	};

}( mediaWiki, wikibase, jQuery ) );
