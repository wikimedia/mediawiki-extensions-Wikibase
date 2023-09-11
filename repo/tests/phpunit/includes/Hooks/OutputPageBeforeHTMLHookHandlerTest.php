<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Hooks;

use MediaWiki\Request\FauxRequest;
use MediaWiki\Title\Title;
use MediaWiki\User\StaticUserOptionsLookup;
use MediaWikiIntegrationTestCase;
use NullHttpRequestFactory;
use NullStatsdDataFactory;
use OutputPage;
use PHPUnit\Framework\MockObject\MockObject;
use RequestContext;
use User;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\EntityFactory;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\LanguageNameLookupFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\Hooks\Helpers\OutputPageEditability;
use Wikibase\Repo\Hooks\Helpers\OutputPageEntityViewChecker;
use Wikibase\Repo\Hooks\Helpers\UserPreferredContentLanguagesLookup;
use Wikibase\Repo\Hooks\OutputPageBeforeHTMLHookHandler;
use Wikibase\Repo\Hooks\OutputPageEntityIdReader;
use Wikibase\Repo\ParserOutput\TermboxView;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers \Wikibase\Repo\Hooks\OutputPageBeforeHTMLHookHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class OutputPageBeforeHTMLHookHandlerTest extends MediaWikiIntegrationTestCase {

	private $editability;
	private $uiLanguageCode;
	private $entityRevisionLookup;
	private $outputPageEntityIdReader;
	private $entityFactory;
	private ItemId $itemId;
	private $languageNameLookup;
	private $preferredLanguageLookup;
	private bool $isExternallyRendered;

	/**
	 * @var MockObject|OutputPageEntityViewChecker
	 */
	private $entityViewChecker;

	private SettingsArray $settings;

	protected function setUp(): void {
		parent::setUp();
		$this->itemId = new ItemId( 'Q1' );
		$this->uiLanguageCode = 'en';

		$this->entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$this->languageNameLookup = $this->createMock( LanguageNameLookup::class );
		$this->outputPageEntityIdReader = $this->createMock( OutputPageEntityIdReader::class );
		$this->entityFactory = $this->createMock( EntityFactory::class );
		$this->editability = $this->mockEditability();
		$this->isExternallyRendered = false;
		$this->settings = new SettingsArray( [
			'ssrServerUrl' => null,
			'ssrServerTimeout' => 3,
			'termboxUserSpecificSsrEnabled' => true,
			'tmpEnableMulLanguageCode' => false,
		] );

		$this->preferredLanguageLookup = $this->createMock( UserPreferredContentLanguagesLookup::class );
		$this->preferredLanguageLookup->method( 'getLanguages' )
			->willReturn( [ $this->uiLanguageCode, 'de', 'es', 'ru' ] );

		$this->entityViewChecker = $this->createMock( OutputPageEntityViewChecker::class );
		$this->entityViewChecker->method( 'hasEntityView' )
			->willReturn( true );
	}

	private function newOutputPage(): OutputPage {
		$mockContext = $this->createMock( RequestContext::class );
		$mockContext->method( 'getLanguage' )
			->willReturn( $this->getServiceContainer()->getLanguageFactory()->getLanguage( $this->uiLanguageCode ) );
		$mockContext->method( 'getUser' )->willReturn( new User() );
		$mockContext->method( 'getRequest' )->willReturn( new FauxRequest() );
		$mockContext->method( 'getConfig' )->willReturn( RequestContext::getMain()->getConfig() );
		$outputPage = new OutputPage( $mockContext );
		$outputPage->setTitle( $this->createMock( Title::class ) );
		$outputPage->setArticleFlag( true );

		return $outputPage;
	}

	private function getHookHandler(): OutputPageBeforeHTMLHookHandler {
		return new OutputPageBeforeHTMLHookHandler(
			new NullHttpRequestFactory(),
			new NullStatsdDataFactory(),
			$this->settings,
			TemplateFactory::getDefaultInstance(),
			$this->entityRevisionLookup,
			$this->createConfiguredMock( LanguageNameLookupFactory::class,
				[ 'getForLanguage' => $this->languageNameLookup ] ),
			$this->outputPageEntityIdReader,
			$this->entityFactory,
			'',
			$this->editability,
			$this->isExternallyRendered,
			$this->preferredLanguageLookup,
			$this->entityViewChecker,
			WikibaseRepo::getLanguageFallbackChainFactory(),
			new StaticUserOptionsLookup( [] ),
			WikibaseRepo::getLanguageDirectionalityLookup()
		);
	}

	/**
	 * Integration test mostly testing that things don't fatal/ throw.
	 */
	public function testOutputPageBeforeHTMLHookHandler() {
		$out = $this->newOutputPage();

		$this->languageNameLookup->expects( $this->never() )
			->method( 'getName' );

		$this->outputPageEntityIdReader = $this->getOutputPageEntityIdReaderReturningEntity( $this->itemId );
		$this->entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );

		$this->preferredLanguageLookup->method( 'getLanguages' )
			->with( $this->uiLanguageCode, $out->getUser() ); // return value already mocked in setUp

		$outputPageBeforeHTMLHookHandler = $this->getHookHandler();

		$html = '';
		$out->setTitle( Title::makeTitle( 0, 'OutputPageBeforeHTMLHookHandlerTest' ) );
		$out->setProperty(
			'wikibase-view-chunks',
			[ '$1' => [ 'entityViewPlaceholder-entitytermsview-entitytermsforlanguagelistview-class' ] ]
		);
		$out->setProperty(
			'wikibase-terms-list-items',
			[]
		);
		$out->setArticleFlag( true );

		$outputPageBeforeHTMLHookHandler->onOutputPageBeforeHTML( $out, $html );
	}

	public function testOutputPageBeforeHTMLHookHandlerShouldNotWorkOnNonEntityViewPages() {
		$out = $this->newOutputPage();
		$this->editability->expects( $this->never() )->method( 'validate' );

		$html = '';
		$this->entityViewChecker = $this->createMock( OutputPageEntityViewChecker::class );
		$this->entityViewChecker->expects( $this->once() )
			->method( 'hasEntityView' )
			->willReturn( false );

		$this->getHookHandler()->onOutputPageBeforeHTML( $out, $html );

		$this->assertSame( '', $html );
	}

	public function testGivenDeletedRevision_hookHandlerDoesNotFail() {
		$this->outputPageEntityIdReader->expects( $this->once() )
			->method( 'getEntityIdFromOutputPage' )
			->willReturn( null );

		$out = $this->newOutputPage();
		$out->setProperty( 'wikibase-view-chunks', [ '$1' => [ 'termbox' ] ] );
		$out->setArticleFlag( true );

		$html = '$1';
		$this->getHookHandler()->onOutputPageBeforeHTML( $out, $html );
		$this->assertSame( '', $html );
	}

	public function testGivenExternallyRenderedMarkup_usesRespectivePlaceholderExpander() {
		$this->entityFactory->expects( $this->once() )
			->method( 'newEmpty' )
			->willReturn( new Item( $this->itemId ) );
		$this->isExternallyRendered = true;

		$this->outputPageEntityIdReader = $this->getOutputPageEntityIdReaderReturningEntity( $this->itemId );

		$expectedHtml = '<div>termbox</div>';
		$placeholder = '$1';

		$out = $this->newOutputPage();
		$out->setProperty( TermboxView::TERMBOX_MARKUP, $expectedHtml );
		$out->setProperty( 'wikibase-view-chunks', [ $placeholder => [ TermboxView::TERMBOX_PLACEHOLDER ] ] );
		$out->setArticleFlag( true );

		$html = $placeholder;
		$this->getHookHandler()->onOutputPageBeforeHTML( $out, $html );

		$this->assertSame( $expectedHtml, $html );
	}

	private function getOutputPageEntityIdReaderReturningEntity(
		ItemId $itemId
	): OutputPageEntityIdReader {
		$outputPageEntityIdReader = $this->createMock( OutputPageEntityIdReader::class );
		$outputPageEntityIdReader->expects( $this->once() )
			->method( 'getEntityIdFromOutputPage' )
			->willReturn( $itemId );

		return $outputPageEntityIdReader;
	}

	public function testGivenPageIsEditable_keepsEditButtonsAndRemovesSpecialMarkup() {
		$contentBetweenEditLinks = 'hello';
		$editLink1 = 'edit link 1';
		$editLink2 = 'edit link 2';
		$html = "<wb:sectionedit>$editLink1</wb:sectionedit> $contentBetweenEditLinks <wb:sectionedit>$editLink2</wb:sectionedit>";
		$out = $this->newOutputPage();

		$this->getHookHandler()->onOutputPageBeforeHTML( $out, $html );

		$this->assertEquals( "$editLink1 $contentBetweenEditLinks $editLink2", $html );
	}

	public function testGivenPageIsNotEditable_removesEditButtonsAndSpecialMarkup() {
		$contentBetweenEditLinks = 'hello';
		$html = "<wb:sectionedit>edit link 1</wb:sectionedit>$contentBetweenEditLinks<wb:sectionedit>edit link 2</wb:sectionedit>";
		$out = $this->newOutputPage();
		$this->editability = $this->mockEditabilityDismissive();

		$this->getHookHandler()->onOutputPageBeforeHTML( $out, $html );

		$this->assertSame( $contentBetweenEditLinks, $html );
	}

	public static function hasMulProvider(): array {
		return [
			'mul is not enabled' => [ false, false ],
			'mul is enabled' => [ true, true ],
			'mul is enabled but not always shown, without mul term present' => [ true, false, false ],
			'mul is enabled but not always shown, with mul term present' => [ true, false, true ],
		];
	}

	/**
	 * @dataProvider hasMulProvider
	 */
	public function testOnOutputPageBeforeHTML_mulHandling( bool $hasMul ) {
		$out = $this->newOutputPage();

		$this->outputPageEntityIdReader = $this->getOutputPageEntityIdReaderReturningEntity( $this->itemId );
		$this->entityFactory->expects( $this->once() )
			->method( 'newEmpty' )
			->willReturn( new Item( $this->itemId ) );

		$this->preferredLanguageLookup->method( 'getLanguages' )
			->with( $this->uiLanguageCode, $out->getUser() ); // return value already mocked in setUp

		$outputPageBeforeHTMLHookHandler = $this->getHookHandler();

		$html = '$1';
		$out->setTitle( Title::makeTitle( 0, 'OutputPageBeforeHTMLHookHandlerTest' ) );
		$out->setProperty( 'wikibase-view-chunks', [ '$1' => [ 'termbox' ] ] );
		$termsListItems = [
			'en' => '<termboxrow lang="en">',
			'es' => '<termboxrow lang="es">',
			'ru' => '<termboxrow lang="ru">',
		];
		if ( $hasMul ) {
			$termsListItems['mul'] = '<termboxrow lang="mul">';
		}
		$out->setProperty( 'wikibase-terms-list-items', $termsListItems );
		$out->setArticleFlag( true );

		$outputPageBeforeHTMLHookHandler->onOutputPageBeforeHTML( $out, $html );
		$this->assertStringContainsString( 'lang="en"', $html );
		$this->assertStringContainsString( 'lang="es"', $html );
		$this->assertStringContainsString( 'lang="ru"', $html );
		if ( $hasMul ) {
			$this->assertStringContainsString( 'lang="mul"', $html );
		} else {
			$this->assertStringNotContainsString( 'lang="mul"', $html );
		}
	}

	private function mockEditability( bool $permissive = true ): OutputPageEditability {
		$editability = $this->createMock( OutputPageEditability::class );
		$editability->method( 'validate' )->willReturn( $permissive );
		return $editability;
	}

	private function mockEditabilityDismissive(): OutputPageEditability {
		return $this->mockEditability( false );
	}

}
