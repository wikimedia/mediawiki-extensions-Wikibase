<?php

namespace Wikibase\Repo\Rdf;

use InvalidArgumentException;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikimedia\Purtle\RdfWriter;

/**
 * Implementation for RDF mapping for Snaks.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class SnakRdfBuilder {

	/**
	 * @var EntityMentionListener
	 */
	private $mentionedEntityTracker;

	/**
	 * @var RdfVocabulary
	 */
	private $vocabulary;

	/**
	 * @var ValueSnakRdfBuilder
	 */
	private $valueBuilder;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyLookup;

	/**
	 * @var string[] local data type cache per property id
	 */
	private $propertyTypes = [];

	public function __construct(
		RdfVocabulary $vocabulary,
		ValueSnakRdfBuilder $valueBuilder,
		PropertyDataTypeLookup $propertyLookup
	) {
		$this->vocabulary = $vocabulary;
		$this->valueBuilder = $valueBuilder;
		$this->propertyLookup = $propertyLookup;

		$this->mentionedEntityTracker = new NullEntityMentionListener();
	}

	/**
	 * @return EntityMentionListener
	 */
	public function getEntityMentionListener() {
		return $this->mentionedEntityTracker;
	}

	public function setEntityMentionListener( EntityMentionListener $mentionedEntityTracker ) {
		$this->mentionedEntityTracker = $mentionedEntityTracker;
	}

	/**
	 * Adds the given Statement's main Snak to the RDF graph.
	 *
	 * @param RdfWriter $writer
	 * @param string $snakNamespace
	 * @param Snak $snak
	 * @param string $propertyNamespace
	 * @param string $parentLName the subject owning the Snak (a statement or reference)
	 *
	 * @throws InvalidArgumentException
	 */
	public function addSnak( RdfWriter $writer, $snakNamespace, Snak $snak, $propertyNamespace, string $parentLName ) {
		$propertyId = $snak->getPropertyId();
		switch ( $snak->getType() ) {
			case 'value':
				/** @var PropertyValueSnak $snak */
				'@phan-var PropertyValueSnak $snak';
				$this->addSnakValue( $writer, $snakNamespace, $snak, $propertyNamespace );
				break;
			case 'somevalue':
				$propertyValueLName = $this->vocabulary->getEntityLName( $propertyId );

				$stableBNodeLabel = md5( implode( '-', [ $parentLName, $propertyNamespace, $snakNamespace, $snak->getHash() ] ) );
				$writer->say( $propertyNamespace, $propertyValueLName )->is( '_', $writer->blank( $stableBNodeLabel ) );
				break;
			case 'novalue':
				$propertyValueLName = $this->vocabulary->getEntityLName( $propertyId );
				$propertyRepositoryName = $this->vocabulary->getEntityRepositoryName( $propertyId );

				$writer->say( 'a' )->is(
					$this->vocabulary->propertyNamespaceNames[$propertyRepositoryName][RdfVocabulary::NSP_NOVALUE],
					$propertyValueLName
				);
				break;
			default:
				throw new InvalidArgumentException( 'Unknown snak type: ' . $snak->getType() );
		}

		$this->mentionedEntityTracker->propertyMentioned( $snak->getPropertyId() );
	}

	/**
	 * Adds the value of the given property to the RDF graph.
	 *
	 * @param RdfWriter $writer
	 * @param string $snakNamespace
	 * @param PropertyValueSnak $snak
	 * @param string $propertyNamespace The property namespace for this snak
	 */
	private function addSnakValue(
		RdfWriter $writer,
		$snakNamespace,
		PropertyValueSnak $snak,
		$propertyNamespace
	) {
		$propertyId = $snak->getPropertyId();
		$propertyValueLName = $this->vocabulary->getEntityLName( $propertyId );
		$propertyKey = $propertyId->getSerialization();

		// cache data type for all properties we encounter
		if ( !isset( $this->propertyTypes[$propertyKey] ) ) {
			try {
				$this->propertyTypes[$propertyKey] = $this->propertyLookup->getDataTypeIdForProperty( $propertyId );
			} catch ( PropertyDataTypeLookupException $e ) {
				$this->propertyTypes[$propertyKey] = "unknown";
			}
		}

		$dataType = $this->propertyTypes[$propertyKey];
		$this->valueBuilder->addValue( $writer, $propertyNamespace, $propertyValueLName, $dataType, $snakNamespace, $snak );
	}

}
