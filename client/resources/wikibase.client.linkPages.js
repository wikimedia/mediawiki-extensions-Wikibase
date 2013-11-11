/**
 * JavaScript that allows linking two articles directly in client wikis.
 *
 * @since 0.5
 *
 * @author Marius Hoch < hoo@online.de >
 */
( function( wb, mw, $ ) {
'use strict';

/**
 * @param {string} firstSiteId
 * @param {string} firstPageName
 * @param {string} secondSiteId
 * @param {string} secondPageName
 */
wb.linkPages = function linkPages( firstSiteId, firstPageName, secondSiteId, secondPageName ) {
	/**
	 * @type string
	 */
	this.firstSiteId = firstSiteId;
	/**
	 * @type string
	 */
	this.firstPageName = firstPageName;
	/**
	 * @type string
	 */
	this.secondSiteId = secondSiteId;
	/**
	 * @type string
	 */
	this.secondPageName = secondPageName;
};

$.extend( wb.linkPages.prototype, {
	/**
	 * @type wb.RepoApi
	 */
	repoApi: new wb.RepoApi(),

	/**
	 * Get the entity for a given page in case there is one
	 *
	 * @param {string} siteId
	 * @param {string} pageName
	 * @param {string|object|null} lang
	 *
	 * @return {jQuery.Promise}
	 */
	_getEntityForPage: function( siteId, pageName, lang ) {
		return this.repoApi.getEntitiesByPage(
			siteId,
			pageName,
			['info', 'sitelinks'],
			lang,
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
	 * Get the entity object from an API response
	 *
	 * @param {object} data
	 *
	 * @return {object}
	 */
	_getEntity: function( data ) {
		for ( var i in data.entities ) {
			if ( data.entities[ i ].sitelinks ) {
				return data.entities[ i ];
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
	createItem: function( firstSiteId, firstPageName, secondSiteId, secondPageName ) {
		// JSON data for the new entity
		var entityData = {
				labels: {},
				sitelinks: {}
			},
			firstSite = wb.getSite( firstSiteId ),
			secondSite = wb.getSite( secondSiteId );

		// Label (page title)
		entityData.labels[ firstSite.getLanguageCode() ] = {
			language: firstSite.getLanguageCode(),
			value: firstPageName
		};
		// Link this page
		entityData.sitelinks[ firstSite.getGlobalSiteId() ] = {
			site: firstSite.getGlobalSiteId(),
			title: firstPageName
		};
		// ...and the one given by the user

		// Label (page title)
		entityData.labels[ secondSite.getLanguageCode() ] = {
			language: secondSite.getLanguageCode(),
			value: secondPageName
		};
		// Link this page
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
	doLink: function() {
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
				self._getEntityForPage( self.secondSiteId, self.secondPageName )
				.fail( deferred.reject )
				.done( function( data ) {
					// Use the normalized title from now on (eg. for creating a new item with proper titles)
					if ( data.normalized ) {
						self.secondPageName = data.normalized.n.to;
					}

					if ( data.entities['-1'] ) {
						// There's no item yet, create one
						self.createItem(
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
			}
		} )
		.fail( deferred.reject );

		return deferred.promise();
	},

	/**
	 * Links the second page with the given entity. If page yet is linked with an item we have to perform a merge.
	 *
	 * @param {object} entity
	 * @param {string} siteId
	 * @param {string} pageName
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
				// The page has no item yet, so just link it with the given entity
				self._setSiteLinkLabel( entity, self.secondSiteId, self.secondPageName )
				.done( deferred.resolve )
				.fail( deferred.reject );
			} else {
				// The page already has an item... this means we have to perform a merge
				var selfEntity = self._getEntity( data ),
					selfSiteLinkCount = self._countSiteLinks( selfEntity ),
					siteLinkCount = self._countSiteLinks( entity ),
					fromId,
					toId;

				// XXX: We could get all properties above and then use a more complete
				// comparision, maybe by abusing $.jSON to get real item sizes. That
				// *might* be a better estimate?!
				if ( selfSiteLinkCount <= siteLinkCount ) {
					fromId = selfEntity.id;
					toId = entity.id;
				} else {
					toId = selfEntity.id;
					fromId = entity.id;
				}

				self.repoApi.mergeItems(
					fromId,
					toId,
					// Ignore label and description conflicts, but fail on link conflicts
					['label', 'description']
				)
				.done( deferred.resolve )
				.fail( deferred.reject );

			}
		} );
		return deferred.promise();
	},

} );

}( wikibase, mediaWiki, jQuery ) );
