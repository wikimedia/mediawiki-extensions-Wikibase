<?php

namespace Wikibase\Test;

use Wikibase\DumpEntities;
use MediaWikiTestCase;
use Wikibase\Repo\Store\Sql\SqlEntityIdPagerFactory;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\DumpEntities
 *
 * @group Wikibase
 * @group NotIsolatedUnitTest
 *
 * @license GPL-2.0-or-later
 */
class DumpEntitiesTest extends MediaWikiTestCase {

	/**
	 * @dataProvider provideEntityTypeData
	 */
	public function testGetEntityTypes_yieldsRelevantTypes(
		array $expected,
		array $existingEntityTypes,
		array $entityTypesToExcludeFromOutput,
		array $cliEntityTypes
	) {
		$dumper = $this->getMockForAbstractClass( DumpEntities::class );
		$dumper->setDumpEntitiesServices(
			$this->getMockBuilder( SqlEntityIdPagerFactory::class )
				->disableOriginalConstructor()
				->getMock(),
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

		$this->assertSame( $expected, $accessibleDumper->getEntityTypes() );
	}

	public function provideEntityTypeData() {
		yield [
			[ 'item', 'property' ],
			[ 'item', 'property' ],
			[],
			[]
		];
		yield [
			[ 'item', 'property', 'lexeme' ],
			[ 'item', 'property', 'lexeme' ],
			[],
			[],
			[]
		];
		yield [
			[ 'lexeme' ],
			[ 'item', 'property', 'lexeme' ],
			[],
			[ 'lexeme' ]
		];
		yield [
			[ 'item', 'property' ],
			[ 'item', 'property', 'lexeme' ],
			[],
			[ 'item', 'property' ]
		];
		yield [
			[ 'item' ],
			[ 'item', 'property', 'lexeme' ],
			[],
			[ 'item' ]
		];
		yield 'no output available for properties' => [
			[ 'item' ],
			[ 'item', 'property' ],
			[ 'property' ],
			[]
		];
		yield 'no output available for properties, property type requested in CLI' => [
			[],
			[ 'item', 'property' ],
			[ 'property' ],
			[ 'property' ]
		];
	}

}
