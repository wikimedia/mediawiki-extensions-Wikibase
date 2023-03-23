<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

use DataValues\DataValue;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @license GPL-2.0-or-later
 */
class PropertyValuePair {

	public const TYPE_VALUE = 'value';
	public const TYPE_NO_VALUE = 'novalue';
	public const TYPE_SOME_VALUE = 'somevalue';

	private PropertyId $propertyId;
	private ?string $propertyDataType;
	private string $valueType;
	private ?DataValue $value;

	public function __construct( PropertyId $propertyId, ?string $propertyDataType, string $valueType, ?DataValue $value = null ) {
		if ( !in_array( $valueType, [ self::TYPE_VALUE, self::TYPE_SOME_VALUE, self::TYPE_NO_VALUE ] ) ) {
			throw new InvalidArgumentException( '$valueType must be one of "value", "somevalue", "novalue"' );
		}
		if ( $valueType === self::TYPE_VALUE && !$value ) {
			throw new InvalidArgumentException( '$value must not be null if $valueType is "value"' );
		}
		if ( $valueType !== self::TYPE_VALUE && $value ) {
			throw new InvalidArgumentException( "There must not be a value if \$valueType is '$valueType'" );
		}

		$this->propertyId = $propertyId;
		$this->propertyDataType = $propertyDataType;
		$this->valueType = $valueType;
		$this->value = $value;
	}

	public function getPropertyId(): PropertyId {
		return $this->propertyId;
	}

	/**
	 * @return string|null null only if the property was deleted/cannot be found
	 */
	public function getPropertyDataType(): ?string {
		return $this->propertyDataType;
	}

	public function getValueType(): string {
		return $this->valueType;
	}

	/**
	 * @return DataValue|null Guaranteed to be non-null if value type is "value", always null otherwise.
	 */
	public function getValue(): ?DataValue {
		return $this->value;
	}

}
