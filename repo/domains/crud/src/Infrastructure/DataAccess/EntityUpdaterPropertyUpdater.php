<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess;

use Wikibase\DataModel\Entity\Property as PropertyWriteModel;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Aliases;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Descriptions;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Labels;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Property;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\PropertyRevision;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\StatementList;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyCreator;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyUpdater;
use Wikibase\Repo\Domains\Crud\Domain\Services\StatementReadModelConverter;

/**
 * @license GPL-2.0-or-later
 */
class EntityUpdaterPropertyUpdater implements PropertyUpdater, PropertyCreator {

	private EntityUpdater $entityUpdater;
	private StatementReadModelConverter $statementReadModelConverter;

	public function __construct( EntityUpdater $entityUpdater, StatementReadModelConverter $statementReadModelConverter ) {
		$this->entityUpdater = $entityUpdater;
		$this->statementReadModelConverter = $statementReadModelConverter;
	}

	public function create( PropertyWriteModel $property, EditMetadata $editMetadata ): PropertyRevision {
		return $this->convertToPropertyRevision( $this->entityUpdater->create( $property, $editMetadata ) );
	}

	public function update( PropertyWriteModel $property, EditMetadata $editMetadata ): PropertyRevision {
		return $this->convertToPropertyRevision( $this->entityUpdater->update( $property, $editMetadata ) );
	}

	private function convertToPropertyRevision( EntityRevision $entityRevision ): PropertyRevision {
		/** @var PropertyWriteModel $savedProperty */
		$savedProperty = $entityRevision->getEntity();
		'@phan-var PropertyWriteModel $savedProperty';

		return new PropertyRevision(
			$this->convertPropertyWriteModelToReadModel( $savedProperty ),
			$entityRevision->getTimestamp(),
			$entityRevision->getRevisionId()
		);
	}

	private function convertPropertyWriteModelToReadModel( PropertyWriteModel $property ): Property {
		return new Property(
			$property->getId(),
			$property->getDataTypeId(),
			Labels::fromTermList( $property->getLabels() ),
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
