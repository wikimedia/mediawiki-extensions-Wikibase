<?php

namespace Wikibase\Lib\Store\Sql;

use stdClass;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Interface for services giving access to meta data about one or more entities as needed for
 * loading entities from WikiPages (via Revision) or to verify an entity against page.page_latest.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Marius Hoch < hoo@online.de >
 */
interface WikiPageEntityMetaDataAccessor {

	/**
	 * Looks up meta data for the given entityId(s) as needed to lookup the latest revision id
	 * of an entity or to load entity content from a MediaWiki revision. Returns an array of
	 * stdClass with the following fields: 'rev_id', 'rev_content_format', 'rev_timestamp',
	 * 'page_latest', 'old_id', 'old_text' and 'old_flags'.
	 *
	 * @param EntityId[] $entityIds
	 * @param string $mode (EntityRevisionLookup::LATEST_FROM_SLAVE or EntityRevisionLookup::LATEST_FROM_MASTER)
	 *
	 * @return stdClass[] Array of entity id serialization => object.
	 */
	public function loadRevisionInformation( array $entityIds, $mode );

	/**
	 * Looks up meta data for the given entityId-revisionId pair as needed to lookup the latest
	 * revision of the entity or to load entity content from a MediaWiki revision. Included fields are
	 * 'rev_id', 'rev_content_format', 'rev_timestamp', 'page_latest', 'old_id', 'old_text'
	 * and 'old_flags'.
	 *
	 * @param EntityId $entityId
	 * @param int $revisionId Revision id to fetch data about, must be an integer greater than 0.
	 *
	 * @return stdClass|bool false if no such entity exists
	 */
	public function loadRevisionInformationByRevisionId( EntityId $entityId, $revisionId );

}
