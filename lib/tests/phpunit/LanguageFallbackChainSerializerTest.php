<?php

namespace Wikibase\Test;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\LanguageFallbackChainSerializer;

/**
 * Tests for the Wikibase\LanguageFallbackChainSerializer class.
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseUtils
 *
 * @licence GNU GPL v2+
 */
class LanguageFallbackChainSerializerTest extends \MediaWikiTestCase {

	private function assertChainEquals( $expected, $languageFallbackChain ) {
		$expectedChain = $expected->getFallbackChain();
		$chain = $languageFallbackChain->getFallbackChain();

		$this->assertEquals( count( $expectedChain ), count( $chain ) );

		foreach ( $expectedChain as $i => $expectedItem ) {
			$this->assertEquals( $expectedItem->getLanguage()->getCode(), $chain[$i]->getLanguage()->getCode() );

			if ( $expectedItem->getSourceLanguage() === null ) {
				$this->assertNull( $chain[$i]->getSourceLanguage() );
			} else {
				$this->assertEquals(
					$expectedItem->getSourceLanguage()->getCode(),
					$chain[$i]->getSourceLanguage()->getCode()
				);
			}
		}
	}

	/**
	 * @group WikibaseLib
	 * @dataProvider provideValidChain
	 */
	public function testValidChain( $languageFallbackChain ) {
		$serializer = new LanguageFallbackChainSerializer();
		$serialized = $serializer->serialize( $languageFallbackChain );
		$unserialized = $serializer->unserialize( $serialized );
		$this->assertChainEquals( $languageFallbackChain, $unserialized );
	}

	public function provideValidChain() {
		$factory = new LanguageFallbackChainFactory();

		// By design, the serialized form is not for persistant storage.
		// We only test it to make sure it can unserialize data back to its original object.
		return array(
			array( $factory->newFromLanguage( \Language::factory( 'en' ) ) ),
			array( $factory->newFromLanguage( \Language::factory( 'de' ) ) ),
			array( $factory->newFromLanguage( \Language::factory( 'sr-ec' ) ) ),
			array( $factory->newFromLanguage( \Language::factory( 'zh-cn' ),
				LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS
			) ),
			array( $factory->newFromLanguage( \Language::factory( 'ii' ) ) ),
			array( new LanguageFallbackChain( $factory->buildFromBabel( array(
				'N' => array( 'de-formal', 'en' ),
			) ) ) ),
			array( new LanguageFallbackChain( $factory->buildFromBabel( array(
				'N' => array( 'zh-hk', 'en-gb' ),
				'3' => array( 'zh', 'en' ),
			) ) ) ),
		);
	}

	/**
	 * @group WikibaseLib
	 * @dataProvider provideInvalidChain
	 */
	public function testInvalidChain( $serialized ) {
		$serializer = new LanguageFallbackChainSerializer();
		$unserialized = $serializer->unserialize( $serialized );
		$this->assertNull( $unserialized );
	}

	public function provideInvalidChain() {
		return array(
			array( '/' ),
			array( 'zh-cn:sr-ec' ),
		);
	}

}
