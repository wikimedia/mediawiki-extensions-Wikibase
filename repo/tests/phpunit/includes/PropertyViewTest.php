<?php

namespace Wikibase\Test;

/**
 * @covers Wikibase\PropertyView
 *
 * @since 0.1
 *
 * @group Wikibase
 * @group WikibasePropertyView
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 *
 * The database group has as a side effect that temporal database tables are created. This makes
 * it possible to test without poisoning a production database.
 * @group Database
 *
 * Some of the tests takes more time, and needs therefor longer time before they can be aborted
 * as non-functional. The reason why tests are aborted is assumed to be set up of temporal databases
 * that hold the first tests in a pending state awaiting access to the database.
 * @group medium
 */
class PropertyViewTest extends EntityViewTest {

	protected function getEntityViewClass() {
		return 'Wikibase\PropertyView';
	}
}
