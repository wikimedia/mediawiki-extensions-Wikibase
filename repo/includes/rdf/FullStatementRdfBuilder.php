<?php

namespace Wikibase\Rdf;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\StatementListProvider;
use Wikimedia\Purtle\RdfWriter;

/**
 * Fully reified RDF mapping for wikibase statements.
 * This does not output simple statements. If both forms (simple and full) are desired,
 * use SimpleStatementRdfBuilder in addition to FullStatementRdfBuilder.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class FullStatementRdfBuilder implements EntityRdfBuilder {

	/**
	 * @var callable
	 */
	private $propertyMentionCallback = null;

	/**
	 * @var callable
	 */
	private $referenceSeenCallback = null;

	/**
	 * @var bool
	 */
	private $produceQualifiers = true;

	/**
	 * @var bool
	 */
	private $produceReferences = true;

	/**
	 * @var RdfVocabulary
	 */
	private $vocabulary;

	/**
	 * @var RdfWriter
	 */
	private $statementWriter;

	/**
	 * @var RdfWriter
	 */
	private $referenceWriter;

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

		// Note: since we process references as nested structures, they need a separate
		// rdf writer, so outputting references doesn't destroy the state of the statement writer.
		$this->statementWriter = $writer->sub();
		$this->referenceWriter = $writer;

		$this->valueBuilder = $valueBuilder;
	}

	/**
	 * @return callable
	 */
	public function getPropertyMentionCallback() {
		return $this->propertyMentionCallback;
	}

	/**
	 * @param callable $propertyMentionCallback
	 */
	public function setPropertyMentionCallback( $propertyMentionCallback ) {
		$this->propertyMentionCallback = $propertyMentionCallback;
	}

	/**
	 * @return callable
	 */
	public function getReferenceSeenCallback() {
		return $this->referenceSeenCallback;
	}

	/**
	 * @param callable $referenceSeenCallback
	 */
	public function setReferenceSeenCallback( $referenceSeenCallback ) {
		$this->referenceSeenCallback = $referenceSeenCallback;
	}

	/**
	 * @return boolean
	 */
	public function getProduceQualifiers() {
		return $this->produceQualifiers;
	}

	/**
	 * @param boolean $produceQualifiers
	 */
	public function setProduceQualifiers( $produceQualifiers ) {
		$this->produceQualifiers = $produceQualifiers;
	}

	/**
	 * @return boolean
	 */
	public function getProduceReferences() {
		return $this->produceReferences;
	}

	/**
	 * @param boolean $produceReferences
	 */
	public function setProduceReferences( $produceReferences ) {
		$this->produceReferences = $produceReferences;
	}

	/**
	 * Adds Statements to the RDF graph.
	 *
	 * @param EntityId $entityId
	 * @param StatementList $statementList
	 */
	public function addStatements( EntityId $entityId, StatementList $statementList ) {
		$bestList = array();

		// FIXME: getBestStatementPerProperty() is expensive, share the result with TruthyStatementRdfBuilder!
		foreach ( $statementList->getBestStatementPerProperty() as $statement ) {
			$bestList[$statement->getGuid()] = true;
		}

		foreach ( $statementList as $statement ) {
			$this->addStatement( $entityId, $statement, isset( $bestList[$statement->getGuid()] ) );
		}
	}

	/**
	 * Returns a qname for the given statement using the given prefix.
	 *
	 * @param Statement $statement
	 *
	 * @return string
	 */
	private function getStatementLName( Statement $statement ) {
		return preg_replace( '/[^\w-]/', '-', $statement->getGuid() );
	}

	/**
	 * Adds the given Statement from the given Entity to the RDF graph.
	 *
	 * @param EntityId $entityId
	 * @param Statement $statement
	 * @param bool $isBest Is this best ranked statement?
	 */
	private function addStatement( EntityId $entityId, Statement $statement, $isBest ) {
		$statementLName = $this->getStatementLName( $statement );
		$this->statementWriter->about( RdfVocabulary::NS_STATEMENT, $statementLName )
			->a( RdfVocabulary::NS_ONTOLOGY, 'Statement' );

		$this->addMainSnak( $entityId, $statement, $isBest );

		// XXX: separate builder for qualifiers?
		if ( $this->produceQualifiers ) {
			// this assumes statement was added by addMainSnak
			foreach ( $statement->getQualifiers() as $q ) {
				$this->addSnak( $this->statementWriter, $q, RdfVocabulary::NS_QUALIFIER );
			}
		}

		// XXX: separate builder for references?
		if ( $this->produceReferences ) {
			foreach ( $statement->getReferences() as $reference ) { //FIXME: split body into separate method
				$hash = $reference->getSnaks()->getHash();
				$refLName = $hash;

				$this->statementWriter->about( RdfVocabulary::NS_STATEMENT, $statementLName )
					->say( RdfVocabulary::NS_PROV, 'wasDerivedFrom' )->is( RdfVocabulary::NS_REFERENCE, $refLName );
				if ( $this->referenceSeen( $hash ) !== false ) {
					continue;
				}

				$this->referenceWriter->about( RdfVocabulary::NS_REFERENCE, $refLName )
					->a( RdfVocabulary::NS_ONTOLOGY, 'Reference' );

				foreach ( $reference->getSnaks() as $refSnak ) {
					$this->addSnak( $this->referenceWriter, $refSnak, RdfVocabulary::NS_VALUE );
				}
			}
		}
	}

	/**
	 * Adds the given Statement's main Snak to the RDF graph.
	 *
	 * @param EntityId $entityId
	 * @param Statement $statement
	 * @param bool $isBest Is this best ranked statement?
	 */
	private function addMainSnak( EntityId $entityId, Statement $statement, $isBest ) {
		$snak = $statement->getMainSnak();

		$entityLName = $this->vocabulary->getEntityLName( $entityId );
		$propertyLName = $this->vocabulary->getEntityLName( $snak->getPropertyId() );
		$statementLName = $this->getStatementLName( $statement );

		$this->statementWriter->about( RdfVocabulary::NS_ENTITY,  $entityLName )
			->say( RdfVocabulary::NS_ENTITY, $propertyLName )->is( RdfVocabulary::NS_STATEMENT, $statementLName );

		$this->statementWriter->about( RdfVocabulary::NS_STATEMENT, $statementLName );
		$this->addSnak( $this->statementWriter, $snak, RdfVocabulary::NS_VALUE );

		$this->propertyMentioned( $snak->getPropertyId() );

		$rank = $statement->getRank();
		if ( isset( RdfVocabulary::$rankMap[$rank] ) ) {
			$this->statementWriter->about( RdfVocabulary::NS_STATEMENT, $statementLName )
				->say( RdfVocabulary::NS_ONTOLOGY, 'rank' )->is( RdfVocabulary::NS_ONTOLOGY, RdfVocabulary::$rankMap[$rank] );
			if( $isBest ) {
				$this->statementWriter->say( RdfVocabulary::NS_ONTOLOGY, 'rank' )->is( RdfVocabulary::NS_ONTOLOGY, RdfVocabulary::WIKIBASE_RANK_BEST );
			}
		} else {
			wfLogWarning( "Unknown rank $rank encountered for $entityId:{$statement->getGuid()}" );
		}

	}

	/**
	 * Adds the given Statement's main Snak to the RDF graph.
	 *
	 * @todo share more of this code with TruthyStatementRdfBuilder
	 *
	 * @param RdfWriter $writer
	 * @param Snak $snak
	 * @param $propertyNamespace
	 */
	private function addSnak( RdfWriter $writer, Snak $snak, $propertyNamespace ) {

		$propertyId = $snak->getPropertyId();
		switch ( $snak->getType() ) {
			case 'value':
				/** @var PropertyValueSnak $snak */
				$this->valueBuilder->addSnakValue( $writer, $propertyId, $snak->getDataValue(), $propertyNamespace );
				break;
			case 'somevalue':
				$propertyValueLName = $this->vocabulary->getEntityLName( $propertyId );

				$writer->say( $propertyNamespace, $propertyValueLName )->is( RdfVocabulary::NS_ONTOLOGY, 'Somevalue' );
				break;
			case 'novalue':
				$propertyValueLName = $this->vocabulary->getEntityLName( $propertyId );

				$writer->say( $propertyNamespace, $propertyValueLName )->is( RdfVocabulary::NS_ONTOLOGY, 'Novalue' );
				break;
			default:
				throw new \InvalidArgumentException( 'Unknown snak type: ' . $snak->getType() );
		}
	}

	/**
	 * @param EntityId $propertyId
	 */
	private function propertyMentioned( EntityId $propertyId ) {
		if ( $this->propertyMentionCallback ) {
			call_user_func( $this->propertyMentionCallback, $propertyId );
		}
	}

	/**
	 * @param string $hash
	 *
	 * @return bool
	 */
	private function referenceSeen( $hash ) {
		if ( $this->referenceSeenCallback ) {
			if ( call_user_func( $this->referenceSeenCallback, $hash ) ) {
				return $hash;
			}
		}

		return false;
	}

	/**
	 * Add fully reified statements for the given entity to the RDF graph.
	 * This may include qualifiers and references, depending on calls to
	 * setProduceQualifiers() resp. setProduceReferences().
	 *
	 * @param EntityDocument $entity the entity to output.
	 */
	public function addEntity( EntityDocument $entity ) {
		if ( $entity instanceof StatementListProvider ) {
			$entityId = $entity->getId();
			$this->addStatements( $entityId, $entity->getStatements() );
		}
	}

}
