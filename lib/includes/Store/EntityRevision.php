<?php

namespace Wikibase\Lib\Store;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;

/**
 * An EntityRevision identifies some revision of the description of an entity.
 * A revision of an entity is uniquely identified by the tuple ( entity-id, revision-id ).
 *
 * Note that the revision ID alone is not guaranteed to be unique unique, and cannot be relied
 * upon to identify an entity. In particular, revisions of two different entitites may have the
 * same revision id.
 *
 * For more information, see docs/entity-storage.txt
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class EntityRevision {

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
	public function __construct( EntityDocument $entity, $revisionId = 0, $mwTimestamp = '' ) {
		if ( !is_int( $revisionId ) || $revisionId < 0 ) {
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
	 * The revision's timestamp. This is furely informational, it does not identify the revision.
	 *
	 * @return string in MediaWiki format or an empty string
	 */
	public function getTimestamp() {
		return $this->mwTimestamp;
	}

}
