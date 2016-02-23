<?php

namespace Wikibase\Client\Test\Store;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Title;
use Wikibase\Client\Store\AddUsagesForPageJob;
use Wikibase\Client\Store\UsageUpdater;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\BasicEntityIdParser;

/**
 * @covers Wikibase\Client\Store\AddUsagesForPageJob
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class AddUsagesForPageJobTest extends PHPUnit_Framework_TestCase {

	public function provideConstructor_failure() {
		$pageId = 17;
		$usageQ5X = new EntityUsage( new ItemId( 'Q5' ), 'X' );
		$usages = array( $usageQ5X->asArray() );

		return array(
			'empty' => array( array() ),

			'$pageId is missing' => array( array(
				'usages' => $usages,
			) ),
			'$pageId is not an int' => array( array(
				'pageId' => 'foo',
				'usages' => $usages,
			) ),
			'$pageId is zero' => array( array(
				'pageId' => 0,
				'usages' => $usages,
			) ),
			'$usages is missing' => array( array(
				'pageId' => $pageId,
			) ),
			'$usages is not an array' => array( array(
				'pageId' => $pageId,
				'usages' => 'xxx',
			) ),
			'$usages is empty' => array( array(
				'pageId' => $pageId,
				'usages' => array(),
			) ),
			'$usages contains crap' => array( array(
				'pageId' => $pageId,
				'usages' => array( 1, 2, 3 ),
			) ),
		);
	}

	/**
	 * @dataProvider provideConstructor_failure
	 */
	public function testConstructor_failure( array $params ) {
		$this->setExpectedException( InvalidArgumentException::class );

		new AddUsagesForPageJob( $this->getMock( Title::class ), $params );
	}

	public function testDeduplicationInfo() {
		$usage = new EntityUsage( new ItemId( 'Q100' ), 'X' );

		$params = array(
			'pageId' => 18,
			'usages' => array( $usage->asArray() )
		);

		$title = Title::makeTitle( NS_MAIN, 'Bar' );

		$expected = array(
			'type' => 'wikibase-addUsagesForPage',
			'namespace' => NS_MAIN,
			'title' => 'Bar',
			'params' => array(
				'pageId' => 18,
				'usages' => array( $usage->asArray() )
			),
		);

		$job = new AddUsagesForPageJob( $title, $params );

		$this->assertEquals( $expected, $job->getDeduplicationInfo() );
	}

	public function testRun() {
		$usageQ5X = new EntityUsage( new ItemId( 'Q5' ), 'X' );
		$params = array(
			'pageId' => 17,
			'usages' => array( $usageQ5X->asArray() )
		);

		$usageUpdater = $this->getMockBuilder( UsageUpdater::class )
			->disableOriginalConstructor()
			->getMock();

		$usageUpdater->expects( $this->once() )
			->method( 'addUsagesForPage' )
			->with(
				$params['pageId'],
				array( $usageQ5X )
			);

		$job = new AddUsagesForPageJob( $this->getMock( Title::class ), $params );
		$job->overrideServices( $usageUpdater, new BasicEntityIdParser() );

		$job->run();
	}

	public function testNewSpec() {
		$usageQ5X = new EntityUsage( new ItemId( 'Q5' ), 'X' );

		$title = $this->getMock( Title::class );
		$title->expects( $this->once() )
			->method( 'getArticleId' )
			->will( $this->returnValue( 17 ) );

		$usages = array( $usageQ5X );

		$spec = AddUsagesForPageJob::newSpec( $title, $usages );

		$expected = array(
			'pageId' => 17,
			'usages' => array( $usageQ5X->asArray() ),
		);

		$this->assertEquals( 'wikibase-addUsagesForPage', $spec->getType() );
		$this->assertSame( $title, $spec->getTitle() );
		$this->assertEquals( $expected, $spec->getParams() );
	}

}
