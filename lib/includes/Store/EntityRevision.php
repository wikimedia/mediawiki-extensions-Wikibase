<?php

namespace Wikibase;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;

/**
 * Represents a revision of a Wikibase entity.
 *
 * @since 0.4
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
	 * @see Revision::getId
	 *
	 * @return int
	 */
	public function getRevisionId() {
		return $this->revisionId;
	}

	/**
	 * @see Revision::getTimestamp
	 *
	 * @return string in MediaWiki format or an empty string
	 */
	public function getTimestamp() {
		return $this->mwTimestamp;
	}

}
