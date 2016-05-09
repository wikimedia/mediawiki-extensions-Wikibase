<?php

namespace Wikibase\Repo\Tests\Hooks;

use DerivativeContext;
use OutputPage;
use PHPUnit_Framework_TestCase;
use RequestContext;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\EntityFactory;
use Wikibase\EntityRevision;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\UserLanguageLookup;
use Wikibase\Repo\Hooks\OutputPageBeforeHTMLHookHandler;
use Wikibase\Repo\Hooks\OutputPageEntityIdReader;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers Wikibase\Repo\Hooks\OutputPageBeforeHTMLHookHandler
 *
 * @since 0.5
 *
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Marius Hoch < hoo@online.de >
 */
class OutputPageBeforeHTMLHookHandlerTest extends PHPUnit_Framework_TestCase {

	/**
	 * @return OutputPage
	 */
	private function newOutputPage( $languageCode = 'lkt' ) {
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setLanguage( $languageCode );
		return new OutputPage( $context );
	}

	/**
	 * @param string $uiLanguageCode
	 *
	 * @return OutputPageBeforeHTMLHookHandler
	 */
	private function getHookHandler( $uiLanguageCode ) {
		$userLanguageLookup = $this->getMock( UserLanguageLookup::class );
		$userLanguageLookup->expects( $this->once() )
			->method( 'getAllUserLanguages' )
			->will( $this->returnValue( array_unique( [ $uiLanguageCode, 'de', 'es', 'ru' ] ) ) );

		$languageNameLookup = $this->getMock( LanguageNameLookup::class );
		$languageNameLookup->expects( $this->any() )
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
			new StaticContentLanguages( [ 'termonly', 'en', 'es', 'ru' ] ),
			$entityRevisionLookup,
			$languageNameLookup,
			$outputPageEntityIdReader,
			new EntityFactory( [] )
		);

		return $outputPageBeforeHTMLHookHandler;
	}

	/**
	 * Integration test mostly testing that things don't fatal/ throw.
	 */
	public function testOutputPageBeforeHTMLHookHandler() {
		$languageCode = 'es';
		$out = $this->newOutputPage( $languageCode );
		$outputPageBeforeHTMLHookHandler = $this->getHookHandler( $languageCode );

		$html = 'termbox';
		$out->setTitle( Title::makeTitle( 0, 'OutputPageBeforeHTMLHookHandlerTest' ) );
		$out->setProperty(
			'wikibase-view-chunks',
			[ 'termbox' => [ 'termbox' ] ]
		);

		$outputPageBeforeHTMLHookHandler->doOutputPageBeforeHTML( $out, $html );

		// Verify the wbUserTermsLanguages JS variable
		$jsConfigVars = $out->getJsConfigVars();
		$wbUserTermsLanguages = $jsConfigVars['wbUserTermsLanguages'];

		$this->assertSame( [ 'es', 'ru' ], $wbUserTermsLanguages );

		$this->assertContains( 'wikibase-entitytermsforlanguageview-' . $languageCode, $html );
	}

	public function testOutputPageBeforeHTMLHookHandler_contentOnlyUiLanguage() {
		$languageCode = 'termonly';

		$out = $this->newOutputPage( $languageCode );
		$outputPageBeforeHTMLHookHandler = $this->getHookHandler( $languageCode );

		$html = 'termbox';
		$out->setTitle( Title::makeTitle( 0, 'OutputPageBeforeHTMLHookHandlerTest' ) );
		$out->setProperty(
			'wikibase-view-chunks',
			[
				'termbox' => [ 'termbox' ]
			]
		);

		$outputPageBeforeHTMLHookHandler->doOutputPageBeforeHTML( $out, $html );

		// Verify the wbUserTermsLanguages JS variable
		$jsConfigVars = $out->getJsConfigVars();
		$wbUserTermsLanguages = $jsConfigVars['wbUserTermsLanguages'];

		$this->assertSame( [ $languageCode, 'es', 'ru' ], $wbUserTermsLanguages );

		$this->assertContains( 'wikibase-entitytermsforlanguageview-termonly', $html );
	}

	public function testOutputPageBeforeHTMLHookHandler_invalidUiLanguage() {
		$languageCode = 'invalid';

		$out = $this->newOutputPage( $languageCode );
		$outputPageBeforeHTMLHookHandler = $this->getHookHandler( $languageCode );

		$html = 'termbox';
		$out->setTitle( Title::makeTitle( 0, 'OutputPageBeforeHTMLHookHandlerTest' ) );
		$out->setProperty(
			'wikibase-view-chunks',
			[
				'termbox' => [ 'termbox' ]
			]
		);

		$outputPageBeforeHTMLHookHandler->doOutputPageBeforeHTML( $out, $html );

		// Verify the wbUserTermsLanguages JS variable
		$jsConfigVars = $out->getJsConfigVars();
		$wbUserTermsLanguages = $jsConfigVars['wbUserTermsLanguages'];

		$this->assertSame( [ 'es', 'ru' ], $wbUserTermsLanguages );

		// FIXME
		// $this->assertNotContains( 'wikibase-entitytermsforlanguageview-invalid', $html );
	}

	public function testGivenDeletedRevision_hookHandlerDoesNotFail() {
		$userLanguageLookup = $this->getMock( UserLanguageLookup::class );
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
			new EntityFactory( [] )
		);

		$out = $this->newOutputPage();
		$out->setProperty( 'wikibase-view-chunks', [ '$1' => [ 'termbox' ] ] );

		$html = '$1';
		$handler->doOutputPageBeforeHTML( $out, $html );
		$this->assertSame( '', $html );
	}

}
