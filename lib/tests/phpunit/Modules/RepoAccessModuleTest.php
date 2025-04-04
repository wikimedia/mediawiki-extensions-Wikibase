<?php

namespace Wikibase\Lib\Tests\Modules;

use MediaWiki\Request\FauxRequest;
use MediaWiki\ResourceLoader\Context;
use MediaWiki\ResourceLoader\ResourceLoader;
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
		$context = new Context( $this->createMock( ResourceLoader::class ), new FauxRequest() );
		$script = $module->getScript( $context );
		$this->assertStringStartsWith( 'mw.config.set({"wbRepo":', $script );
		$this->assertStringEndsWith( '});', $script );
	}

}
