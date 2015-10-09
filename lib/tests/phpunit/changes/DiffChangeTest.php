<?php

namespace Wikibase\Test;

/**
 * @covers Wikibase\DiffChange
 *
 * @since 0.1
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseChange
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class DiffChangeTest extends ChangeRowTest {

	/**
	 * @see ORMRowTest::getRowClass
	 * @since 0.4
	 * @return string
	 */
	protected function getRowClass() {
		return '\Wikibase\DiffChange';
	}

}
