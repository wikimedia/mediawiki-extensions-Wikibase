<?php

namespace Wikibase\Lib\Tests\Modules;

use PHPUnit4And6Compat;
use ResourceLoaderContext;
use Wikibase\RepoAccessModule;

/**
 * @covers Wikibase\RepoAccessModule
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class RepoAccessModuleTest extends \PHPUnit\Framework\TestCase {
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
		$module = new RepoAccessModule();
		$script = $module->getScript( $this->getContext() );
		$this->assertStringStartsWith( 'mw.config.set({"wbRepo":', $script );
		$this->assertStringEndsWith( '});', $script );
	}

}
