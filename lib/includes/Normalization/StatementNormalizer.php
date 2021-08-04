<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Normalization;

use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;

/**
 * @license GPL-2.0-or-later
 */
class StatementNormalizer {

	/** @var SnakNormalizer */
	private $snakNormalizer;

	/** @var ReferenceNormalizer */
	private $referenceNormalizer;

	public function __construct(
		SnakNormalizer $snakNormalizer,
		ReferenceNormalizer $referenceNormalizer
	) {
		$this->snakNormalizer = $snakNormalizer;
		$this->referenceNormalizer = $referenceNormalizer;
	}

	public function normalize( Statement $statement ): Statement {
		$normalized = new Statement(
			$this->snakNormalizer->normalize( $statement->getMainSnak() ),
			new SnakList( array_map(
				[ $this->snakNormalizer, 'normalize' ],
				$statement->getQualifiers()->getArrayCopy()
			) ),
			new ReferenceList( array_map(
				[ $this->referenceNormalizer, 'normalize' ],
				iterator_to_array( $statement->getReferences() )
			) ),
			$statement->getGuid()
		);
		$normalized->setRank( $statement->getRank() );
		return $normalized;
	}

}
