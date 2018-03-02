<?php

namespace Wikibase\Client\RecentChanges;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Represents an external change
 *
 * @license GPL-2.0-or-later
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
	 * @return EntityId
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

	/**
	 * @return string
	 */
	public function getSiteId() {
		return $this->rev->getSiteId();
	}

}
