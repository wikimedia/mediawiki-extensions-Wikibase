<?php

namespace Wikibase\Client\Tests\Modules;

use PHPUnit_Framework_TestCase;
use ResourceLoaderContext;
use Wikibase\SiteModule;

/**
 * @covers Wikibase\SiteModule
 *
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class SiteModuleTest extends PHPUnit_Framework_TestCase {

	/**
	 * @return ResourceLoaderContext
	 */
	private function getContext() {
		return $this->getMockBuilder( ResourceLoaderContext::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testGetScript() {
		$module = new SiteModule();
		$script = $module->getScript( $this->getContext() );
		$this->assertStringStartsWith( 'mediaWiki.config.set( "wbCurrentSite", ', $script );
		$this->assertStringEndsWith( ' );', $script );
	}

}
