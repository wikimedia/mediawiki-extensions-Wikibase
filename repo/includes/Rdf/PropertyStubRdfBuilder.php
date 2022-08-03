<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Rdf;

use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\ContentLanguages;
use Wikimedia\Purtle\RdfWriter;

/**
 * @license GPL-2.0-or-later
 */
class PropertyStubRdfBuilder implements PrefetchingEntityStubRdfBuilder {

	public const OBJECT_PROPERTY = 'ObjectProperty';
	private const DATATYPE_PROPERTY = 'DatatypeProperty';
	private const NO_NORMALIZATION = null;

	/**
	 * @var PrefetchingTermLookup
	 */
	private $prefetchingLookup;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	/**
	 * @var ContentLanguages
	 */
	private $termLanguages;

	/**
	 * @var RdfVocabulary
	 */
	private $vocabulary;

	/**
	 * @var RdfWriter
	 */
	private $writer;

	/**
	 * @var string[][][] Map of type to array of [ ns, local ] for each label predicate
	 */
	private $labelPredicates;

	/**
	 * @var array
	 */
	private $dataTypes;

	private $idsToPrefetch = [];

	public function __construct(
		PrefetchingTermLookup $prefetchingLookup,
		PropertyDataTypeLookup $dataTypeLookup,
		ContentLanguages $termLanguages,
		RdfVocabulary $vocabulary,
		RdfWriter $writer,
		array $dataTypes = [],
		array $labelPredicates = []
	) {
		$this->prefetchingLookup = $prefetchingLookup;
		$this->dataTypeLookup = $dataTypeLookup;
		$this->termLanguages = $termLanguages;
		$this->dataTypes = $dataTypes;
		$this->labelPredicates = $labelPredicates;
		$this->vocabulary = $vocabulary;
		$this->writer = $writer;
	}

	public function addEntityStub( EntityId $entityId ): void {
		$this->prefetchEntityStubData();
		$propertyDescriptions = $this->prefetchingLookup->getDescriptions( $entityId, $this->termLanguages->getLanguages() );
		$propertyLabels = $this->prefetchingLookup->getLabels( $entityId, $this->termLanguages->getLanguages() );
		$entityLName = $this->vocabulary->getEntityLName( $entityId );
		$entityRepoName = $this->vocabulary->getEntityRepositoryName( $entityId );
		$entityNamespace = $this->vocabulary->entityNamespaceNames[ $entityRepoName ];

		$this->addLabels(
			$entityNamespace,
			$entityLName,
			$propertyLabels,
			$this->getLabelPredicates( $entityId )
		);
		$this->addDescriptions( $entityNamespace, $entityLName, $propertyDescriptions );
		$this->addProperty( $entityId );
	}

	/**
	 * Adds the descriptions of the given entity to the RDF graph.
	 *
	 * @param string $entityNamespace
	 * @param string $entityLName
	 * @param array $descriptions
	 */
	private function addDescriptions( $entityNamespace, $entityLName, array $descriptions ) {
		foreach ( $descriptions as $languageCode => $description ) {
		$this->writer->about( $entityNamespace, $entityLName )
			->say( RdfVocabulary::NS_SCHEMA_ORG, 'description' )->text( $description, $languageCode );
		}
	}

	/**
	 * Adds the labels of the given entity to the RDF graph
	 *
	 * @param string $entityNamespace
	 * @param string $entityLName
	 * @param array $labels
	 * @param string[][] $labelPredicates array of [ ns, local ] for each label predicate
	 */
	private function addLabels( $entityNamespace, $entityLName, array $labels, array $labelPredicates ) {
		if ( empty( $labelPredicates ) ) {
			// If we want no predicates, no need to bother with the rest.
			return;
		}
		foreach ( $labels as $languageCode => $labelText ) {
			$this->writer->about( $entityNamespace, $entityLName );
			foreach ( $labelPredicates as $predicate ) {
				$this->writer->say( $predicate[ 0 ], $predicate[ 1 ] )->text( $labelText, $languageCode );
			}
		}
	}

	/**
	 * Get predicates that will be used for labels.
	 * @param EntityId $entityId
	 * @return string[][] array of [ ns, local ] for each label predicate
	 */
	private function getLabelPredicates( EntityId $entityId ) {
		return $this->labelPredicates[ $entityId->getEntityType() ] ?? [
				[ 'rdfs', 'label' ],
				[ RdfVocabulary::NS_SKOS, 'prefLabel' ],
				[ RdfVocabulary::NS_SCHEMA_ORG, 'name' ],
			];
	}

	/**
	 * Adds property info to the RDF graph
	 *
	 * @param PropertyId $propertyId
	 */
	private function addProperty( PropertyId $propertyId ) {
		$propertyLName = $this->vocabulary->getEntityLName( $propertyId );
		$repositoryName = $this->vocabulary->getEntityRepositoryName( $propertyId );

		$dataTypeId = $this->dataTypeLookup->getDataTypeIdForProperty( $propertyId );

		$this->writer->about(
			$this->vocabulary->entityNamespaceNames[$repositoryName],
			$propertyLName
		)->say( RdfVocabulary::NS_ONTOLOGY, 'propertyType' )
			->is( $this->vocabulary->getDataTypeURI( $dataTypeId ) );

		$this->writePropertyPredicates(
			$propertyLName,
			$repositoryName,
			$this->getPropertyRdfType( $dataTypeId ),
			$this->getNormalizedPropertyRdfType( $dataTypeId )
		);
		$this->writeNovalueClass( $propertyLName, $repositoryName );
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
	 * @param string $dataTypeId
	 * @return string RDF/OWL type name for this property.
	 */
	private function getPropertyRdfType( string $dataTypeId ) {
		return $this->dataTypes[ $dataTypeId ] ?? self::DATATYPE_PROPERTY;
	}

	/**
	 * @param string $dataTypeId
	 *
	 * @return string|null
	 */
	private function getNormalizedPropertyRdfType( string $dataTypeId ) {
		switch ( $dataTypeId ) {
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

	public function markForPrefetchingEntityStub( EntityId $id ): void {
		$this->idsToPrefetch[$id->getSerialization()] = $id;
	}

	private function prefetchEntityStubData(): void {
		if ( $this->idsToPrefetch === [] ) {
			return;
		}

		$this->prefetchingLookup->prefetchTerms(
			array_values( $this->idsToPrefetch ),
			[
				TermTypes::TYPE_DESCRIPTION,
				TermTypes::TYPE_LABEL,
			],
			$this->termLanguages->getLanguages()
		);

		$this->idsToPrefetch = [];
	}
}
