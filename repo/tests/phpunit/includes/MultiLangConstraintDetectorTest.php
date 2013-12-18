<?php

namespace Wikibase\Test;

use Status;
use Wikibase\Item;
use Wikibase\MultiLangConstraintDetector;

/**
 * @covers Wikibase\MultiLangConstraintDetector
 *
 * @since 0.4
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group MultiLangConstraintDetector
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class MultiLangConstraintDetectorTest extends \PHPUnit_Framework_TestCase {

	protected static $limit = 23;

	protected static $short = 'This is a short string'; // 22 bytes

	protected static $long = 'This is a to long string'; // 24 bytes

	public function mlStringProvider() {
		return array(
			array(
				array( 'en' => self::$short ), // 22 bytes
				array(),
				false
			),
			array(
				array( 'en' => self::$long ), // 24 bytes
				array( 'en' => self::$long ), // 24 bytes
				true
			)
		);
	}

	/**
	 * @dataProvider mlStringProvider
	 */
	public function testGetStringLengthConstraintViolations( $data, $expected, $fatal ) {
		$status = Status::newGood();
		$extData = array(
			'da' => self::$short,
			'de' => self::$long,
		);
		$extExpected = array(
			'de' => self::$long,
		);

		$detector = new MultiLangConstraintDetector();
		$result = $detector->getLengthConstraintViolations(
			array_merge( $extData, $data ),
			self::$limit,
			$status
		);

		$this->assertEquals( array_merge( $extExpected, $expected ), $result );
		$this->assertEquals( empty( $result ), $status->isGood() );
	}

	/**
	 * @dataProvider mlStringProvider
	 */
	public function testGetArrayLengthConstraintViolations( $data, $expected, $fatal ) {
		$status = Status::newGood();

		$data = array_map( function ( $v ) {
			return array( $v );
		}, $data );

		$expected = array_map( function( $v ) {
			return array( $v );
		}, $expected );

		$extData = array(
			'da' => array( self::$short ),
			'de' => array( self::$long ),
		);
		$extExpected = array(
			'de' => array( self::$long ),
		);

		$detector = new MultiLangConstraintDetector();
		$result = $detector->getLengthConstraintViolations(
			array_merge_recursive( $extData, $data ),
			self::$limit,
			$status
		);
		$this->assertEquals( array_merge_recursive( $extExpected, $expected ), $result );
		$this->assertEquals( empty( $result ), $status->isGood() );
	}

	/**
	 * @dataProvider mlStringProvider
	 */
	public function testAddStringConstraintChecks( $data, $expected, $fatal ) {
		$status = Status::newGood();
		$detector = new MultiLangConstraintDetector();

		$extData = array(
			'da' => self::$short,
			'de' => self::$long,
		);

		// test labels ---------------
		$baseEntity = new Item( array( 'label' => $extData ) );
		$newEntity = new Item( array( 'label' => array_merge( $extData, $data ) ) );
		$diff = $baseEntity->getDiff( $newEntity );
		$detector->addConstraintChecks( $newEntity, $status, $diff, array( 'length' => self::$limit ) );

		$this->assertEquals( $fatal, !$status->isOk() );

		// test descriptions ---------------
		$baseEntity = new Item( array( 'description' => $extData ) );
		$newEntity = new Item( array( 'description' => array_merge( $extData, $data ) ) );
		$diff = $baseEntity->getDiff( $newEntity );
		$detector->addConstraintChecks( $newEntity, $status, $diff, array( 'length' => self::$limit ) );

		$this->assertEquals( $fatal, !$status->isOk() );
	}

	/**
	 * @dataProvider mlStringProvider
	 */
	public function testAddArrayConstraintChecks( $data, $expected, $fatal ) {
		$status = Status::newGood();
		$detector = new MultiLangConstraintDetector();

		$data = array_map( function( $v ) {
			return array( $v );
		}, $data );

		$extData = array(
			'da' => array( self::$short ),
			'de' => array( self::$long ),
		);

		// test aliases ---------------
		$baseEntity = new Item( array( 'aliases' => $extData ) );
		$newEntity = new Item( array( 'aliases' => array_merge_recursive( $extData, $data ) ) );
		$diff = $baseEntity->getDiff( $newEntity );
		$detector->addConstraintChecks( $newEntity, $status, $diff, array( 'length' => self::$limit ) );

		$this->assertEquals( $fatal, !$status->isOk() );
	}
}
