<?php

namespace Wikibase\Repo\Tests\ParserOutput\PlaceholderExpander;

use IContextSource;
use Language;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Repo\ParserOutput\PlaceholderExpander\TermboxRequestInspector;

/**
 * @covers \Wikibase\Repo\ParserOutput\PlaceholderExpander\TermboxRequestInspector
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermboxRequestInspectorTest extends TestCase {

	use \PHPUnit4And6Compat;

	public function testGivenContextWithDefaultLanguages_returnsTrue() {
		$language = Language::factory( 'de' );
		$context = $this->newContextWithLanguage( $language );

		$languageFallbackChainFactory = $this->createMock( LanguageFallbackChainFactory::class );
		$languageFallbackChainFactory->expects( $this->once() )
			->method( 'newFromLanguage' )
			->with( $language )
			->willReturn( new LanguageFallbackChain( [ $language, 'en' ] ) );

		$languageFallbackChainFactory->expects( $this->once() )
			->method( 'newFromContext' )
			->with( $context )
			->willReturn( new LanguageFallbackChain( [ $language, 'en' ] ) );

		$inspector = new TermboxRequestInspector( $languageFallbackChainFactory );

		$this->assertTrue( $inspector->isDefaultRequest( $context ) );
	}

	public function testGivenContextWithNonDefault_returnFalse() {
		$language = Language::factory( 'en' );
		$context = $this->newContextWithLanguage( $language );

		$languageFallbackChainFactory = $this->createMock( LanguageFallbackChainFactory::class );
		$languageFallbackChainFactory->expects( $this->once() )
			->method( 'newFromLanguage' )
			->with( $language )
			->willReturn( new LanguageFallbackChain( [ $language ] ) );

		$languageFallbackChainFactory->expects( $this->once() )
			->method( 'newFromContext' )
			->with( $context )
			->willReturn( new LanguageFallbackChain( [ $language, 'de' ] ) );

		$inspector = new TermboxRequestInspector( $languageFallbackChainFactory );

		$this->assertFalse( $inspector->isDefaultRequest( $context ) );
	}

	/**
	 * @param string $language
	 * @return MockObject|IContextSource
	 */
	protected function newContextWithLanguage( $language ) {
		$context = $this->createMock( IContextSource::class );
		$context->expects( $this->once() )
			->method( 'getLanguage' )
			->willReturn( $language );

		return $context;
	}

}
