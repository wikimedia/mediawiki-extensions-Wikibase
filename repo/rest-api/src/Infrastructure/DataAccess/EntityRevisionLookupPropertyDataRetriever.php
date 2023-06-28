<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure\DataAccess;

use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Statement\StatementList as DataModelStatementList;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyParts;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyPartsBuilder;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\PropertyPartsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyStatementsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\StatementReadModelConverter;

/**
 * @license GPL-2.0-or-later
 */
class EntityRevisionLookupPropertyDataRetriever implements PropertyPartsRetriever, PropertyStatementsRetriever {

	private EntityRevisionLookup $entityRevisionLookup;
	private StatementReadModelConverter $statementReadModelConverter;

	public function __construct(
		EntityRevisionLookup $entityRevisionLookup,
		StatementReadModelConverter $statementReadModelConverter
	) {
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->statementReadModelConverter = $statementReadModelConverter;
	}

	public function getProperty( PropertyId $propertyId ): ?Property {
		$entityRevision = $this->entityRevisionLookup->getEntityRevision( $propertyId );

		if ( !$entityRevision ) {
			return null;
		}

		// @phan-suppress-next-line PhanTypeMismatchReturn
		return $entityRevision->getEntity();
	}

	public function getPropertyParts( PropertyId $propertyId, array $fields ): ?PropertyParts {
		$property = $this->getProperty( $propertyId );
		if ( $property === null ) {
			return null;
		}
		return $this->propertyPartsFromRequestedFields( $fields, $property );
	}

	private function propertyPartsFromRequestedFields( array $fields, Property $property ): PropertyParts {
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

	public function getStatements( PropertyId $subjectPropertyId, ?PropertyId $filterPropertyId = null ): ?StatementList {
		$property = $this->getProperty( $subjectPropertyId );
		if ( $property === null ) {
			return null;
		}

		return $this->convertDataModelStatementListToReadModel(
			$filterPropertyId ? $property->getStatements()->getByPropertyId( $filterPropertyId ) : $property->getStatements()
		);
	}

	private function convertDataModelStatementListToReadModel( DataModelStatementList $list ): StatementList {
		return new StatementList( ...array_map(
			[ $this->statementReadModelConverter, 'convert' ],
			iterator_to_array( $list )
		) );
	}

}
