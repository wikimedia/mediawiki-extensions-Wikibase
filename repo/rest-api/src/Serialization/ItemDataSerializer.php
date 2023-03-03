<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Serialization;

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
			ItemData::FIELD_TYPE => fn() => $itemData->getType(),
			ItemData::FIELD_LABELS => fn() => $this->labelsSerializer->serialize( $itemData->getLabels() ),
			ItemData::FIELD_DESCRIPTIONS => fn() => $this->descriptionsSerializer->serialize( $itemData->getDescriptions() ),
			ItemData::FIELD_ALIASES => fn() => $this->aliasesSerializer->serialize( $itemData->getAliases() ),
			ItemData::FIELD_STATEMENTS => fn() => $this->statementsSerializer->serialize( $itemData->getStatements() ),
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
