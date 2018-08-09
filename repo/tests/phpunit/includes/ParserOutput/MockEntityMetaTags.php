<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\View\EntityMetaTags;

class MockEntityMetaTags implements EntityMetaTags {

	function __construct() {
		$this->constructParams = func_get_args();
	}

	function getMetaTags( EntityDocument $entity ) {
		return;
	}

}
