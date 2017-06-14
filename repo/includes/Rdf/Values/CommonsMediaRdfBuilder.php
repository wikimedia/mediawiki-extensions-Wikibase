<?php

namespace Wikibase\Rdf\Values;

use DataValues\DataValue;
use Wikibase\Rdf\RdfVocabulary;

/**
 * RDF mapping for commonsMedia DataValues.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class CommonsMediaRdfBuilder extends ObjectUriRdfBuilder {

	/**
	 * @var RdfVocabulary
	 */
	private $vocabulary;

	public function __construct( RdfVocabulary $vocabulary ) {
		$this->vocabulary = $vocabulary;
	}

	/**
	 * @param DataValue $value
	 *
	 * @return string the object URI
	 */
	protected function getValueUri( DataValue $value ) {
		return $this->vocabulary->getMediaFileURI( $value->getValue() );
	}

}
