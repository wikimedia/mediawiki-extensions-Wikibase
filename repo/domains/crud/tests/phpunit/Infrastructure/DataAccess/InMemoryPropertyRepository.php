<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Infrastructure\DataAccess;

use LogicException;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property as PropertyWriteModel;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Aliases;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Descriptions;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Labels;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Property;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\PropertyRevision;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\StatementList;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyAliasesRetriever;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyCreator;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyDescriptionsRetriever;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyLabelsRetriever;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyRetriever;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyUpdater;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyWriteModelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class InMemoryPropertyRepository implements
	PropertyWriteModelRetriever,
	PropertyRetriever,
	PropertyLabelsRetriever,
	PropertyDescriptionsRetriever,
	PropertyAliasesRetriever,
	PropertyUpdater,
	PropertyCreator
{

	use StatementReadModelHelper;

	private array $properties = [];
	private array $latestRevisionData = [];

	public function addProperty( PropertyWriteModel $property ): void {
		if ( !$property->getId() ) {
			throw new LogicException( 'Test property must have an ID.' );
		}

		$this->properties[$property->getId()->getSerialization()] = $property;
	}

	public function getLatestRevisionId( PropertyId $id ): int {
		return $this->latestRevisionData["$id"]['revId'];
	}

	public function getLatestRevisionTimestamp( PropertyId $id ): string {
		return $this->latestRevisionData["$id"]['revTime'];
	}

	public function getLatestRevisionEditMetadata( PropertyId $id ): EditMetadata {
		return $this->latestRevisionData["$id"]['editMetadata'];
	}

	public function getPropertyWriteModel( PropertyId $propertyId ): ?PropertyWriteModel {
		return $this->properties[ $propertyId->getSerialization() ] ?? null;
	}

	public function getProperty( PropertyId $propertyId ): ?Property {
		return $this->convertToReadModel( $this->getPropertyWriteModel( $propertyId ) );
	}

	public function getLabels( PropertyId $propertyId ): ?Labels {
		return $this->properties["$propertyId"] ? $this->convertToReadModel( $this->properties["$propertyId"] )->getLabels() : null;
	}

	public function getDescriptions( PropertyId $propertyId ): ?Descriptions {
		return $this->properties["$propertyId"] ? $this->convertToReadModel( $this->properties["$propertyId"] )->getDescriptions() : null;
	}

	public function getAliases( PropertyId $propertyId ): ?Aliases {
		return $this->properties["$propertyId"] ? $this->convertToReadModel( $this->properties["$propertyId"] )->getAliases() : null;
	}

	public function create( PropertyWriteModel $property, EditMetadata $editMetadata ): PropertyRevision {
		$property->setId( new NumericPropertyId( 'P' . rand( 1, 9999 ) ) );

		return $this->update( $property, $editMetadata );
	}

	public function update( PropertyWriteModel $property, EditMetadata $editMetadata ): PropertyRevision {
		$this->properties[$property->getId()->getSerialization()] = $property;
		$revisionData = [
			'revId' => rand(),
			// using the real date/time here is a bit dangerous, but should be ok as long as revId is also checked.
			'revTime' => date( 'YmdHis' ),
			'editMetadata' => $editMetadata,
		];
		$this->latestRevisionData[$property->getId()->getSerialization()] = $revisionData;

		return new PropertyRevision( $this->convertToReadModel( $property ), $revisionData['revTime'], $revisionData['revId'] );
	}

	private function convertToReadModel( PropertyWriteModel $property ): Property {
		return new Property(
			$property->getId(),
			$property->getDataTypeId(),
			Labels::fromTermList( $property->getLabels() ),
			Descriptions::fromTermList( $property->getDescriptions() ),
			Aliases::fromAliasGroupList( $property->getAliasGroups() ),
			new StatementList( ...array_map(
				[ $this->newStatementReadModelConverter(), 'convert' ],
				iterator_to_array( $property->getStatements() )
			) )
		);
	}

}
