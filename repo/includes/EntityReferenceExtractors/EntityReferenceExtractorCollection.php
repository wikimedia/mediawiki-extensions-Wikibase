<?php

namespace Wikibase\Repo\EntityReferenceExtractors;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikimedia\Assert\Assert;

/**
 * Merges extracted entity ids from multiple EntityReferenceExtractors
 *
 * @license GPL-2.0-or-later
 */
class EntityReferenceExtractorCollection implements EntityReferenceExtractor {

	/**
	 * @var EntityReferenceExtractor[]
	 */
	private $referenceExtractors;

	/**
	 * @param EntityReferenceExtractor[] $referenceExtractors
	 */
	public function __construct( array $referenceExtractors ) {
		Assert::parameterElementType( EntityReferenceExtractor::class, $referenceExtractors, '$referenceExtractors' );
		$this->referenceExtractors = $referenceExtractors;
	}

	public function extractEntityIds( EntityDocument $entity ) {
		$ids = [];

		foreach ( $this->referenceExtractors as $referenceExtractor ) {
			$ids = array_merge( $ids, $referenceExtractor->extractEntityIds( $entity ) );
		}

		return array_values( array_unique( $ids ) );
	}

}
