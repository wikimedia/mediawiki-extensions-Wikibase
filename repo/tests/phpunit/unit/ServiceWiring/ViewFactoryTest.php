<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Language;
use Title;
use TitleFactory;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\Formatters\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\EntityIdHtmlLinkFormatterFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;
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
		$this->mockService( 'WikibaseRepo.UserLanguage',
			$this->createMock( Language::class ) );
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
		$this->mockService( 'WikibaseRepo.SnakFormatterFactory',
			$this->createMock( OutputFormatSnakFormatterFactory::class ) );
		$this->mockService( 'WikibaseRepo.DataTypeFactory',
			new DataTypeFactory( [] ) );
		$this->mockService( 'WikibaseRepo.LanguageNameLookup',
			new LanguageNameLookup() );

		$this->assertInstanceOf(
			ViewFactory::class,
			$this->getService( 'WikibaseRepo.ViewFactory' )
		);
	}

}
