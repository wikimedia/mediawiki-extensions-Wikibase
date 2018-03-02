<?php

namespace Wikibase\Repo\ChangeOp\Deserialization;

use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;

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
