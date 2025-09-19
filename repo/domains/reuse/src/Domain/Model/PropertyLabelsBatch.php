<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Model;

use Wikibase\DataModel\Entity\PropertyId;

/**
 * @license GPL-2.0-or-later
 */
class PropertyLabelsBatch {

	/**
	 * @param array<string, Labels> $propertyLabelsBatch
	 */
	public function __construct( public readonly array $propertyLabelsBatch ) {
	}

	public function getPropertyLabels( PropertyId $propertyId ): Labels {
		return $this->propertyLabelsBatch[ $propertyId->getSerialization() ];
	}

}
