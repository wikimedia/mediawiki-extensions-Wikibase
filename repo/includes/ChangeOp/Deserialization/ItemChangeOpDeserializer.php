<?php

namespace Wikibase\Repo\ChangeOp\Deserialization;

use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\ChangeOps;

/**
 * Constructs ChangeOps for item change requests
 *
 * @license GPL-2.0-or-later
 */
class ItemChangeOpDeserializer implements ChangeOpDeserializer {

	/**
	 * @var ChangeOpDeserializerFactory
	 */
	private $factory;

	public function __construct( ChangeOpDeserializerFactory $factory ) {
		$this->factory = $factory;
	}

	/**
	 * @see ChangeOpDeserializer::createEntityChangeOp
	 *
	 * @param array[] $changeRequest
	 *
	 * @return ChangeOp
	 *
	 * @throws ChangeOpDeserializationException
	 */
	public function createEntityChangeOp( array $changeRequest ) {
		$changeOps = new ChangeOps();

		$changeOps->add( $this->factory
			->getFingerprintChangeOpDeserializer()
			->createEntityChangeOp( $changeRequest )
		);

		if ( array_key_exists( 'sitelinks', $changeRequest ) ) {
			$changeOps->add(
				$this->factory
					->getSiteLinksChangeOpDeserializer()
					->createEntityChangeOp( $changeRequest )
			);
		}

		if ( array_key_exists( 'claims', $changeRequest ) ) {
			$changeOps->add(
				$this->factory
					->getClaimsChangeOpDeserializer()
					->createEntityChangeOp( $changeRequest )
			);
		}

		return $changeOps;
	}

}
