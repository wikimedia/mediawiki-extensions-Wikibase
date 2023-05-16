<?php

declare( strict_types = 1 );

namespace Wikibase\View\Tests\Module;

use HashConfig;
// phpcs:disable MediaWiki.Classes.FullQualifiedClassName -- T308814
use MediaWiki\ResourceLoader as RL;
use Wikibase\View\Module\TemplateModule;

/**
 * @covers \Wikibase\View\Module\TemplateModule
 *
 * @uses Wikibase\View\Template\TemplateFactory
 * @uses Wikibase\View\Template\TemplateRegistry
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class TemplateModuleTest extends \PHPUnit\Framework\TestCase {

	public function testGetScript() {
		$script = TemplateModule::getScript( $this->getResourceLoaderContext() );
		$this->assertIsString( $script );
		$this->assertStringContainsString( 'wbTemplates', $script );
		$this->assertStringContainsString( 'set( {', $script );
	}

	public function testGetDefinitionSummary() {
		$resources = require __DIR__ . '/../../../resources.php';
		$context = $this->getResourceLoaderContext();
		$module = new RL\FileModule( $resources['wikibase.templates'] );
		$module->setConfig( new HashConfig() );
		$summary = $module->getDefinitionSummary( $context );
		$this->assertIsArray( $summary );
		$this->assertSame(
			hash(
				'md4',
				hash_file( 'md4', __DIR__ . '/../../../resources/templates.php' ) .
				hash_file( 'md4', __DIR__ . '/../../../resources/wikibase/templates.js' )
			),
			$summary[0]['fileHashes']
		);
	}

	/**
	 * @return RL\Context
	 */
	private function getResourceLoaderContext() {
		$context = $this->createMock( RL\Context::class );
		$context->method( 'getLanguage' )
			->willReturn( 'en' );
		return $context;
	}

}
