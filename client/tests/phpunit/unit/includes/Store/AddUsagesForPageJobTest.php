<?php

namespace Wikibase\Client\Tests\Unit\Store;

use InvalidArgumentException;
use Title;
use Wikibase\Client\Store\AddUsagesForPageJob;
use Wikibase\Client\Store\UsageUpdater;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;

/**
 * @covers \Wikibase\Client\Store\AddUsagesForPageJob
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class AddUsagesForPageJobTest extends \PHPUnit\Framework\TestCase {

	public function provideConstructor_failure() {
		$pageId = 17;
		$usageQ5X = new EntityUsage( new ItemId( 'Q5' ), 'X' );
		$usages = [ $usageQ5X->asArray() ];

		return [
			'empty' => [ [] ],

			'$pageId is missing' => [ [
				'usages' => $usages,
			] ],
			'$pageId is not an int' => [ [
				'pageId' => 'foo',
				'usages' => $usages,
			] ],
			'$pageId is zero' => [ [
				'pageId' => 0,
				'usages' => $usages,
			] ],
			'$usages is missing' => [ [
				'pageId' => $pageId,
			] ],
			'$usages is not an array' => [ [
				'pageId' => $pageId,
				'usages' => 'xxx',
			] ],
			'$usages is empty' => [ [
				'pageId' => $pageId,
				'usages' => [],
			] ],
			'$usages contains crap' => [ [
				'pageId' => $pageId,
				'usages' => [ 1, 2, 3 ],
			] ],
		];
	}

	/**
	 * @dataProvider provideConstructor_failure
	 */
	public function testConstructor_failure( array $params ) {
		$this->expectException( InvalidArgumentException::class );

		new AddUsagesForPageJob( $this->createMock( Title::class ), $params );
	}

	public function testDeduplicationInfo() {
		$usage = new EntityUsage( new ItemId( 'Q100' ), 'X' );

		$params = [
			'pageId' => 18,
			'usages' => [ $usage->asArray() ],
		];

		$title = Title::makeTitle( NS_MAIN, 'Bar' );

		$job = new AddUsagesForPageJob( $title, $params );

		$info = $job->getDeduplicationInfo();
		$this->assertEquals( 'wikibase-addUsagesForPage', $info['type'] );
		$this->assertEquals(
			$params,
			array_intersect_key( $info['params'], $params )
		);
	}

	public function testRun() {
		$usageQ5X = new EntityUsage( new ItemId( 'Q5' ), 'X' );
		$params = [
			'pageId' => 17,
			'usages' => [ $usageQ5X->asArray() ],
		];

		$usageUpdater = $this->createMock( UsageUpdater::class );

		$usageUpdater->expects( $this->once() )
			->method( 'addUsagesForPage' )
			->with(
				$params['pageId'],
				[ $usageQ5X ]
			);

		$job = new AddUsagesForPageJob( $this->createMock( Title::class ), $params );
		$job->overrideServices( $usageUpdater, new ItemIdParser() );

		$job->run();
	}

	public function testNewSpec() {
		$usageQ5X = new EntityUsage( new ItemId( 'Q5' ), 'X' );

		$title = $this->createMock( Title::class );
		$title->expects( $this->once() )
			->method( 'getArticleID' )
			->willReturn( 17 );

		$usages = [ $usageQ5X ];

		$spec = AddUsagesForPageJob::newSpec( $title, $usages );

		$expected = [
			'pageId' => 17,
			'usages' => [ $usageQ5X->asArray() ],
			'namespace' => $title->getNamespace(),
			'title' => $title->getDBkey(),
		];

		$this->assertEquals( 'wikibase-addUsagesForPage', $spec->getType() );
		$this->assertEquals( $expected, array_intersect_key( $spec->getParams(), $expected ) );
	}

}
