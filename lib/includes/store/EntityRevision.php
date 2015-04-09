<?php

namespace Wikibase;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Entity;

/**
 * Represents a specific revision of a Wikibase entity.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityRevision {

	/**
	 * @var Entity
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
	 * @param Entity $entity
	 * @param int $revisionId Non-zero revision number.
	 * @param string $mwTimestamp in MediaWiki format or an empty string for none
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( Entity $entity, $revisionId, $mwTimestamp = '' ) {
		if ( !is_int( $revisionId ) || $revisionId <= 0 ) {
			throw new InvalidArgumentException( '$revisionId must be a positive integer.' );
		}

		if ( $mwTimestamp !== '' && !preg_match( '/^\d{14}$/', $mwTimestamp ) ) {
			throw new InvalidArgumentException( '$mwTimestamp must be a string of 14 digits or empty.' );
		}

		$this->entity = $entity;
		$this->revisionId = $revisionId;
		$this->mwTimestamp = $mwTimestamp;
	}

	/**
	 * TODO: change return type to EntityDocument
	 *
	 * @return Entity
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
