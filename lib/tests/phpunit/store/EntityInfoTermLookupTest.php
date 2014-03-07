<?php

namespace Wikibase\Test;

use Wikibase\Lib\Store\EntityInfoTermLookup;

/**
 * @covers Wikibase\EntityInfoTermLookup
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStore
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class EntityInfoTermLookupTest extends EntityTermLookupTest {

	protected function getEntityTermLookup() {
		return new EntityInfoTermLookup( array(
			'P1' => array(
				'labels' => array(
					'de' => array( 'language' => 'de', 'value' => 'de-label' ),
					'en' => array( 'language' => 'en', 'value' => 'en-label' ),
				),
				'descriptions' => array(
					'de' => array( 'language' => 'de', 'value' => 'de-description' ),
					'en' => array( 'language' => 'en', 'value' => 'en-description' ),
				),
			),
		) );
	}

}
