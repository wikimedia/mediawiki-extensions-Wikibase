/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
	'use strict';

var MODULE = wb.serialization;

/**
 * Factory for creating serializers specific to certain objects, e.g. of the Wikibase data model.
 *
 * @constructor
 * @since 1.0
 */
var SELF = MODULE.SerializerFactory = function wbSerializerFactory() {};

/**
 * Array of arrays where the inner arrays holds two constructors. The first one the constructor
 * a serializer's output should be the instance of and the second one the actual serializer.
 * @type {Array[]}
 */
var store = [];

$.extend( SELF.prototype, {
	/**
	 * Returns a new serializer object suitable for serializing a specific object or a specific
	 * constructor's instances.
	 *
	 * @param {Object|Function} object
	 * @return {wikibase.serialization.Serializer}
	 */
	newSerializerFor: function( object ) {
		if( !object ) {
			throw new Error( 'Constructor or object expected' );
		}

		var Constructor = $.isFunction( object ) ? object : object.constructor;

		if( !$.isFunction( Constructor ) ) {
			throw new Error( 'No proper constructor provided for choosing a Serializer' );
		}

		for( var i = 0; i < store.length; i++ ) {
			if( store[i][0] === Constructor ) {
				return new store[i][1]();
			}
		}

		throw new Error( 'No suitable Serializer registered' );
	}
} );

/**
 * Registers a serializer for objects of a specific constructor.
 *
 * @param {Function} Serializer
 * @param {Function} Constructor
 */
SELF.registerSerializer = function( Serializer, Constructor ) {
	if( !$.isFunction( Constructor ) ) {
		throw new Error( 'No constructor (function) provided' );
	} else if( !( ( new Serializer() ) instanceof MODULE.Serializer ) ) {
		throw new Error( 'Given Serializer is not an implementation of '
			+ 'wb.serialization.Serializer' );
	}

	store.push( [Constructor, Serializer] );
};

}( wikibase, jQuery ) );
