<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ServiceWiringTest extends ServiceWiringTestCase {

	private const EXTENSION_PREFIX = 'WikibaseRepo.';

	/**
	 * @dataProvider provideWiring
	 */
	public function testAllWiringsAreProperlyShaped( $name, $definition ): void {
		$this->assertStringStartsWith( self::EXTENSION_PREFIX, $name );
		$this->assertIsCallable( $definition );
	}

	/**
	 * @dataProvider provideWiring
	 */
	public function testAllWiringsAreTested( $name, $definition ): void {
		$unprefixedName = preg_replace( '/^' . preg_quote( self::EXTENSION_PREFIX ) . '/', '', $name );
		$testClass = '\\Wikibase\\Repo\\Tests\\Unit\\ServiceWiring\\' . $unprefixedName . 'Test';
		$this->assertTrue(
			class_exists( $testClass ),
			"Expecting test of the '$name' wiring in $testClass"
		);
	}

}
