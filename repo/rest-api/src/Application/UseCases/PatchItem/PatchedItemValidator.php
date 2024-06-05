<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItem;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementsDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class PatchedItemValidator {

	private LabelsDeserializer $labelsDeserializer;
	private DescriptionsDeserializer $descriptionsDeserializer;
	private AliasesDeserializer $aliasesDeserializer;
	private SitelinkDeserializer $sitelinkDeserializer;
	private StatementsDeserializer $statementsDeserializer;

	public function __construct(
		LabelsDeserializer $labelsDeserializer,
		DescriptionsDeserializer $descriptionsDeserializer,
		AliasesDeserializer $aliasesDeserializer,
		SitelinkDeserializer $sitelinkDeserializer,
		StatementsDeserializer $statementsDeserializer
	) {
		$this->labelsDeserializer = $labelsDeserializer;
		$this->descriptionsDeserializer = $descriptionsDeserializer;
		$this->aliasesDeserializer = $aliasesDeserializer;
		$this->sitelinkDeserializer = $sitelinkDeserializer;
		$this->statementsDeserializer = $statementsDeserializer;
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

		return new Item(
			new ItemId( $serialization[ 'id' ] ),
			new Fingerprint(
				$this->labelsDeserializer->deserialize( $serialization[ 'labels' ] ?? [] ),
				$this->descriptionsDeserializer->deserialize( $serialization[ 'descriptions' ] ?? [] ),
				$this->aliasesDeserializer->deserialize( $serialization[ 'aliases' ] ?? [] )
			),
			$this->deserializeSitelinks( $serialization[ 'sitelinks' ] ?? [] ),
			$this->statementsDeserializer->deserialize( $serialization[ 'statements' ] ?? [] )
		);
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

	private function deserializeSitelinks( array $sitelinksSerialization ): SiteLinkList {
		$sitelinkList = [];
		foreach ( $sitelinksSerialization as $siteId => $sitelink ) {
			$sitelinkList[] = $this->sitelinkDeserializer->deserialize( $siteId, $sitelink );
		}

		return new SiteLinkList( $sitelinkList );
	}

}
