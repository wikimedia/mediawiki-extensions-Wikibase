<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\TitleLookupBasedEntityExistenceChecker;

/**
 * @covers \Wikibase\Lib\Store\TitleBasedEntityExistenceChecker
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TitleLookupBasedEntityExistenceCheckerTest extends TestCase {

	/**
	 * @dataProvider existenceProvider
	 */
	public function testExists( bool $isNull, bool $isKnown, bool $expected ) {
		$entityId = new ItemId( 'Q123' );

		$mockTitle = $this->createMock( Title::class );
		$mockTitle->expects( $this->atMost( 1 ) )
			->method( 'isKnown' )
			->willReturn( $isKnown );

		$titleLookup = $this->createMock( EntityTitleLookup::class );
		$titleLookup->expects( $this->once() )
			->method( 'getTitleForId' )
			->with( $entityId )
			->willReturn( $isNull ? null : $mockTitle );

		$result = ( new TitleLookupBasedEntityExistenceChecker( $titleLookup ) )
			->exists( $entityId );
		$this->assertSame(
			$expected,
			$result
		);
	}

	public function existenceProvider() {
		return [
			'title is null' => [
				'isNull' => true,
				'isKnown' => false,
				'expected' => false
			],
			'title is not null and is known' => [
				'isNull' => false,
				'isKnown' => true,
				'expected' => true
			],
			'title is not null and not known' => [
				'isNull' => false,
				'isKnown' => false,
				'expected' => false
			]
		];
	}

}
