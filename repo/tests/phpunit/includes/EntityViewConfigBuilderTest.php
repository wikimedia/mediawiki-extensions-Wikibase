<?php

namespace Wikibase\Test;

use Language;
use Title;
use User;
use ValueParsers\ParserOptions;
use Wikibase\CopyrightMessageBuilder;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityRevision;
use Wikibase\EntityViewConfigBuilder;
use Wikibase\Item;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\NamespaceUtils;

/**
 * @covers Wikibase\EntityViewConfigBuilder
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
class EntityViewConfigBuilderTest extends \MediaWikiTestCase {

	public function testGetJsConfigVars() {
		$entityViewJsConfig = new EntityViewConfigBuilder(
			new LanguageFallbackChain( array() ),
			new MockRepository(),
			new BasicEntityIdParser(),
			$this->getEntityTitleLookupMock(),
			'en'
		);

		$configVars = $entityViewJsConfig->buildJsConfigVars(
			$this->getEntity(),
			$this->getCopyrightMessage(),
			true
		);

		// @todo dataProvider
		$this->assertInternalType( 'array', $configVars );

		$this->assertEquals( 'item', $configVars['wbEntityType'], 'item type' );
		$this->assertEquals( 'English', $configVars['wbDataLangName'], 'data lang name is English' );
		$this->assertEquals( 'Q5881', $configVars['wbEntityId'], 'wbEntityId' );
		$this->assertInternalType( 'array', $configVars['wbCopyright'], 'copyright is array' ); //@fixme
		$this->assertEquals( true, $configVars['wbExperimentalFeatures'], 'wbExperimentalFeatures is true' );
		$this->assertEquals( array(), json_decode( $configVars['wbUsedEntities'], true ), 'wbUsedEntities' );
		$this->assertInternalType( 'array', json_decode( $configVars['wbEntity'], true ), 'wbEntity' ); // @fixme, more detailed test
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

	/**
	 * @return Message
	 */
	private function getCopyrightMessage() {
		$copyrightMessageBuilder = new CopyrightMessageBuilder();
		$copyrightMessage = $copyrightMessageBuilder->build(
			'https://creativecommons.org',
			'cc-0',
			Language::factory( 'en' )
		);

		return $copyrightMessage;
	}

}
