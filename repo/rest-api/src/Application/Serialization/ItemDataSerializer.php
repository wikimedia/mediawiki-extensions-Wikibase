<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\Repo\RestApi\Domain\ReadModel\ItemData;

/**
 * @license GPL-2.0-or-later
 */
class ItemDataSerializer {

	private LabelsSerializer $labelsSerializer;
	private DescriptionsSerializer $descriptionsSerializer;
	private AliasesSerializer $aliasesSerializer;
	private StatementListSerializer $statementsSerializer;
	private SiteLinksSerializer $siteLinksSerializer;

	public function __construct(
		LabelsSerializer $labelsSerializer,
		DescriptionsSerializer $descriptionsSerializer,
		AliasesSerializer $aliasesSerializer,
		StatementListSerializer $statementsSerializer,
		SiteLinksSerializer $siteLinksSerializer
	) {
		$this->labelsSerializer = $labelsSerializer;
		$this->descriptionsSerializer = $descriptionsSerializer;
		$this->aliasesSerializer = $aliasesSerializer;
		$this->statementsSerializer = $statementsSerializer;
		$this->siteLinksSerializer = $siteLinksSerializer;
	}

	public function serialize( ItemData $itemData ): array {
		$fieldSerializers = [
			ItemData::FIELD_TYPE => fn() => ItemData::TYPE,
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			ItemData::FIELD_LABELS => fn() => $this->labelsSerializer->serialize( $itemData->getLabels() ),
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			ItemData::FIELD_DESCRIPTIONS => fn() => $this->descriptionsSerializer->serialize( $itemData->getDescriptions() ),
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			ItemData::FIELD_ALIASES => fn() => $this->aliasesSerializer->serialize( $itemData->getAliases() ),
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			ItemData::FIELD_STATEMENTS => fn() => $this->statementsSerializer->serialize( $itemData->getStatements() ),
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			ItemData::FIELD_SITELINKS => fn() => $this->siteLinksSerializer->serialize( $itemData->getSiteLinks() ),
		];

		// serialize all $itemData fields, filtered by isRequested()
		$serialization = array_map(
			fn( callable $serializeField ) => $serializeField(),
			array_filter(
				$fieldSerializers,
				fn ( string $fieldName ) => $itemData->isRequested( $fieldName ),
				ARRAY_FILTER_USE_KEY
			)
		);

		$serialization['id'] = $itemData->getId()->getSerialization();

		return $serialization;
	}

}
