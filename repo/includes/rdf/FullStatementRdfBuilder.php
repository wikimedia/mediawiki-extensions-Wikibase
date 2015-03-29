<?php

namespace Wikibase;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\StatementListProvider;
use Wikibase\RDF\RdfWriter;

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
class FullStatementRdfBuilder {

	const NS_ONTOLOGY = 'wikibase'; // wikibase ontology (shared)
	const NS_ENTITY = 'entity'; // concept uris
	const NS_VALUE = 'v'; // statement -> value
	const NS_DIRECT_CLAIM = 'wdt'; // direct assertion entity -> value

	const NS_QUALIFIER = 'q'; // statement -> qualifier
	const NS_STATEMENT = 's'; // entity -> statement
	const NS_REFERENCE = 'ref';
	const NS_PROV = 'prov'; // for provenance

	const WIKIBASE_RANK_BEST = 'BestRank';

	/**
	 * @var RdfWriter
	 */
	private $writer;

	/**
	 * @var RdfWriter
	 */
	private $referenceWriter;

	/**
	 * @var SnakValueRdfBuilder
	 */
	private $valueBuilder;

	/**
	 * @param RdfWriter $writer
	 * @param RdfWriter $referenceWriter
	 * @param SnakValueRdfBuilder $valueBuilder
	 */
	public function __construct( RdfWriter $writer, RdfWriter $referenceWriter, SnakValueRdfBuilder $valueBuilder ) {
		$this->writer = $writer;
		$this->valueBuilder = $valueBuilder;
		$this->referenceWriter = $referenceWriter;
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
	 * @param EntityDocument $entity
	 */
	private function addStatements( EntityDocument $entity ) {
		$entityId = $entity->getId();

		if ( $entity instanceof StatementListProvider ) {
			$statementList = $entity->getStatements();

			if ( !$this->shouldProduce( RdfProducer::PRODUCE_ALL_STATEMENTS ) ) {
				$statementList = $statementList->getBestStatementPerProperty();
			}

			$bestList...;

			foreach ( $statementList as $statement ) {
				$this->addStatement( $entityId, $statement, isset( $bestList[$statement->getGuid()] ) );
			}
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
		$this->writer->about( self::NS_STATEMENT, $statementLName )
			->a( self::NS_ONTOLOGY, 'Statement' );

		$this->addMainSnak( $entityId, $statement, $isBest );

		// XXX: separate builder for qualifiers?
		if ( $this->shouldProduce( RdfProducer::PRODUCE_QUALIFIERS ) ) {
			// this assumes statement was added by addMainSnak
			foreach ( $statement->getQualifiers() as $q ) {
				$this->addSnak( $this->writer, $q, self::NS_QUALIFIER );
			}
		}

		// XXX: separate builder for references?
		if ( $this->shouldProduce( RdfProducer::PRODUCE_REFERENCES ) ) {
			foreach ( $statement->getReferences() as $reference ) { //FIXME: split body into separate method
				$hash = $reference->getSnaks()->getHash();
				$refLName = $hash;

				$this->writer->about( self::NS_STATEMENT, $statementLName )
					->say( self::NS_PROV, 'wasDerivedFrom' )->is( self::NS_REFERENCE, $refLName );
				if ( $this->alreadySeen( $hash, 'R' ) ) {
					continue;
				}

				$this->referenceWriter->about( self::NS_REFERENCE, $refLName )
					->a( self::NS_ONTOLOGY, 'Reference' );

				foreach ( $reference->getSnaks() as $refSnak ) {
					$this->addSnak( $this->referenceWriter, $refSnak, self::NS_VALUE );
				}
			}
		}
	}

	private static $rankMap = array(
		Statement::RANK_DEPRECATED => 'DeprecatedRank',
		Statement::RANK_NORMAL => 'NormalRank',
		Statement::RANK_PREFERRED => 'PreferredRank',
	);

	/**
	 * Adds the given Statement's main Snak to the RDF graph.
	 *
	 * @param EntityId $entityId
	 * @param Statement $statement
	 * @param bool $isBest Is this best ranked statement?
	 */
	private function addMainSnak( EntityId $entityId, Statement $statement, $isBest ) {
		$snak = $statement->getMainSnak();

		$entityLName = $this->getEntityLName( $entityId );
		$propertyLName = $this->getEntityLName( $snak->getPropertyId() );
		$statementLName = $this->getStatementLName( $statement );

		$this->writer->about( self::NS_ENTITY,  $entityLName )
			->say( self::NS_ENTITY, $propertyLName )->is( self::NS_STATEMENT, $statementLName );

		$this->writer->about( self::NS_STATEMENT, $statementLName );
		$this->addSnak( $this->writer, $snak, self::NS_VALUE );

		if ( $this->shouldProduce( RdfProducer::PRODUCE_PROPERTIES ) ) {
			$this->entityMentioned( $snak->getPropertyId() );
		}

		$rank = $statement->getRank();
		if ( isset( self::$rankMap[$rank] ) ) {
			$this->writer->about( self::NS_STATEMENT, $statementLName )
				->say( self::NS_ONTOLOGY, 'rank' )->is( self::NS_ONTOLOGY, self::$rankMap[$rank] );
			if( $isBest ) {
				$this->writer->say( self::NS_ONTOLOGY, 'rank' )->is( self::NS_ONTOLOGY, self::WIKIBASE_RANK_BEST );
			}
		} else {
			wfLogWarning( "Unknown rank $rank encountered for $entityId:{$statement->getGuid()}" );
		}

	}

	/**
	 * Adds the given Statement's main Snak to the RDF graph.
	 *
	 * @todo share more code with TruthyStatementRdfBuilder
	 *
	 * @param EntityId $entityId
	 * @param Statement $statement
	 */
	private function addSnak( Snak $snak, $propertyNamespace ) {

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
