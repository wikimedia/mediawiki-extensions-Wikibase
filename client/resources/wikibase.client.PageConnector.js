/**
 * JavaScript that allows connecting two articles easily.
 *
 * @since 0.5
 *
 * @author Marius Hoch < hoo@online.de >
 */
( function( wb, $ ) {
'use strict';

/**
 * @param {string} firstSiteId
 * @param {string} firstPageName
 * @param {string} secondSiteId
 * @param {string} secondPageName
 */
wb.PageConnector = function PageConnector( firstSiteId, firstPageName, secondSiteId, secondPageName ) {
	this.firstSiteId = firstSiteId;
	this.firstPageName = firstPageName;
	this.secondSiteId = secondSiteId;
	this.secondPageName = secondPageName;
	this.repoApi = new wb.RepoApi();
};

$.extend( wb.PageConnector.prototype, {
	/**
	 * @type wb.RepoApi
	 */
	repoApi: null,

	/**
	 * @type string
	 */
	firstSiteId: null,

	/**
	 * @type string
	 */
	firstPageName: null,

	/**
	 * @type string
	 */
	secondSiteId: null,

	/**
	 * @type string
	 */
	secondPageName: null,

	/**
	 * Get the entity for a given page in case there is one
	 *
	 * @param {string} siteId
	 * @param {string} pageName
	 *
	 * @return {jQuery.Promise}
	 */
	_getEntityForPage: function( siteId, pageName ) {
		return this.repoApi.getEntitiesByPage(
			siteId,
			pageName,
			['info', 'sitelinks'],
			'',
			'sitelinks',
			'ascending',
			true
		);
	},

	/**
	 * Count the number of sites attached to a given entity
	 *
	 * @param {object} entity
	 *
	 * @return {number}
	 */
	_countSiteLinks: function( entity ) {
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
	 * Get the (first) entity object from an API response.
	 *
	 * @param {object} apiResult
	 *
	 * @return {Object|undefined} Entity as returned by the API
	 */
	_getEntity: function( apiResult ) {
		for ( var i in apiResult.entities ) {
			if ( apiResult.entities[ i ].sitelinks ) {
				return apiResult.entities[ i ];
			}
		}
	},

	/**
	 * Link an item with a page and set the page name as label for the item.
	 *
	 * @param {object} entity
	 * @param {string} siteId
	 * @param {string} pageName
	 *
	 * @return {jQuery.promise}
	 */
	_setSiteLinkLabel: function( entity, siteId, pageName ) {
		var self = this,
			deferred = new $.Deferred();

		self.repoApi.setSitelink(
			entity.id,
			entity.lastrevid,
			siteId,
			pageName
		)
		.done( function() {
			// Also set the label
			self.repoApi.setLabel(
				entity.id,
				'',
				pageName,
				wb.getSite( siteId ).getLanguageCode()
			)
			.done( deferred.resolve )
			.fail( deferred.reject );
		} )
		.fail( deferred.reject );

		return deferred.promise();
	},

	/**
	 * Get a list of pages that will also be linked with the first page.
	 * This is needed to visualize the potential side effects of a merge
	 * to users.
	 *
	 * @return {jQuery.promise}
	 */
	getNewlyLinkedPages: function() {
		var self = this,
			deferred = new $.Deferred();

		this._getEntityForPage( this.secondSiteId, this.secondPageName )
		.fail( deferred.reject )
		.done( function( data ) {
			var entity, siteLinkCount;

			if ( data.entities['-1'] ) {
				deferred.resolve( {} );
				return;
			}
			entity = self._getEntity( data );

			// Count site links
			siteLinkCount = self._countSiteLinks( entity );

			if ( siteLinkCount >= 1 ) {
				deferred.resolve( entity );
			} else {
				deferred.resolve( {} );
			}
		} );

		return deferred.promise();
	},

	/**
	 * @param {string} firstSiteId
	 * @param {string} firstPageName
	 * @param {string} secondSiteId
	 * @param {string} secondPageName
	 *
	 * @return {jQuery.Promise}
	 */
	_createItem: function( firstSiteId, firstPageName, secondSiteId, secondPageName ) {
		// JSON data for the new entity
		var entityData = {
				labels: {},
				sitelinks: {}
			},
			firstSite = wb.getSite( firstSiteId ),
			secondSite = wb.getSite( secondSiteId );

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
		entityData.sitelinks[ firstSite.getGlobalSiteId() ] = {
			site: firstSite.getGlobalSiteId(),
			title: firstPageName
		};
		entityData.sitelinks[ secondSite.getGlobalSiteId() ] = {
			site: secondSite.getGlobalSiteId(),
			title: secondPageName
		};

		return this.repoApi.createEntity( 'item', entityData );
	},

	/**
	 * Links the two articles by either creating a new item, updating an
	 * existing one or merging two existing ones.
	 *
	 * @return {jQuery.Promise}
	 */
	linkPages: function() {
		var self = this,
			deferred = new $.Deferred();

		this._getEntityForPage( self.firstSiteId, self.firstPageName )
		.done( function( data ) {
			// Use the normalized title from now on (eg. for creating a new item with proper titles)
			if ( data.normalized ) {
				self.firstPageName = data.normalized.n.to;
			}

			if ( !data.entities['-1'] ) {
				var entity = self._getEntity( data );

				// The first page has an entity attached, so link/ merge the second page with it
				self._linkOrMergeSecondPage( entity )
				.done( deferred.resolve )
				.fail( deferred.reject );
			} else {
				// There is no item for the first page ... maybe there's one for the second
				self._onFirstPageNoItem()
				.done( deferred.resolve )
				.fail( deferred.reject );
			}
		} )
		.fail( deferred.reject );

		return deferred.promise();
	},

	/**
	 * Called in case there's no item for the first page yet. If the second page has an item, it
	 * links the first page with the item of the second page. If not, it creates a new item.
	 *
	 * @return {jQuery.promise}
	 */
	_onFirstPageNoItem: function() {
		var self = this,
			deferred = new $.Deferred();

		this._getEntityForPage( self.secondSiteId, self.secondPageName )
		.fail( deferred.reject )
		.done( function( data ) {
			// Use the normalized title from now on (eg. for creating a new item with proper titles)
			if ( data.normalized ) {
				self.secondPageName = data.normalized.n.to;
			}

			if ( data.entities['-1'] ) {
				// Neither the first nor the second page have an item yet, create one
				self._createItem(
					self.firstSiteId,
					self.firstPageName,
					self.secondSiteId,
					self.secondPageName
				)
				.done( deferred.resolve )
				.fail( deferred.reject );
			} else {
				// There already is an entity with the second page linked, so just link the first one
				var entity = self._getEntity( data );

				self._setSiteLinkLabel( entity, self.firstSiteId, self.firstPageName )
				.done( deferred.resolve )
				.fail( deferred.reject );
			}
		} );

		return deferred.promise();
	},

	/**
	 * Links the second page with the given entity. If page yet is linked with an item we have to perform a merge.
	 *
	 * @param {object} entity
	 *
	 * @return {jQuery.Promise}
	 */
	_linkOrMergeSecondPage: function( entity ) {
		var self = this,
			deferred = new $.Deferred();

		this._getEntityForPage( self.secondSiteId, self.secondPageName )
		.fail( deferred.reject )
		.done( function( data ) {
			if ( data.normalized ) {
				// Use the normalized title from now on (eg. for creating a new item with proper titles)
				self.secondPageName = data.normalized.n.to;
			}

			if ( data.entities['-1'] ) {
				// The second page has no item yet, so just link it with the given entity
				self._setSiteLinkLabel( entity, self.secondSiteId, self.secondPageName )
				.done( deferred.resolve )
				.fail( deferred.reject );
			} else {
				// The page already has an item... this means we have to perform a merge
				self._mergeEntities(
					entity,
					self._getEntity( data )
				)
				.done( deferred.resolve )
				.fail( deferred.reject );

			}
		} );
		return deferred.promise();
	},

	/**
	 * Merges to entities
	 *
	 * @param {object} firstEntity
	 * @param {object} secondEntity
	 *
	 * @return {jQuery.promise}
	 */
	_mergeEntities: function( firstEntity, secondEntity ) {
		var firstSiteLinkCount = this._countSiteLinks( firstEntity ),
			secondSiteLinkCount = this._countSiteLinks( secondEntity ),
			fromId,
			toId;

		// XXX: We could get all properties above and then use a more complete
		// comparision, maybe by abusing $.jSON to get real item sizes. That
		// *might* be a better estimate?!
		if ( firstSiteLinkCount <= secondSiteLinkCount ) {
			fromId = firstEntity.id;
			toId = secondEntity.id;
		} else {
			toId = firstEntity.id;
			fromId = secondEntity.id;
		}

		return this.repoApi.mergeItems(
			fromId,
			toId,
			// Ignore label and description conflicts, but fail on link conflicts
			['label', 'description']
		);
	}

} );

}( wikibase, jQuery ) );
