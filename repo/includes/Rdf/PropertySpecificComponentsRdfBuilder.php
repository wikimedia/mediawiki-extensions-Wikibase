<?php

namespace Wikibase\Repo\Rdf;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikimedia\Purtle\RdfWriter;

/**
 * Rdfbuilder to create the triples
 * that describe:
 * - the datatypes of properties
 * - the predicates that will be used when creating value triples
 * - classes for making novalue statements
 *
 * This RDF builder should only write content for properties
 * and should do nothing for other entities.
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class PropertySpecificComponentsRdfBuilder {

	public const OBJECT_PROPERTY = 'ObjectProperty';
	private const DATATYPE_PROPERTY = 'DatatypeProperty';
	private const NO_NORMALIZATION = null;

	/**
	 * @var RdfVocabulary
	 */
	private $vocabulary;

	/**
	 * @var RdfWriter
	 */
	private $writer;
	/**
	 * @var array
	 */
	private $dataTypes;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	public function __construct(
		RdfVocabulary $vocabulary,
		RdfWriter $writer,
		PropertyDataTypeLookup $dataTypeLookup,
		array $dataTypes = []
	) {
		$this->vocabulary = $vocabulary;
		$this->writer = $writer;
		$this->dataTypes = $dataTypes;
		$this->dataTypeLookup = $dataTypeLookup;
	}

	/**
	 * Write predicates linking property entity to property predicates
	 * @param string $localName
	 * @param string $repositoryName
	 * @param string $propertyRdfType OWL data type (OBJECT_PROPERTY or DATATYPE_PROPERTY)
	 * @param string|null $normalizedPropertyRdfType Does the property have normalized predicates,
	 *  and if so does the property normalize to data or objects?
	 */
	private function writePropertyPredicates( $localName, $repositoryName, $propertyRdfType, $normalizedPropertyRdfType ) {
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'directClaim' )->is(
			$this->vocabulary->propertyNamespaceNames[$repositoryName][RdfVocabulary::NSP_DIRECT_CLAIM],
			$localName
		);
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'claim' )->is(
			$this->vocabulary->propertyNamespaceNames[$repositoryName][RdfVocabulary::NSP_CLAIM],
			$localName
		);
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'statementProperty' )->is(
			$this->vocabulary->propertyNamespaceNames[$repositoryName][RdfVocabulary::NSP_CLAIM_STATEMENT],
			$localName
		);
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'statementValue' )->is(
			$this->vocabulary->propertyNamespaceNames[$repositoryName][RdfVocabulary::NSP_CLAIM_VALUE],
			$localName
		);
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'qualifier' )->is(
			$this->vocabulary->propertyNamespaceNames[$repositoryName][RdfVocabulary::NSP_QUALIFIER],
			$localName
		);
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'qualifierValue' )->is(
			$this->vocabulary->propertyNamespaceNames[$repositoryName][RdfVocabulary::NSP_QUALIFIER_VALUE],
			$localName
		);
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'reference' )->is(
			$this->vocabulary->propertyNamespaceNames[$repositoryName][RdfVocabulary::NSP_REFERENCE],
			$localName
		);
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'referenceValue' )->is(
			$this->vocabulary->propertyNamespaceNames[$repositoryName][RdfVocabulary::NSP_REFERENCE_VALUE],
			$localName
		);
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'novalue' )->is(
			$this->vocabulary->propertyNamespaceNames[$repositoryName][RdfVocabulary::NSP_NOVALUE],
			$localName
		);

		if ( $normalizedPropertyRdfType !== self::NO_NORMALIZATION ) {
			$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'directClaimNormalized' )->is(
				$this->vocabulary->propertyNamespaceNames[$repositoryName][RdfVocabulary::NSP_DIRECT_CLAIM_NORM],
				$localName
			);
			$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'statementValueNormalized' )->is(
				$this->vocabulary->propertyNamespaceNames[$repositoryName][RdfVocabulary::NSP_CLAIM_VALUE_NORM],
				$localName
			);
			$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'qualifierValueNormalized' )->is(
				$this->vocabulary->propertyNamespaceNames[$repositoryName][RdfVocabulary::NSP_QUALIFIER_VALUE_NORM],
				$localName
			);
			$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'referenceValueNormalized' )->is(
				$this->vocabulary->propertyNamespaceNames[$repositoryName][RdfVocabulary::NSP_REFERENCE_VALUE_NORM],
				$localName
			);
		}

		// Always object properties
		$this->writer->about(
			$this->vocabulary->propertyNamespaceNames[$repositoryName][RdfVocabulary::NSP_CLAIM],
			$localName
		)->a( 'owl', 'ObjectProperty' );
		$this->writer->about(
			$this->vocabulary->propertyNamespaceNames[$repositoryName][RdfVocabulary::NSP_CLAIM_VALUE],
			$localName
		)->a( 'owl', 'ObjectProperty' );
		$this->writer->about(
			$this->vocabulary->propertyNamespaceNames[$repositoryName][RdfVocabulary::NSP_QUALIFIER_VALUE],
			$localName
		)->a( 'owl', 'ObjectProperty' );
		$this->writer->about(
			$this->vocabulary->propertyNamespaceNames[$repositoryName][RdfVocabulary::NSP_REFERENCE_VALUE],
			$localName
		)->a( 'owl', 'ObjectProperty' );

		$this->writer->about(
			$this->vocabulary->propertyNamespaceNames[$repositoryName][RdfVocabulary::NSP_DIRECT_CLAIM],
			$localName
		)->a( 'owl', $propertyRdfType );
		$this->writer->about(
			$this->vocabulary->propertyNamespaceNames[$repositoryName][RdfVocabulary::NSP_CLAIM_STATEMENT],
			$localName
		)->a( 'owl', $propertyRdfType );
		$this->writer->about(
			$this->vocabulary->propertyNamespaceNames[$repositoryName][RdfVocabulary::NSP_QUALIFIER],
			$localName
		)->a( 'owl', $propertyRdfType );
		$this->writer->about(
			$this->vocabulary->propertyNamespaceNames[$repositoryName][RdfVocabulary::NSP_REFERENCE],
			$localName
		)->a( 'owl', $propertyRdfType );

		if ( $normalizedPropertyRdfType !== self::NO_NORMALIZATION ) {
			$this->writer->about(
				$this->vocabulary->propertyNamespaceNames[$repositoryName][RdfVocabulary::NSP_CLAIM_VALUE_NORM],
				$localName
			)->a( 'owl', 'ObjectProperty' );
			$this->writer->about(
				$this->vocabulary->propertyNamespaceNames[$repositoryName][RdfVocabulary::NSP_QUALIFIER_VALUE_NORM],
				$localName
			)->a( 'owl', 'ObjectProperty' );
			$this->writer->about(
				$this->vocabulary->propertyNamespaceNames[$repositoryName][RdfVocabulary::NSP_REFERENCE_VALUE_NORM],
				$localName
			)->a( 'owl', 'ObjectProperty' );

			$this->writer->about(
				$this->vocabulary->propertyNamespaceNames[$repositoryName][RdfVocabulary::NSP_DIRECT_CLAIM_NORM],
				$localName
			)->a( 'owl', $normalizedPropertyRdfType );
		}
	}

	/**
	 * Check if the property describes link between objects
	 * or just data item.
	 *
	 * @param Property $property
	 * @return string RDF/OWL type name for this property.
	 */
	private function getPropertyRdfType( Property $property ) {
		return $this->dataTypes[$property->getDataTypeId()] ?? self::DATATYPE_PROPERTY;
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
	 * @param string $localName
	 * @param string $repositoryName
	 */
	private function writeNovalueClass( $localName, $repositoryName ) {
		$this->writer->about(
			$this->vocabulary->propertyNamespaceNames[$repositoryName][RdfVocabulary::NSP_NOVALUE],
			$localName
		)->a( 'owl', 'Class' );
		$stableBNodeLabel = md5( implode( '-', [ 'owl:complementOf', $repositoryName, $localName ] ) );
		$internalClass = $this->writer->blank( $stableBNodeLabel );
		$this->writer->say( 'owl', 'complementOf' )->is( '_', $internalClass );
		$this->writer->about( '_', $internalClass )->say( 'a' )->is( 'owl', 'Restriction' );
		$this->writer->say( 'owl', 'onProperty' )->is(
			$this->vocabulary->propertyNamespaceNames[$repositoryName][RdfVocabulary::NSP_DIRECT_CLAIM],
			$localName
		);
		$this->writer->say( 'owl', 'someValuesFrom' )->is( 'owl', 'Thing' );
	}

	private function addProperty( Property $property ) {
		$id = $property->getId();
		$propertyLName = $this->vocabulary->getEntityLName( $id );
		$repositoryName = $this->vocabulary->getEntityRepositoryName( $id );
		$dataTypeId = $this->dataTypeLookup->getDataTypeIdForProperty( $id );

		$this->writer->about(
			$this->vocabulary->entityNamespaceNames[$repositoryName],
			$propertyLName
		)->say( RdfVocabulary::NS_ONTOLOGY, 'propertyType' )
			->is( $this->vocabulary->getDataTypeURI( $dataTypeId ) );

		$this->writePropertyPredicates(
			$propertyLName,
			$repositoryName,
			$this->getPropertyRdfType( $property ),
			$this->getNormalizedPropertyRdfType( $property )
		);
		$this->writeNovalueClass( $propertyLName, $repositoryName );
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

}
