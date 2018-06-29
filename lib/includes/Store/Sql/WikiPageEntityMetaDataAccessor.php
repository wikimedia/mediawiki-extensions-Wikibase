<?php

namespace Wikibase\Lib\Store\Sql;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Interface for services giving access to meta data about one or more entities as needed for
 * loading entities from WikiPages (via Revision) or to verify an entity against page.page_latest.
 *
 * @todo This whole interface may no longer be needed with the introduction of RevisionRecord which can
 * represent a revision on any wiki.
 * MetaData accessing can probably be killed and instead RevisionRecord just be returned.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
interface WikiPageEntityMetaDataAccessor {

	/**
	 * Looks up meta data for the given entityId(s) as needed to lookup the latest revision id
	 * of an entity or to load entity content from a MediaWiki revision. Returns an array of
	 * stdClass with the following fields: 'rev_id', 'rev_timestamp',
	 * 'page_latest'.
	 *
	 * @param EntityId[] $entityIds
	 * @param string $mode (EntityRevisionLookup::LATEST_FROM_REPLICA,
	 *     EntityRevisionLookup::LATEST_FROM_REPLICA_WITH_FALLBACK or
	 *     EntityRevisionLookup::LATEST_FROM_MASTER)
	 *
	 * @return (stdClass|bool)[] Array mapping entity ID serializations to either objects
	 * or false if an entity could not be found.
	 */
	public function loadRevisionInformation( array $entityIds, $mode );

	/**
	 * Looks up the latest revision ID(s) for the given entityId(s).
	 * Returns an array of integer revision IDs
	 *
	 * @param EntityId[] $entityIds
	 * @param string $mode (EntityRevisionLookup::LATEST_FROM_REPLICA,
	 *     EntityRevisionLookup::LATEST_FROM_REPLICA_WITH_FALLBACK or
	 *     EntityRevisionLookup::LATEST_FROM_MASTER)
	 *
	 * @return (int|bool)[] Array mapping entity ID serializations to either revision IDs
	 * or false if an entity could not be found (including if the page is a redirect).
	 */
	public function loadLatestRevisionIds( array $entityIds, $mode );

}
