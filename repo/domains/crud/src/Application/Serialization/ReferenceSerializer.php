<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyValuePair;
use Wikibase\Repo\RestApi\Domain\ReadModel\Reference;

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
				fn( PropertyValuePair $part ) => $this->propertyValuePairSerializer->serialize( $part ),
				$reference->getParts()
			),
		];
	}
}
