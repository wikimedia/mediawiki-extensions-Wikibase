<?php

namespace Wikibase\Repo\ChangeOp\Deserialization;

use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\ChangeOps;

/**
 * Constructs ChangeOp objects for property change requests
 *
 * @license GPL-2.0-or-later
 */
class PropertyChangeOpDeserializer implements ChangeOpDeserializer {

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

		if ( array_key_exists( 'claims', $changeRequest ) ) {
			$changeOps->add(
				$this->factory->getClaimsChangeOpDeserializer()->createEntityChangeOp( $changeRequest )
			);
		}

		if ( array_key_exists( 'sitelinks', $changeRequest ) ) {
			throw new ChangeOpDeserializationException( 'Non Items cannot have sitelinks', 'not-supported' );
		}

		return $changeOps;
	}

}
