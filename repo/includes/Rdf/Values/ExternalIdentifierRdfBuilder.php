<?php

namespace Wikibase\Rdf\Values;

use DataValues\DataValue;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\PropertyInfoProvider;
use Wikibase\Rdf\ValueSnakRdfBuilder;
use Wikimedia\Purtle\RdfWriter;

/**
 * RDF mapping for StringValues that are interpreted as external identifiers.
 * URIs for the external identifier are generated based on a URI pattern associated with
 * the respective property.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class ExternalIdentifierRdfBuilder implements ValueSnakRdfBuilder {

	/**
	 * @var PropertyInfoProvider
	 */
	private $uriPatternProvider;

	/**
	 * @param PropertyInfoProvider $uriPatternProvider
	 */
	public function __construct( PropertyInfoProvider $uriPatternProvider ) {
		$this->uriPatternProvider = $uriPatternProvider;
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
		$id = $this->getValueId( $snak->getDataValue() );
		$uriPattern = $this->uriPatternProvider->getPropertyInfo( $snak->getPropertyId() );

		if ( $uriPattern !== null ) {
			$uri = str_replace( '$1', urlencode( $id ), $uriPattern );
			$writer->say( $propertyValueNamespace, $propertyValueLName )->is( $uri );
		} else {
			$writer->say( $propertyValueNamespace, $propertyValueLName )->value( $id );
		}
	}

	/**
	 * @param DataValue $value
	 *
	 * @return string the object URI
	 */
	protected function getValueId( DataValue $value ) {
		return trim( strval( $value->getValue() ) );
	}

}
