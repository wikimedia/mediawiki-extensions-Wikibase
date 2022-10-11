<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Serialization;

use Serializers\Serializer;
use Wikibase\DataModel\Serializers\TypedSnakSerializer;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\TypedSnak;

/**
 * @license GPL-2.0-or-later
 */
class SnakSerializer implements Serializer {

	private TypedSnakSerializer $typedSnakSerializer;
	private PropertyDataTypeLookup $propertyDataTypeLookup;

	public function __construct(
		TypedSnakSerializer $typedSnakSerializer,
		PropertyDataTypeLookup $propertyDataTypeLookup
	) {
		$this->typedSnakSerializer = $typedSnakSerializer;
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
	}

	/**
	 * @param Snak $snak
	 */
	// phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	public function serialize( $snak ): array {
		return $this->typedSnakSerializer->serialize(
			new TypedSnak(
				$snak,
				$this->propertyDataTypeLookup->getDataTypeIdForProperty( $snak->getPropertyId() )
			)
		);
	}
}
