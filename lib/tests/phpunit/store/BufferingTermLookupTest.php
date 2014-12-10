<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\BufferingTermLookup;
use Wikibase\Lib\Store\EntityTermLookup;

/**
 * @covers Wikibase\Lib\Store\BufferingTermLookup
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert
 */
class BufferingTermLookupTest extends EntityTermLookupTest {

	protected function getEntityTermLookup() {
		$termIndex = $this->getTermIndex();
		return new BufferingTermLookup( $termIndex, 10 );
	}

}
