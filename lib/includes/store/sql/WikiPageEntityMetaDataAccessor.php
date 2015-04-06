<?php

namespace Wikibase\Lib\Store\Sql;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Interface for services giving access to meta data about one or more entities as needed for
 * loading entities from WikiPages (via Revision) or to verify an entity against page.page_latest.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
interface WikiPageEntityMetaDataAccessor {

	/**
	 * @param EntityId[] $entityIds
	 * @param string $mode (EntityRevisionLookup::LATEST_FROM_SLAVE or EntityRevisionLookup::LATEST_FROM_MASTER)
	 *
	 * @return array entity id serialization -> stdClass or false if no such entity exists
	 */
	public function loadRevisionInformation( array $entityIds, $mode );

	/**
	 * @param EntityId $entityId
	 * @param string $mode (EntityRevisionLookup::LATEST_FROM_SLAVE or EntityRevisionLookup::LATEST_FROM_MASTER)
	 *
	 * @return stdClass|false false if no such entity exists
	 */
	public function loadRevisionInformationByEntityId( EntityId $entityId, $mode );

	/**
	 * @param EntityId $entityId
	 * @param int $revisionId
	 *
	 * @return object|bool false if no such entity exists
	 */
	public function loadRevisionInformationByRevisionId( EntityId $entityId, $revisionId );

}
