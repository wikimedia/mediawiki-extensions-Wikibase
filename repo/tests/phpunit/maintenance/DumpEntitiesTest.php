<?php

namespace Wikibase\Repo\Tests\Maintenance;

use MediaWikiCoversValidator;
use Wikibase\Repo\Maintenance\DumpEntities;
use Wikibase\Repo\Store\Sql\SqlEntityIdPagerFactory;
use Wikimedia\TestingAccessWrapper;

// files in maintenance/ are not autoloaded to avoid accidental usage, so load explicitly
require_once __DIR__ . '/../../../maintenance/DumpEntities.php';

/**
 * @covers \Wikibase\Repo\Maintenance\DumpEntities
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DumpEntitiesTest extends \PHPUnit\Framework\TestCase {
	use MediaWikiCoversValidator;

	/**
	 * @dataProvider provideEntityTypeData
	 */
	public function testGetEntityTypes_yieldsRelevantTypes(
		array $expected,
		string $expectedWarning,
		array $existingEntityTypes,
		array $entityTypesToExcludeFromOutput,
		array $cliEntityTypes
	) {
		$dumper = $this->getMockForAbstractClass( DumpEntities::class );
		$dumper->setDumpEntitiesServices(
			$this->createMock( SqlEntityIdPagerFactory::class ),
			$existingEntityTypes,
			$entityTypesToExcludeFromOutput
		);

		$argv = [ 'dumpRdf.php' ];
		foreach ( $cliEntityTypes as $type ) {
			$argv[] = '--entity-type';
			$argv[] = $type;
		}

		$dumper->loadWithArgv( $argv );

		$accessibleDumper = TestingAccessWrapper::newFromObject( $dumper );
		$this->expectOutputString( $expectedWarning );

		$this->assertSame( $expected, $accessibleDumper->getEntityTypes() );
	}

	public function provideEntityTypeData() {
		yield [
			[ 'item', 'property' ],
			'',
			[ 'item', 'property' ],
			[],
			[],
		];
		yield [
			[ 'item', 'property', 'lexeme' ],
			'',
			[ 'item', 'property', 'lexeme' ],
			[],
			[],
			[],
		];
		yield [
			[ 'lexeme' ],
			'',
			[ 'item', 'property', 'lexeme' ],
			[],
			[ 'lexeme' ],
		];
		yield [
			[ 'item', 'property' ],
			'',
			[ 'item', 'property', 'lexeme' ],
			[],
			[ 'item', 'property' ],
		];
		yield [
			[ 'item' ],
			'',
			[ 'item', 'property', 'lexeme' ],
			[],
			[ 'item' ],
		];
		yield 'Discard unknown entity type' => [
			[ 'item' ],
			"Warning: Unknown entity type banana.\n",
			[ 'item', 'property', 'lexeme' ],
			[],
			[ 'item', 'banana' ],
		];
		yield 'Discard unknown entity types' => [
			[],
			"Warning: Unknown entity type banana.\nWarning: Unknown entity type chocolate.\n",
			[ 'item', 'property', 'lexeme' ],
			[],
			[ 'banana', 'chocolate' ],
		];
		yield 'no output available for properties' => [
			[ 'item' ],
			'',
			[ 'item', 'property' ],
			[ 'property' ],
			[],
		];
		yield 'no output available for properties, property type requested in CLI' => [
			[],
			'',
			[ 'item', 'property' ],
			[ 'property' ],
			[ 'property' ],
		];
	}

}
