<?php

namespace Wikibase\Repo\Tests\Hooks;

use DerivativeContext;
use OutputPage;
use PHPUnit_Framework_TestCase;
use RequestContext;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Item;
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
	 * @return OutputPageBeforeHTMLHookHandler
	 */
	private function getHookHandler( $languageNameLookup, $uiLanguageCode ) {
		$userLanguageLookup = $this->getMock( UserLanguageLookup::class );
		$userLanguageLookup->expects( $this->once() )
			->method( 'getAllUserLanguages' )
			->will( $this->returnValue( array_unique( [ $uiLanguageCode, 'de', 'es', 'ru' ] ) ) );

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
			$outputPageEntityIdReader
		);

		return $outputPageBeforeHTMLHookHandler;
	}

	public function testOutputPageBeforeHTMLHookHandler_contentOnlyUiLanguage() {
		$languageCode = 'termonly';

		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setLanguage( $languageCode );
		$languageNameLookup = $this->getMock( LanguageNameLookup::class );
		$languageNameLookup->expects( $this->exactly( 3 ) )
			->method( 'getName' );
		$outputPageBeforeHTMLHookHandler = $this->getHookHandler( $languageNameLookup, $languageCode );

		$html = 'termbox';
		$out = new OutputPage( $context );
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

		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setLanguage( $languageCode );
		$languageNameLookup = $this->getMock( LanguageNameLookup::class );
		$languageNameLookup->expects( $this->exactly( 2 ) )
			->method( 'getName' );
		$outputPageBeforeHTMLHookHandler = $this->getHookHandler( $languageNameLookup, $languageCode );

		$html = 'termbox';
		$out = new OutputPage( $context );
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

		$this->assertNotContains( 'wikibase-entitytermsforlanguageview-invalid', $html );
	}

}
