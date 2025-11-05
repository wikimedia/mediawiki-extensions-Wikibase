<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Model;

use Wikibase\DataModel\Statement\StatementGuid;

/**
 * @license GPL-2.0-or-later
 */
class Statement {

	/**
	 * @param StatementGuid $id
	 * @param Rank $rank
	 * @param Qualifiers $qualifiers
	 * @param Reference[] $references
	 * @param PredicateProperty $property
	 * @param Value|null $value
	 * @param ValueType $valueType
	 */
	public function __construct(
		public readonly StatementGuid $id,
		public readonly Rank $rank,
		public readonly Qualifiers $qualifiers,
		public readonly array $references,
		public readonly PredicateProperty $property,
		public readonly ?Value $value,
		public readonly ValueType $valueType
	) {
	}

}
