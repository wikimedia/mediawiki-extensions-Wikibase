<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

use DataValues\DataValue;
use InvalidArgumentException;

/**
 * @license GPL-2.0-or-later
 */
class Value {

	public const TYPE_VALUE = 'value';
	public const TYPE_NO_VALUE = 'novalue';
	public const TYPE_SOME_VALUE = 'somevalue';

	private string $valueType;
	private ?DataValue $content;

	public function __construct( string $valueType, ?DataValue $content = null ) {
		if ( !in_array( $valueType, [ self::TYPE_VALUE, self::TYPE_SOME_VALUE, self::TYPE_NO_VALUE ] ) ) {
			throw new InvalidArgumentException( '$valueType must be one of "value", "somevalue", "novalue"' );
		}
		if ( $valueType === self::TYPE_VALUE && !$content ) {
			throw new InvalidArgumentException( '$value must not be null if $valueType is "value"' );
		}
		if ( $valueType !== self::TYPE_VALUE && $content ) {
			throw new InvalidArgumentException( "There must not be a value if \$valueType is '$valueType'" );
		}

		$this->valueType = $valueType;
		$this->content = $content;
	}

	public function getType(): string {
		return $this->valueType;
	}

	/**
	 * @return DataValue|null Guaranteed to be non-null if value type is "value", always null otherwise.
	 */
	public function getContent(): ?DataValue {
		return $this->content;
	}

}
