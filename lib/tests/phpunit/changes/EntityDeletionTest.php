<?php

namespace Wikibase\Test;

/**
 * Tests for the Wikibase\EntityDeletion class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseChange
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityDeletionTest extends EntityRefreshTest {

	protected function getClass() {
		return 'Wikibase\EntityDeletion';
	}

}
	
