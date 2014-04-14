<?php

namespace Wikibase;

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
	private $entityId;

	/**
	 * @var RevisionData
	 */
	private $rev;

	/**
	 * @var string
	 */
	private $changeType;

	/**
	 * @param EntityId $entityId
	 * @param RevisionData $rev
	 * @param string $changeType
	 */
	public function __construct( EntityId $entityId, RevisionData $rev, $changeType ) {
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
	 * @return RevisionData
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
