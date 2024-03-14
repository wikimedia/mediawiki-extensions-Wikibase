<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\DataModel\Entity\Property as PropertyWriteModel;
use Wikibase\Repo\RestApi\Domain\ReadModel\Property as PropertyReadModel;

/**
 * @license GPL-2.0-or-later
 */
class PropertySerializer {

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

	public function serialize( PropertyReadModel $property ): array {
		return [
			'id' => $property->getId()->getSerialization(),
			'type' => PropertyWriteModel::ENTITY_TYPE,
			'data-type' => $property->getDataType(),
			'labels' => $this->labelsSerializer->serialize( $property->getLabels() ),
			'descriptions' => $this->descriptionsSerializer->serialize( $property->getDescriptions() ),
			'aliases' => $this->aliasesSerializer->serialize( $property->getAliases() ),
			'statements' => $this->statementsSerializer->serialize( $property->getStatements() ),
		];
	}

}
