<?php

namespace Wikibase\Lib;

use Wikibase\DataModel\Claim\ClaimGuid;
use Wikibase\DataModel\Entity\EntityId;

/**
 * @since 0.3
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class ClaimGuidGenerator  {

	/**
	 * @var V4GuidGenerator
	 */
	private $baseGenerator;

	/**
	 * @since 0.5
	 */
	public function __construct() {
		$this->baseGenerator = new V4GuidGenerator();
	}

	/**
	 * Generates and returns a GUID for a claim in the given Entity.
	 *
	 * @since 0.5
	 *
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	public function newGuid( EntityId $entityId ) {
		return $entityId->getSerialization() . ClaimGuid::SEPARATOR . $this->baseGenerator->newGuid();
	}

}
