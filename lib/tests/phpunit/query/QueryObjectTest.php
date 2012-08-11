<?php

namespace Wikibase\Test;
use \Wikibase\QueryObject as QueryObject;
use \Wikibase\Query as Query;

/**
 * Tests for the Wikibase\QueryObject class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseQuery
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class QueryObjectTest extends EntityObjectTest {

	/**
	 * @see EntityObjectTest::getNewEmpty
	 *
	 * @since 0.1
	 *
	 * @return \Wikibase\Query
	 */
	protected function getNewEmpty() {
		return QueryObject::newEmpty();
	}

}