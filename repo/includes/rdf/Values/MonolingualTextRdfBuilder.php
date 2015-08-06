<?php

namespace Wikibase\Rdf\Values;

use DataValues\DataValue;
use DataValues\MonolingualTextValue;
use Wikibase\Rdf\DataValueRdfBuilder;
use Wikimedia\Purtle\RdfWriter;

/**
 * RDF mapping for MonolingualTextValues.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class MonolingualTextRdfBuilder implements DataValueRdfBuilder {

	/**
	 * Adds specific value
	 *
	 * @param RdfWriter $writer
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param MonolingualTextValue $value
	 */
	public function addValue(
		RdfWriter $writer,
		$propertyValueNamespace,
		$propertyValueLName,
		$dataType,
		DataValue $value
	) {
		/** @var MonolingualTextValue $value */
		$writer->say( $propertyValueNamespace, $propertyValueLName )
			->text( $value->getText(), $value->getLanguageCode() );
	}

}
