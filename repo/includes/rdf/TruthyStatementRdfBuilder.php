<?php

namespace Wikibase;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\StatementListProvider;
use Wikibase\RDF\RdfWriter;

/**
 * "Truthy" RDF mapping for wikibase statements.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class TruthyStatementRdfBuilder {

	const NS_ONTOLOGY = 'wikibase'; // wikibase ontology (shared)
	const NS_ENTITY = 'entity'; // concept uris
	const NS_DIRECT_CLAIM = 'wdt'; // direct assertion entity -> value

	/**
	 * @var RdfWriter
	 */
	private $writer;

	/**
	 * @var SnakValueRdfBuilder
	 */
	private $valueBuilder;

	/**
	 * @param RdfWriter $writer
	 * @param SnakValueRdfBuilder $valueBuilder
	 */
	public function __construct( RdfWriter $writer, SnakValueRdfBuilder $valueBuilder ) {
		$this->writer = $writer;
		$this->valueBuilder = $valueBuilder;
	}

	/**
	 * Returns a local name for the given entity using the given prefix.
	 *
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	private function getEntityLName( EntityId $entityId ) {
		return ucfirst( $entityId->getSerialization() );
	}

	/**
	 * Adds all Statements from the given entity to the RDF graph.
	 *
	 * @param EntityId $entityId
	 * @param StatementListProvider $entity
	 */
	public function addStatements( EntityId $entityId, StatementListProvider $entity ) {
		foreach ( $entity->getStatements()->getBestStatementPerProperty() as $statement ) {
			$this->addMainSnak( $entityId, $statement, true );
		}
	}

	/**
	 * Adds the given Statement's main Snak to the RDF graph.
	 *
	 * @param EntityId $entityId
	 * @param Statement $statement
	 */
	private function addMainSnak( EntityId $entityId, Statement $statement ) {
		$snak = $statement->getMainSnak();

		$entityLName = $this->getEntityLName( $entityId );

		$this->writer->about( self::NS_ENTITY, $entityLName );

		$propertyId = $snak->getPropertyId();
		switch ( $snak->getType() ) {
			case 'value':
				/** @var PropertyValueSnak $snak */
				$this->valueBuilder->addStatementValue( $propertyId, $snak->getDataValue(), self::NS_DIRECT_CLAIM );
				break;
			case 'somevalue':
				$propertyValueLName = $this->getEntityLName( $propertyId );

				$this->writer->say( self::NS_DIRECT_CLAIM, $propertyValueLName )->is( self::NS_ONTOLOGY, 'Somevalue' );
				break;
			case 'novalue':
				$propertyValueLName = $this->getEntityLName( $propertyId );

				$this->writer->say( self::NS_DIRECT_CLAIM, $propertyValueLName )->is( self::NS_ONTOLOGY, 'Novalue' );
				break;
			default:
				throw new \InvalidArgumentException( 'Unknown snak type: ' . $snak->getType() );
		}
	}

}
