<?php

namespace Wikibase\Repo\Api;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\ChangeOp\ChangeOp;

/**
 * Object representing the request to wbsetclaim API.
 * Contains the following:
 *  - on which entity change is going to be made (ID)
 *  - what is the statement data to set
 *  - change op object to be applied - TODO: does this really belong here?
 */
class SetClaimRequest {

	/**
	 * @var EntityId
	 */
	private $entityId;

	/**
	 * @var ChangeOp
	 */
	private $changeOp;

	private $statement;

	public function __construct( EntityId $entityId, Statement $statement, ChangeOp $changeOp ) {
		$this->entityId = $entityId;
		$this->statement = $statement;
		$this->changeOp = $changeOp;
	}

	public function getEntityId() {
		return $this->entityId;
	}

	public function getChangeOp() {
		return $this->changeOp;
	}

	public function getStatement() {
		return $this->statement;
	}

}
