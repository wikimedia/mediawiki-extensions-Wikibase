/**
 * @license GPL-2.0-or-later
 *
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( $, mw, wb ) {
	'use strict';

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

		this._deserializedEntityPromise = entityPromise.then( function ( entity ) {
			return getDeserializer().then( function ( entityDeserializer ) {
				return entityDeserializer.deserialize( entity );
			} );
		} );
	};

	$.extend( EntityInitializer.prototype, {

		/**
		 * @type {jQuery.Promise} Promise for wikibase.datamodel.Entity
		 */
		_deserializedEntityPromise: null,

		/**
		 * Retrieves an entity from mw.config.
		 *
		 * @return {Object} jQuery Promise
		 *         Resolved parameters:
		 *         - {wikibase.datamodel.Entity}
		 *         No rejected parameters.
		 */
		getEntity: function () {
			return this._deserializedEntityPromise;
		}
	} );

	function isThenable( arg ) {
		return typeof arg === 'object' && typeof arg.then === 'function';
	}

	/**
	 * Get entity from config
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

	/**
	 * @return {Object} jQuery promise
	 *         Resolved parameters:
	 *         - {wikibase.serialization.EntityDeserializer}
	 *         No rejected parameters.
	 */
	function getDeserializer() {
		var entityDeserializer = new wb.serialization.EntityDeserializer(),
			deferred = $.Deferred();

		var entityTypes = mw.config.get( 'wbEntityTypes' );
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

}( jQuery, mediaWiki, wikibase ) );
