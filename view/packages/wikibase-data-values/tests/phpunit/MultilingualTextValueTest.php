<?php

namespace DataValues\Tests;

use DataValues\MonolingualTextValue;
use DataValues\MultilingualTextValue;

/**
 * @covers DataValues\MultilingualTextValue
 *
 * @file
 * @since 0.1
 *
 * @ingroup DataValue
 *
 * @group DataValue
 * @group DataValueExtensions
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MultilingualTextTest extends DataValueTest {

	/**
	 * @see DataValueTest::getClass
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getClass() {
		return 'DataValues\MultilingualTextValue';
	}

	/**
	 * @see DataValueTest::constructorProvider
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public function constructorProvider() {
		$argLists = array();

		$argLists[] = array( false );
		$argLists[] = array( false, 42 );
		$argLists[] = array( false, false );
		$argLists[] = array( false, true );
		$argLists[] = array( false, null );
		$argLists[] = array( false, 'foo' );
		$argLists[] = array( false, 'en' );
		$argLists[] = array( false, 'en', 42 );
		$argLists[] = array( false, 'en', false );
		$argLists[] = array( false, 'en', array() );
		$argLists[] = array( false, 'en', null );
		$argLists[] = array( false, '', 'foo' );
		$argLists[] = array( false, 'en', 'foo' );
		$argLists[] = array( false, 'en', ' foo bar baz foo bar baz foo bar baz foo bar baz foo bar baz foo bar baz ' );
		$argLists[] = array( false, new MonolingualTextValue( 'en', 'foo' ) );

		$argLists[] = array( true, array() );
		$argLists[] = array( true, array( new MonolingualTextValue( 'en', 'foo' ) ) );
		$argLists[] = array( true, array( new MonolingualTextValue( 'en', 'foo' ), new MonolingualTextValue( 'de', 'foo' ) ) );
		$argLists[] = array( true, array( new MonolingualTextValue( 'en', 'foo' ), new MonolingualTextValue( 'de', 'bar' ) ) );
		$argLists[] = array( true, array(
			new MonolingualTextValue( 'en', 'foo' ),
			new MonolingualTextValue( 'de', ' foo bar baz foo bar baz foo bar baz foo bar baz foo bar baz foo bar baz ' )
		) );

		$argLists[] = array( false, array( 'foo' ) );
		$argLists[] = array( false, array( 42 => 'foo' ) );
		$argLists[] = array( false, array( '' => 'foo' ) );
		$argLists[] = array( false, array( 'en' => 42 ) );
		$argLists[] = array( false, array( 'en' => null ) );
		$argLists[] = array( false, array( 'en' => true ) );
		$argLists[] = array( false, array( 'en' => array() ) );
		$argLists[] = array( false, array( 'en' => 4.2 ) );

		$argLists[] = array( false, array( new MonolingualTextValue( 'en', 'foo' ), false ) );
		$argLists[] = array( false, array( new MonolingualTextValue( 'en', 'foo' ), 'foobar' ) );

		return $argLists;
	}

	/**
	 * @dataProvider instanceProvider
	 * @param MultilingualTextValue $texts
	 * @param array $arguments
	 */
	public function testGetTexts( MultilingualTextValue $texts, array $arguments ) {
		$actual = $texts->getTexts();

		$this->assertInternalType( 'array', $actual );

		/**
		 * @var MonolingualTextValue $monolingualValue
		 */
		foreach ( $actual as $monolingualValue ) {
			$this->assertInstanceOf( '\DataValues\MonolingualTextValue', $monolingualValue );
		}

		$this->assertEquals( $arguments[0], array_values( $actual ) );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param MultilingualTextValue $texts
	 * @param array $arguments
	 */
	public function testGetValue( MultilingualTextValue $texts, array $arguments ) {
		$this->assertInstanceOf( $this->getClass(), $texts->getValue() );
	}

}
