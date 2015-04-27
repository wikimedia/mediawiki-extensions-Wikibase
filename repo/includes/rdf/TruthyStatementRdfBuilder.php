<?php

namespace Wikibase\Rdf;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\StatementListProvider;
use Wikimedia\Purtle\RdfWriter;

/**
 * "Truthy" RDF mapping for wikibase statements.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class TruthyStatementRdfBuilder implements EntityRdfBuilder {

	/**
	 * @var RdfVocabulary
	 */
	private $vocabulary;

	/**
	 * @var RdfWriter
	 */
	private $writer;

	/**
	 * @var SnakValueRdfBuilder
	 */
	private $valueBuilder;

	/**
	 * @param RdfVocabulary $vocabulary
	 * @param RdfWriter $writer
	 * @param SnakValueRdfBuilder $valueBuilder
	 */
	public function __construct( RdfVocabulary $vocabulary, RdfWriter $writer, SnakValueRdfBuilder $valueBuilder ) {
		$this->vocabulary = $vocabulary;
		$this->writer = $writer;
		$this->valueBuilder = $valueBuilder;
	}

	/**
	 * Adds Statements to the RDF graph.
	 *
	 * @param EntityId $entityId
	 * @param StatementList $statementList
	 */
	public function addStatements( EntityId $entityId, StatementList $statementList ) {
		// FIXME: getBestStatementPerProperty() uis expensive, share the result with FullStatementRdfBuilder!
		foreach ( $statementList->getBestStatementPerProperty() as $statement ) {
			$this->addMainSnak( $entityId, $statement, true );
		}
	}

	/**
	 * Adds the given Statement's main Snak to the RDF graph.
	 *
	 * @todo share more of this code with FullStatementRdfBuilder
	 *
	 * @param EntityId $entityId
	 * @param Statement $statement
	 */
	private function addMainSnak( EntityId $entityId, Statement $statement ) {
		$snak = $statement->getMainSnak();

		$entityLName = $this->vocabulary->getEntityLName( $entityId );

		$this->writer->about( RdfVocabulary::NS_ENTITY, $entityLName );

		$propertyId = $snak->getPropertyId();
		switch ( $snak->getType() ) {
			case 'value':
				/** @var PropertyValueSnak $snak */
				$this->valueBuilder->addSnakValue( $this->writer, $propertyId, $snak->getDataValue(), RdfVocabulary::NSP_DIRECT_CLAIM );
				break;
			case 'somevalue':
				$propertyValueLName = $this->vocabulary->getEntityLName( $propertyId );

				$this->writer->say( RdfVocabulary::NSP_DIRECT_CLAIM, $propertyValueLName )->is( '_', $this->writer->blank() );
				break;
			case 'novalue':
				$propertyValueLName = $this->vocabulary->getEntityLName( $propertyId );

				$this->writer->say( 'a' )->is( RdfVocabulary::NSP_NOVALUE, $propertyValueLName );
				break;
			default:
				throw new InvalidArgumentException( 'Unknown snak type: ' . $snak->getType() );
		}
	}

	/**
	 * Add truthy statements for the given entity to the RDF graph.
	 *
	 * @param EntityDocument $entity the entity to output.
	 */
	public function addEntity( EntityDocument $entity ) {
		$entityId = $entity->getId();

		if ( $entity instanceof StatementListProvider ) {
			$this->addStatements( $entityId, $entity->getStatements() );
		}
	}

}
