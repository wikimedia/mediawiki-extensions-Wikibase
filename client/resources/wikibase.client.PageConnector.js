/**
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
( function ( wb ) {
	'use strict';

	/**
	 * PageConnector connects two articles easily.
	 *
	 * @constructor
	 *
	 * @param {wikibase.api.RepoApi} repoApi
	 * @param {string} firstSiteId
	 * @param {string} firstPageName
	 * @param {string} secondSiteId
	 * @param {string} secondPageName
	 */
	var PageConnector = function PageConnector(
		repoApi,
		firstSiteId,
		firstPageName,
		secondSiteId,
		secondPageName
	) {
		this._repoApi = repoApi;

		this._firstSiteId = firstSiteId;
		this._firstPageName = firstPageName;
		this._secondSiteId = secondSiteId;
		this._secondPageName = secondPageName;
	};

	$.extend( PageConnector.prototype, {
		/**
		 * @type wikibase.api.RepoApi
		 */
		_repoApi: null,

		/**
		 * @type string
		 */
		_firstSiteId: null,

		/**
		 * @type string
		 */
		_firstPageName: null,

		/**
		 * @type string
		 */
		_secondSiteId: null,

		/**
		 * @type string
		 */
		_secondPageName: null,

		/**
		 * Gets a list of pages that will also be linked with the first page. This may visualize
		 * potential side effects of a merge to users.
		 *
		 * @return {jQuery.promise}
		 */
		getNewlyLinkedPages: function () {
			var self = this,
				deferred = new $.Deferred();

			this._getEntityForPage( this._secondSiteId, this._secondPageName )
			.fail( deferred.reject )
			.done( function ( data ) {
				var entity, siteLinkCount;

				if ( data.entities[ '-1' ] ) {
					deferred.resolve( {} );
					return;
				}

				entity = self._extractEntity( data );

				// Count site links
				siteLinkCount = self._countSiteLinks( entity );

				deferred.resolve( siteLinkCount ? entity : {} );
			} );

			return deferred.promise();
		},

		/**
		 * Get the entity for a given page in case there is one
		 *
		 * @param {string} siteId
		 * @param {string} pageName
		 *
		 * @return {jQuery.Promise}
		 */
		_getEntityForPage: function ( siteId, pageName ) {
			return this._repoApi.getEntitiesByPage(
				siteId,
				pageName,
				[ 'info', 'sitelinks' ],
				'',
				true
			);
		},

		/**
		 * Get the (first) entity object from an API response.
		 *
		 * @param {Object} apiResult
		 *
		 * @return {Object|undefined} Entity as returned by the API
		 */
		_extractEntity: function ( apiResult ) {
			for ( var i in apiResult.entities ) {
				if ( apiResult.entities[ i ].sitelinks ) {
					return apiResult.entities[ i ];
				}
			}
		},

		/**
		 * Counts the number of sites attached to a given entity.
		 *
		 * @param {Object} entity
		 *
		 * @return {number}
		 */
		_countSiteLinks: function ( entity ) {
			var siteLinkCount = 0,
				i;

			for ( i in entity.sitelinks ) {
				if ( entity.sitelinks[ i ].site ) {
					siteLinkCount += 1;
				}
			}
			return siteLinkCount;
		},

		/**
		 * Links the two articles by either creating a new item, updating an existing one or merging two
		 * existing ones.
		 *
		 * @return {jQuery.Promise}
		 */
		linkPages: function () {
			var self = this,
				deferred = new $.Deferred();

			this._getEntityForPage( self._firstSiteId, self._firstPageName )
			.done( function ( data ) {
				// Use the normalized title from now on (e.g. for creating a new item with proper
				// titles).
				if ( data.normalized ) {
					self._firstPageName = data.normalized.n.to;
				}

				if ( !data.entities[ '-1' ] ) {
					var entity = self._extractEntity( data );

					// The first page has an entity attached, so link/ merge the second page with it
					self._linkOrMergeSecondPage( entity )
					.done( deferred.resolve )
					.fail( deferred.reject );
				} else {
					// There is no item for the first page ... maybe there's one for the second
					self._linkFirstPageOrCreateItem()
					.done( deferred.resolve )
					.fail( deferred.reject );
				}
			} )
			.fail( deferred.reject );

			return deferred.promise();
		},

		/**
		 * Links the second page with the given entity. If page is linked to an item already, a merge is
		 * performed.
		 *
		 * @param {Object} entity
		 *
		 * @return {jQuery.Promise}
		 */
		_linkOrMergeSecondPage: function ( entity ) {
			var self = this,
				deferred = new $.Deferred();

			this._getEntityForPage( self._secondSiteId, self._secondPageName )
			.done( function ( data ) {
				if ( data.normalized ) {
					// Use the normalized title from now on (e.g. for creating a new item with proper
					// titles).
					self._secondPageName = data.normalized.n.to;
				}

				if ( data.entities[ '-1' ] ) {
					// The second page has no item yet, so just link it with the given entity
					self._setSiteLink( entity, self._secondSiteId, self._secondPageName )
					.done( deferred.resolve )
					.fail( deferred.reject );
				} else {
					// The page already has an item... this means we have to perform a merge
					self._mergeEntities( entity, self._extractEntity( data ) )
					.done( deferred.resolve )
					.fail( deferred.reject );
				}
			} )
			.fail( deferred.reject );

			return deferred.promise();
		},

		/**
		 * If the second page has an item, it links the first page with the item of the second page. If
		 * not, a new item is created.
		 *
		 * @return {jQuery.Promise}
		 */
		_linkFirstPageOrCreateItem: function () {
			var self = this,
				deferred = new $.Deferred();

			this._getEntityForPage( self._secondSiteId, self._secondPageName )
			.fail( deferred.reject )
			.done( function ( data ) {
				// Use the normalized title from now on (e.g. for creating a new item with proper
				// titles).
				if ( data.normalized ) {
					self._secondPageName = data.normalized.n.to;
				}

				if ( data.entities[ '-1' ] ) {
					// Neither the first nor the second page have an item yet, create one
					self._createItem(
						self._firstSiteId,
						self._firstPageName,
						self._secondSiteId,
						self._secondPageName
					)
					.done( deferred.resolve )
					.fail( deferred.reject );
				} else {
					// There already is an entity with the second page linked, so just link the first
					// one
					var entity = self._extractEntity( data );

					self._setSiteLink( entity, self._firstSiteId, self._firstPageName )
					.done( deferred.resolve )
					.fail( deferred.reject );
				}
			} );

			return deferred.promise();
		},

		/**
		 * Links an item with a page.
		 *
		 * @param {Object} entity
		 * @param {string} siteId
		 * @param {string} pageName
		 *
		 * @return {jQuery.Promise}
		 */
		_setSiteLink: function ( entity, siteId, pageName ) {
			return this._repoApi.setSitelink(
				entity.id,
				entity.lastrevid,
				siteId,
				pageName
			);
		},

		/**
		 * Merges two entities.
		 *
		 * @param {Object} firstEntity
		 * @param {Object} secondEntity
		 *
		 * @return {jQuery.Promise}
		 */
		_mergeEntities: function ( firstEntity, secondEntity ) {
			var firstSiteLinkCount = this._countSiteLinks( firstEntity ),
				secondSiteLinkCount = this._countSiteLinks( secondEntity ),
				fromId,
				toId;

			// XXX: We could get all properties above and then use a more complete
			// comparison, maybe by abusing JSON.stringify to get real item sizes. That
			// *might* be a better estimate?!
			if ( firstSiteLinkCount <= secondSiteLinkCount ) {
				fromId = firstEntity.id;
				toId = secondEntity.id;
			} else {
				toId = firstEntity.id;
				fromId = secondEntity.id;
			}

			return this._repoApi.mergeItems(
				fromId,
				toId,
				// Ignore label and description conflicts, but fail on link conflicts
				[ 'label', 'description' ]
			);
		},

		/**
		 * Creates an item in the repository.
		 *
		 * @param {string} firstSiteId
		 * @param {string} firstPageName
		 * @param {string} secondSiteId
		 * @param {string} secondPageName
		 *
		 * @return {jQuery.Promise}
		 */
		_createItem: function ( firstSiteId, firstPageName, secondSiteId, secondPageName ) {
			// JSON data for the new entity
			var entityData = {
					labels: {},
					sitelinks: {}
				},
				wbSites = require( './wikibase.sites.js' ),
				firstSite = wbSites.getSite( firstSiteId ),
				secondSite = wbSites.getSite( secondSiteId );

			// Labels (page titles)
			entityData.labels[ firstSite.getLanguageCode() ] = {
				language: firstSite.getLanguageCode(),
				value: firstPageName
			};
			entityData.labels[ secondSite.getLanguageCode() ] = {
				language: secondSite.getLanguageCode(),
				value: secondPageName
			};

			// Sitelinks
			entityData.sitelinks[ firstSite.getId() ] = {
				site: firstSite.getId(),
				title: firstPageName
			};
			entityData.sitelinks[ secondSite.getId() ] = {
				site: secondSite.getId(),
				title: secondPageName
			};

			return this._repoApi.createEntity( 'item', entityData );
		}

	} );

	module.exports = wb.PageConnector = PageConnector;

}( wikibase ) );
