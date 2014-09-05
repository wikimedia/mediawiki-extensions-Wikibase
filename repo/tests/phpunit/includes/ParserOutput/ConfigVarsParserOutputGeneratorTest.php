<?php

namespace Wikibase\Test;

use ParserOutput;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\InMemoryDataTypeLookup;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\ParserOutput\ConfigVarsParserOutputGenerator;

/**
 * @covers Wikibase\Repo\ParserOutput\ConfigVarsParserOutputGenerator
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ConfigVarsParserOutputGeneratorTest extends \PHPUnit_Framework_TestCase {

	public function testAssignToParserOutput() {
		$configVarsParserOutputGenerator = $this->getConfigVarsParserOutputGenerator();
		$languageFallbackChain = $this->getLanguageFallbackChainMock();
		$pout = new ParserOutput();
		$item = Item::newEmpty();
		$item->setId( 42 );

		$configVarsParserOutputGenerator->assignToParserOutput( $pout, $item, $languageFallbackChain, 'en' );

		$configVars = $pout->getJsConfigVars();
		$this->assertEquals( 'Q42', $configVars['wbEntityId'] );
		$this->assertEquals( '{"id":"Q42","type":"item"}', $configVars['wbEntity'] );
	}

	private function getLanguageFallbackChainMock() {
		return $this->getMockBuilder( 'Wikibase\LanguageFallbackChain' )
			->disableOriginalConstructor()
			->getMock();
	}

	private function getConfigVarsParserOutputGenerator() {
		$repository = new MockRepository();
		$propertyDataTypeLookup = new InMemoryDataTypeLookup();

		return new ConfigVarsParserOutputGenerator(
			$repository,
			$this->getEntityTitleLookup(),
			$propertyDataTypeLookup
		);
	}

	private function getEntityTitleLookup() {
		$entityTitleLookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );

		$entityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $entityId ) {
				$name = $entityId->getEntityType() . ':' . $entityId->getPrefixedId();
				return Title::makeTitle( NS_MAIN, $name );
			} ) );

		return $entityTitleLookup;
	}

}
