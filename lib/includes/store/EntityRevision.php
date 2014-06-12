<?php

namespace Wikibase;

use InvalidArgumentException;

/**
 * Represents a revision of a Wikibase entity.
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
	protected $entity;

	/**
	 * @var int
	 */
	protected $revisionId;

	/**
	 * @var string
	 */
	protected $mwTimestamp;

	/**
	 * @param Entity $entity
	 * @param int $revisionId (use 0 for none)
	 * @param string $mwTimestamp in mediawiki format (use '' for none)
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( Entity $entity, $revisionId = 0, $mwTimestamp = '' ) {
		if ( !is_int( $revisionId ) || $revisionId < 0 ) {
			throw new InvalidArgumentException( '$revisionId must be an non-negative integer' );
		}

		if ( $mwTimestamp !== '' && !preg_match( '/^\d{14}$/', $mwTimestamp ) ) {
			throw new InvalidArgumentException( '$mwTimestamp must be a string of 14 digits (or empty)' );
		}

		$this->entity = $entity;
		$this->revisionId = $revisionId;
		$this->mwTimestamp = $mwTimestamp;
	}

	/**
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
	 * @return string
	 */
	public function getTimestamp() {
		return $this->mwTimestamp;
	}

}
