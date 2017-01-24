<?php
namespace Wikibase\Repo\Search\Elastic\Fields;

use SearchEngine;
use Wikibase\DataModel\Entity\EntityDocument;

/**
 * Field which contains combination of all labels.
 */
class AllLabelsField extends TermIndexField {

	public function __construct() {
		parent::__construct( "", \SearchIndexField::INDEX_TYPE_TEXT );
	}

	/**
	 * @param SearchEngine $engine
	 * @return array
	 */
	public function getMapping( SearchEngine $engine ) {
		// Since we need a specially tuned field, we can not use
		// standard search engine types.
		if ( !( $engine instanceof \CirrusSearch ) ) {
			// For now only Cirrus/Elastic is supported
			return [];
		}

		$config = $this->getUnindexedField();
		$config['fields']['prefix'] =
			$this->getSubfield( 'prefix_asciifolding', 'near_match_asciifolding' );
		$config['fields']['near_match_folded'] = $this->getSubfield( 'near_match_asciifolding' );

		return $config;
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return mixed Get the value of the field to be indexed when a page/document
	 *               is indexed. This might be an array with nested data, if the field
	 *               is defined with nested type or an int or string for simple field types.
	 */
	public function getFieldData( EntityDocument $entity ) {
		// All-labels has no data, it is assembled from individual fields by Elastic via copy_to.
		return null;
	}

}
