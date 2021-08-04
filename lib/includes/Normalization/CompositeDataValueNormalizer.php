<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Normalization;

use DataValues\DataValue;

/**
 * A data value normalizer applying a list of other normalizations in order.
 *
 * With an empty list of normalizations, this doubles as a no-op normalizer.
 *
 * @license GPL-2.0-or-later
 */
class CompositeDataValueNormalizer implements DataValueNormalizer {

	/** @var DataValueNormalizer[] */
	private $normalizers;

	/**
	 * @param DataValueNormalizer[] $normalizers
	 */
	public function __construct( array $normalizers ) {
		$this->normalizers = $normalizers;
	}

	public function normalize( DataValue $value ): DataValue {
		foreach ( $this->normalizers as $normalizer ) {
			$value = $normalizer->normalize( $value );
		}
		return $value;
	}

}
