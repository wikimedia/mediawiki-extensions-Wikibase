<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Model;

use DataValues\DataValue;

/**
 * @license GPL-2.0-or-later
 */
class PropertyValuePair {

	public function __construct(
		public readonly PredicateProperty $property,
		public readonly ?DataValue $value,
		public readonly ValueType $valueType,
	) {
	}

}
