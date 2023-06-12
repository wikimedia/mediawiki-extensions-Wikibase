<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyParts;

/**
 * @license GPL-2.0-or-later
 */
class PropertyPartsSerializer {

	private LabelsSerializer $labelsSerializer;
	private DescriptionsSerializer $descriptionsSerializer;
	private AliasesSerializer $aliasesSerializer;
	private StatementListSerializer $statementsSerializer;

	public function __construct(
		LabelsSerializer $labelsSerializer,
		DescriptionsSerializer $descriptionsSerializer,
		AliasesSerializer $aliasesSerializer,
		StatementListSerializer $statementsSerializer
	) {
		$this->labelsSerializer = $labelsSerializer;
		$this->descriptionsSerializer = $descriptionsSerializer;
		$this->aliasesSerializer = $aliasesSerializer;
		$this->statementsSerializer = $statementsSerializer;
	}

	public function serialize( PropertyParts $propertyParts ): array {
		$fieldSerializers = [
			PropertyParts::FIELD_TYPE => fn() => $propertyParts::TYPE,
			PropertyParts::FIELD_DATA_TYPE => fn() => $propertyParts->getDataType(),
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			PropertyParts::FIELD_LABELS => fn() => $this->labelsSerializer->serialize( $propertyParts->getLabels() ),
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			PropertyParts::FIELD_DESCRIPTIONS => fn() => $this->descriptionsSerializer->serialize( $propertyParts->getDescriptions() ),
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			PropertyParts::FIELD_ALIASES => fn() => $this->aliasesSerializer->serialize( $propertyParts->getAliases() ),
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			PropertyParts::FIELD_STATEMENTS => fn() => $this->statementsSerializer->serialize( $propertyParts->getStatements() ),
		];

		// serialize all fields, filtered by isRequested()
		$serialization = array_map(
			fn( callable $serializeField ) => $serializeField(),
			array_filter(
				$fieldSerializers,
				fn ( string $fieldName ) => $propertyParts->isRequested( $fieldName ),
				ARRAY_FILTER_USE_KEY
			),
		);

		$serialization['id'] = $propertyParts->getId()->getSerialization();

		return $serialization;
	}

}
