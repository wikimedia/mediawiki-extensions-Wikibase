<?php

namespace Wikibase\Repo\Search\Elastic\Fields;

use SearchEngine;

/**
 * Generic numeric field.
 *
 * @license GPL-2.0+
 * @author Stas Malyshev
 */
abstract class WikibaseNumericField implements WikibaseIndexField {

	/**
	 * @param SearchEngine $engine
	 * @param string       $name
	 *
	 * @return \SearchIndexField
	 */
	public function getMappingField( SearchEngine $engine, $name ) {
		return $engine->makeSearchFieldMapping(
			$name,
			\SearchIndexField::INDEX_TYPE_INTEGER
		);
	}

}
