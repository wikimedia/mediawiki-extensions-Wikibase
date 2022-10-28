<?php

namespace Wikibase\Lib\Store;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;

/**
 * An EntityRevision contains a specific revision of an EntityDocument. A revision of an entity is
 * uniquely identified by the tuple ( entity ID, revision ID ).
 *
 * Note that the revision ID alone cannot be relied upon to identify an entity. Revisions of two
 * different entities may have the same revision ID. For more information on the relationship
 * between entities and wiki pages, see docs/entity-storage.wiki.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityRevision {

	public const UNSAVED_REVISION = 0;

	/**
	 * @var EntityDocument
	 */
	private $entity;

	/**
	 * @var int
	 */
	private $revisionId;

	/**
	 * @var string
	 */
	private $mwTimestamp;

	/**
	 * @param EntityDocument $entity
	 * @param int $revisionId Revision ID or 0 for none
	 * @param string $mwTimestamp in MediaWiki format or an empty string for none
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		EntityDocument $entity,
		int $revisionId = self::UNSAVED_REVISION,
		string $mwTimestamp = ''
	) {
		if ( $revisionId < 0 ) {
			throw new InvalidArgumentException( 'Revision ID must be a non-negative integer.' );
		}

		if ( $mwTimestamp !== '' && !preg_match( '/^\d{14}\z/', $mwTimestamp ) ) {
			throw new InvalidArgumentException( 'Timestamp must be a string of 14 digits or empty.' );
		}

		$this->entity = $entity;
		$this->revisionId = $revisionId;
		$this->mwTimestamp = $mwTimestamp;
	}

	/**
	 * @return EntityDocument
	 */
	public function getEntity() {
		return $this->entity;
	}

	/**
	 * The ID of the revision of the given entity.
	 *
	 * Note that this number is not guaranteed to be globally unique, nor to be increasing over
	 * time.
	 *
	 * @return int
	 */
	public function getRevisionId() {
		return $this->revisionId;
	}

	/**
	 * The revision's timestamp. This is purely informational, it does not identify the revision.
	 *
	 * @return string in MediaWiki format or an empty string
	 */
	public function getTimestamp() {
		return $this->mwTimestamp;
	}

}
