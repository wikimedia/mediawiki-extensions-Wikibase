<?php
namespace Wikibase\Repo\Search\Elastic\Fields;

use CirrusSearch;
use SearchEngine;
use Wikibase\DataModel\Entity\EntityDocument;

/**
 * Field which contains combination of all labels.
 *
 * @license GPL-2.0+
 * @author Stas Malyshev
 */
class AllLabelsField extends TermIndexField {

	/**
	 * Field name
	 */
	const NAME = 'labels_all';

	public function __construct() {
		parent::__construct( static::NAME, \SearchIndexField::INDEX_TYPE_TEXT );
	}

	/**
	 * @param SearchEngine $engine
	 * @return array
	 */
	public function getMapping( SearchEngine $engine ) {
		// Since we need a specially tuned field, we can not use
		// standard search engine types.
		if ( !( $engine instanceof CirrusSearch ) ) {
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
	 * @return null Always returns null since this field is filled in by copy from other fields.
	 */
	public function getFieldData( EntityDocument $entity ) {
		// All-labels has no data, it is assembled from individual fields by Elastic via copy_to.
		return null;
	}

}
