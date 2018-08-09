<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\View\EntityMetaTags;

/**
 * Class MockEntityMetaTags is a mock implementation of EntityMetaTags for test purposes
 */
class MockEntityMetaTags implements EntityMetaTags {

	public function __construct() {
		$this->constructParams = func_get_args();
	}

	public function getMetaTags( EntityDocument $entity ) {
		return;
	}

}
