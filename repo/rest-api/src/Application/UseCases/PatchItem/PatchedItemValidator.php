<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItem;

use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\RestApi\Application\Serialization\ItemDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class PatchedItemValidator {

	private ItemDeserializer $itemDeserializer;

	public function __construct( ItemDeserializer $itemDeserializer ) {
		$this->itemDeserializer = $itemDeserializer;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( array $serialization, Item $originalItem ): Item {
		if ( !isset( $serialization['id'] ) ) { // ignore ID removal
			$serialization['id'] = $originalItem->getId()->getSerialization();
		}

		$this->assertNoIllegalModification( $serialization, $originalItem );
		$this->assertNoUnexpectedFields( $serialization );
		$this->assertValidFields( $serialization );

		return $this->itemDeserializer->deserialize( $serialization );
	}

	private function assertNoIllegalModification( array $serialization, Item $originalItem ): void {
		if ( $serialization[ 'id' ] !== $originalItem->getId()->getSerialization() ) {
			throw new UseCaseError(
				UseCaseError::PATCHED_ITEM_INVALID_OPERATION_CHANGE_ITEM_ID,
				'Cannot change the ID of the existing item'
			);
		}
	}

	private function assertNoUnexpectedFields( array $serialization ): void {
		$expectedFields = [ 'id', 'type', 'labels', 'descriptions', 'aliases', 'sitelinks', 'statements' ];

		foreach ( array_keys( $serialization ) as $field ) {
			if ( !in_array( $field, $expectedFields ) ) {
				throw new UseCaseError(
					UseCaseError::PATCHED_ITEM_UNEXPECTED_FIELD,
					"The patched item contains an unexpected field: '$field'"
				);
			}
		}
	}

	private function assertValidFields( array $serialization ): void {
		// 'id' is not modifiable and 'type' is ignored, so we only check the expected array fields
		foreach ( [ 'labels', 'descriptions', 'aliases', 'sitelinks', 'statements' ] as $field ) {
			if ( isset( $serialization[$field] ) && !is_array( $serialization[$field] ) ) {
				throw new UseCaseError(
					UseCaseError::PATCHED_ITEM_INVALID_FIELD,
					"Invalid input for '$field' in the patched item",
					[
						UseCaseError::CONTEXT_PATH => $field,
						UseCaseError::CONTEXT_VALUE => $serialization[$field],
					]
				);
			}
		}
	}

}
