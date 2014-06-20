<?php

namespace Wikibase\Test;

use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\ItemContent;
use Wikibase\ItemHandler;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\SettingsArray;

/**
 * @covers Wikibase\ItemHandler
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseItem
 * @group WikibaseEntity
 * @group WikibaseEntityHandler
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemHandlerTest extends EntityHandlerTest {

	/**
	 * @see EntityHandlerTest::getModelId
	 * @return string
	 */
	public function getModelId() {
		return CONTENT_MODEL_WIKIBASE_ITEM;
	}

	/**
	 * @see EntityHandlerTest::getClassName
	 * @return string
	 */
	public function getClassName() {
		return '\Wikibase\ItemHandler';
	}

	/**
	 * @see EntityHandlerTest::contentProvider
	 */
	public function contentProvider() {
		$contents = parent::contentProvider();

		/**
		 * @var ItemContent $content
		 */
		$content = clone $contents[1][0];
		$content->getItem()->addSiteLink( new SimpleSiteLink( 'enwiki', 'Foobar' ) );
		$contents[] = array( $content );

		return $contents;
	}

	public function testGetTitleForId() {
		$handler = $this->getHandler();
		$id = new ItemId( 'Q123' );

		$title = $handler->getTitleForId( $id );
		$this->assertEquals( $id->getSerialization(), $title->getText() );
	}

	public function testGetIdForTitle() {
		$handler = $this->getHandler();
		$title = Title::makeTitle( $handler->getEntityNamespace(), 'Q123' );

		$id = $handler->getIdForTitle( $title );
		$this->assertEquals( $title->getText(), $id->getSerialization() );
	}

	protected function newEntity() {
		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q7' ) );
		return $item;
	}

	/**
	 * @param SettingsArray $settings
	 *
	 * @return ItemHandler
	 */
	protected function getHandler( SettingsArray $settings = null ) {
		$repo = WikibaseRepo::getDefaultInstance();
		$validator = $repo->getEntityConstraintProvider()->getConstraints( Item::ENTITY_TYPE );
		$entityPerPage = $repo->getStore()->newEntityPerPage();
		$termIndex = $repo->getStore()->getTermIndex();
		$codec = $repo->getEntityContentDataCodec();
		$errorLocalizer = $repo->getValidatorErrorLocalizer();
		$siteLinkStore = $repo->getStore()->newSiteLinkCache();

		if ( !$settings ) {
			$settings = $repo->getSettings();
		}

		$transformOnExport = $settings->getSetting( 'transformLegacyFormatOnExport' );

		return new ItemHandler(
			$entityPerPage,
			$termIndex,
			$codec,
			array( $validator ),
			$errorLocalizer,
			$siteLinkStore,
			$transformOnExport
		);
	}

}
