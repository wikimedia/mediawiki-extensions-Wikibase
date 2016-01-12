<?php

namespace Wikibase\Rdf\Values;

use DataValues\Geo\Values\GlobeCoordinateValue;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Rdf\ValueSnakRdfBuilder;
use Wikibase\Rdf\RdfVocabulary;
use Wikimedia\Purtle\RdfWriter;

/**
 * RDF mapping for GlobeCoordinateValue.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class GlobeCoordinateRdfBuilder implements ValueSnakRdfBuilder {

	/**
	 * @var ComplexValueRdfHelper|null
	 */
	private $complexValueHelper;

	/**
	 * @param ComplexValueRdfHelper|null $complexValueHelper
	 */
	public function __construct( ComplexValueRdfHelper $complexValueHelper = null ) {
		$this->complexValueHelper = $complexValueHelper;
	}

	/**
	 * Adds specific value
	 *
	 * @param RdfWriter $writer
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param PropertyValueSnak $snak
	 */
	public function addValue(
		RdfWriter $writer,
		$propertyValueNamespace,
		$propertyValueLName,
		$dataType,
		PropertyValueSnak $snak
	) {
		/** @var GlobeCoordinateValue $value */
		$value = $snak->getDataValue();
		$point = "Point({$value->getLatitude()} {$value->getLongitude()})";
		$writer->say( $propertyValueNamespace, $propertyValueLName )
			->value( $point, RdfVocabulary::NS_GEO, "wktLiteral" );

		if ( $this->complexValueHelper !== null ) {
			$this->addValueNode( $writer, $propertyValueNamespace, $propertyValueLName, $dataType, $value );
		}
	}

	/**
	 * Adds a value node representing all details of $value
	 *
	 * @param RdfWriter $writer
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param GlobeCoordinateValue $value
	 */
	private function addValueNode(
		RdfWriter $writer,
		$propertyValueNamespace,
		$propertyValueLName,
		$dataType,
		GlobeCoordinateValue $value
	) {
		$valueLName = $this->complexValueHelper->attachValueNode(
			$writer,
			$propertyValueNamespace,
			$propertyValueLName,
			$dataType,
			$value
		);

		if ( $valueLName === null ) {
			// The value node is already present in the output, don't create it again!
			return;
		}

		$valueWriter = $this->complexValueHelper->getValueNodeWriter();

		$valueWriter->say( RdfVocabulary::NS_ONTOLOGY, 'geoLatitude' )
			->value( $value->getLatitude(), 'xsd', 'decimal' );

		$valueWriter->say( RdfVocabulary::NS_ONTOLOGY, 'geoLongitude' )
			->value( $value->getLongitude(), 'xsd', 'decimal' );

		// Disallow nulls in precision, see T123392
		$precision = $value->getPrecision();
		if ( is_null( $precision ) ) {
			$valueWriter->a( RdfVocabulary::NS_ONTOLOGY, 'GeoAutoPrecision' );
			// 1/3600 comes from GeoCoordinateFormatter.php default value for no prcision
			$precision = 1 / 3600;
		}
		$valueWriter->say( RdfVocabulary::NS_ONTOLOGY, 'geoPrecision' )
			->value( $precision, 'xsd', 'decimal' );

		$valueWriter->say( RdfVocabulary::NS_ONTOLOGY, 'geoGlobe' )
			->is( trim( $value->getGlobe() ) );
	}

}
