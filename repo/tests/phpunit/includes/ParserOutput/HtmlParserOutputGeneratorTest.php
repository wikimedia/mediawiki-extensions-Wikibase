<?php

namespace Wikibase\Test;

use ParserOutput;
use Wikibase\DataModel\Entity\Item;
use Wikibase\EntityRevision;
use Wikibase\Repo\ParserOutput\HtmlParserOutputGenerator;

/**
 * @covers Wikibase\Repo\ParserOutput\HtmlParserOutputGenerator
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class HtmlParserOutputGeneratorTest extends \PHPUnit_Framework_TestCase {

	public function testAssignToParserOutput() {
		$htmlParserOutputGenerator = $this->getHtmlParserOutputGenerator();
		$pout = new ParserOutput();
		$entityRevision = new EntityRevision( Item::newEmpty() );
		$htmlParserOutputGenerator->assignToParserOutput( $pout, $entityRevision, true );
		$moduleStyles = array( 'wikibase.common', 'wikibase.toc', 'jquery.ui.core',
			'jquery.wikibase.statementview', 'jquery.wikibase.toolbar' );

		$this->assertEquals( '<div>HTML</div>', $pout->getText() );
		$this->assertEquals( array( 'place' => 'holder' ), $pout->getExtensionData( 'wikibase-view-chunks' ) );
		$this->assertEquals( $moduleStyles, $pout->getModuleStyles() );
		$this->assertEquals( array( 'wikibase.ui.entityViewInit' ), $pout->getModules() );
	}

	private function getHtmlParserOutputGenerator() {
		return new HtmlParserOutputGenerator(
			$this->getEntityViewMock()
		);
	}

	private function getEntityViewMock() {
		$entityView = $this->getMockBuilder( 'Wikibase\Repo\View\ItemView' )
			->disableOriginalConstructor()
			->getMock();

		$entityView->expects( $this->any() )
			->method( 'getHtml' )
			->will( $this->returnValue( '<div>HTML</div>' ) );

		$entityView->expects( $this->any() )
			->method( 'getPlaceholders' )
			->will( $this->returnValue( array( 'place' => 'holder' ) ) );

		return $entityView;
	}

}
