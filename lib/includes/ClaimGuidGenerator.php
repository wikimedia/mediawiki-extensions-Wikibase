<?php

namespace Wikibase\Lib;

use InvalidArgumentException;

/**
 * Claim GUID generator.
 *
 * @since 0.3
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ClaimGuidGenerator implements GuidGenerator {

	/**
	 * @since 0.3
	 *
	 * @var GuidGenerator
	 */
	protected $baseGenerator;

	protected $entityIdString;

	/**
	 * @param string $entityIdString
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $entityIdString ) {
		if ( !is_string( $entityIdString ) ) {
			throw new InvalidArgumentException( '$entityIdString needs to be a string' );
		}

		$this->entityIdString = $entityIdString;
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
		return $this->entityIdString . '$' . $this->baseGenerator->newGuid();
	}

}
