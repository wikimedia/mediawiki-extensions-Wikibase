<?php

namespace Wikibase\Repo\ChangeOp\Deserialization;

use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;

/**
 * Constructs ChangeOps for item change requests
 *
 * @license GPL-2.0+
 */
class ItemChangeOpDeserializer implements ChangeOpDeserializer {

	/**
	 * @var WikibaseChangeOpDeserializerFactory
	 */
	private $factory;

	public function __construct( WikibaseChangeOpDeserializerFactory $factory ) {
		$this->factory = $factory;
	}

	/**
	 * @see ChangeOpDeserializer::createEntityChangeOp
	 *
	 * @param array[] $changeRequest
	 *
	 * @return ChangeOp
	 */
	public function createEntityChangeOp( array $changeRequest ) {
		$changeOps = new ChangeOps();

		if ( array_key_exists( 'labels', $changeRequest ) ) {
			$changeOps->add(
				$this->factory
					->getLabelsChangeOpDeserializer()
					->createEntityChangeOp( $changeRequest )
			);
		}

		if ( array_key_exists( 'descriptions', $changeRequest ) ) {
			$changeOps->add(
				$this->factory
					->getDescriptionsChangeOpDeserializer()
					->createEntityChangeOp( $changeRequest )
			);
		}

		if ( array_key_exists( 'aliases', $changeRequest ) ) {
			$changeOps->add(
				$this->factory
					->getAliasesChangeOpDeserializer()
					->createEntityChangeOp( $changeRequest )
			);
		}

		if ( array_key_exists( 'sitelinks', $changeRequest ) ) {
			// TODO
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
