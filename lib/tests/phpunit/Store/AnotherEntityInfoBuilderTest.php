<?php

namespace Wikibase\Lib\Tests\Store;

use Wikibase\Lib\Store\AnotherEntityInfoBuilder;
use Wikibase\Lib\Store\CachingLabelDescriptionLookupForBatch;
use Wikibase\Lib\Store\EntityRetrievingLabelDescriptionLookupForBatch;
use Wikibase\Lib\Store\EntityRevisionCache;

class AnotherEntityInfoBuilderTest extends EntityInfoBuilderTestCase {

	protected function newEntityInfoBuilder() {
		return new AnotherEntityInfoBuilder(
			new CachingLabelDescriptionLookupForBatch(
				new EntityRevisionCache( new \HashBagOStuff() ),
				new \HashBagOStuff(),
				new EntityRetrievingLabelDescriptionLookupForBatch()
			)
		);
	}

}
