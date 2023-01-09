<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Title;
use TitleFactory;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\Formatters\NumberLocalizerFactory;
use Wikibase\Lib\Formatters\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\LanguageNameLookupFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\EntityIdHtmlLinkFormatterFactory;
use Wikibase\Repo\EntityIdLabelFormatterFactory;
use Wikibase\Repo\LocalizedTextProviderFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;
use Wikibase\View\LanguageDirectionalityLookup;
use Wikibase\View\ViewFactory;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ViewFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'statementSections' => [],
				'siteLinkGroups' => [],
				'specialSiteLinkGroups' => [],
				'badgeItems' => [],
			] ) );
		$this->mockService( 'WikibaseRepo.PropertyDataTypeLookup',
			new InMemoryDataTypeLookup() );
		$this->mockService( 'WikibaseRepo.StatementGuidParser',
			new StatementGuidParser( new ItemIdParser() ) );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getWikiPageFactory' );
		$titleFactory = $this->createMock( TitleFactory::class );
		$titleFactory->expects( $this->once() )
			->method( 'newFromText' )
			->with( 'MediaWiki:Wikibase-SortedProperties' )
			->willReturn( $this->createMock( Title::class ) );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getTitleFactory' )
			->willReturn( $titleFactory );
		$entityIdHtmlLinkFormatterFactory = $this->createMock( EntityIdHtmlLinkFormatterFactory::class );
		$entityIdHtmlLinkFormatterFactory->method( 'getOutputFormat' )
			->willReturn( SnakFormatter::FORMAT_HTML );
		$this->mockService( 'WikibaseRepo.EntityIdHtmlLinkFormatterFactory',
			$entityIdHtmlLinkFormatterFactory );
		$entityIdLabelFormatterFactory = $this->createMock( EntityIdLabelFormatterFactory::class );
		$entityIdLabelFormatterFactory->method( 'getOutputFormat' )
			->willReturn( SnakFormatter::FORMAT_PLAIN );
		$this->mockService( 'WikibaseRepo.EntityIdLabelFormatterFactory',
			$entityIdLabelFormatterFactory );
		$this->mockService( 'WikibaseRepo.SnakFormatterFactory',
			$this->createMock( OutputFormatSnakFormatterFactory::class ) );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getSiteLookup' );
		$this->mockService( 'WikibaseRepo.DataTypeFactory',
			new DataTypeFactory( [] ) );
		$this->mockService( 'WikibaseRepo.LanguageNameLookupFactory',
			$this->createMock( LanguageNameLookupFactory::class ) );
		$this->mockService( 'WikibaseRepo.LanguageDirectionalityLookup',
			$this->createMock( LanguageDirectionalityLookup::class ) );
		$this->mockService(
			'WikibaseRepo.NumberLocalizerFactory',
			$this->createMock( NumberLocalizerFactory::class )
		);
		$this->mockService(
			'WikibaseRepo.LocalizedTextProviderFactory',
			$this->createMock( LocalizedTextProviderFactory::class )
		);
		$this->serviceContainer->expects( $this->once() )
			->method( 'getLanguageFactory' );

		$this->assertInstanceOf(
			ViewFactory::class,
			$this->getService( 'WikibaseRepo.ViewFactory' )
		);
	}

}
