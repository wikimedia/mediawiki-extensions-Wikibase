<?php

namespace Wikibase\Test;

use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\LanguageFallbackChainFactory;

/**
 * @covers Wikibase\Lib\Serializers\SerializationOptions
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Liangent < liangent@gmail.com >
 */
class SerializationOptionsTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider constructorProvider
	 */
	public function testConstructor( array $array ) {
		$options = new SerializationOptions( $array );

		foreach ( $array as $key => $value ) {
			if ( $value === null ) {
				$this->assertFalse( $options->hasOption( $key ) );
				$this->assertNull( $options->getOption( $key ) );
			} else {
				$this->assertTrue( $options->hasOption( $key ) );
				$this->assertEquals( $value, $options->getOption( $key ) );
			}
		}

		$this->assertTrue( true );
	}

	public function constructorProvider() {
		return array(
			array( array() ),
			array( array( 'foo' => 'spam', 'bar' => null, 'stuff' => array( 1, 2, 3 ) ) ),
			array( array( 'foo.bar' => 'spam', 'huff/puff#42' => 'ham' ) ),
		);
	}

	/**
	 * @dataProvider constructorErrorProvider
	 */
	public function testConstructorError( $array, $error ) {
		$this->setExpectedException( $error );

		new SerializationOptions( $array );
	}

	public function constructorErrorProvider() {
		return array(
			//array( 'nope', 'PHPUnit_Framework_Error' ),
			//array( 13, 'PHPUnit_Framework_Error' ),
			array( array( 1, 2, 3 ), 'InvalidArgumentException' ),
			array( array( 'strange stuff' => 23 ), 'InvalidArgumentException' ),
			array( array( '' => 17 ), 'InvalidArgumentException' ),
		);
	}

	public function testInitOption() {
		$options = new SerializationOptions( array( 'test' => 'text' ) );

		$options->initOption( 'test', 'spam' );
		$options->initOption( 'zest', 'ham' );

		$this->assertEquals( 'text', $options->getOption( 'test' ), 'should not override' );
		$this->assertEquals( 'ham',  $options->getOption( 'zest' ), 'should initialize' );

		$options->initOption( 'test', null );
		$options->initOption( 'best', null );

		$this->assertEquals( 'text', $options->getOption( 'test' ), 'should not remove' );
		$this->assertFalse( $options->hasOption( 'best' ), 'should not initialize' );
	}

	/**
	 * @dataProvider setOptionErrorProvider
	 */
	public function testInitOptionError( $key, $value ) {
		$this->setExpectedException( 'InvalidArgumentException' );

		$options = new SerializationOptions();
		$options->initOption( $key, $value );
	}

	public function testSetOption() {
		$options = new SerializationOptions( array( 'test' => 'text' ) );

		$options->setOption( 'test', 'spam' );
		$options->setOption( 'zest', 'ham' );

		$this->assertEquals( 'spam', $options->getOption( 'test' ), 'should override' );
		$this->assertEquals( 'ham',  $options->getOption( 'zest' ), 'should initialize' );

		$options->setOption( 'test', null );
		$options->setOption( 'best', null );

		$this->assertFalse( $options->hasOption( 'test' ), 'should remove' );
		$this->assertFalse( $options->hasOption( 'best' ), 'should not initialize' );
	}

	/**
	 * @dataProvider setOptionErrorProvider
	 */
	public function testSetOptionError( $key, $value ) {
		$this->setExpectedException( 'InvalidArgumentException' );

		$options = new SerializationOptions();
		$options->setOption( $key, $value );
	}

	public function setOptionErrorProvider() {
		return array(
			array( '', 'foo' ),
			array( null, 'foo' ),
			array( 7, 'foo' ),
			array( '(*)', 'foo' ),
		);
	}

	public function testGetOption() {
		$options = new SerializationOptions( array( 'test' => 'text' ) );

		$this->assertEquals( 'text', $options->getOption( 'test' ) );
		$this->assertEquals( 'text', $options->getOption( 'test', 'default' ) );

		$this->assertEquals( null,  $options->getOption( 'zest' ) );
		$this->assertEquals( 17.3,  $options->getOption( 'zest', 17.3 ) );
	}

	/**
	 * @dataProvider setOptionsProvider
	 */
	public function testSetOptions( $base, $extra ) {
		$options = new SerializationOptions( $base );
		$options->setOptions( $extra );

		$expected = array_merge( $base, $extra );

		foreach ( $expected as $key => $value ) {
			if ( $value === null ) {
				$this->assertFalse( $options->hasOption( $key ) );
				$this->assertNull( $options->getOption( $key ) );
			} else {
				$this->assertTrue( $options->hasOption( $key ) );
				$this->assertEquals( $value, $options->getOption( $key ) );
			}
		}
	}

	public function setOptionsProvider() {
		return array(
			array( array(), array() ),
			array( array(), array( 'foo' => 'spam', 'bar' => null, 'stuff' => array( 1, 2, 3 ) ) ),
			array( array(), array( 'foo.bar' => 'spam', 'huff/puff#42' => 'ham' ) ),

			array( array( 'A' => 1, 'foo' => 2 ), array() ),
			array( array( 'A' => 1, 'foo' => 2 ), array( 'foo' => 'spam', 'bar' => null, 'stuff' => array( 1, 2, 3 ) ) ),
			array( array( 'A' => 1, 'foo' => 2 ), array( 'foo.bar' => 'spam', 'huff/puff#42' => 'ham' ) ),
		);
	}

	/**
	 * @dataProvider setOptionsProvider
	 */
	public function testMerge( $base, $extra ) {
		$options = new SerializationOptions( $base );
		$options->merge( new SerializationOptions( $extra ) );

		$expected = array_merge( $base, $extra );

		foreach ( $expected as $key => $value ) {
			if ( $value === null ) {
				$this->assertFalse( $options->hasOption( $key ) );
				$this->assertNull( $options->getOption( $key ) );
			} else {
				$this->assertTrue( $options->hasOption( $key ) );
				$this->assertEquals( $value, $options->getOption( $key ) );
			}
		}
	}

	/**
	 * @dataProvider getOptionsProvider
	 */
	public function testGetOptions( $array ) {
		$options = new SerializationOptions( $array );
		$actual = $options->getOptions();
		$actual = array_intersect_key( $actual, $array );

		$this->assertEquals( $array, $actual );
	}

	public function getOptionsProvider() {
		return array(
			array( array() ),
			array( array( 'foo' => 'spam', 'stuff' => array( 1, 2, 3 ) ) ),
			array( array( 'foo.bar' => 'spam', 'huff/puff#42' => 'ham' ) ),
		);
	}

	public function testHasOption() {
		$options = new SerializationOptions( array( 'test' => 'text' ) );

		$this->assertFalse( $options->hasOption( 'spam' ) );
		$this->assertFalse( $options->hasOption( 'TEST' ) );
		$this->assertTrue( $options->hasOption( 'test' ) );

		$options->setOption( 'spam', 6 );
		$this->assertTrue( $options->hasOption( 'spam' ) );
	}

	public function testAddToOption() {
		$options = new SerializationOptions( array( 'zest' => 5 ) );

		$options->addToOption( 'test', 'A' );
		$this->assertInternalType( 'array', $options->getOption( 'test' ) );
		$this->assertContains( 'A', $options->getOption( 'test' ) );

		$options->addToOption( 'test', 'B' );
		$this->assertContains( 'A', $options->getOption( 'test' ) );
		$this->assertContains( 'B', $options->getOption( 'test' ) );

		$options->addToOption( 'test', 'A' );
		$this->assertCount( 2, $options->getOption( 'test' ) );

		$this->setExpectedException( 'RuntimeException' );
		$options->addToOption( 'zest', 'X' );
	}

	public function testRemoveFromOption() {
		$options = new SerializationOptions( array(
			'test' => array( 'A', 'B' ),
			'zest' => 5
		) );

		$options->removeFromOption( 'test', 'X' );
		$this->assertContains( 'A', $options->getOption( 'test' ) );
		$this->assertContains( 'B', $options->getOption( 'test' ) );

		$options->removeFromOption( 'test', 'B' );
		$this->assertContains( 'A', $options->getOption( 'test' ) );

		$options->removeFromOption( 'test', 'A' );
		$this->assertEquals( array(), $options->getOption( 'test' ) );

		$this->setExpectedException( 'RuntimeException' );
		$options->removeFromOption( 'zest', 'X' );
	}

	public function testSerializationOptionsConstructor() {
		new SerializationOptions();
		$this->assertTrue( true );
	}

	private function preprocessTestSerializationOptionsLanguages( $languages ) {
		if ( $languages === null ) {
			return null;
		}

		$factory = new LanguageFallbackChainFactory();

		foreach ( $languages as $languageKey => &$languageValue ) {
			if ( !is_numeric( $languageKey ) ) {
				$languageValue = $factory->newFromLanguageCode( $languageKey, $languageValue );
			}
		}

		return $languages;
	}

	/**
	 * @dataProvider provideTestSerializationOptionsLanguages
	 */
	public function testSerializationOptionsLanguages( $languages, $codes, $fallbackChains ) {
		$languages = $this->preprocessTestSerializationOptionsLanguages( $languages );
		$fallbackChains = $this->preprocessTestSerializationOptionsLanguages( $fallbackChains );

		$options = new SerializationOptions();
		$options->setLanguages( $languages );

		$this->assertEquals( $codes, $options->getLanguages() );
		$this->assertEquals( $fallbackChains, $options->getLanguageFallbackChains() );
	}

	public function provideTestSerializationOptionsLanguages() {
		return array(
			array( null, null, null ),
			array( array( 'en' ), array( 'en' ), array( 'en' => LanguageFallbackChainFactory::FALLBACK_SELF ) ),
			array( array( 'en', 'de' ), array( 'en', 'de' ), array(
				'en' => LanguageFallbackChainFactory::FALLBACK_SELF, 'de' => LanguageFallbackChainFactory::FALLBACK_SELF
			) ),
			array(
				array( 'en', 'zh' => LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS ),
				array( 'en', 'zh' ),
				array(
					'en' => LanguageFallbackChainFactory::FALLBACK_SELF,
					'zh' => LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS,
				),
			),
			array(
				array(
					'de-formal' => LanguageFallbackChainFactory::FALLBACK_OTHERS,
					'sr' => LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS,
				),
				array( 'de-formal', 'sr' ),
				array(
					'de-formal' => LanguageFallbackChainFactory::FALLBACK_OTHERS,
					'sr' => LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS,
				),
			),
		);
	}

	/**
	 * @dataProvider provideIdKeyMode
	 */
	public function testSetIdKeyMode( $mode ) {
		$options = new SerializationOptions();
		$options->setIdKeyMode( $mode );

		$this->assertEquals( $mode & SerializationOptions::ID_KEYS_LOWER, $options->shouldUseLowerCaseIdsAsKeys() );
		$this->assertEquals( $mode & SerializationOptions::ID_KEYS_UPPER, $options->shouldUseUpperCaseIdsAsKeys() );
	}

	public function provideIdKeyMode() {
		return array(
			'lower' => array( SerializationOptions::ID_KEYS_LOWER ),
			'upper' => array( SerializationOptions::ID_KEYS_UPPER ),
			'both' => array( SerializationOptions::ID_KEYS_BOTH ),
		);
	}

	/**
	 * @dataProvider provideBadIdKeyMode
	 */
	public function testBadSetIdKeyMode( $mode ) {
		$this->setExpectedException( '\InvalidArgumentException' );

		$options = new SerializationOptions();
		$options->setIdKeyMode( $mode );
	}

	public function provideBadIdKeyMode() {
		return array(
			'none' => array( 0 ),
			'badr' => array( 17 ),
		);
	}

}
