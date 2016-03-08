<?php

namespace Wikibase\Lib\Tests\Modules;

use PHPUnit_Framework_TestCase;
use ResourceLoaderContext;
use Wikibase\SitesModule;

/**
 * @covers Wikibase\SitesModule
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class SitesModuleTest extends PHPUnit_Framework_TestCase {

	/**
	 * @return ResourceLoaderContext
	 */
	private function getContext() {
		return $this->getMockBuilder( ResourceLoaderContext::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testGetScript() {
		$module = new SitesModule();
		$script = $module->getScript( $this->getContext() );
		$this->assertStringStartsWith( 'mediaWiki.config.set("wbSiteDetails",', $script );
		$this->assertStringEndsWith( ');', $script );
	}

	public function testGetDefinitionSummary() {
		$module = new SitesModule();
		$summary = $module->getDefinitionSummary( $this->getContext() );
		$this->assertInternalType( 'array', $summary );
		$this->assertArrayHasKey( 0, $summary );
		$this->assertArrayHasKey( 'dataHash', $summary[0] );
	}

}
