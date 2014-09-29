/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, $ ) {
	'use strict';

var MODULE = wb.serialization;

/**
 * Factory for creating deserializers specific to certain objects, e.g. of the Wikibase data model.
 * @constructor
 * @since 2.0
 */
var SELF = MODULE.DeserializerFactory = function wbDeserializerFactory() {};

/**
 * Array of arrays where the inner arrays holds two constructors. The first one the constructor a
 * deserializer's output should be the instance of and the second one the actual deserializer.
 * @type {Array[]}
 */
var store = [];

$.extend( SELF.prototype, {
	/**
	 * Returns a new deserializer object suitable for deserializing some data into an instance of
	 * a specific constructor.
	 *
	 * @param {Function} Constructor
	 * @return {wikibase.serialization.Deserializer}
	 */
	newDeserializerFor: function( Constructor ) {
		if( !$.isFunction( Constructor ) ) {
			throw new Error( 'No proper constructor provided for choosing a Deserializer' );
		}

		for( var i = 0; i < store.length; i++ ) {
			if( store[i][0] === Constructor ) {
				return new store[i][1]();
			}
		}

		throw new Error( 'No suitable Deserializer registered' );
	}
} );

/**
 * Registers a deserializer for objects of a specific constructor.
 *
 * @param {Function} Deserializer
 * @param {Function} Constructor
 */
SELF.registerDeserializer = function( Deserializer, Constructor ) {
	if( !$.isFunction( Constructor ) ) {
		throw new Error( 'No constructor (function) provided' );
	} else if( !( ( new Deserializer() ) instanceof MODULE.Deserializer ) ) {
		throw new Error( 'Given Deserializer is not an implementation of '
			+ 'wb.serialization.Deserializer' );
	}

	store.push( [Constructor, Deserializer] );
};

}( wikibase, jQuery ) );
