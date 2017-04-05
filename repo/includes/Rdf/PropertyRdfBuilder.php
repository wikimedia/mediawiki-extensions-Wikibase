<?php

namespace Wikibase\Rdf;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikimedia\Purtle\RdfWriter;

/**
 * Factory to return Rdf builders for special parts of properties
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class PropertyRdfBuilder implements EntityRdfBuilder {

	/**
	 * @var RdfVocabulary
	 */
	private $vocabulary;

	/**
	 * @var RdfWriter
	 */
	private $writer;

	/**
	 * @param RdfVocabulary $vocabulary
	 * @param RdfWriter $writer
	 */
	public function __construct(
		RdfVocabulary $vocabulary,
		RdfWriter $writer
	) {
		$this->vocabulary = $vocabulary;
		$this->writer = $writer;
	}

	/**
	 * Write predicates linking property entity to property predicates
	 * @param PropertyId $id
	 * @param boolean $isObjectProperty Is the property data or object property?
	 */
	private function writePropertyPredicates( PropertyId $id, $isObjectProperty ) {
		$idLName = $this->vocabulary->getEntityLName( $id );
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'directClaim' )->is(
			$this->vocabulary->getDirectClaimPropertyNamespace( $id ),
			$idLName
		);
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'claim' )->is(
			$this->vocabulary->getClaimPropertyNamespace( $id ),
			$idLName
		);
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'statementProperty' )->is(
			$this->vocabulary->getClaimStatementPropertyNamespace( $id ),
			$idLName
		);
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'statementValue' )->is(
			$this->vocabulary->getClaimValuePropertyNamespace( $id ),
			$idLName
		);
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'statementValueNormalized' )->is(
			$this->vocabulary->getClaimNormalizedValuePropertyNamespace( $id ),
			$idLName
		);
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'qualifier' )->is(
			$this->vocabulary->getQualifierPropertyNamespace( $id ),
			$idLName
		);
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'qualifierValue' )->is(
			$this->vocabulary->getQualifierValuePropertyNamespace( $id ),
			$idLName
		);
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'qualifierValueNormalized' )->is(
			$this->vocabulary->getQualifierNormalizedValuePropertyNamespace( $id ),
			$idLName
		);
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'reference' )->is(
			$this->vocabulary->getReferencePropertyNamespace( $id ),
			$idLName
		);
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'referenceValue' )->is(
			$this->vocabulary->getReferenceValuePropertyNamespace( $id ),
			$idLName
		);
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'referenceValueNormalized' )->is(
			$this->vocabulary->getReferenceNormalizedValuePropertyNamespace( $id ),
			$idLName
		);
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'novalue' )->is(
			$this->vocabulary->getNoValuePropertyNamespace( $id ),
			$idLName
		);

		// Always object properties
		$this->writer->about( $this->vocabulary->getClaimPropertyNamespace( $id ), $idLName )
			->a( 'owl', 'ObjectProperty' );
		$this->writer->about( $this->vocabulary->getClaimValuePropertyNamespace( $id ), $idLName )
			->a( 'owl', 'ObjectProperty' );
		$this->writer->about( $this->vocabulary->getQualifierValuePropertyNamespace( $id ), $idLName )
			->a( 'owl', 'ObjectProperty' );
		$this->writer->about( $this->vocabulary->getReferenceValuePropertyNamespace( $id ), $idLName )
			->a( 'owl', 'ObjectProperty' );
		$this->writer->about( $this->vocabulary->getClaimNormalizedValuePropertyNamespace( $id ), $idLName )
			->a( 'owl', 'ObjectProperty' );
		$this->writer->about( $this->vocabulary->getQualifierNormalizedValuePropertyNamespace( $id ), $idLName )
			->a( 'owl', 'ObjectProperty' );
		$this->writer->about( $this->vocabulary->getReferenceNormalizedValuePropertyNamespace( $id ), $idLName )
			->a( 'owl', 'ObjectProperty' );

		// Depending on property type
		if ( $isObjectProperty ) {
			$datatype = 'ObjectProperty';
		} else {
			$datatype = 'DatatypeProperty';
		}
		$this->writer->about( $this->vocabulary->getDirectClaimPropertyNamespace( $id ), $idLName )->a( 'owl', $datatype );
		$this->writer->about( $this->vocabulary->getClaimStatementPropertyNamespace( $id ), $idLName )->a( 'owl', $datatype );
		$this->writer->about( $this->vocabulary->getQualifierPropertyNamespace( $id ), $idLName )->a( 'owl', $datatype );
		$this->writer->about( $this->vocabulary->getReferencePropertyNamespace( $id ), $idLName )->a( 'owl', $datatype );
	}

	/**
	 * Check if the property describes link between objects
	 * or just data item.
	 *
	 * @param Property $property
	 * @return boolean
	 */
	private function propertyIsLink( Property $property ) {
		// For now, it's very simple but can be more complex later
		return in_array(
			$property->getDataTypeId(),
			[ 'wikibase-item', 'wikibase-property', 'url', 'commonsMedia' ]
		);
	}

	/**
	 * Write definition for wdno:P123 class to use as novalue
	 * @param PropertyId $id
	 */
	private function writeNovalueClass( PropertyId $id ) {
		$localName = $this->vocabulary->getEntityLName( $id );

		$this->writer->about(
			$this->vocabulary->getNoValuePropertyNamespace( $id ),
			$localName
		)->a( 'owl', 'Class' );
		$internalClass = $this->writer->blank();
		$this->writer->say( 'owl', 'complementOf' )->is( '_', $internalClass );
		$this->writer->about( '_', $internalClass )->say( 'a' )->is( 'owl', 'Restriction' );
		$this->writer->say( 'owl', 'onProperty' )->is(
			$this->vocabulary->getDirectClaimPropertyNamespace( $id ),
			$localName
		);
		$this->writer->say( 'owl', 'someValuesFrom' )->is( 'owl', 'Thing' );
	}

	private function addProperty( Property $property ) {
		$id = $property->getId();

		$this->writer->about(
			$this->vocabulary->getEntityNamespace( $id ),
			$this->vocabulary->getEntityLName( $property->getId() )
		)->a( RdfVocabulary::NS_ONTOLOGY, $this->vocabulary->getEntityTypeName( $property->getType() ) );

		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'propertyType' )
			->is( $this->vocabulary->getDataTypeURI( $property ) );

		$this->writePropertyPredicates( $id, $this->propertyIsLink( $property ) );
		$this->writeNovalueClass( $id );
	}

	/**
	 * Map a property to the RDF graph
	 *
	 * @param EntityDocument $entity
	 */
	public function addEntity(
		EntityDocument $entity
	) {
		if ( !$entity instanceof Property ) {
			return;
		}

		$this->addProperty( $entity );
	}

	/**
	 * Map some aspects of a property to the RDF graph, as it should appear in the stub
	 * representation of the property.
	 *
	 * @param EntityDocument $entity
	 */
	public function addEntityStub( EntityDocument $entity ) {
		if ( !$entity instanceof Property ) {
			return;
		}

		$this->addProperty( $entity );
	}

}
