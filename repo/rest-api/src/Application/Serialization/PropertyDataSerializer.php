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
			PropertyData::FIELD_ID => fn() => $propertyData->getId()->getSerialization(),
			PropertyData::FIELD_TYPE => fn() => $propertyData::TYPE,
			PropertyData::FIELD_DATA_TYPE => fn() => $propertyData->getDataType(),
			PropertyData::FIELD_LABELS => fn() => $this->labelsSerializer->serialize( $propertyData->getLabels() ),
			PropertyData::FIELD_DESCRIPTIONS => fn() => $this->descriptionsSerializer->serialize( $propertyData->getDescriptions() ),
			PropertyData::FIELD_ALIASES => fn() => $this->aliasesSerializer->serialize( $propertyData->getAliases() ),
			PropertyData::FIELD_STATEMENTS => fn() => $this->statementsSerializer->serialize( $propertyData->getStatements() ),
		];

		return array_map(
			fn( callable $serializeField ) => $serializeField(),
			$fieldSerializers,
		);
	}

}
