<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\View\EntityMetaTagsCreator;

/**
 * Class MockEntityMetaTags is a mock implementation of EntityMetaTags for test purposes
 */
class StubEntityMetaTagsCreator implements EntityMetaTagsCreator {

	public function getMetaTags( EntityDocument $entity ) : array {
		return [];
	}

}
