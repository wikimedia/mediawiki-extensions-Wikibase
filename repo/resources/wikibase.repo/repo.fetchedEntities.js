/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 *
 * Singleton holding very basic information about all entities used on a page (e.g. in an entity
 * view). Entity IDs are used as keys for many inner hashes where each is an instance of
 * wb.FetchedContent. Each of these wb.FetchedContent instances holds an instance of wb.Entity as
 * its content. This entity data might be incomplete though, it is guaranteed that ID, and label are
 * provided, for wb.Property instances it is also guaranteed that the data type is provided.
 *
 * TODO: (Bug 54082) This global store is bad and should be replaced by proper store implementations
 *  which should then be properly injected into our view widgets.
 *
 * @since 0.5 (as plain object in wikibase.fetchedEntities since 0.4)
 *
 * @type {Object}
 */
wikibase.repo.fetchedEntities = new ( function WbRepoFetchedEntities() {
	'use strict';
} )();

/**
 * @deprecated Use wikibase.repo.fetchedEntities instead. Has been moved to wikibase.repo to make
 *             clear that this should only be used in the repo part of wikibase.
 *
 * @since 0.4
 *
 * @type {Object}
 */
wikibase.fetchedEntities = wikibase.repo.fetchedEntities;
