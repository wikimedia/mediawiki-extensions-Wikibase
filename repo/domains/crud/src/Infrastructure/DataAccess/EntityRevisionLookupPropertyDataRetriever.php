<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess;

use Wikibase\DataModel\Entity\Property as PropertyWriteModel;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Statement\StatementList as StatementListWriteModel;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Aliases;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Descriptions;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Labels;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Property;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\PropertyParts;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\PropertyPartsBuilder;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\StatementList;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyPartsRetriever;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyRetriever;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyStatementsRetriever;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyWriteModelRetriever;
use Wikibase\Repo\Domains\Crud\Domain\Services\StatementReadModelConverter;

/**
 * @license GPL-2.0-or-later
 */
class EntityRevisionLookupPropertyDataRetriever
	implements PropertyRetriever, PropertyPartsRetriever, PropertyWriteModelRetriever, PropertyStatementsRetriever {

	private EntityRevisionLookup $entityRevisionLookup;
	private StatementReadModelConverter $statementReadModelConverter;

	public function __construct(
		EntityRevisionLookup $entityRevisionLookup,
		StatementReadModelConverter $statementReadModelConverter
	) {
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->statementReadModelConverter = $statementReadModelConverter;
	}

	public function getPropertyWriteModel( PropertyId $propertyId ): ?PropertyWriteModel {
		$entityRevision = $this->entityRevisionLookup->getEntityRevision( $propertyId );
		// @phan-suppress-next-line PhanTypeMismatchReturn
		return $entityRevision ? $entityRevision->getEntity() : null;
	}

	public function getProperty( PropertyId $propertyId ): ?Property {
		$property = $this->getPropertyWriteModel( $propertyId );

		if ( $property === null ) {
			return null;
		}

		return new Property(
			$property->getId(),
			$property->getDataTypeId(),
			Labels::fromTermList( $property->getLabels() ),
			Descriptions::fromTermList( $property->getDescriptions() ),
			Aliases::fromAliasGroupList( $property->getAliasGroups() ),
			$this->convertStatementListWriteModelToReadModel( $property->getStatements() )
		);
	}

	public function getPropertyParts( PropertyId $propertyId, array $fields ): ?PropertyParts {
		$property = $this->getPropertyWriteModel( $propertyId );
		if ( $property === null ) {
			return null;
		}
		return $this->propertyPartsFromRequestedFields( $fields, $property );
	}

	private function propertyPartsFromRequestedFields( array $fields, PropertyWriteModel $property ): PropertyParts {
		$propertyParts = ( new PropertyPartsBuilder( $property->getId(), $fields ) );

		if ( in_array( PropertyParts::FIELD_DATA_TYPE, $fields ) ) {
			$propertyParts->setDataType( $property->getDataTypeId() );
		}
		if ( in_array( PropertyParts::FIELD_LABELS, $fields ) ) {
			$propertyParts->setLabels( Labels::fromTermList( $property->getLabels() ) );
		}
		if ( in_array( PropertyParts::FIELD_DESCRIPTIONS, $fields ) ) {
			$propertyParts->setDescriptions( Descriptions::fromTermList( $property->getDescriptions() ) );
		}
		if ( in_array( PropertyParts::FIELD_ALIASES, $fields ) ) {
			$propertyParts->setAliases( Aliases::fromAliasGroupList( $property->getAliasGroups() ) );
		}
		if ( in_array( PropertyParts::FIELD_STATEMENTS, $fields ) ) {
			$propertyParts->setStatements(
				new StatementList(
					...array_map(
						[ $this->statementReadModelConverter, 'convert' ],
						iterator_to_array( $property->getStatements() )
					)
				)
			);
		}

		return $propertyParts->build();
	}

	public function getStatements( PropertyId $propertyId, ?PropertyId $filterPropertyId = null ): ?StatementList {
		$property = $this->getPropertyWriteModel( $propertyId );
		if ( $property === null ) {
			return null;
		}

		return $this->convertStatementListWriteModelToReadModel(
			$filterPropertyId ? $property->getStatements()->getByPropertyId( $filterPropertyId ) : $property->getStatements()
		);
	}

	private function convertStatementListWriteModelToReadModel( StatementListWriteModel $list ): StatementList {
		return new StatementList( ...array_map(
			[ $this->statementReadModelConverter, 'convert' ],
			iterator_to_array( $list )
		) );
	}

}
