<?php

/**
 * Minimal set of classes necessary to fulfill needs of parts of Wikibase relying on
 * the ruflin/elastica library.
 */

namespace Elastica {
	class Param implements ArrayableInterface {
	}
}

namespace Elastica\Query {
	use Elastica\Param;

	abstract class AbstractQuery extends Param {
	}

	class Exists extends AbstractQuery {
		public function __construct($field) {
		}
	}
}
