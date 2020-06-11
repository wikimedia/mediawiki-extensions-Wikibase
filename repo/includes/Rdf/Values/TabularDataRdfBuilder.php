<?php

namespace Wikibase\Repo\Rdf\Values;

use DataValues\DataValue;
use Wikibase\Repo\Rdf\RdfVocabulary;

/**
 * @license GPL-2.0-or-later
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
