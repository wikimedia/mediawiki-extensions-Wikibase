<?php

namespace Wikibase\Test;

/**
 *  Tests for the Wikibase\Entity class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseEntity
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 */
class EntityTests extends \MediaWikiTestCase {

	public function testGetModelName()  {
		// this should not do anything usefull, except checking that its there
		$stub = $this->getMockForAbstractClass( '\Wikibase\Entity', array( CONTENT_MODEL_WIKIBASE_ITEM ) );
		$this->assertEquals( CONTENT_MODEL_WIKIBASE_ITEM, $stub->getModel() );
	}

}