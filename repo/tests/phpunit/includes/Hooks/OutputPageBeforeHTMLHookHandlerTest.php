<?php

namespace Wikibase\Repo\Tests\Hooks;

use DerivativeContext;
use OutputPage;
use PHPUnit_Framework_TestCase;
use RequestContext;
use Title;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Hooks\OutputPageBeforeHTMLHookHandler;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers Wikibase\Repo\Hooks\OutputPageBeforeHTMLHookHandler
 *
 * @since 0.5
 *
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class OutputPageBeforeHTMLHookHandlerTest extends PHPUnit_Framework_TestCase {

	/**
	 * @return OutputPageBeforeHTMLHookHandler
	 */
	private function getHookHandler() {
		$userLanguageLookup = $this->getMock( 'Wikibase\Lib\UserLanguageLookup' );
		$userLanguageLookup->expects( $this->once() )
			->method( 'getUserSpecifiedLanguages' )
			->will( $this->returnValue( array( 'de', 'es', 'ru' ) ) );

		$contentLanguages = $this->getMock( 'Wikibase\Lib\ContentLanguages' );
		$contentLanguages->expects( $this->once() )
			->method( 'getLanguages' )
			->will( $this->returnValue( array( 'en', 'es', 'ru' ) ) );

		$outputPageBeforeHTMLHookHandler = new OutputPageBeforeHTMLHookHandler(
			TemplateFactory::getDefaultInstance(),
			$userLanguageLookup,
			$contentLanguages,
			new BasicEntityIdParser(),
			$this->getMock( 'Wikibase\Lib\Store\EntityRevisionLookup' ),
			new LanguageNameLookup(),
			new EntityContentFactory( array() )
		);

		return $outputPageBeforeHTMLHookHandler;
	}

	/**
	 * Integration test mostly testing that things don't fatal/ throw.
	 */
	public function testOutputPageBeforeHTMLHookHandler() {
		$outputPageBeforeHTMLHookHandler = $this->getHookHandler();

		$html = '';
		$context = new DerivativeContext( RequestContext::getMain() );
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

		$this->assertSame( array( 'es', 'ru' ), $wbUserSpecifiedLanguages );
	}
}
