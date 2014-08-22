/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, $ ) {
	'use strict';

	var MODULE = wb.serialization;

	/**
	 * Factory for creating serializers and unserializers suitable for certain objects, e.g. of the
	 * Wikibase data model.
	 *
	 * @constructor
	 * @since 1.0
	 */
	var SELF = MODULE.SerializerFactory = function SerializerFactory() {};

	/**
	 * Array of arrays where the inner arrays holds two constructors. The first one the constructor
	 * a serializer's output should be the instance of and the second one the actual serializer.
	 * @type {Array[]}
	 */
	var serializers = [];

	/**
	 * Array of arrays where the inner arrays holds two constructors. The first one the constructor
	 * a unserializer's output should be the instance of and the second one the actual unserializer.
	 * @type {Array[]}
	 */
	var unserializers = [];

	/**
	 * Helper for building a new function for registering a factory member.
	 *
	 * @param {Array[]} store The factory's store.
	 * @param {Function} type Constructor newly registered factory members have to be instances of.
	 * @return {Function}
	 */
	function buildRegisterFn( store, type ) {
		return function( FactoryMember, constructor ) {
			if( !$.isFunction( constructor ) ) {
				throw new Error( 'No constructor (function) given' );
			}
			if( !( ( new FactoryMember() ) instanceof type ) ) {
				throw new Error( 'Given (un)serializer is not an implementation of '
					+ 'wb.serialization.(Un/S)erializer' );
			}

			store.push( [
				constructor,
				FactoryMember
			] );
		};
	}

	/**
	 * Helper for building a new function for finding the right factory member and creating a new
	 * instance of it.
	 *
	 * @param {Array[]} store The factory's store.
	 * @param {string} storeSubject The subject of the store, used in error message descriptions.
	 * @return {Function}
	 */
	function buildLookupFn( store, storeSubject ) {
		return function( constructor, options ) {
			if( !$.isFunction( constructor ) ) {
				throw new Error( 'No proper constructor has been provided for choosing a '
					+ storeSubject );
			}

			// find constructor matching the given one and create new instance of factory member
			// responsible for handling instances of that given constructor:
			for( var i in store ) {
				if( store[i][0] === constructor ) {
					return new store[i][1]( options );
				}
			}
			throw new Error( 'No suitable ' + storeSubject + ' has been registered' );
		};
	}

	$.extend( SELF.prototype, {
		/**
		 * Returns a new serializer object suitable for a given object or for a given constructor's
		 * instances.
		 *
		 * @param {Object|Function} object
		 * @param {Object} [options]
		 * @return {wikibase.serialization.Serializer}
		 */
		newSerializerFor: ( function() {
			var lookupFn = buildLookupFn( serializers, 'Serializer' );

			// Build a function which will do the normal lookup but also allows passing and object
			// as first parameter. In that case we have to get the object's constructor.
			return function( object, options ) {
				if( !object ) {
					throw new Error( 'Constructor or object expected' );
				}
				var constructorOfSerialized = $.isFunction( object ) ? object : object.constructor;
				return lookupFn( constructorOfSerialized, options );
			};
		}() ),

		/**
		 * Returns a new unserializer object suitable for unserializing some data into an instance
		 * of the given constructor.
		 *
		 * @param {Function} constructor
		 * @param {Object} [options]
		 * @return {wikibase.serialization.Unserializer}
		 */
		newUnserializerFor: buildLookupFn( unserializers, 'Unserializer' )
	} );

	/**
	 * Registers a serializer for objects of a certain given constructor.
	 *
	 * @param {wikibase.serialization.Serializer} serializer
	 * @param {Function} constructor
	 */
	SELF.registerSerializer = buildRegisterFn( serializers, MODULE.Serializer );

	/**
	 * Registers a unserializer for objects of a certain given constructor.
	 *
	 * @param {wikibase.serialization.Unserializer} unserializer
	 * @param {Function} constructor
	 */
	SELF.registerUnserializer = buildRegisterFn( unserializers, MODULE.Unserializer );

}( wikibase, jQuery ) );
