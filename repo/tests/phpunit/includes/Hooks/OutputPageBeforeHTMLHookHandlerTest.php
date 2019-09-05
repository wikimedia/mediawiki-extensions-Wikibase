<?php

namespace Wikibase\Repo\Tests\Hooks;

use Language;
use MediaWikiIntegrationTestCase;
use OutputPage;
use PHPUnit4And6Compat;
use RequestContext;
use Title;
use User;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\EntityFactory;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\UserLanguageLookup;
use Wikibase\Repo\Hooks\Helpers\OutputPageEditability;
use Wikibase\Repo\Hooks\Helpers\UserPreferredContentLanguagesLookup;
use Wikibase\Repo\Hooks\OutputPageBeforeHTMLHookHandler;
use Wikibase\Repo\Hooks\OutputPageEntityIdReader;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\Repo\ParserOutput\TermboxView;

/**
 * @covers \Wikibase\Repo\Hooks\OutputPageBeforeHTMLHookHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class OutputPageBeforeHTMLHookHandlerTest extends MediaWikiIntegrationTestCase {
	use PHPUnit4And6Compat;

	private $editability;
	private $uiLanguageCode;
	private $userLanguageLookup;
	private $entityRevisionLookup;
	private $outputPageEntityIdReader;
	private $entityFactory;

	/**
	 * @var ItemId
	 */
	private $itemId;
	private $languageNameLookup;
	private $preferredLanguageLookup;

	/**
	 * @var StaticContentLanguages
	 */
	private $contentLanguages;

	/**
	 * @var bool
	 */
	private $isExternallyRendered;

	public function setUp() {
		parent::setUp();
		$this->itemId = new ItemId( 'Q1' );
		$this->uiLanguageCode = 'en';

		$this->userLanguageLookup = $this->createMock( UserLanguageLookup::class );
		$this->contentLanguages = new StaticContentLanguages( [ 'en', 'es', 'ru' ] );
		$this->entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$this->languageNameLookup = $this->getMock( LanguageNameLookup::class );
		$this->outputPageEntityIdReader = $this->createMock( OutputPageEntityIdReader::class );
		$this->entityFactory = $this->createMock( EntityFactory::class );
		$this->editability = $this->mockEditability();
		$this->isExternallyRendered = false;

		$this->preferredLanguageLookup = $this->createMock( UserPreferredContentLanguagesLookup::class );
		$this->preferredLanguageLookup->method( 'getLanguages' )
			->willReturn( [ [ $this->uiLanguageCode, 'de', 'es', 'ru' ] ] );
	}

	public function testNewFromGlobalState_returnsSelf() {
		$this->assertInstanceOf(
			OutputPageBeforeHTMLHookHandler::class,
			OutputPageBeforeHTMLHookHandler::newFromGlobalState()
		);
	}

	/**
	 * @return OutputPage
	 */
	private function newOutputPage() {
		$mockContext = $this->createMock( RequestContext::class );
		$mockContext->method( 'getLanguage' )->willReturn( new Language( 'qqx' ) );
		$mockContext->method( 'getUser' )->willReturn( new User() );
		$outputPage = new OutputPage( $mockContext );
		$outputPage->setTitle( new Title() );
		$outputPage->setArticleFlag( true );

		return $outputPage;
	}

	private function getHookHandler() {
		return new OutputPageBeforeHTMLHookHandler(
			TemplateFactory::getDefaultInstance(),
			$this->userLanguageLookup,
			$this->contentLanguages,
			$this->entityRevisionLookup,
			$this->languageNameLookup,
			$this->outputPageEntityIdReader,
			$this->entityFactory,
			'',
			$this->editability,
			$this->isExternallyRendered,
			$this->preferredLanguageLookup
		);
	}

	/**
	 * Integration test mostly testing that things don't fatal/ throw.
	 */
	public function testOutputPageBeforeHTMLHookHandler() {
		$out = $this->newOutputPage();

		$this->userLanguageLookup = $this->getUserLanguageLookupReturnsSpecifiedLangs();
		$this->languageNameLookup->expects( $this->never() )
			->method( 'getName' );

		$this->outputPageEntityIdReader = $this->getOutputPageEntityIdReaderReturningEntity( $this->itemId );
		$this->entityRevisionLookup = $this->getEntityRevisionLookupReturningEntity( $this->itemId );

		$this->preferredLanguageLookup->expects( $this->once() )
			->method( 'getLanguages' )
			->with( $this->uiLanguageCode, $out->getUser() )
			->willReturn( [ [ $this->uiLanguageCode, 'de', 'es', 'ru' ] ] );

		$outputPageBeforeHTMLHookHandler = $this->getHookHandler();

		$html = '';
		$out->setTitle( Title::makeTitle( 0, 'OutputPageBeforeHTMLHookHandlerTest' ) );
		$out->setProperty(
			'wikibase-view-chunks',
			[ '$1' => [ 'entityViewPlaceholder-entitytermsview-entitytermsforlanguagelistview-class' ] ]
		);
		$out->setArticleFlag( true );

		$outputPageBeforeHTMLHookHandler->doOutputPageBeforeHTML( $out, $html );

		// Verify the wbUserSpecifiedLanguages JS variable
		$jsConfigVars = $out->getJsConfigVars();
		$wbUserSpecifiedLanguages = $jsConfigVars['wbUserSpecifiedLanguages'];

		$this->assertSame( [ 'es', 'ru' ], $wbUserSpecifiedLanguages );
	}

	public function testOutputPageBeforeHTMLHookHandlerShouldNotWorkOnNonArticles() {
		$out = $this->newOutputPage();
		$this->userLanguageLookup->expects( $this->never() )
			->method( 'getUserSpecifiedLanguages' );
		$this->userLanguageLookup->expects( $this->never() )
			->method( 'getAllUserLanguages' );

		$html = '';
		$out->setTitle( Title::makeTitle( 0, 'OutputPageBeforeHTMLHookHandlerTest' ) );
		$out->setArticleFlag( false );

		$this->getHookHandler()->doOutputPageBeforeHTML( $out, $html );

		// Verify the wbUserSpecifiedLanguages JS variable
		$jsConfigVars = $out->getJsConfigVars();
		$this->assertFalse( isset( $jsConfigVars['wbUserSpecifiedLanguages'] ) );
	}

	public function testGivenDeletedRevision_hookHandlerDoesNotFail() {
		$this->outputPageEntityIdReader->expects( $this->once() )
			->method( 'getEntityIdFromOutputPage' )
			->will( $this->returnValue( null ) );

		$this->userLanguageLookup = $this->getUserLanguageLookupReturnsSpecifiedLangs();

		$out = $this->newOutputPage();
		$out->setProperty( 'wikibase-view-chunks', [ '$1' => [ 'termbox' ] ] );
		$out->setArticleFlag( true );

		$html = '$1';
		$this->getHookHandler()->doOutputPageBeforeHTML( $out, $html );
		$this->assertSame( '', $html );
	}

	public function testGivenExternallyRenderedMarkup_usesRespectivePlaceholderExpander() {
		$this->entityFactory->expects( $this->once() )
			->method( 'newEmpty' )
			->willReturn( new Item( $this->itemId ) );
		$this->userLanguageLookup->expects( $this->once() )
			->method( 'getUserSpecifiedLanguages' )
			->will( $this->returnValue( [] ) );
		$this->isExternallyRendered = true;

		$this->outputPageEntityIdReader = $this->getOutputPageEntityIdReaderReturningEntity( $this->itemId );

		$expectedHtml = '<div>termbox</div>';
		$placeholder = '$1';

		$out = $this->newOutputPage();
		$out->setProperty( TermboxView::TERMBOX_MARKUP, $expectedHtml );
		$out->setProperty( 'wikibase-view-chunks', [ $placeholder => [ TermboxView::TERMBOX_PLACEHOLDER ] ] );
		$out->setArticleFlag( true );

		$html = $placeholder;
		$this->getHookHandler()->doOutputPageBeforeHTML( $out, $html );

		$this->assertSame( $expectedHtml, $html );
	}

	private function newUserLanguageLookup() {
		$userLanguageLookup = $this->getMock( UserLanguageLookup::class );
		$userLanguageLookup->expects( $this->any() )
			->method( 'getUserSpecifiedLanguages' )
			->will( $this->returnValue( [] ) );
		$userLanguageLookup->expects( $this->any() )
			->method( 'getAllUserLanguages' )
			->will( $this->returnValue( [] ) );
		return $userLanguageLookup;
	}

	/**
	 * @param $itemId
	 * @return \PHPUnit\Framework\MockObject\MockObject
	 */
	private function getOutputPageEntityIdReaderReturningEntity( $itemId ) {
		$outputPageEntityIdReader = $this->createMock( OutputPageEntityIdReader::class );
		$outputPageEntityIdReader->expects( $this->once() )
			->method( 'getEntityIdFromOutputPage' )
			->willReturn( $itemId );

		return $outputPageEntityIdReader;
	}

	/**
	 * @param $itemId
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	private function getEntityRevisionLookupReturningEntity( $itemId ): EntityRevisionLookup {
		$entityRevisionLookup = $this->getMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->will( $this->returnValue( new EntityRevision( new Item( $itemId ) ) ) );
		return $entityRevisionLookup;
	}

	public function testGivenPageIsEditable_keepsEditButtonsAndRemovesSpecialMarkup() {
		$contentBetweenEditLinks = 'hello';
		$editLink1 = 'edit link 1';
		$editLink2 = 'edit link 2';
		$html = "<wb:sectionedit>$editLink1</wb:sectionedit> $contentBetweenEditLinks <wb:sectionedit>$editLink2</wb:sectionedit>";
		$out = $this->newOutputPage();
		$this->userLanguageLookup = $this->newUserLanguageLookup();

		$this->getHookHandler()->doOutputPageBeforeHTML( $out, $html );

		$this->assertEquals( "$editLink1 $contentBetweenEditLinks $editLink2", $html );
	}

	public function testGivenPageIsNotEditable_removesEditButtonsAndSpecialMarkup() {
		$contentBetweenEditLinks = 'hello';
		$html = "<wb:sectionedit>edit link 1</wb:sectionedit>$contentBetweenEditLinks<wb:sectionedit>edit link 2</wb:sectionedit>";
		$out = $this->newOutputPage();
		$this->userLanguageLookup = $this->newUserLanguageLookup();
		$this->editability = $this->mockEditabilityDismissive();

		$this->getHookHandler()->doOutputPageBeforeHTML( $out, $html );

		$this->assertSame( $contentBetweenEditLinks, $html );
	}

	private function mockEditability( $permissive = true ) {
		$editability = $this->getMock( OutputPageEditability::class );
		$editability->method( 'validate' )->willReturn( $permissive );
		return $editability;
	}

	private function mockEditabilityDismissive() {
		return $this->mockEditability( false );
	}

	private function getUserLanguageLookupReturnsSpecifiedLangs() : UserLanguageLookup {
		$userLanguageLookup = $this->createMock( UserLanguageLookup::class );
		$userLanguageLookup->expects( $this->once() )
			->method( 'getUserSpecifiedLanguages' )
			->will( $this->returnValue( [ 'de', 'es', 'ru' ] ) );
		return $userLanguageLookup;
	}

}
