<?php

namespace Wikibase\Rdf\Values;

use DataValues\DataValue;
use DataValues\Geo\Values\GlobeCoordinateValue;
use Wikimedia\Purtle\RdfWriter;

/**
 * RDF mapping for GlobeCoordinateValue.
 *
 * @todo: FIXME: test me!
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class GlobeCoordinateRdfBuilder implements DataValueRdfBuilder {

	/**
	 * Adds specific value
	 *
	 * @param RdfWriter $writer
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param GlobeCoordinateValue $value
	 */
	public function addValue(
		RdfWriter $writer,
		$propertyValueNamespace,
		$propertyValueLName,
		$dataType,
		DataValue $value
	) {
		/** @var GlobeCoordinateValue $value */
		$point = "Point({$value->getLatitude()} {$value->getLongitude()})";
		$writer->say( $propertyValueNamespace, $propertyValueLName )
			->value( $point, RdfVocabulary::NS_GEO, "wktLiteral" );
	}

}
