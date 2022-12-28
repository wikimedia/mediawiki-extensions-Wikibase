<?php

namespace Wikibase\Repo\Tests\ParserOutput\PlaceholderExpander;

use IContextSource;
use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\LanguageWithConversion;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\Repo\ParserOutput\PlaceholderExpander\TermboxRequestInspector;

/**
 * @covers \Wikibase\Repo\ParserOutput\PlaceholderExpander\TermboxRequestInspector
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermboxRequestInspectorTest extends TestCase {

	private $stubContentLanguages;

	protected function setUp(): void {
		$this->stubContentLanguages = $this->createStub( ContentLanguages::class );
		$this->stubContentLanguages->method( 'hasLanguage' )
			->willReturn( true );
	}

	public function testGivenContextWithDefaultLanguages_returnsTrue() {
		$language = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'de' );
		$context = $this->newContextWithLanguage( $language );

		$languageFallbackChainFactory = $this->createMock( LanguageFallbackChainFactory::class );
		$languageFallbackChainFactory->expects( $this->once() )
			->method( 'newFromLanguage' )
			->with( $language )
			->willReturn( new TermLanguageFallbackChain(
				[ LanguageWithConversion::factory( 'de' ), LanguageWithConversion::factory( 'en' ) ],
				$this->stubContentLanguages
			) );

		$languageFallbackChainFactory->expects( $this->once() )
			->method( 'newFromContext' )
			->with( $context )
			->willReturn( new TermLanguageFallbackChain(
				[ LanguageWithConversion::factory( 'de' ), LanguageWithConversion::factory( 'en' ) ],
				$this->stubContentLanguages
			) );

		$inspector = new TermboxRequestInspector( $languageFallbackChainFactory );

		$this->assertTrue( $inspector->isDefaultRequest( $context ) );
	}

	public function testGivenContextWithNonDefault_returnFalse() {
		$language = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' );
		$context = $this->newContextWithLanguage( $language );

		$languageFallbackChainFactory = $this->createMock( LanguageFallbackChainFactory::class );
		$languageFallbackChainFactory->expects( $this->once() )
			->method( 'newFromLanguage' )
			->with( $language )
			->willReturn( new TermLanguageFallbackChain( [ LanguageWithConversion::factory( 'en' ) ], $this->stubContentLanguages ) );

		$languageFallbackChainFactory->expects( $this->once() )
			->method( 'newFromContext' )
			->with( $context )
			->willReturn(
				new TermLanguageFallbackChain( [
					LanguageWithConversion::factory( 'en' ),
					LanguageWithConversion::factory( 'de' ),
				], $this->stubContentLanguages )
			);

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
