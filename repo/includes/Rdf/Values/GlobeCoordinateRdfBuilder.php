<?php

namespace Wikibase\Repo\Rdf\Values;

use DataValues\Geo\Values\GlobeCoordinateValue;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\ValueSnakRdfBuilder;
use Wikimedia\Purtle\RdfWriter;

/**
 * RDF mapping for GlobeCoordinateValue.
 *
 * @license GPL-2.0-or-later
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
	 * @param string $snakNamespace
	 * @param PropertyValueSnak $snak
	 */
	public function addValue(
		RdfWriter $writer,
		$propertyValueNamespace,
		$propertyValueLName,
		$dataType,
		$snakNamespace,
		PropertyValueSnak $snak
	) {
		/** @var GlobeCoordinateValue $value */
		$value = $snak->getDataValue();
		'@phan-var GlobeCoordinateValue $value';
		$point = "Point({$value->getLongitude()} {$value->getLatitude()})";
		$globe = $value->getGlobe();

		if ( $globe && $globe !== GlobeCoordinateValue::GLOBE_EARTH ) {
			$globe = str_replace( '>', '%3E', $globe );
			// Add coordinate system according to http://www.opengeospatial.org/standards/geosparql
			// Per https://portal.opengeospatial.org/files/?artifact_id=47664 sec 8.5.1
			//    All RDFS Literals of type geo:wktLiteral shall consist of an optional URI
			//    identifying the coordinate reference system followed by Simple Features Well Known
			//   Text (WKT) describing a geometric value.
			// Ex: "<http://www.opengis.net/def/crs/EPSG/0/4326> Point(33.95 -83.38)"^^<http://www.opengis.net/ont/geosparql#wktLiteral>
			$point = "<$globe> $point";
		}

		$writer->say( $propertyValueNamespace, $propertyValueLName )
			->value( $point, RdfVocabulary::NS_GEO, "wktLiteral" );

		if ( $this->complexValueHelper !== null ) {
			$this->addValueNode( $writer, $propertyValueNamespace, $propertyValueLName, $dataType, $snakNamespace, $value );
		}
	}

	/**
	 * Adds a value node representing all details of $value
	 *
	 * @param RdfWriter $writer
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param string $snakNamespace
	 * @param GlobeCoordinateValue $value
	 */
	private function addValueNode(
		RdfWriter $writer,
		$propertyValueNamespace,
		$propertyValueLName,
		$dataType,
		$snakNamespace,
		GlobeCoordinateValue $value
	) {
		$valueLName = $this->complexValueHelper->attachValueNode(
			$writer,
			$propertyValueNamespace,
			$propertyValueLName,
			$dataType,
			$snakNamespace,
			$value
		);

		if ( $valueLName === null ) {
			// The value node is already present in the output, don't create it again!
			return;
		}

		$valueWriter = $this->complexValueHelper->getValueNodeWriter();

		$valueWriter->say( RdfVocabulary::NS_ONTOLOGY, 'geoLatitude' )
			->value( $value->getLatitude(), 'xsd', 'double' );

		$valueWriter->say( RdfVocabulary::NS_ONTOLOGY, 'geoLongitude' )
			->value( $value->getLongitude(), 'xsd', 'double' );

		// Disallow nulls in precision, see T123392
		$precision = $value->getPrecision();
		if ( $precision === null ) {
			$valueWriter->a( RdfVocabulary::NS_ONTOLOGY, 'GeoAutoPrecision' );
			// 1/3600 comes from LatLongFormatter.php default value for no precision
			$precision = 1 / 3600;
		}
		$valueWriter->say( RdfVocabulary::NS_ONTOLOGY, 'geoPrecision' )
			->value( $precision, 'xsd', 'double' );

		$valueWriter->say( RdfVocabulary::NS_ONTOLOGY, 'geoGlobe' )
			->is( trim( $value->getGlobe() ) );
	}

}
