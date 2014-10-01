<?php

namespace Wikibase\Test;

use ContentHandler;
use DataValues\StringValue;
use RequestContext;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\EntityContent;
use Wikibase\Hook\MakeGlobalVariablesScriptHandler;
use Wikibase\ItemContent;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\ParserOutputJsConfigBuilder;
use Wikibase\Repo\Content\EntityContentFactory;

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
	public function testHandle( array $expected, $revisionId, array $parserConfigVars ) {
		$hookHandler = new MakeGlobalVariablesScriptHandler(
			$this->getEntityContentFactory(),
			$this->getParserOutputJsConfigBuilder( $parserConfigVars ),
			array( 'de', 'en', 'es', 'fr' )
		);

		$entityId = new ItemId( 'Q' . $revisionId );
		$title = $this->getTitleForId( $entityId );

		$context = new RequestContext();
		$context->setTitle( $title );

		$output = $context->getOutput();
		$output->setRevisionId( $revisionId );

		$hookHandler->handle( $output );

		$configVars = $output->getJsConfigVars();

		$this->assertEquals( $expected, array_keys( $configVars ) );
	}

	private function itemSupportsRedirects() {
		$handler = ContentHandler::getForModelID( CONTENT_MODEL_WIKIBASE_ITEM );
		return $handler->supportsRedirects();
	}

	public function handleProvider() {
		$cases = array();

		// NOTE: For the sake of this test, entity IDs are based directly on revision Ids,
		// so revision 4 refers to item Q4.
		$cases['config vars without parser cache'] = array(
			'$expected' => array( 'wbEntityId' ),
			'$revisionId' => 4,
			'$parserConfigVars' => array( 'wbEntityId' => 'Q4' ),
		);

		if ( $this->itemSupportsRedirects() ) {
			// NOTE: As per getEntityContentForRevision, odd revision IDs refer to redirects,
			// so revision 5 would refer to a redirect from Q5 to Q10.
			$cases['config vars for redirect'] = array(
				'$expected' => array(),
				'$revisionId' => 5,
				'$parserConfigVars' => array( 'wbEntityId' => 'Q5' ), // redirects currently have no config vars
			);
		}

		return $cases;
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
		$entityContentFactory = $this->getMockBuilder( 'Wikibase\Repo\Content\EntityContentFactory' )
			->disableOriginalConstructor()
			->getMock();

		$entityContentFactory->expects( $this->any() )
			->method( 'getFromRevision' )
			->will( $this->returnCallback( array( $this, 'getEntityContentForRevision' ) ) );

		$entityContentFactory->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( array( $this, 'getTitleForId' ) ) );

		return $entityContentFactory;
	}

	/**
	 * Returns an EntityContent based on the given revision ID.
	 * For odd revision IDs, the resulting EntityContent will represent
	 * a redirect.
	 *
	 * @param int $revisionId
	 *
	 * @return EntityContent
	 */
	public function getEntityContentForRevision( $revisionId ) {
		$itemId = new ItemId( 'Q' . $revisionId );

		// Odd revision IDs refer to redirects!
		if ( $revisionId % 2 ) {
			$targetId = new ItemId( 'Q' . ( $revisionId * 2 ) );
			return $this->newItemRedirectContent( $itemId, $targetId );
		} else {
			return $this->newItemContent( $itemId );
		}
	}

	/**
	 * @param ItemId $itemId
	 *
	 * @return ItemContent
	 */
	private function newItemContent( ItemId $itemId ) {
		$item = Item::newEmpty();

		$item->setId( $itemId );
		$item->setLabel( 'en', 'Cake' );

		$snak = new PropertyValueSnak( new PropertyId( 'P794' ), new StringValue( 'a' ) );

		$statement = new Statement( $snak );
		$statement->setGuid( 'P794$muahahaha' );

		$item->addClaim( $statement );

		$entityContent = ItemContent::newFromItem( $item );
		return $entityContent;
	}

	/**
	 * @param ItemId $itemId
	 * @param ItemId $targetId
	 *
	 * @return ItemContent
	 */
	private function newItemRedirectContent( ItemId $itemId, ItemId $targetId ) {
		$redirect = new EntityRedirect( $itemId, $targetId );

		$entityContent = ItemContent::newFromRedirect( $redirect, $this->getTitleForId( $targetId) );
		return $entityContent;
	}

}
