<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyData;

/**
 * @license GPL-2.0-or-later
 */
class PropertyDataSerializer {

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

	public function serialize( PropertyData $propertyData ): array {
		$fieldSerializers = [
			PropertyData::FIELD_TYPE => fn() => $propertyData::TYPE,
			PropertyData::FIELD_DATA_TYPE => fn() => $propertyData->getDataType(),
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			PropertyData::FIELD_LABELS => fn() => $this->labelsSerializer->serialize( $propertyData->getLabels() ),
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			PropertyData::FIELD_DESCRIPTIONS => fn() => $this->descriptionsSerializer->serialize( $propertyData->getDescriptions() ),
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			PropertyData::FIELD_ALIASES => fn() => $this->aliasesSerializer->serialize( $propertyData->getAliases() ),
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			PropertyData::FIELD_STATEMENTS => fn() => $this->statementsSerializer->serialize( $propertyData->getStatements() ),
		];

		// serialize all fields, filtered by isRequested()
		$serialization = array_map(
			fn( callable $serializeField ) => $serializeField(),
			array_filter(
				$fieldSerializers,
				fn ( string $fieldName ) => $propertyData->isRequested( $fieldName ),
				ARRAY_FILTER_USE_KEY
			),
		);

		$serialization['id'] = $propertyData->getId()->getSerialization();

		return $serialization;
	}

}
