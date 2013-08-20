<?php

namespace Wikibase;

use Wikibase\Client\WikibaseClient;

/**
 * Represents an external change
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ExternalChange {

	/**
	 * @var EntityId
	 */
	protected $entityId;

	/**
	 * @var RevisionObject
	 */
	protected $rev;

	/**
	 * @var string
	 */
	protected $changeType;

	/**
	 * @param EntityId $entityId
	 * @param RevisionObject $rev
	 * @param string $changeType
	 */
	public function __construct( EntityId $entityId, RevisionObject $rev, $changeType ) {
		$this->entityId = $entityId;
		$this->rev = $rev;
		$this->changeType = $changeType;
	}

	/**
	 * return EntityId
	 */
	public function getEntityId() {
		return $this->entityId;
	}

	/**
	 * @return RevisionObject
	 */
	public function getRev() {
		return $this->rev;
	}

	/**
	 * @return string
	 */
	public function getChangeType() {
		return $this->changeType;
	}

}
