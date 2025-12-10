<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Model;

use Wikibase\DataModel\Entity\PropertyId;

/**
 * @license GPL-2.0-or-later
 */
class PropertyValueFilter {

	public function __construct(
		public readonly PropertyId $propertyId,
		public readonly ?string $value = null,
	) {
	}
}
