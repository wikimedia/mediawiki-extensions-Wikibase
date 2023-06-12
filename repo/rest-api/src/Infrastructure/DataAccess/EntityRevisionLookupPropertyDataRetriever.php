<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure\DataAccess;

use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyData;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyDataBuilder;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\PropertyDataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\StatementReadModelConverter;

/**
 * @license GPL-2.0-or-later
 */
class EntityRevisionLookupPropertyDataRetriever implements PropertyDataRetriever {

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

	public function getPropertyData( PropertyId $propertyId, array $fields ): ?PropertyData {
		$property = $this->getProperty( $propertyId );
		if ( $property === null ) {
			return null;
		}
		return $this->propertyDataFromRequestedFields( $fields, $property );
	}

	private function propertyDataFromRequestedFields( array $fields, Property $property ): PropertyData {
		$propertyData = ( new PropertyDataBuilder( $property->getId(), $fields ) );

		if ( in_array( PropertyData::FIELD_DATA_TYPE, $fields ) ) {
			$propertyData->setDataType( $property->getDataTypeId() );
		}
		if ( in_array( PropertyData::FIELD_LABELS, $fields ) ) {
			$propertyData->setLabels( Labels::fromTermList( $property->getLabels() ) );
		}
		if ( in_array( PropertyData::FIELD_DESCRIPTIONS, $fields ) ) {
			$propertyData->setDescriptions( Descriptions::fromTermList( $property->getDescriptions() ) );
		}
		if ( in_array( PropertyData::FIELD_ALIASES, $fields ) ) {
			$propertyData->setAliases( Aliases::fromAliasGroupList( $property->getAliasGroups() ) );
		}
		if ( in_array( PropertyData::FIELD_STATEMENTS, $fields ) ) {
			$propertyData->setStatements(
				new StatementList(
					...array_map(
						[ $this->statementReadModelConverter, 'convert' ],
						iterator_to_array( $property->getStatements() )
					)
				)
			);
		}

		return $propertyData->build();
	}
}
