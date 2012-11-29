<?php

namespace Wikibase\Test;
use Wikibase\ItemContent;

/**
 * Holds ItemContent objects for testing proposes.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
final class TestItemContents {

	/**
	 * @since 0.1
	 * @return ItemContent[]
	 */
	public static function getItems() {
		// @codeCoverageIgnoreStart
		return array_map(
			'\Wikibase\ItemContent::newFromItem',
			TestItems::getItems()
		);
		// @codeCoverageIgnoreEnd
	}

}
