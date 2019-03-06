<?php

namespace Wikibase\Repo\Tests\Hooks;

use DerivativeContext;
use OutputPage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit4And6Compat;
use RequestContext;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\EntityFactory;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\UserLanguageLookup;
use Wikibase\Repo\Hooks\Helpers\OutputPageEditability;
use Wikibase\Repo\Hooks\OutputPageBeforeHTMLHookHandler;
use Wikibase\Repo\Hooks\OutputPageEntityIdReader;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers \Wikibase\Repo\Hooks\OutputPageBeforeHTMLHookHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class OutputPageBeforeHTMLHookHandlerTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @var OutputPageEditability|MockObject
	 */
	private $editablity;

	public function setUp() {
		parent::setUp();

		$this->editablity = $this->mockEditability();
	}

	/**
	 * @return OutputPage
	 */
	private function newOutputPage() {
		$outputPage = new OutputPage( new DerivativeContext( RequestContext::getMain() ) );
		$outputPage->setTitle( new Title() );

		return $outputPage;
	}

	/**
	 * @param string $uiLanguageCode
	 *
	 * @return OutputPageBeforeHTMLHookHandler
	 */
	private function getHookHandler( $uiLanguageCode ) {
		$userLanguageLookup = $this->getMock( UserLanguageLookup::class );
		$userLanguageLookup->expects( $this->once() )
			->method( 'getUserSpecifiedLanguages' )
			->will( $this->returnValue( [ 'de', 'es', 'ru' ] ) );
		$userLanguageLookup->expects( $this->once() )
			->method( 'getAllUserLanguages' )
			->will( $this->returnValue( array_unique( [ $uiLanguageCode, 'de', 'es', 'ru' ] ) ) );

		$languageNameLookup = $this->getMock( LanguageNameLookup::class );
		$languageNameLookup->expects( $this->never() )
			->method( 'getName' );

		$itemId = new ItemId( 'Q1' );

		$outputPageEntityIdReader = $this->getMockBuilder( OutputPageEntityIdReader::class )
			->disableOriginalConstructor()
			->getMock();
		$outputPageEntityIdReader->expects( $this->once() )
			->method( 'getEntityIdFromOutputPage' )
			->will( $this->returnValue( $itemId ) );

		$entityRevisionLookup = $this->getMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->will( $this->returnValue( new EntityRevision( new Item( $itemId ) ) ) );

		$outputPageBeforeHTMLHookHandler = new OutputPageBeforeHTMLHookHandler(
			TemplateFactory::getDefaultInstance(),
			$userLanguageLookup,
			new StaticContentLanguages( [ 'en', 'es', 'ru' ] ),
			$entityRevisionLookup,
			$languageNameLookup,
			$outputPageEntityIdReader,
			new EntityFactory( [] ),
			'',
			$this->editablity
		);

		return $outputPageBeforeHTMLHookHandler;
	}

	/**
	 * Integration test mostly testing that things don't fatal/ throw.
	 */
	public function testOutputPageBeforeHTMLHookHandler() {
		$out = $this->newOutputPage();
		$outputPageBeforeHTMLHookHandler = $this->getHookHandler( $out->getLanguage()->getCode() );

		$html = '';
		$out->setTitle( Title::makeTitle( 0, 'OutputPageBeforeHTMLHookHandlerTest' ) );
		$out->setProperty(
			'wikibase-view-chunks',
			[ '$1' => [ 'entityViewPlaceholder-entitytermsview-entitytermsforlanguagelistview-class' ] ]
		);

		$outputPageBeforeHTMLHookHandler->doOutputPageBeforeHTML( $out, $html );

		// Verify the wbUserSpecifiedLanguages JS variable
		$jsConfigVars = $out->getJsConfigVars();
		$wbUserSpecifiedLanguages = $jsConfigVars['wbUserSpecifiedLanguages'];

		$this->assertSame( [ 'es', 'ru' ], $wbUserSpecifiedLanguages );
	}

	public function testGivenDeletedRevision_hookHandlerDoesNotFail() {
		$userLanguageLookup = $this->getMock( UserLanguageLookup::class );
		$userLanguageLookup->expects( $this->any() )
			->method( 'getUserSpecifiedLanguages' )
			->will( $this->returnValue( [] ) );
		$userLanguageLookup->expects( $this->any() )
			->method( 'getAllUserLanguages' )
			->will( $this->returnValue( [] ) );

		$outputPageEntityIdReader = $this->getMockBuilder( OutputPageEntityIdReader::class )
			->disableOriginalConstructor()
			->getMock();
		$outputPageEntityIdReader->expects( $this->once() )
			->method( 'getEntityIdFromOutputPage' )
			->will( $this->returnValue( null ) );

		$handler = new OutputPageBeforeHTMLHookHandler(
			TemplateFactory::getDefaultInstance(),
			$userLanguageLookup,
			new StaticContentLanguages( [] ),
			$this->getMock( EntityRevisionLookup::class ),
			$this->getMock( LanguageNameLookup::class ),
			$outputPageEntityIdReader,
			new EntityFactory( [] ),
			'',
			$this->editablity
		);

		$out = $this->newOutputPage();
		$out->setProperty( 'wikibase-view-chunks', [ '$1' => [ 'termbox' ] ] );

		$html = '$1';
		$handler->doOutputPageBeforeHTML( $out, $html );
		$this->assertSame( '', $html );
	}

	public function testGivenPageIsEditable_keepsEditButtonsAndRemovesSpecialMarkup() {
		$contentBetweenEditLinks = 'hello';
		$editLink1 = 'edit link 1';
		$editLink2 = 'edit link 2';
		$html = "<wb:sectionedit>$editLink1</wb:sectionedit> $contentBetweenEditLinks <wb:sectionedit>$editLink2</wb:sectionedit>";
		$out = $this->newOutputPage();

		$hookHandler = new OutputPageBeforeHTMLHookHandler(
			TemplateFactory::getDefaultInstance(),
			$this->newUserLanguageLookup(),
			new StaticContentLanguages( [] ),
			$this->createMock( EntityRevisionLookup::class ),
			$this->createMock( LanguageNameLookup::class ),
			$this->createMock( OutputPageEntityIdReader::class ),
			$this->createMock( EntityFactory::class ),
			'',
			$this->editablity
		);

		$hookHandler->doOutputPageBeforeHTML( $out, $html );

		$this->assertEquals( "$editLink1 $contentBetweenEditLinks $editLink2", $html );
	}

	public function testGivenPageIsNotEditable_removesEditButtonsAndSpecialMarkup() {
		$contentBetweenEditLinks = 'hello';
		$html = "<wb:sectionedit>edit link 1</wb:sectionedit>$contentBetweenEditLinks<wb:sectionedit>edit link 2</wb:sectionedit>";
		$out = $this->newOutputPage();

		$hookHandler = new OutputPageBeforeHTMLHookHandler(
			TemplateFactory::getDefaultInstance(),
			$this->newUserLanguageLookup(),
			new StaticContentLanguages( [] ),
			$this->createMock( EntityRevisionLookup::class ),
			$this->createMock( LanguageNameLookup::class ),
			$this->createMock( OutputPageEntityIdReader::class ),
			$this->createMock( EntityFactory::class ),
			'',
			$this->mockEditabilityDismissive()
		);

		$hookHandler->doOutputPageBeforeHTML( $out, $html );

		$this->assertSame( $contentBetweenEditLinks, $html );
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

	private function mockEditability( $permissive = true ) {
		$editability = $this->getMock( OutputPageEditability::class );
		$editability->method( 'validate' )->willReturn( $permissive );
		return $editability;
	}

	private function mockEditabilityDismissive() {
		return $this->mockEditability( false );
	}

}
