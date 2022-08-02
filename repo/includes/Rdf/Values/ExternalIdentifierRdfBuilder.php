<?php

namespace Wikibase\Repo\Rdf\Values;

use DataValues\StringValue;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\Store\PropertyInfoProvider;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\ValueSnakRdfBuilder;
use Wikimedia\Purtle\RdfWriter;

/**
 * RDF mapping for StringValues that are interpreted as external identifiers.
 * URIs for the external identifier are generated based on a URI pattern associated with
 * the respective property.
 *
 * @since 0.5
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class ExternalIdentifierRdfBuilder implements ValueSnakRdfBuilder {

	/** @var PropertyInfoProvider */
	private $uriPatternProvider;

	/** @var RdfVocabulary */
	private $rdfVocabulary;

	public function __construct( RdfVocabulary $rdfVocabulary, PropertyInfoProvider $uriPatternProvider ) {
		$this->rdfVocabulary = $rdfVocabulary;
		$this->uriPatternProvider = $uriPatternProvider;
	}

	/**
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
		$snakNamespace,
		PropertyValueSnak $snak
	) {
		// @fixme Add a check for that!
		$id = $this->getValueId( $snak->getDataValue() );
		$uriPattern = $this->uriPatternProvider->getPropertyInfo( $snak->getPropertyId() );

		$writer->say( $propertyValueNamespace, $propertyValueLName )->value( $id );

		$normalizedValueNamespace = $this->rdfVocabulary->normalizedPropertyValueNamespace[$propertyValueNamespace];
		if ( $uriPattern !== null && $normalizedValueNamespace !== null ) {
			$uri = str_replace( '$1', wfUrlencode( $id ), $uriPattern );
			$writer->say( $normalizedValueNamespace, $propertyValueLName )->is( $uri );
		}
	}

	/**
	 * @param StringValue $value
	 *
	 * @return string the external ID
	 */
	private function getValueId( StringValue $value ) {
		return trim( strval( $value->getValue() ) );
	}

}
