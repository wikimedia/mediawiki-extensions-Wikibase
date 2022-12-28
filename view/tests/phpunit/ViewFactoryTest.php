<?php

namespace Wikibase\View\Tests;

use HashSiteStore;
use InvalidArgumentException;
use MediaWiki\Languages\LanguageFactory;
use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Services\Statement\Grouper\NullStatementGrouper;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\Formatters\NumberLocalizerFactory;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\LanguageNameLookupFactory;
use Wikibase\Lib\Store\PropertyOrderProvider;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\Repo\LocalizedTextProviderFactory;
use Wikibase\View\CacheableEntityTermsView;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\EntityIdFormatterFactory;
use Wikibase\View\HtmlSnakFormatterFactory;
use Wikibase\View\ItemView;
use Wikibase\View\LanguageDirectionalityLookup;
use Wikibase\View\PropertyView;
use Wikibase\View\SpecialPageLinker;
use Wikibase\View\StatementSectionsView;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\Template\TemplateRegistry;
use Wikibase\View\ViewFactory;

/**
 * @covers \Wikibase\View\ViewFactory
 *
 * @uses Wikibase\View\StatementHtmlGenerator
 * @uses Wikibase\View\EditSectionGenerator
 * @uses Wikibase\View\EntityTermsView
 * @uses Wikibase\View\EntityView
 * @uses Wikibase\View\ItemView
 * @uses Wikibase\View\PropertyView
 * @uses Wikibase\View\SiteLinksView
 * @uses Wikibase\View\SnakHtmlGenerator
 * @uses Wikibase\View\StatementGroupListView
 * @uses Wikibase\View\StatementSectionsView
 * @uses Wikibase\View\Template\Template
 * @uses Wikibase\View\Template\TemplateFactory
 * @uses Wikibase\View\Template\TemplateRegistry
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Thiemo Kreuz
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ViewFactoryTest extends \PHPUnit\Framework\TestCase {

	private function newViewFactory(
		EntityIdFormatterFactory $htmlFactory = null,
		EntityIdFormatterFactory $plainFactory = null
	) {
		$templateFactory = new TemplateFactory( new TemplateRegistry( [] ) );

		$languageNameLookup = $this->createMock( LanguageNameLookup::class );
		$languageNameLookup->expects( $this->never() )
			->method( 'getName' );
		$languageNameLookupFactory = $this->createMock( LanguageNameLookupFactory::class );
		$languageNameLookupFactory->method( 'getForLanguage' )
			->willReturn( $languageNameLookup );

		return new ViewFactory(
			$htmlFactory ?: $this->getEntityIdFormatterFactory( SnakFormatter::FORMAT_HTML ),
			$plainFactory ?: $this->getEntityIdFormatterFactory( SnakFormatter::FORMAT_PLAIN ),
			$this->getSnakFormatterFactory(),
			new NullStatementGrouper(),
			$this->createMock( PropertyOrderProvider::class ),
			new HashSiteStore(),
			new DataTypeFactory( [] ),
			$templateFactory,
			$languageNameLookupFactory,
			$this->createMock( LanguageDirectionalityLookup::class ),
			$this->createMock( NumberLocalizerFactory::class ),
			[],
			[],
			[],
			$this->createMock( LocalizedTextProviderFactory::class ),
			$this->createMock( SpecialPageLinker::class ),
			$this->createMock( LanguageFactory::class )
		);
	}

	/**
	 * @dataProvider invalidConstructorArgumentsProvider
	 */
	public function testConstructorThrowsException(
		EntityIdFormatterFactory $htmlFormatterFactory,
		EntityIdFormatterFactory $plainFormatterFactory
	) {
		$this->expectException( InvalidArgumentException::class );
		$this->newViewFactory( $htmlFormatterFactory, $plainFormatterFactory );
	}

	public function invalidConstructorArgumentsProvider() {
		$htmlFactory = $this->getEntityIdFormatterFactory( SnakFormatter::FORMAT_HTML );
		$plainFactory = $this->getEntityIdFormatterFactory( SnakFormatter::FORMAT_PLAIN );
		$wikiFactory = $this->getEntityIdFormatterFactory( SnakFormatter::FORMAT_WIKI );

		return [
			[ $wikiFactory, $plainFactory ],
			[ $htmlFactory, $wikiFactory ],
		];
	}

	public function testNewItemView() {
		$factory = $this->newViewFactory();
		$itemView = $factory->newItemView(
			MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' ),
			new TermLanguageFallbackChain( [], $this->createStub( ContentLanguages::class ) ),
			$this->createMock( CacheableEntityTermsView::class )
		);

		$this->assertInstanceOf( ItemView::class, $itemView );
	}

	public function testNewPropertyView() {
		$factory = $this->newViewFactory();
		$propertyView = $factory->newPropertyView(
			MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' ),
			new TermLanguageFallbackChain( [], $this->createStub( ContentLanguages::class ) ),
			$this->createMock( CacheableEntityTermsView::class )
		);

		$this->assertInstanceOf( PropertyView::class, $propertyView );
	}

	public function testNewStatementSectionsView() {
		$statementSectionsView = $this->newViewFactory()->newStatementSectionsView(
			'de',
			new TermLanguageFallbackChain( [], $this->createStub( ContentLanguages::class ) ),
			$this->createMock( EditSectionGenerator::class )
		);

		$this->assertInstanceOf( StatementSectionsView::class, $statementSectionsView );
	}

	/**
	 * @param string $format
	 *
	 * @return EntityIdFormatterFactory
	 */
	private function getEntityIdFormatterFactory( $format ) {
		$entityIdFormatter = $this->createMock( EntityIdFormatter::class );

		$formatterFactory = $this->createMock( EntityIdFormatterFactory::class );

		$formatterFactory->method( 'getOutputFormat' )
			->willReturn( $format );

		$formatterFactory->method( 'getEntityIdFormatter' )
			->willReturn( $entityIdFormatter );

		return $formatterFactory;
	}

	/**
	 * @return HtmlSnakFormatterFactory
	 */
	private function getSnakFormatterFactory() {
		$snakFormatter = $this->createMock( SnakFormatter::class );

		$snakFormatter->method( 'getFormat' )
			->willReturn( SnakFormatter::FORMAT_HTML );

		$snakFormatterFactory = $this->createMock( HtmlSnakFormatterFactory::class );

		$snakFormatterFactory->method( 'getSnakFormatter' )
			->willReturn( $snakFormatter );

		return $snakFormatterFactory;
	}

}
