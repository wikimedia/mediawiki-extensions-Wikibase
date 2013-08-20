<?php

namespace DataValues\Tests;

use DataValues\IriValue;

/**
 * @covers DataValues\IriValue
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
class IriValueTest extends DataValueTest {

	/**
	 * @see DataValueTest::getClass
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getClass() {
		return 'DataValues\IriValue';
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
		$argLists[] = array( false, '' );
		$argLists[] = array( false, ' foo bar baz foo bar baz foo bar baz foo bar baz foo bar baz foo bar baz ' );

		$argLists[] = array( false, '', '', '', '' );
		$argLists[] = array( false, 'foo', '', '', '' );
		$argLists[] = array( false, '', 'bar', '', '' );
		$argLists[] = array( false, '', 'foo', 'bar', 'baz' );
		$argLists[] = array( false, 'foo', '', 'bar', 'baz' );
		$argLists[] = array( false, '***', 'foo', 'bar', 'baz' );
		$argLists[] = array( false, 'abc42', 'foo', 'bar', 'baz' );

		$argLists[] = array( true, 'ohi', 'foo', 'bar', 'baz' );
		$argLists[] = array( true, 'http', '//www.wikidata.org/w/index.php', 'title=Special:Version', 'sv-credits-datavalues' );
		$argLists[] = array( true, 'ohi', 'foo', '', 'baz' );
		$argLists[] = array( true, 'ohi', 'foo', 'bar', '' );
		$argLists[] = array( true, 'ohi', 'foo', '', '' );
		$argLists[] = array( true, 'ohi', 'foo' );

		$argLists[] = array( false, 'ohi', 'foo', 1 );
		$argLists[] = array( false, 'ohi', 'foo', true );
		$argLists[] = array( false, 'ohi', 'foo', 'baz', null );
		$argLists[] = array( false, 'ohi', 'foo', 'baz', array() );

		return $argLists;
	}

	/**
	 * @dataProvider instanceProvider
	 * @param \DataValues\IriValue $iri
	 * @param array $arguments
	 */
	public function testGetScheme( IriValue $iri, array $arguments ) {
		$this->assertEquals( $arguments[0], $iri->getScheme() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param \DataValues\IriValue $iri
	 * @param array $arguments
	 */
	public function testGetHierarchicalPart( IriValue $iri, array $arguments ) {
		$this->assertEquals( $arguments[1], $iri->getHierarchicalPart() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param \DataValues\IriValue $iri
	 * @param array $arguments
	 */
	public function testGetQuery( IriValue $iri, array $arguments ) {
		$expected = array_key_exists( 2, $arguments ) ? $arguments[2] : '';
		$this->assertEquals( $expected, $iri->getQuery() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param \DataValues\IriValue $iri
	 * @param array $arguments
	 */
	public function testGetFragment( IriValue $iri, array $arguments ) {
		$expected = array_key_exists( 3, $arguments ) ? $arguments[3] : '';
		$this->assertEquals( $expected, $iri->getFragment() );
	}

	public function serializationProvider() {
		$argLists = array();

		$argLists[] = array( 'ohi:there', 'ohi', 'there' );
		$argLists[] = array( 'http://www.wikidata.org', 'http', '//www.wikidata.org' );
		$argLists[] = array( 'http://www.wikidata.org/wiki/Special:Version', 'http', '//www.wikidata.org/wiki/Special:Version' );
		$argLists[] = array( 'http://www.wikidata.org?a=b&c=d', 'http', '//www.wikidata.org', 'a=b&c=d' );
		$argLists[] = array( 'http://www.wikidata.org?a=b&c=d#onoez', 'http', '//www.wikidata.org', 'a=b&c=d', 'onoez' );
		$argLists[] = array( 'http://www.wikidata.org#onoez', 'http', '//www.wikidata.org', '', 'onoez' );
		$argLists[] = array( 'http://www.wikidata.org#onoez#o_O', 'http', '//www.wikidata.org', '', 'onoez#o_O' );

		return $argLists;
	}

	/**
	 * @dataProvider serializationProvider
	 */
	public function testGetIriParts() {
		$expectedParts = func_get_args();
		$input = array_shift( $expectedParts );

		$expectedParts = array_pad( $expectedParts, 4, '' );

		$obtainedParts = IriValue::getIriParts( $input );

		$this->assertEquals( $expectedParts, $obtainedParts );
	}

	/**
	 * @dataProvider serializationProvider
	 */
	public function testGetValue() {
		$args = func_get_args();
		$expected = array_shift( $args );

		$reflector = new \ReflectionClass( $this->getClass() );
		$instance = $reflector->newInstanceArgs( $args );

		$actual = $instance->getValue();

		$this->assertInternalType( 'string', $actual );
		$this->assertEquals( $expected, $actual );
	}

}
