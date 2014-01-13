<?php

namespace Wikibase;

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
	 * @since 0.4
	 * @var array
	 */
	protected $entity;

	/**
	 * @var int
	 */
	protected $revision;

	/**
	 * @var string
	 */
	protected $timestamp;

	/**
	 * @param Entity $entity
	 * @param int $revision (use 0 for none)
	 * @param string $timestamp in mediawiki format (use '' for none)
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct( Entity $entity, $revision = 0, $timestamp = '' ) {
		if ( !is_int( $revision ) ) {
			throw new \InvalidArgumentException( '$revision must be an integer' );
		}

		if ( $revision < 0 ) {
			throw new \InvalidArgumentException( '$revision must not be negative' );
		}

		if ( $timestamp !== '' && !preg_match( '/^\d{14}$/', $timestamp ) ) {
			throw new \InvalidArgumentException( '$timestamp must be a string of 14 digits (or empty)' );
		}

		$this->entity = $entity;
		$this->revision = $revision;
		$this->timestamp = $timestamp;
	}

	/**
	 * @return Entity
	 */
	public function getEntity() {
		return $this->entity;
	}

	/**
	 * @return int
	 */
	public function getRevision() {
		return $this->revision;
	}

	/**
	 * @return string
	 */
	public function getTimestamp() {
		return $this->timestamp;
	}
}
