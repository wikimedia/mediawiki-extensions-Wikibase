<?php

namespace Wikibase\Test;

use Title;
use User;
use ValueParsers\ParserOptions;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityRevision;
use Wikibase\EntityViewJsConfig;
use Wikibase\Item;
use Wikibase\LanguageFallbackChain;
use Wikibase\NamespaceUtils;

/**
 * @covers Wikibase\EntityViewJsConfig
 *
 * @since 0.5
 *
 * @group WikibaseRepoTest
 * @group EntityView
 * @group Wikibase
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityViewJsConfigTest extends \MediaWikiTestCase {

	public function testGetJsConfigVars() {
		// fixme
		$itemNs = NamespaceUtils::getEntityNamespace( CONTENT_MODEL_WIKIBASE_ITEM );

		$entityViewJsConfig = new EntityViewJsConfig(
			new LanguageFallbackChain( array() ),
			new MockRepository(), // EntityInfoBuilder
			new BasicEntityIdParser(),
			$this->getEntityTitleLookupMock()
		);

		$configVars = $entityViewJsConfig->getJsConfigVars(
			$this->getEntityRevision(),
			$this->getEntity(),
			Title::makeTitle( $itemNs, 'Q5881' ),
			User::newFromId( 0 ),
			'en',
			true
		);

		$this->assertInternalType( 'array', $configVars );
	}

	private function getEntityRevision() {
		$timestamp = wfTimestamp( TS_MW );
		$item = $this->getEntity();

		return new EntityRevision( $item, 1, $timestamp );
	}

	private function getEntity() {
		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q5881' ) );
		$item->setLabel( 'en', 'Cake' );

		return $item;
	}

	public function getTitleForId( EntityId $id ) {
		$name = $id->getEntityType() . ':' . $id->getPrefixedId();
		return Title::makeTitle( NS_MAIN, $name );
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getEntityTitleLookupMock() {
		$lookup = $this->getMock( 'Wikibase\EntityTitleLookup' );
		$lookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( array( $this, 'getTitleForId' ) ) );

		return $lookup;
	}

}
