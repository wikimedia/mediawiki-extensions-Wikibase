<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\Repo\RestApi\Domain\ReadModel\ItemParts;

/**
 * @license GPL-2.0-or-later
 */
class ItemPartsSerializer {

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

	public function serialize( ItemParts $itemParts ): array {
		$fieldSerializers = [
			ItemParts::FIELD_TYPE => fn() => ItemParts::TYPE,
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			ItemParts::FIELD_LABELS => fn() => $this->labelsSerializer->serialize( $itemParts->getLabels() ),
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			ItemParts::FIELD_DESCRIPTIONS => fn() => $this->descriptionsSerializer->serialize( $itemParts->getDescriptions() ),
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			ItemParts::FIELD_ALIASES => fn() => $this->aliasesSerializer->serialize( $itemParts->getAliases() ),
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			ItemParts::FIELD_STATEMENTS => fn() => $this->statementsSerializer->serialize( $itemParts->getStatements() ),
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			ItemParts::FIELD_SITELINKS => fn() => $this->siteLinksSerializer->serialize( $itemParts->getSiteLinks() ),
		];

		// serialize all fields, filtered by isRequested()
		$serialization = array_map(
			fn( callable $serializeField ) => $serializeField(),
			array_filter(
				$fieldSerializers,
				fn ( string $fieldName ) => $itemParts->isRequested( $fieldName ),
				ARRAY_FILTER_USE_KEY
			)
		);

		$serialization['id'] = $itemParts->getId()->getSerialization();

		return $serialization;
	}

}
