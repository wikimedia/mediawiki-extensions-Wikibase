<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Normalization;

use Wikibase\DataModel\Reference;

/**
 * @license GPL-2.0-or-later
 */
class ReferenceNormalizer {

	/** @var SnakNormalizer */
	private $snakNormalizer;

	public function __construct( SnakNormalizer $snakNormalizer ) {
		$this->snakNormalizer = $snakNormalizer;
	}

	public function normalize( Reference $reference ): Reference {
		return new Reference( array_map(
			[ $this->snakNormalizer, 'normalize' ],
			$reference->getSnaks()->getArrayCopy()
		) );
	}

}
