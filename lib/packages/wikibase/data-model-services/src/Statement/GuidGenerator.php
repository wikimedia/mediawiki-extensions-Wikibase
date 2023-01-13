<?php

namespace Wikibase\DataModel\Services\Statement;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Statement\StatementGuid;

/**
 * @since 1.0
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Addshore
 */
class GuidGenerator {

	/**
	 * @var V4GuidGenerator
	 */
	private $baseGenerator;

	public function __construct() {
		$this->baseGenerator = new V4GuidGenerator();
	}

	/**
	 * Generates and returns a GUID for a statement in the given Entity.
	 *
	 * @since 1.0
	 *
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	public function newGuid( EntityId $entityId ) {
		return $entityId->getSerialization() . StatementGuid::SEPARATOR . $this->baseGenerator->newGuid();
	}

	public function newStatementId( EntityId $entityId ): StatementGuid {
		return new StatementGuid( $entityId, $this->baseGenerator->newGuid() );
	}

}
