<?php

namespace Wikibase\Lib;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @since 0.3
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ClaimGuidGenerator implements GuidGenerator {

	/**
	 * @since 0.3
	 * @var GuidGenerator
	 */
	protected $baseGenerator;

	/**
	 * @since 0.5
	 * @var EntityId
	 */
	protected $entityId;

	/**
	 * @param EntityId $entityId
	 */
	public function __construct( EntityId $entityId ) {
		$this->entityId = $entityId;
		$this->baseGenerator = new V4GuidGenerator();
	}

	/**
	 * Generates and returns a GUID.
	 * @see http://php.net/manual/en/function.com-create-guid.php
	 * @see GuidGenerator::newGuid
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function newGuid() {
		return $this->entityId->getSerialization() . '$' . $this->baseGenerator->newGuid();
	}

}
