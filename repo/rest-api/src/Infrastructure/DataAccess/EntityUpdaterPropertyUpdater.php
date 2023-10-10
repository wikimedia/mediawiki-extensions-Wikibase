<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure\DataAccess;

use Wikibase\DataModel\Entity\Property as DataModelProperty;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Property;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyRevision;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;
use Wikibase\Repo\RestApi\Domain\Services\StatementReadModelConverter;

/**
 * @license GPL-2.0-or-later
 */
class EntityUpdaterPropertyUpdater implements PropertyUpdater {

	private EntityUpdater $entityUpdater;
	private StatementReadModelConverter $statementReadModelConverter;

	public function __construct( EntityUpdater $entityUpdater, StatementReadModelConverter $statementReadModelConverter ) {
		$this->entityUpdater = $entityUpdater;
		$this->statementReadModelConverter = $statementReadModelConverter;
	}

	public function update( DataModelProperty $property, EditMetadata $editMetadata ): PropertyRevision {
		$entityRevision = $this->entityUpdater->update( $property, $editMetadata );

		/** @var DataModelProperty $savedProperty */
		$savedProperty = $entityRevision->getEntity();
		'@phan-var DataModelProperty $savedProperty';

		return new PropertyRevision(
			$this->convertDataModelPropertyToReadModel( $savedProperty ),
			$entityRevision->getTimestamp(),
			$entityRevision->getRevisionId()
		);
	}

	private function convertDataModelPropertyToReadModel( DataModelProperty $property ): Property {
		return new Property(
			Descriptions::fromTermList( $property->getDescriptions() ),
			Aliases::fromAliasGroupList( $property->getAliasGroups() ),
			new StatementList(
				...array_map(
					[ $this->statementReadModelConverter, 'convert' ],
					iterator_to_array( $property->getStatements() )
				)
			)
		);
	}

}
