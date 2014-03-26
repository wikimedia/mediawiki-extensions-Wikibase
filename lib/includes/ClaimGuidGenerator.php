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
	 * @since 0.3
	 * @var GuidGenerator
	 */
	protected $baseGenerator;

	/**
	 * @since 0.5
	 *
	 * @param GuidGenerator $baseGenerator (defaults to new V4GuidGenerator())
	 */
	public function __construct( GuidGenerator $baseGenerator = null ) {
		if ( $baseGenerator === null ) {
			$baseGenerator = new V4GuidGenerator();
		}

		$this->baseGenerator = $baseGenerator;
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
