<?php

namespace Wikibase\Test;
use \Wikibase\ItemContent as ItemContent;

/**
 * Holds ItemContent objects for testing proposes.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup WikibaseRepo
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
final class TestItemContents {

	/**
	 * @since 0.1
	 * @return array of ItemContent
	 */
	public static function getEntities() {
		// @codeCoverageIgnoreStart
		return array_map(
			'\Wikibase\ItemContent::newFromItem',
			TestItems::getEntities()
		);
		// @codeCoverageIgnoreEnd
	}

}
