<?php

namespace DataValues\Test;

use DataValues\MonolingualTextValue;

/**
 * Tests for the DataValues\MonolingualTextValue class.
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
class MonolingualTextTest extends DataValueTest {

	/**
	 * @see DataValueTest::getClass
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getClass() {
		return 'DataValues\MonolingualTextValue';
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
		$argLists[] = array( false, array() );
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
		$argLists[] = array( true, 'en', 'foo' );
		$argLists[] = array( true, 'en', ' foo bar baz foo bar baz foo bar baz foo bar baz foo bar baz foo bar baz ' );

		return $argLists;
	}

	/**
	 * @dataProvider instanceProvider
	 * @param \DataValues\MonolingualTextValue $text
	 * @param array $arguments
	 */
	public function testGetText( MonolingualTextValue $text, array $arguments ) {
		$this->assertEquals( $arguments[1], $text->getText() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param \DataValues\MonolingualTextValue $text
	 * @param array $arguments
	 */
	public function testGetLanguageCode( MonolingualTextValue $text, array $arguments ) {
		$this->assertEquals( $arguments[0], $text->getLanguageCode() );
	}

}
