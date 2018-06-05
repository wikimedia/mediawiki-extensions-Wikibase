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
		array $disabledEntityTypes,
		array $cliEntityTypes
	) {
		$dumper = $this->getMockForAbstractClass( DumpEntities::class );
		$dumper->setDumpEntitiesServices(
			$this->getMockBuilder( SqlEntityIdPagerFactory::class )
				->disableOriginalConstructor()
				->getMock(),
			$existingEntityTypes,
			$disabledEntityTypes
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
			[ 'item', 'property' ],
			[ 'item', 'property', 'lexeme' ],
			[ 'lexeme' ],
			[]
		];
		yield [
			[ 'item', 'property', 'lexeme' ],
			[ 'item', 'property', 'lexeme' ],
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
			[ 'lexeme' ],
			[ 'item' ]
		];
		yield [
			[], // TODO handle scenario where effectively no entity types are returned
			[ 'item', 'property', 'lexeme' ],
			[ 'lexeme' ],
			[ 'lexeme' ]
		];
	}

}
