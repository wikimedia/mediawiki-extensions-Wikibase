<?php

namespace Wikibase\Lib\Tests\Modules;

use Language;
use PHPUnit_Framework_TestCase;
use ResourceLoaderContext;
use Wikibase\SitesModule;

/**
 * @covers Wikibase\SitesModule
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Thiemo Kreuz
 */
class SitesModuleTest extends PHPUnit_Framework_TestCase {

	/**
	 * @return ResourceLoaderContext
	 */
	private function getContext() {
		$context = $this->getMockBuilder( ResourceLoaderContext::class )
			->disableOriginalConstructor()
			->getMock();

		$context->expects( $this->any() )
			->method( 'getLanguage' )
			->will( $this->returnCallback( function() {
				return Language::factory( 'en' );
			} ) );

		return $context;
	}

	public function testGetScript() {
		$module = new SitesModule();
		$script = $module->getScript( $this->getContext() );
		$this->assertStringStartsWith( 'mw.config.set({"wbSiteDetails":', $script );
		$this->assertStringEndsWith( '});', $script );
	}

	public function testGetDefinitionSummary() {
		$module = new SitesModule();
		$summary = $module->getDefinitionSummary( $this->getContext() );
		$this->assertInternalType( 'array', $summary );
		$this->assertArrayHasKey( 0, $summary );
		$this->assertArrayHasKey( 'dataHash', $summary[0] );
	}

}
