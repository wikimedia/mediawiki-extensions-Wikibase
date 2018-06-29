<?php

namespace Wikibase\Repo\EntityReferenceExtractors;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikimedia\Assert\Assert;

/**
 * Creates an EntityReferenceExtractor based on the given entity's type
 *
 * TODO: naming? delegating EntityReferenceExtractor thingy thing.
 *
 * @license GPL-2.0-or-later
 */
class EntityReferenceExtractorFactory implements EntityReferenceExtractor {

	private $callbacks;

	/**
	 * @param callable[] $callbacks - maps entity types to EntityReferenceExtractors
	 */
	public function __construct( array $callbacks ) {
		Assert::parameterElementType( 'callable', $callbacks, '$callbacks' );
		$this->callbacks = $callbacks;
	}

	public function extractEntityIds( EntityDocument $entity ) {
		if ( array_key_exists( $entity->getType(), $this->callbacks ) ) {
			return $this->callbacks[$entity->getType()]()->extractEntityIds( $entity );
		}

		return []; // TODO: should at least extract from statements?
	}

}
