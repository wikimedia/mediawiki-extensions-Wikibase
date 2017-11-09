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

	const OBJECT_PROPERTY = 'ObjectProperty';
	const DATATYPE_PROPERTY = 'DatatypeProperty';
	const NO_NORMALIZATION = null;

	/**
	 * @var RdfVocabulary
	 */
	private $vocabulary;

	/**
	 * @var RdfWriter
	 */
	private $writer;

	public function __construct( RdfVocabulary $vocabulary, RdfWriter $writer ) {
		$this->vocabulary = $vocabulary;
		$this->writer = $writer;
	}

	/**
	 * Write predicates linking property entity to property predicates
	 *
	 * @param string $id The serialized property ID string
	 * @param string $propertyRdfType Is the property data or object property?
	 * @param string|null $normalizedPropertyRdfType Does the property have normalized predicates,
	 *  and if so does the property normalize to data or objects?
	 */
	private function writePropertyPredicates( $id, $propertyRdfType, $normalizedPropertyRdfType ) {
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'directClaim' )->is(
			RdfVocabulary::NSP_DIRECT_CLAIM,
			$id
		);
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'claim' )->is(
			RdfVocabulary::NSP_CLAIM,
			$id
		);
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'statementProperty' )->is( RdfVocabulary::NSP_CLAIM_STATEMENT, $id
		);
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'statementValue' )->is(
			RdfVocabulary::NSP_CLAIM_VALUE,
			$id
		);
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'qualifier' )->is(
			RdfVocabulary::NSP_QUALIFIER,
			$id
		);
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'qualifierValue' )->is(
			RdfVocabulary::NSP_QUALIFIER_VALUE,
			$id
		);
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'reference' )->is(
			RdfVocabulary::NSP_REFERENCE,
			$id
		);
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'referenceValue' )->is(
			RdfVocabulary::NSP_REFERENCE_VALUE,
			$id
		);
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'novalue' )->is(
			RdfVocabulary::NSP_NOVALUE,
			$id
		);

		if ( $normalizedPropertyRdfType !== self::NO_NORMALIZATION ) {
			$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'directClaimNormalized' )->is(
				RdfVocabulary::NSP_DIRECT_CLAIM_NORM,
				$id
			);
			$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'statementValueNormalized' )->is(
				RdfVocabulary::NSP_CLAIM_VALUE_NORM,
				$id
			);
			$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'qualifierValueNormalized' )->is(
				RdfVocabulary::NSP_QUALIFIER_VALUE_NORM,
				$id
			);
			$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'referenceValueNormalized' )->is(
				RdfVocabulary::NSP_REFERENCE_VALUE_NORM,
				$id
			);
		}

		// Always object properties
		$this->writer->about(
			RdfVocabulary::NSP_CLAIM,
			$id
		)->a( 'owl', 'ObjectProperty' );
		$this->writer->about(
			RdfVocabulary::NSP_CLAIM_VALUE,
			$id
		)->a( 'owl', 'ObjectProperty' );
		$this->writer->about(
			RdfVocabulary::NSP_QUALIFIER_VALUE,
			$id
		)->a( 'owl', 'ObjectProperty' );
		$this->writer->about(
			RdfVocabulary::NSP_REFERENCE_VALUE,
			$id
		)->a( 'owl', 'ObjectProperty' );

		$this->writer->about(
			RdfVocabulary::NSP_DIRECT_CLAIM,
			$id
		)->a( 'owl', $propertyRdfType );
		$this->writer->about(
			RdfVocabulary::NSP_CLAIM_STATEMENT,
			$id
		)->a( 'owl', $propertyRdfType );
		$this->writer->about(
			RdfVocabulary::NSP_QUALIFIER,
			$id
		)->a( 'owl', $propertyRdfType );
		$this->writer->about(
			RdfVocabulary::NSP_REFERENCE,
			$id
		)->a( 'owl', $propertyRdfType );

		if ( $normalizedPropertyRdfType !== self::NO_NORMALIZATION ) {
			$this->writer->about(
				RdfVocabulary::NSP_CLAIM_VALUE_NORM,
				$id
			)->a( 'owl', 'ObjectProperty' );
			$this->writer->about(
				RdfVocabulary::NSP_QUALIFIER_VALUE_NORM,
				$id
			)->a( 'owl', 'ObjectProperty' );
			$this->writer->about(
				RdfVocabulary::NSP_REFERENCE_VALUE_NORM,
				$id
			)->a( 'owl', 'ObjectProperty' );

			$this->writer->about(
				RdfVocabulary::NSP_DIRECT_CLAIM_NORM,
				$id
			)->a( 'owl', $normalizedPropertyRdfType );
		}
	}

	/**
	 * Check if the property describes link between objects
	 * or just data item.
	 *
	 * @param Property $property
	 *
	 * @return string
	 */
	private function getPropertyRdfType( Property $property ) {
		$propertyTypesToBeObjects = [
			'wikibase-item',
			'wikibase-property',
			'url',
			'commonsMedia',
			'geo-shape',
			'tabular-data',
		];

		if ( in_array( $property->getDataTypeId(), $propertyTypesToBeObjects ) ) {
			return self::OBJECT_PROPERTY;
		} else {
			return self::DATATYPE_PROPERTY;
		}
	}

	/**
	 * @param Property $property
	 *
	 * @return string|null
	 */
	private function getNormalizedPropertyRdfType( Property $property ) {
		switch ( $property->getDataTypeId() ) {
			case 'quantity':
				return self::DATATYPE_PROPERTY;
			case 'external-id':
				return self::OBJECT_PROPERTY;
			default:
				return self::NO_NORMALIZATION;
		}
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

		$propertyId = $property->getId()->getSerialization();
		$this->writePropertyPredicates(
			$propertyId,
			$this->getPropertyRdfType( $property ),
			$this->getNormalizedPropertyRdfType( $property )
		);
		$this->writeNovalueClass( $propertyId );
	}

	/**
	 * Map a property to the RDF graph
	 *
	 * @param EntityDocument $entity
	 */
	public function addEntity( EntityDocument $entity ) {
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
