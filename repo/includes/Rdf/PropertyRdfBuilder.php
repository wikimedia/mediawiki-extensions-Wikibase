<?php

namespace Wikibase\Rdf;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Property;
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
	 * @param string $id
	 * @param boolean $isObjectProperty Is the property data or object property?
	 */
	private function writePropertyPredicates( $id, $isObjectProperty ) {
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'directClaim' )->is( RdfVocabulary::NSP_DIRECT_CLAIM, $id );
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'claim' )->is( RdfVocabulary::NSP_CLAIM, $id );
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'statementProperty' )->is( RdfVocabulary::NSP_CLAIM_STATEMENT, $id );
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'statementValue' )->is( RdfVocabulary::NSP_CLAIM_VALUE, $id );
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'statementValueNormalized' )->is( RdfVocabulary::NSP_CLAIM_VALUE_NORM, $id );
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'qualifier' )->is( RdfVocabulary::NSP_QUALIFIER, $id );
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'qualifierValue' )->is( RdfVocabulary::NSP_QUALIFIER_VALUE, $id );
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'qualifierValueNormalized' )->is( RdfVocabulary::NSP_QUALIFIER_VALUE_NORM, $id );
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'reference' )->is( RdfVocabulary::NSP_REFERENCE, $id );
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'referenceValue' )->is( RdfVocabulary::NSP_REFERENCE_VALUE, $id );
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'referenceValueNormalized' )->is( RdfVocabulary::NSP_REFERENCE_VALUE_NORM, $id );
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'novalue' )->is( RdfVocabulary::NSP_NOVALUE, $id );
		// Always object properties
		$this->writer->about( RdfVocabulary::NSP_CLAIM, $id )->a( 'owl', 'ObjectProperty' );
		$this->writer->about( RdfVocabulary::NSP_CLAIM_VALUE, $id )->a( 'owl', 'ObjectProperty' );
		$this->writer->about( RdfVocabulary::NSP_QUALIFIER_VALUE, $id )->a( 'owl', 'ObjectProperty' );
		$this->writer->about( RdfVocabulary::NSP_REFERENCE_VALUE, $id )->a( 'owl', 'ObjectProperty' );
		$this->writer->about( RdfVocabulary::NSP_CLAIM_VALUE_NORM, $id )->a( 'owl', 'ObjectProperty' );
		$this->writer->about( RdfVocabulary::NSP_QUALIFIER_VALUE_NORM, $id )->a( 'owl', 'ObjectProperty' );
		$this->writer->about( RdfVocabulary::NSP_REFERENCE_VALUE_NORM, $id )->a( 'owl', 'ObjectProperty' );
		// Depending on property type
		if ( $isObjectProperty ) {
			$datatype = 'ObjectProperty';
		} else {
			$datatype = 'DatatypeProperty';
		}
		$this->writer->about( RdfVocabulary::NSP_DIRECT_CLAIM, $id )->a( 'owl', $datatype );
		$this->writer->about( RdfVocabulary::NSP_CLAIM_STATEMENT, $id )->a( 'owl', $datatype );
		$this->writer->about( RdfVocabulary::NSP_QUALIFIER, $id )->a( 'owl', $datatype );
		$this->writer->about( RdfVocabulary::NSP_REFERENCE, $id )->a( 'owl', $datatype );
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
	 * @param string $id
	 */
	private function writeNovalueClass( $id ) {
		$this->writer->about( RdfVocabulary::NSP_NOVALUE, $id )->say( 'a' )->is( 'owl', 'Class' );
		$internalClass = $this->writer->blank();
		$this->writer->say( 'owl', 'complementOf' )->is( '_', $internalClass );
		$this->writer->about( '_', $internalClass )->say( 'a' )->is( 'owl', 'Restriction' );
		$this->writer->say( 'owl', 'onProperty' )->is( RdfVocabulary::NSP_DIRECT_CLAIM, $id );
		$this->writer->say( 'owl', 'someValuesFrom' )->is( 'owl', 'Thing' );
	}

	private function addProperty( Property $property ) {
		$this->writer->about(
			RdfVocabulary::NS_ENTITY,
			$this->vocabulary->getEntityLName( $property->getId() )
		)->a( RdfVocabulary::NS_ONTOLOGY, $this->vocabulary->getEntityTypeName( $property->getType() ) );

		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'propertyType' )
			->is( $this->vocabulary->getDataTypeURI( $property ) );

		$id = $property->getId()->getSerialization();
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
