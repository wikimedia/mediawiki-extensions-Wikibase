<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Model;

use Wikibase\DataModel\Statement\StatementGuid;

/**
 * @license GPL-2.0-or-later
 */
class Statement {

	public function __construct(
		public readonly StatementGuid $id,
		public readonly Rank $rank,
		public readonly Qualifiers $qualifiers,
		public readonly PredicateProperty $property,
	) {
	}

}
