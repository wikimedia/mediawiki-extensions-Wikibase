/**
 * @license GPL-2.0-or-later
 *
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( wb ) {
	'use strict';

	var config = require( './config.json' ),
		serialization = require( 'wikibase.serialization' );

	/**
	 * Entity initializer.
	 * Deserializes the entity passed to JavaScript via mw.config variable or
	 * as entity object promise.
	 *
	 * @constructor
	 *
	 * @param {string|Thenable} arg Config variable name or entity object promise
	 *
	 * @throws {Error} if required parameter is not specified properly.
	 */
	var EntityInitializer = wb.EntityInitializer = function ( arg ) {
		var entityPromise;
		if ( typeof arg === 'string' ) {
			entityPromise = getFromConfig( arg );
		} else if ( isThenable( arg ) ) {
			entityPromise = arg;
		} else {
			throw new Error(
				'Config variable name or entity promise needs to be specified'
			);
		}

		this._entityPromise = entityPromise;
	};

	EntityInitializer.newFromEntityLoadedHook = function () {
		var entityPromise = $.Deferred( function ( deferred ) {
			mw.hook( 'wikibase.entityPage.entityLoaded' ).add( function ( entity ) {
				deferred.resolve( entity );
			} );
		} ).promise();

		return new EntityInitializer( entityPromise );
	};

	$.extend( EntityInitializer.prototype, {

		/**
		 * @type {jQuery.Promise} Promise for serialized entity
		 */
		_entityPromise: null,

		/**
		 * Retrieves an entity from mw.config.
		 *
		 * @return {Object} jQuery Promise
		 *         Resolved parameters:
		 *         - {wikibase.datamodel.Entity}
		 *         No rejected parameters.
		 */
		getEntity: function () {
			var self = this;

			return this._entityPromise.then( function ( entity ) {
				return self._getDeserializer().then( function ( entityDeserializer ) {
					return entityDeserializer.deserialize( entity );
				} );
			} );
		},

		/**
		 * @return {Object} jQuery promise
		 *         Resolved parameters:
		 *         - {serialization.EntityDeserializer}
		 *         No rejected parameters.
		 */
		_getDeserializer: function () {
			var entityDeserializer = new serialization.EntityDeserializer(),
				deferred = $.Deferred();

			var entityTypes = config.entityTypes;
			var modules = [];
			var typeNames = [];
			entityTypes.types.forEach( function ( type ) {
				var deserializerFactoryFunction = entityTypes[ 'deserializer-factory-functions' ][ type ];
				if ( deserializerFactoryFunction ) {
					modules.push( deserializerFactoryFunction );
					typeNames.push( type );
				}
			} );
			mw.loader.using( modules, function ( require ) {
				modules.forEach( function ( module, index ) {
					entityDeserializer.registerStrategy(
						require( module )(),
						typeNames[ index ]
					);
				} );

				deferred.resolve( entityDeserializer );
			} );
			return deferred.promise();
		}
	} );

	function isThenable( arg ) {
		return typeof arg === 'object' && typeof arg.then === 'function';
	}

	/**
	 * Get entity from config
	 *
	 * @param configVarName
	 * @return {jQuery.Promise}
	 *         Resolved parameters:
	 *         - {object} Entity object
	 *         No rejected parameters.
	 */
	function getFromConfig( configVarName ) {
		return $.Deferred( function ( deferred ) {
			mw.hook( 'wikipage.content' ).add( function () {
				var serializedEntity = mw.config.get( configVarName );

				if ( serializedEntity === null ) {
					deferred.reject();
					return;
				}

				deferred.resolve( JSON.parse( serializedEntity ) );
			} );
		} ).promise();
	}

}( wikibase ) );
