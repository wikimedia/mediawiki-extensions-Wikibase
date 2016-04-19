<?php

namespace Wikibase\Repo\Tests\Hooks;

use DerivativeContext;
use OutputPage;
use PHPUnit_Framework_TestCase;
use RequestContext;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\UserLanguageLookup;
use Wikibase\Repo\Hooks\OutputPageBeforeHTMLHookHandler;
use Wikibase\Repo\Hooks\OutputPageEntityIdReader;
use Wikibase\Repo\WikibaseRepo;
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

		$outputPageBeforeHTMLHookHandler = new OutputPageBeforeHTMLHookHandler(
			TemplateFactory::getDefaultInstance(),
			$userLanguageLookup,
			new StaticContentLanguages( [ 'en', 'es', 'ru' ] ),
			$languageNameLookup,
			$outputPageEntityIdReader,
			WikibaseRepo::getDefaultInstance()->getEntityContentFactory()
		);

		return $outputPageBeforeHTMLHookHandler;
	}

	/**
	 * Integration test mostly testing that things don't fatal/ throw.
	 */
	public function testOutputPageBeforeHTMLHookHandler() {
		$context = new DerivativeContext( RequestContext::getMain() );
		$outputPageBeforeHTMLHookHandler = $this->getHookHandler( $context->getLanguage()->getCode() );

		$html = '';
		$out = new OutputPage( $context );
		$out->setTitle( Title::makeTitle( 0, 'OutputPageBeforeHTMLHookHandlerTest' ) );
		$out->setProperty(
			'wikibase-view-chunks',
			array( array( 'entityViewPlaceholder-entitytermsview-entitytermsforlanguagelistview-class' ) )
		);

		$outputPageBeforeHTMLHookHandler->doOutputPageBeforeHTML( $out, $html );

		// Verify the wbUserSpecifiedLanguages JS variable
		$jsConfigVars = $out->getJsConfigVars();
		$wbUserSpecifiedLanguages = $jsConfigVars['wbUserSpecifiedLanguages'];

		$this->assertSame( [ 'es', 'ru' ], $wbUserSpecifiedLanguages );
	}

}
