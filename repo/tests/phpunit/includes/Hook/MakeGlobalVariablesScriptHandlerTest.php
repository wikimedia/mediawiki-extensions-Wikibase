<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use RequestContext;
use Title;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\EntityContent;
use Wikibase\EntityContentFactory;
use Wikibase\Hook\MakeGlobalVariablesScriptHandler;
use Wikibase\ItemContent;
use Wikibase\ParserOutputJsConfigBuilder;

/**
 * @covers Wikibase\Hook\MakeGlobalVariablesScriptHandler
 *
 * @since 0.5
 *
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class MakeGlobalVariablesScriptHandlerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider handleProvider
	 */
	public function testHandle( array $expected, Title $title, array $parserConfigVars, $message ) {
		$hookHandler = new MakeGlobalVariablesScriptHandler(
			$this->getEntityContentFactory(),
			$this->getParserOutputJsConfigBuilder( $parserConfigVars ),
			array( 'de', 'en', 'es', 'fr' )
		);

		$context = new RequestContext();
		$context->setTitle( $title );

		$output = $context->getOutput();
		$output->setRevisionId( 9320 );

		$hookHandler->handle( $output );

		$configVars = $output->getJsConfigVars();

		$this->assertEquals( $expected, array_keys( $configVars ), $message );
	}

	public function handleProvider() {
		$entityId = new ItemId( 'Q4' );
		$title = $this->getTitleForId( $entityId );

		$expected = array(
			'wbEntityId'
		);

		$parserConfigVars = array(
			'wbEntityId' => 'Q4'
		);

		return array(
			array( $expected, $title, $parserConfigVars, 'config vars without parser cache' )
		);
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return Title
	 */
	public function getTitleForId( EntityId $entityId ) {
		$name = $entityId->getEntityType() . ':' . $entityId->getSerialization();
		return Title::makeTitle( NS_MAIN, $name );
	}

	/**
	 * @param array $configVars
	 *
	 * @return ParserOutputJsConfigBuilder
	 */
	private function getParserOutputJsConfigBuilder( array $configVars ) {
		$configBuilder = $this->getMockBuilder( 'Wikibase\ParserOutputJsConfigBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$configBuilder->expects( $this->any() )
			->method( 'build' )
			->will( $this->returnCallback(
				function() use( $configVars ) {
					return $configVars;
				}
			)
		);

		return $configBuilder;
	}

	/**
	 * @return EntityContentFactory
	 */
	private function getEntityContentFactory() {
		$entityContentFactory = $this->getMockBuilder( 'Wikibase\EntityContentFactory' )
			->disableOriginalConstructor()
			->getMock();

		$entityContentFactory->expects( $this->any() )
			->method( 'getFromRevision' )
			->will( $this->returnCallback( array( $this, 'getEntityContent' ) ) );

		$entityContentFactory->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( array( $this, 'getTitleForId' ) ) );

		return $entityContentFactory;
	}

	/**
	 * @return EntityContent
	 */
	public function getEntityContent() {
		$item = Item::newEmpty();

		$itemId = new ItemId( 'Q5881' );
		$item->setId( $itemId );
		$item->setLabel( 'en', 'Cake' );

		$snak = new PropertyValueSnak( new PropertyId( 'P794' ), new StringValue( 'a' ) );

		$claim = new Claim( $snak );
		$claim->setGuid( 'P794$muahahaha' );

		$item->addClaim( $claim );

		$entityContent = new ItemContent( $item );

		return $entityContent;
	}

}
