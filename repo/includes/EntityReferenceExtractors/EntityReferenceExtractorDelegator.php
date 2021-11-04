<?php

namespace Wikibase\Repo\EntityReferenceExtractors;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\StatementListProvidingEntity;
use Wikimedia\Assert\Assert;

/**
 * Uses an EntityReferenceExtractor based on the given entity's type
 *
 * @license GPL-2.0-or-later
 */
class EntityReferenceExtractorDelegator implements EntityReferenceExtractor {

	/**
	 * @var callable[]
	 */
	private $callbacks;

	/**
	 * @var StatementEntityReferenceExtractor
	 */
	private $statementEntityReferenceExtractor;

	/**
	 * @param array $callbacks maps entity types to EntityReferenceExtractors
	 * @param StatementEntityReferenceExtractor $statementEntityReferenceExtractor
	 */
	public function __construct( array $callbacks, StatementEntityReferenceExtractor $statementEntityReferenceExtractor ) {
		Assert::parameterElementType( 'callable', $callbacks, '$callbacks' );
		$this->callbacks = $callbacks;
		$this->statementEntityReferenceExtractor = $statementEntityReferenceExtractor;
	}

	public function extractEntityIds( EntityDocument $entity ) {
		if ( array_key_exists( $entity->getType(), $this->callbacks ) ) {
			return $this->callbacks[$entity->getType()]()->extractEntityIds( $entity );
		}

		if ( $entity instanceof StatementListProvidingEntity ) {
			return $this->statementEntityReferenceExtractor->extractEntityIds( $entity );
		}

		return [];
	}

}
