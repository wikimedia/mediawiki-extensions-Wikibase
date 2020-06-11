<?php

namespace Wikibase\Repo\Rdf\Values;

use DataValues\MonolingualTextValue;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Repo\Rdf\ValueSnakRdfBuilder;
use Wikimedia\Purtle\RdfWriter;

/**
 * RDF mapping for MonolingualTextValues.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class MonolingualTextRdfBuilder implements ValueSnakRdfBuilder {

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
		$snakNamespace,
		PropertyValueSnak $snak
	) {
		/** @var MonolingualTextValue $value */
		$value = $snak->getDataValue();
		'@phan-var MonolingualTextValue $value';
		$writer->say( $propertyValueNamespace, $propertyValueLName )
			->text( $value->getText(), $value->getLanguageCode() );
	}

}
