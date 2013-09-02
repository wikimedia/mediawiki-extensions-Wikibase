<?php

namespace Wikibase\Lib;

/**
 * Globally Unique IDentifier generator.
 *
 * @since 0.3
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface GuidGenerator {

	/**
	 * Generates and returns a Globally Unique IDentifier.
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function newGuid();

}

/**
 * Globally Unique IDentifier generator.
 *
 * @since 0.3
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class V4GuidGenerator implements GuidGenerator {

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
		if ( function_exists( 'com_create_guid' ) ) {
			return trim( com_create_guid(), '{}' );
		}

		return sprintf(
			'%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
			mt_rand( 0, 65535 ),
			mt_rand( 0, 65535 ),
			mt_rand( 0, 65535 ),
			mt_rand( 16384, 20479 ),
			mt_rand( 32768, 49151 ),
			mt_rand( 0, 65535 ),
			mt_rand( 0, 65535 ),
			mt_rand( 0, 65535 )
		);
	}

}

use MWException;
use Wikibase\EntityId;

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

	/**
	 * @since 0.3
	 *
	 * @var EntityId|null
	 */
	protected $entityId;

	/**
	 * Constructor.
	 *
	 * @since 0.3
	 */
	public function __construct( EntityId $entityId = null ) {
		$this->baseGenerator = new V4GuidGenerator();
		$this->entityId = $entityId;
	}

	/**
	 * Generates and returns a GUID.
	 * @see http://php.net/manual/en/function.com-create-guid.php
	 * @see GuidGenerator::newGuid
	 *
	 * @since 0.3
	 *
	 * @return string
	 * @throws MWException
	 */
	public function newGuid() {
		if ( $this->entityId === null ) {
			throw new MWException( 'Entity ID needs to be set before a Claim GUID can be generated' );
		}

		return $this->entityId->getPrefixedId() . '$' . $this->baseGenerator->newGuid();
	}

}
