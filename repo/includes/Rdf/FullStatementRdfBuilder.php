<?php

namespace Wikibase\Repo\Rdf;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikimedia\Purtle\RdfWriter;

/**
 * Fully reified RDF mapping for wikibase statements, including deprecated and non-"best"
 * statements, ranks, qualifiers, and references. This modells statements as identifiable objects
 * and does not output a direct property to value mapping as the TruthyStatementRdfBuilder does. If
 * both forms (direct and full) are desired, use TruthyStatementRdfBuilder in addition to
 * FullStatementRdfBuilder.
 *
 * @see TruthyStatementRdfBuilder
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class FullStatementRdfBuilder implements EntityRdfBuilder {

	/**
	 * @var DedupeBag
	 */
	private $dedupeBag;

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
	 * @var SnakRdfBuilder
	 */
	private $snakBuilder;

	public function __construct( RdfVocabulary $vocabulary, RdfWriter $writer, SnakRdfBuilder $snakBuilder ) {
		$this->vocabulary = $vocabulary;

		// Note: since we process references as nested structures, they need a separate
		// rdf writer, so outputting references doesn't destroy the state of the statement writer.
		$this->statementWriter = $writer;
		$this->referenceWriter = $writer->sub();

		$this->snakBuilder = $snakBuilder;

		$this->dedupeBag = new NullDedupeBag();
	}

	public function setDedupeBag( DedupeBag $dedupeBag ) {
		$this->dedupeBag = $dedupeBag;
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
		$bestList = [];

		// FIXME: This is expensive, share the result with TruthyStatementRdfBuilder!
		foreach ( $statementList->getPropertyIds() as $propertyId ) {
			$bestStatements = $statementList->getByPropertyId( $propertyId )->getBestStatements();
			foreach ( $bestStatements->toArray() as $statement ) {
				$bestList[$statement->getGuid()] = true;
			}
		}

		foreach ( $statementList->toArray() as $statement ) {
			$this->addStatement( $entityId, $statement, isset( $bestList[$statement->getGuid()] ) );
		}
	}

	/**
	 * Adds the given Statement from the given Entity to the RDF graph.
	 *
	 * @param EntityId $entityId
	 * @param Statement $statement
	 * @param bool $isBest Is this best ranked statement?
	 */
	private function addStatement( EntityId $entityId, Statement $statement, $isBest ) {
		$statementLName = $this->vocabulary->getStatementLName( $statement );

		$entityRepository = $this->vocabulary->getEntityRepositoryName( $entityId );

		$this->addMainSnak( $entityId, $statementLName, $statement, $isBest );

		// XXX: separate builder for qualifiers?
		if ( $this->produceQualifiers ) {
			// this assumes statement was added by addMainSnak
			foreach ( $statement->getQualifiers() as $q ) {
				$propertyRepository = $this->vocabulary->getEntityRepositoryName( $q->getPropertyId() );
				$this->snakBuilder->addSnak(
					$this->statementWriter,
					$this->vocabulary->statementNamespaceNames[$entityRepository][RdfVocabulary::NS_VALUE],
					$q,
					$this->vocabulary->propertyNamespaceNames[$propertyRepository][RdfVocabulary::NSP_QUALIFIER],
					$statementLName
				);
			}
		}

		// XXX: separate builder for references?
		if ( $this->produceReferences ) {
			$entityRepository = $this->vocabulary->getEntityRepositoryName( $entityId );
			/** @var Reference $reference */
			foreach ( $statement->getReferences() as $reference ) { //FIXME: split body into separate method
				$hash = $reference->getSnaks()->getHash();
				$refLName = $hash;

				$this->statementWriter->about(
					$this->vocabulary->statementNamespaceNames[$entityRepository][RdfVocabulary::NS_STATEMENT],
					$statementLName
				)
					->say( RdfVocabulary::NS_PROV, 'wasDerivedFrom' )->is(
						$this->vocabulary->statementNamespaceNames[$entityRepository][RdfVocabulary::NS_REFERENCE],
						$refLName
					);
				if ( $this->dedupeBag->alreadySeen( $hash, 'R' ) !== false ) {
					continue;
				}

				$this->referenceWriter->about(
					$this->vocabulary->statementNamespaceNames[$entityRepository][RdfVocabulary::NS_REFERENCE],
					$refLName
				)
					->a( RdfVocabulary::NS_ONTOLOGY, 'Reference' );

				foreach ( $reference->getSnaks() as $refSnak ) {
					$propertyRepository = $this->vocabulary->getEntityRepositoryName( $refSnak->getPropertyId() );
					$this->snakBuilder->addSnak(
						$this->referenceWriter,
						$this->vocabulary->statementNamespaceNames[$entityRepository][RdfVocabulary::NS_VALUE],
						$refSnak,
						$this->vocabulary->propertyNamespaceNames[$propertyRepository][RdfVocabulary::NSP_REFERENCE],
						$refLName
					);
				}
			}
		}
	}

	/**
	 * Adds the given Statement's main Snak to the RDF graph.
	 *
	 * @param EntityId $entityId
	 * @param string $statementLName
	 * @param Statement $statement
	 * @param bool $isBest Is this best ranked statement?
	 */
	private function addMainSnak( EntityId $entityId, $statementLName, Statement $statement, $isBest ) {
		$snak = $statement->getMainSnak();

		$entityLName = $this->vocabulary->getEntityLName( $entityId );
		$entityRepository = $this->vocabulary->getEntityRepositoryName( $entityId );
		$propertyId = $snak->getPropertyId();
		$propertyLName = $this->vocabulary->getEntityLName( $propertyId );
		$propertyRepository = $this->vocabulary->getEntityRepositoryName( $propertyId );

		$this->statementWriter->about(
			$this->vocabulary->entityNamespaceNames[$entityRepository],
			$entityLName
		)
			->say(
				$this->vocabulary->propertyNamespaceNames[$propertyRepository][RdfVocabulary::NSP_CLAIM],
				$propertyLName
			)
			->is( $this->vocabulary->statementNamespaceNames[$entityRepository][RdfVocabulary::NS_STATEMENT], $statementLName );

		$this->statementWriter->about(
			$this->vocabulary->statementNamespaceNames[$entityRepository][RdfVocabulary::NS_STATEMENT],
			$statementLName
		)
			->a( RdfVocabulary::NS_ONTOLOGY, 'Statement' );

		$rank = $statement->getRank();
		if ( isset( RdfVocabulary::RANK_MAP[$rank] ) ) {
			if ( $isBest ) {
				$this->statementWriter->a( RdfVocabulary::NS_ONTOLOGY, RdfVocabulary::WIKIBASE_RANK_BEST );
			}
			$this->statementWriter->about(
				$this->vocabulary->statementNamespaceNames[$entityRepository][RdfVocabulary::NS_STATEMENT],
				$statementLName
			)
				->say( RdfVocabulary::NS_ONTOLOGY, 'rank' )->is( RdfVocabulary::NS_ONTOLOGY, RdfVocabulary::RANK_MAP[$rank] );
		} else {
			wfLogWarning( "Unknown rank $rank encountered for $entityId:{$statement->getGuid()}" );
		}

		$this->snakBuilder->addSnak(
			$this->statementWriter,
			$this->vocabulary->statementNamespaceNames[$entityRepository][RdfVocabulary::NS_VALUE],
			$snak,
			$this->vocabulary->propertyNamespaceNames[$propertyRepository][RdfVocabulary::NSP_CLAIM_STATEMENT],
			$statementLName
		);
	}

	/**
	 * Add fully reified statements for the given entity to the RDF graph.
	 * This may include qualifiers and references, depending on calls to
	 * setProduceQualifiers() resp. setProduceReferences().
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
