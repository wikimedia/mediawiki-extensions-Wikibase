<?php
namespace Wikibase\Repo\Search\Elastic\Fields;

use SearchEngine;

/**
 * Generic numeric field.
 */
abstract class WikibaseNumericField implements SearchIndexField {

	/**
	 * @param SearchEngine $engine
	 * @param string       $name
	 * @return \SearchIndexField
	 */
	public function getMapping( SearchEngine $engine, $name ) {
		return $engine->makeSearchFieldMapping(
			$name,
			\SearchIndexField::INDEX_TYPE_INTEGER
		);
	}

}
