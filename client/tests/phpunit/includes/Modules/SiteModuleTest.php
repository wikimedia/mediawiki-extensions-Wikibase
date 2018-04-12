<?php

namespace Wikibase\Client\Tests\Modules;

use PHPUnit4And6Compat;
use ResourceLoaderContext;
use Wikibase\Client\Modules\SiteModule;

/**
 * @covers Wikibase\Client\Modules\SiteModule
 *
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class SiteModuleTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

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
		$this->assertStringStartsWith( 'mw.config.set({"wbCurrentSite":', $script );
		$this->assertStringEndsWith( '});', $script );
	}

}
