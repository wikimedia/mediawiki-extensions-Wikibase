<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use DatabaseBase;
use LoadBalancer;
use MediaWikiTestCase;
use Wikibase\Repo\Store\SQL\ChangeRequeuer;

/**
 * @author Addshore
 * @license GPL-2.0+
 * @covers Wikibase\Repo\Store\SQL\ChangeRequeuer
 */
class ChangeRequeuerTest extends MediaWikiTestCase {

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject|LoadBalancer
	 */
	private function getMockLoadBalancer() {
		$lb = $this->getMockBuilder( LoadBalancer::class )
			->disableOriginalConstructor()
			->getMock();
		return $lb;
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject|DatabaseBase
	 */
	private function getMockDatabase() {
		$lb = $this->getMockBuilder( DatabaseBase::class )
			->disableOriginalConstructor()
			->getMock();
		return $lb;
	}

	public function provideTestGetChangeRowIds() {
		return array(
			array( '2016', '2017', array( '1', '2', '3', '4' ) ),
			array( '20150101010101', '20160101010102', array( '9999', '2', '4567', '4', '90', '45326475321' ) ),
		);
	}

	/**
	 * @dataProvider provideTestGetChangeRowIds
	 */
	public function testGetChangeRowIdsInRange( $from, $to, $expected ) {
		$lb = $this->getMockLoadBalancer();
		$db = $this->getMockDatabase();

		$lb->expects( $this->once() )
			->method( 'getConnection' )
			->will( $this->returnValue( $db ) );
		$db->expects( $this->exactly( 2 ) )
			->method( 'timestamp' )
			->willReturnCallback(
				function ( $value ) {
					return "T{$value}T";
				}
			);
		$db->expects( $this->exactly( 2 ) )
			->method( 'addQuotes' )
			->willReturnCallback(
				function ( $value ) {
					return "Q{$value}Q";
				}
			);
		$db->expects( $this->once() )
			->method( 'selectFieldValues' )
			->with(
				'wb_changes',
				'change_id',
				array(
					'change_time >= QT' . $from . 'TQ',
					'change_time <= QT' . $to . 'TQ',
				)
			)
			->will( $this->returnValue( $expected ) );

		$requeuer = new ChangeRequeuer( $lb );
		$ids = $requeuer->getChangeRowIdsInRange( $from, $to );
		$this->assertEquals( $expected, $ids );
	}

	public function testRequeueRowBatch() {
		$ids = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 );
		$batchSize = 4;

		$lb = $this->getMockLoadBalancer();
		$db = $this->getMockDatabase();

		$lb->expects( $this->once() )
			->method( 'getConnection' )
			->will( $this->returnValue( $db ) );
		$db->expects( $this->at( 0 ) )
			->method( 'insertSelect' )
			->with(
				'wb_changes',
				'wb_changes',
				// No point in testing this hardcoded array below
				$this->isType( 'array' ),
				array(
					'change_id' => array( 1, 2, 3, 4 )
				)
			);
		$db->expects( $this->at( 1 ) )
			->method( 'insertSelect' )
			->with(
				'wb_changes',
				'wb_changes',
				// No point in testing this hardcoded array below
				$this->isType( 'array' ),
				array(
					'change_id' => array( 5, 6, 7, 8 )
				)
			);
		$db->expects( $this->at( 2 ) )
			->method( 'insertSelect' )
			->with(
				'wb_changes',
				'wb_changes',
				// No point in testing this hardcoded array below
				$this->isType( 'array' ),
				array(
					'change_id' => array( 9, 10 )
				)
			);

		$requeuer = new ChangeRequeuer( $lb );
		$requeuer->requeueRowBatch( $ids, $batchSize );
	}

}
