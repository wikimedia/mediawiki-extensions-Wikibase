<?php

namespace Wikibase\Rdf\Values;

use DataValues\DataValue;
use Wikibase\Rdf\RdfVocabulary;

/**
 * RDF mapping for commonsMedia DataValues.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class CommonsMediaRdfBuilder extends ObjectValueRdfBuilder {

	/**
	 * @var RdfVocabulary
	 */
	private $vocabulary;

	function __construct( RdfVocabulary $vocabulary ) {
		$this->vocabulary = $vocabulary;
	}

	/**
	 * @param DataValue $value
	 *
	 * @return string the object URI
	 */
	protected function getValueUri( DataValue $value ) {
		return $this->vocabulary->getCommonsURI( $value->getValue() );
	}
}
