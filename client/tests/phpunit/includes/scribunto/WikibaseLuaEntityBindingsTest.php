<?php

namespace Wikibase\Test;

use Wikibase\Client\Scribunto\WikibaseLuaEntityBindings;

/**
 * @covers Wikibase\Client\WikibaseLuaEntityBindings
 *
 * @since 0.5
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseScribunto
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class WikibaseLuaEntityBindingsTest extends \PHPUnit_Framework_TestCase {

	public function testConstructor() {
		$wikibaseLibrary = $this->getWikibaseLibraryImplementation();
		$this->assertInstanceOf(
			'Wikibase\Client\Scribunto\WikibaseLuaEntityBindings',
			$wikibaseLibrary
		);
	}

	private function getWikibaseLibraryImplementation() {
		return new WikibaseLuaEntityBindings( 'enwiki' );
	}

	public function testGetGlobalSiteId() {
		$wikibaseLibrary = $this->getWikibaseLibraryImplementation();
		$this->assertEquals( array( 'enwiki' ), $wikibaseLibrary->getGlobalSiteId() );
	}
}
