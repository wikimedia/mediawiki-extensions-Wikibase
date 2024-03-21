<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use LogicException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\ItemDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\UnexpectedFieldException;

/**
 * @license GPL-2.0-or-later
 */
class ItemValidator {

	public const CODE_INVALID_FIELD = 'invalid-item-field';
	public const CODE_UNEXPECTED_FIELD = 'item-data-unexpected-field';
	public const CODE_MISSING_LABELS_AND_DESCRIPTIONS = 'missing-labels-and-descriptions';

	public const CONTEXT_FIELD_NAME = 'field';
	public const CONTEXT_FIELD_VALUE = 'value';

	private ?Item $deserializedItem = null;
	private ItemDeserializer $deserializer;

	public function __construct( ItemDeserializer $deserializer ) {
		$this->deserializer = $deserializer;
	}

	public function validate( array $itemSerialization ): ?ValidationError {
		try {
			$this->deserializedItem = $this->deserializer->deserialize( $itemSerialization );
		} catch ( UnexpectedFieldException $e ) {
			return new ValidationError( self::CODE_UNEXPECTED_FIELD, [ self::CONTEXT_FIELD_NAME => $e->getField() ] );
		} catch ( InvalidFieldException $e ) {
			return new ValidationError(
				self::CODE_INVALID_FIELD,
				[ self::CONTEXT_FIELD_NAME => $e->getField(), self::CONTEXT_FIELD_VALUE => $e->getValue() ]
			);
		}

		if (
			$this->deserializedItem->getLabels()->isEmpty() &&
			$this->deserializedItem->getDescriptions()->isEmpty()
		) {
			return new ValidationError( self::CODE_MISSING_LABELS_AND_DESCRIPTIONS );
		}

		return null;
	}

	public function getValidatedItem(): Item {
		if ( $this->deserializedItem === null ) {
			throw new LogicException( 'getValidatedItem() called before validate()' );
		}

		return $this->deserializedItem;
	}

}
