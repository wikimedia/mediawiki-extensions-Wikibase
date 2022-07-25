<?php

namespace Wikibase\Lib\Tests\Modules;

use MediaWiki\ResourceLoader\Context;
use Wikibase\Lib\Modules\RepoAccessModule;

/**
 * @covers \Wikibase\Lib\Modules\RepoAccessModule
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class RepoAccessModuleTest extends \PHPUnit\Framework\TestCase {

	public function testGetScript() {
		$module = new RepoAccessModule();
		$script = $module->getScript( $this->createMock( Context::class ) );
		$this->assertStringStartsWith( 'mw.config.set({"wbRepo":', $script );
		$this->assertStringEndsWith( '});', $script );
	}

}
