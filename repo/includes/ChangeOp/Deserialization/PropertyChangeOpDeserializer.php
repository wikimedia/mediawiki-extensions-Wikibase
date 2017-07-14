<?php

namespace Wikibase\Repo\ChangeOp\Deserialization;

use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOps;

/**
 * Constructs ChangeOp objects for property change requests
 *
 * @license GPL-2.0+
 */
class PropertyChangeOpDeserializer implements PermissionAwareChangeOpDeserializer {

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
				$this->factory->getLabelsChangeOpDeserializer()->createEntityChangeOp( $changeRequest )
			);
		}

		if ( array_key_exists( 'descriptions', $changeRequest ) ) {
			$changeOps->add(
				$this->factory->getDescriptionsChangeOpDeserializer()->createEntityChangeOp( $changeRequest )
			);
		}

		if ( array_key_exists( 'aliases', $changeRequest ) ) {
			$changeOps->add(
				$this->factory->getAliasesChangeOpDeserializer()->createEntityChangeOp( $changeRequest )
			);
		}

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

	public function includesChangesToEntityTerms( array $changeRequest ) {
		return array_key_exists( 'labels', $changeRequest ) || array_key_exists( 'descriptions', $changeRequest ) ||
			array_key_exists( 'aliases', $changeRequest );
	}

}
