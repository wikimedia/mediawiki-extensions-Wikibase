<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Language;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\MessageInLanguageProvider;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\Repo\SummaryFormatter;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SummaryFormatterTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.EntityTitleLookup',
			$this->createMock( EntityTitleLookup::class ) );
		$this->mockService( 'WikibaseRepo.DataTypeDefinitions',
			new DataTypeDefinitions( [] ) );
		$this->mockService( 'WikibaseRepo.EntityTypeDefinitions',
			new EntityTypeDefinitions( [] ) );
		$contentLanguage = $this->createMock( Language::class );
		$contentLanguage->method( 'getCode' )
			->willReturn( 'en' );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getContentLanguage' )
			->willReturn( $contentLanguage );
		$languageFallbackChainFactory = $this->createMock( LanguageFallbackChainFactory::class );
		$languageFallbackChainFactory->method( 'newFromLanguageCode' )
			->willReturn( $this->createMock( TermLanguageFallbackChain::class ) );
		$this->mockService( 'WikibaseRepo.LanguageFallbackChainFactory',
			$languageFallbackChainFactory );
		$this->mockService( 'WikibaseRepo.PropertyDataTypeLookup',
			new InMemoryDataTypeLookup() );
		$this->mockService( 'WikibaseRepo.DataTypeFactory',
			new DataTypeFactory( [] ) );
		$this->mockService( 'WikibaseRepo.MessageInLanguageProvider',
			$this->createMock( MessageInLanguageProvider::class ) );
		$this->mockService( 'WikibaseRepo.EntityIdParser',
			new ItemIdParser() );

		$this->assertInstanceOf(
			SummaryFormatter::class,
			$this->getService( 'WikibaseRepo.SummaryFormatter' )
		);
	}

}
