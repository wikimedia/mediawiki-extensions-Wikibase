<?php

declare( strict_types = 1 );

namespace Wikibase\View\Tests;

use PHPUnit\Framework\TestCase;
use Wikibase\View\Wbui2025ComponentsFactory;

/**
 * @covers \Wikibase\View\Wbui2025ComponentsFactory
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0-or-later
 * @author Mahmoud Abdelsattar <mahmoud.abdelsattar@wikimedia.de>
 */
class Wbui2025ComponentsFactoryTest extends TestCase {

	private Wbui2025ComponentsFactory $factory;

	protected function setUp(): void {
		$this->factory = new Wbui2025ComponentsFactory();
	}

	public function testGetTemplateFiles_isNotEmpty(): void {
		$this->assertNotEmpty( $this->factory->getTemplateFiles() );
	}

	public function testGetTemplateFiles_valuesAreRepoRelativePaths(): void {
		foreach ( $this->factory->getTemplateFiles() as $relPath ) {
			$this->assertStringStartsWith( 'resources/wikibase.wbui2025/', $relPath );
		}
	}

	public function testGetTemplateCallable_returnsCallableForKnownComponent(): void {
		$callable = $this->factory->getTemplateCallable( 'wbui2025-statement-sections' );
		$this->assertIsCallable( $callable );
	}

	public function testGetTemplateCallable_callableReturnsVueSfcContent(): void {
		$callable = $this->factory->getTemplateCallable( 'wbui2025-statement-sections' );
		$content = $callable();
		$this->assertIsString( $content );
		$this->assertStringContainsString( '<template>', $content );
	}

	public function testGetTemplateCallable_throwsForUnknownComponent(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessageMatches( '/Unknown wbui2025 component/' );
		$this->factory->getTemplateCallable( 'wbui2025-does-not-exist' );
	}

}
