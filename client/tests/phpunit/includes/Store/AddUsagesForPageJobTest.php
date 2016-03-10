<?php

namespace Wikibase\Client\Test\Store;

use InvalidArgumentException;
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
class AddUsagesForPageJobTest extends \PHPUnit_Framework_TestCase {

	public function provideConstructor_failure() {
		$pageId = 17;
		$usageQ5X = new EntityUsage( new ItemId( 'Q5' ), 'X' );
		$usages = array( $usageQ5X->asArray() );
		$touched = '20150101000000';

		return array(
			'empty' => array( array() ),

			'$pageId is missing' => array( array(
				'usages' => $usages,
				'touched' => $touched,
			) ),
			'$pageId is not an int' => array( array(
				'pageId' => 'foo',
				'usages' => $usages,
				'touched' => $touched,
			) ),
			'$pageId is zero' => array( array(
				'pageId' => 0,
				'usages' => $usages,
				'touched' => $touched,
			) ),

			'$usages is missing' => array( array(
				'pageId' => $pageId,
				'touched' => $touched,
			) ),
			'$usages is not an array' => array( array(
				'pageId' => $pageId,
				'usages' => 'xxx',
				'touched' => $touched,
			) ),
			'$usages is empty' => array( array(
				'pageId' => $pageId,
				'usages' => array(),
				'touched' => $touched,
			) ),
			'$usages contains crap' => array( array(
				'pageId' => $pageId,
				'usages' => array( 1, 2, 3 ),
				'touched' => $touched,
			) ),

			'$touched is missing' => array( array(
				'pageId' => $pageId,
				'usages' => $usages,
			) ),
			'$touched is not a string' => array( array(
				'pageId' => $pageId,
				'usages' => $usages,
				'touched' => 23,
			) ),
			'$touched is empty' => array( array(
				'pageId' => $pageId,
				'usages' => $usages,
				'touched' => '',
			) ),
		);
	}

	/**
	 * @dataProvider provideConstructor_failure
	 */
	public function testConstructor_failure( array $params ) {
		$this->setExpectedException( InvalidArgumentException::class );

		$title = Title::makeTitle( NS_MAIN, 'Foo' );
		new AddUsagesForPageJob( $title, $params );
	}

	public function testDeduplicationInfo() {
		$usage = new EntityUsage( new ItemId( 'Q100' ), 'X' );

		$params = array(
			'pageId' => 18,
			'usages' => array( $usage->asArray() ),
			'touched' => '20150801000000'
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
			'usages' => array( $usageQ5X->asArray() ),
			'touched' => '20150101000000'
		);

		$usageUpdater = $this->getMockBuilder( UsageUpdater::class )
			->disableOriginalConstructor()
			->getMock();

		$usageUpdater->expects( $this->once() )
			->method( 'addUsagesForPage' )
			->with(
				$params['pageId'],
				array( $usageQ5X ),
				$params['touched']
			);

		$title = Title::makeTitle( NS_MAIN, 'Foo' );
		$job = new AddUsagesForPageJob( $title, $params );
		$job->overrideServices( $usageUpdater, new BasicEntityIdParser() );

		$job->run();
	}

	public function testNewSpec() {
		$usageQ5X = new EntityUsage( new ItemId( 'Q5' ), 'X' );

		$title = Title::makeTitle( NS_MAIN, 'Foo' );
		$title->resetArticleID( 17 );

		$touched = '20150101000000';
		$usages = array( $usageQ5X );

		$spec = AddUsagesForPageJob::newSpec( $title, $usages, $touched );

		$expected = array(
			'pageId' => $title->getArticleID(),
			'usages' => array( $usageQ5X->asArray() ),
			'touched' => '20150101000000'
		);

		$this->assertEquals( 'wikibase-addUsagesForPage', $spec->getType() );
		$this->assertEquals( $title->getFullText(), $spec->getTitle()->getFullText() );
		$this->assertEquals( $expected, $spec->getParams() );
	}

}
