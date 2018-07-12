<?php

namespace Wikibase\Repo\EntityReferenceExtractors;

use DataValues\DataValue;
use DataValues\UnboundedQuantityValue;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\DataModel\Services\EntityId\SuffixEntityIdParser;

/**
 * Extracts ids of entities that are referenced on a given entity within its statements.
 *
 * @license GPL-2.0-or-later
 */
class StatementEntityReferenceExtractor implements EntityReferenceExtractor {

	/**
	 * @var EntityId[]
	 */
	private $entityIds = [];

	/**
	 * @var SuffixEntityIdParser
	 */
	private $entityIdUriParser;

	public function __construct( SuffixEntityIdParser $entityIdParser ) {
		$this->entityIdUriParser = $entityIdParser;
	}

	/**
	 * @param EntityDocument|StatementListProvider $entity
	 *
	 * @return EntityId[]
	 */
	public function extractEntityIds( EntityDocument $entity ) {
		foreach ( $entity->getStatements() as $statement ) {
			$this->processStatement( $statement );
		}

		return array_values( $this->entityIds );
	}

	private function processStatement( Statement $statement ) {
		foreach ( $statement->getAllSnaks() as $snak ) {
			$this->processSnak( $snak );
		}
	}

	private function processSnak( Snak $snak ) {
		$propertyId = $snak->getPropertyId();
		$this->entityIds[$propertyId->getSerialization()] = $propertyId;

		if ( $snak instanceof PropertyValueSnak ) {
			$this->processDataValue( $snak->getDataValue() );
		}
	}

	private function processDataValue( DataValue $dataValue ) {
		if ( $dataValue instanceof EntityIdValue ) {
			$entityId = $dataValue->getEntityId();
			$this->entityIds[$entityId->getSerialization()] = $entityId;
		} elseif ( $dataValue instanceof UnboundedQuantityValue ) {
			$unitUri = $dataValue->getUnit();
			$this->processUri( $unitUri );
		}

		// TODO: EntityIds from GlobeCoordinateValue's globe URI (Wikidata, not local item URI!)
		// TODO: EntityIds from TimeValue's calendar URI (Wikidata, not local item URI!)
	}

	private function processUri( $uri ) {
		try {
			$entityId = $this->entityIdUriParser->parse( $uri );
			$this->entityIds[$entityId->getSerialization()] = $entityId;
		} catch ( EntityIdParsingException $ex ) {
			// noop
		}
	}

}
