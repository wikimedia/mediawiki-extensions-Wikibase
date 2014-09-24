/**
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
( function( wb, $ ) {
'use strict';

var PARENT = wb.RepoApi;

/**
 * Provides abstracted access functions for the Wikibase Repo Api handling and returning Wikibase
 * data model objects.
 * @constructor
 * @since 0.4
 * @todo Allow passing actual data model objects to the functions.
 * @todo Return RepoApiError objects when failing.
 */
wb.AbstractedRepoApi = util.inherit( 'wbAbstractedRepoApi', PARENT, {

	/**
	 * Gets one or more Entities.
	 *
	 * @param {String[]|String} ids
	 * @param {String[]|String} [props] Key(s) of property/ies to retrieve from the API
	 *                          default: null (will return all properties)
	 * @param {String[]}        [languages]
	 *                          default: null (will return results in all languages)
	 * @param {String[]|String} [sort] Key(s) of property/ies to sort on
	 *                          default: null (unsorted)
	 * @param {String}          [dir] Sort direction may be 'ascending' or 'descending'
	 *                          default: null (ascending)
	 * @return {jQuery.Promise} If successful, the first parameter of the done callbacks will be
	 *         an object with keys of the entity's IDs and values of the requested entities
	 *         represented as wb.datamodel.Entity objects. If a requested Entity does not exist, it will not
	 *         be represented in the result.
	 *
	 * @todo Requires more? tests!
	 */
	getEntities: function( ids, props, languages, sort, dir ) {
		return this._abstract(
			PARENT.prototype.getEntities.apply( this, arguments ),
			function( result ) {
				var entities = {},
					unserializer = ( new wb.serialization.SerializerFactory() ).newUnserializerFor(
						wb.datamodel.Entity
					);

				$.each( result.entities, function( id, entityData ) {
					if( entityData.missing === '' ) {
						return; // missing entity
					}

					var entity = unserializer.unserialize( entityData );
					entities[ entity.getId() ] = entity;
				} );

				return [ entities ];
			}
		);
	},

	/**
	 * Applies a callback function to the result of a successfully resolved promise, finally
	 * returning a new promise with the callback's result.
	 * @since 0.4
	 *
	 * @param {jQuery.Promise} apiPromise
	 * @param {Function} callbackForAbstraction
	 * @return {jQuery.Promise}
	 */
	_abstract: function( apiPromise, callbackForAbstraction ) {
		var deferred = $.Deferred(),
			self = this;

		apiPromise
		.done( function() {
			var args = callbackForAbstraction.apply( self, arguments );
			deferred.resolve.apply( deferred, args );
		} )
		.fail( deferred.reject );

		return deferred.promise();
	}
} );

}( wikibase, jQuery ) );
