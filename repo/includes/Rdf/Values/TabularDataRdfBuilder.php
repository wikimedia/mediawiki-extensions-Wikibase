<?php

namespace Wikibase\Repo\Rdf\Values;

use DataValues\DataValue;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Rdf\Values\ObjectUriRdfBuilder;

/**
 * @license GPL-2.0+
 */
class TabularDataRdfBuilder extends ObjectUriRdfBuilder {

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
		return $this->vocabulary->getTabularDataURI( $value->getValue() );
	}

}
