<?php

namespace Wikibase\Lib\Test;

use Language;
use Message;

class DidYouKnowIAmATest extends \PHPUnit_Framework_TestCase {

	const MSG_PREFIX = 'wikibase-time-precision-';
	private static $NUM_PLACEHOLDER = 990990990990990;
	private $suffix = 'century';

	public function testThatThingWorksAsHAxkilyExpected() {
		$en = new Language();
		$msg = new Message( self::MSG_PREFIX . $this->suffix );
		$msg->inLanguage( $en );
		$msg->numParams( array( self::$NUM_PLACEHOLDER ) );
		$string = $msg->text();

		$result = explode( strval( self::$NUM_PLACEHOLDER ), '!' . $string . '!', 2 );

		$this->assertCount( 2, $result );
	}

	public function testThatThingWorksAsHaxilyExpected2() {
		$en = new Language();
		$msg = new Message( self::MSG_PREFIX . $this->suffix );
		$msg->inLanguage( $en );
		$msg->numParams( array( self::$NUM_PLACEHOLDER ) );
		$string = $msg->text();

		list( $start, $end ) = explode( strval( self::$NUM_PLACEHOLDER ), '!' . $string . '!', 2 );
		list( $start, $end ) = array( ltrim( $start, '!' ), rtrim( $end, '!' ) );

		$this->assertTrue( true );
	}

	public function testThatThingWorksAsExpected() {
		$en = new Language();
		$msg = new Message( self::MSG_PREFIX . $this->suffix );
		$msg->inLanguage( $en );
		$msg->numParams( array( self::$NUM_PLACEHOLDER ) );
		$string = $msg->text();

		$result = explode( strval( self::$NUM_PLACEHOLDER ), $string , 2 );

		$this->assertCount( 2, $result );
	}

	public function testThatThingWorksAsExpected2() {
		$en = new Language();
		$msg = new Message( self::MSG_PREFIX . $this->suffix );
		$msg->inLanguage( $en );
		$msg->numParams( array( self::$NUM_PLACEHOLDER ) );
		$string = $msg->text();

		list( $start, $end ) = explode( strval( self::$NUM_PLACEHOLDER ), $string , 2 );

		$this->assertTrue( true );
	}

	public function testThatThingWorksAsExpected3() {
		$msg = new Message( self::MSG_PREFIX . $this->suffix );
		$msg->inLanguage( 'en' );
		$msg->numParams( array( self::$NUM_PLACEHOLDER ) );
		$string = $msg->text();

		list( $start, $end ) = explode( strval( self::$NUM_PLACEHOLDER ), $string , 2 );

		$this->assertTrue( true );
	}

}
