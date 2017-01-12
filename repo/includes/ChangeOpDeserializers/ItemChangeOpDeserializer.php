<?php

namespace Wikibase\Repo\ChangeOpDeserializers;

use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;

class ItemChangeOpDeserializer implements ChangeOpDeserializer {

	private $factory;

	public function __construct( WikibaseChangeOpDeserializerFactory $factory ) {
		$this->factory = $factory;
	}

	/**
	 * @param array $changeRequest
	 *
	 * @return ChangeOps
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
