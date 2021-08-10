<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Normalization;

use DataValues\DataValue;

/**
 * A service to create normalized versions of data values.
 *
 * Normalization should have the following properties:
 * * Deterministic: Normalizing the same value should produce the same result.
 * * Idempotent: Normalizing an already normalized value returns the same value
 *   (in terms of {@see DataValue::equals `equals()`}, not necessarily `===`).
 *
 * @license GPL-2.0-or-later
 */
interface DataValueNormalizer {

	/**
	 * Normalize the given value.
	 *
	 * @param DataValue $value The value to normalize.
	 * Every implementation must be able to handle every data value type without error,
	 * even if it’s just by returning the same value without modification.
	 * @return DataValue A normalized version of the value.
	 * If the input value was not normalized, this must be a new object,
	 * since data values are immutable; if it was already normalized,
	 * this may be a new object or the same instance.
	 */
	public function normalize( DataValue $value ): DataValue;

}
