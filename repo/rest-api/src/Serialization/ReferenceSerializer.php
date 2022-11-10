<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Serialization;

use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\Snak;

/**
 * @license GPL-2.0-or-later
 */
class ReferenceSerializer {

	private PropertyValuePairSerializer $propertyValuePairSerializer;

	public function __construct( PropertyValuePairSerializer $propertyValuePairSerializer ) {
		$this->propertyValuePairSerializer = $propertyValuePairSerializer;
	}

	public function serialize( Reference $reference ): array {
		return [
			'hash' => $reference->getHash(),
			'parts' => array_map(
				fn( Snak $part ) => $this->propertyValuePairSerializer->serialize( $part ),
				iterator_to_array( $reference->getSnaks() )
			),
		];
	}
}
