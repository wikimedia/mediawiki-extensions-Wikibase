<?php

namespace Wikibase\Test;

use Title;
use User;
use ValueParsers\ParserOptions;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityRevision;
use Wikibase\EntityViewConfigRegistry;
use Wikibase\Item;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\NamespaceUtils;

/**
 * @covers Wikibase\EntityViewConfigRegistry
 *
 * @since 0.5
 *
 * @group WikibaseRepo
 * @group EntityView
 * @group Wikibase
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityViewConfigRegistryTest extends \MediaWikiTestCase {

	public function testGetJsConfigVars() {
		$entityViewJsConfig = new EntityViewConfigRegistry(
			new LanguageFallbackChain( array() ),
			new MockRepository(),
			new BasicEntityIdParser(),
			$this->getEntityTitleLookupMock(),
			'en'
		);

		$configVars = $entityViewJsConfig->getJsConfigVars(
			new EntityRevision( $this->getEntity(), 10, wfTimestamp( TS_MW ) ),
			true
		);

		// @todo dataProvider
		$this->assertInternalType( 'array', $configVars );

		$this->assertEquals( true, $configVars['wbIsEditView'], 'is edit view' );
		$this->assertEquals( 'item', $configVars['wbEntityType'], 'item type' );
		$this->assertEquals( 'English', $configVars['wbDataLangName'], 'data lang name is English' );
		$this->assertEquals( 'Q5881', $configVars['wbEntityId'], 'wbEntityId' );
		$this->assertInternalType( 'array', $configVars['wbCopyright'], 'copyright is array' ); //@fixme
		$this->assertEquals( true, $configVars['wbExperimentalFeatures'], 'wbExperimentalFeatures is true' );
		$this->assertEquals( array(), $configVars['wbUsedEntities'], 'wbUsedEntities' );
		$this->assertInternalType( 'array', $configVars['wbEntity'], 'wbEntity' ); // @fixme
	}

	private function getEntity() {
		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q5881' ) );
		$item->setLabel( 'en', 'Cake' );

		return $item;
	}

	public function getSerializedEntity() {
		$entity = $this->getEntity();
		$entityType = $entity->getType();

		$serializerFactory = new SerializerFactory();
		$options = new SerializationOptions();
		$serializer = $serializerFactory->newSerializerForEntity( $entityType, $options );

		return $serializer->getSerialized( $entity );
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
