<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Domain\Model;

use Wikibase\DataModel\Entity\PropertyId;

/**
 * @license GPL-2.0-or-later
 */
class PropertyPrefixSearchResult {

	public function __construct(
		public readonly PropertyId $propertyId,
		public readonly ?Label $label,
		public readonly ?Description $description,
		public readonly MatchedData $matchedData,
		public readonly string $dataType,
	) {
	}
}
