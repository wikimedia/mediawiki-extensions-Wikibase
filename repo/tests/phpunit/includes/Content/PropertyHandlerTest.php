<?php

namespace Wikibase\Repo\Tests\Content;

use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\PropertyContent;
use Wikibase\Repo\Content\PropertyHandler;
use Wikibase\SettingsArray;

/**
 * @covers Wikibase\Repo\Content\PropertyHandler
 * @covers Wikibase\Repo\Content\EntityHandler
 *
 * @group Wikibase
 * @group WikibaseProperty
 * @group WikibaseEntity
 * @group WikibaseEntityHandler
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyHandlerTest extends EntityHandlerTest {

	/**
	 * @see EntityHandlerTest::getModelId
	 * @return string
	 */
	public function getModelId() {
		return CONTENT_MODEL_WIKIBASE_PROPERTY;
	}

	public function testGetModelID() {
		$this->assertSame( CONTENT_MODEL_WIKIBASE_PROPERTY, $this->getHandler()->getModelID() );
	}

	/**
	 * @see EntityHandlerTest::contentProvider
	 */
	public function contentProvider() {
		$contents = [];
		$contents[] = [ $this->newEntityContent() ];

		/** @var PropertyContent $content */
		$content = $this->newEntityContent();
		$content->getEntity()->setAliases( 'en', [ 'foo' ] );
		$content->getEntity()->setDescription( 'de', 'foobar' );
		$content->getEntity()->setDescription( 'en', 'baz' );
		$content->getEntity()->setLabel( 'nl', 'o_O' );
		$contents[] = [ $content ];

		$content = clone $contents[1][0];
		// TODO: add some prop-specific stuff: $content->getProperty()->;
		$contents[] = [ $content ];

		return $contents;
	}

	public function testGetTitleForId() {
		$handler = $this->getHandler();
		$id = new PropertyId( 'P123' );

		$title = $handler->getTitleForId( $id );
		$this->assertEquals( $id->getSerialization(), $title->getText() );
	}

	public function testGetIdForTitle() {
		$handler = $this->getHandler();
		$title = Title::makeTitle( $handler->getEntityNamespace(), 'P123' );

		$id = $handler->getIdForTitle( $title );
		$this->assertEquals( $title->getText(), $id->getSerialization() );
	}

	protected function newEntity( EntityId $id = null ) {
		if ( !$id ) {
			$id = new PropertyId( 'P7' );
		}

		$property = Property::newFromType( 'string' );
		$property->setId( $id );
		return $property;
	}

	public function entityIdProvider() {
		return array(
			array( 'P7' )
		);
	}

	/**
	 * @param SettingsArray|null $settings
	 *
	 * @return PropertyHandler
	 */
	protected function getHandler( SettingsArray $settings = null ) {
		return $this->getWikibaseRepo( $settings )->newPropertyHandler();
	}

	public function testAllowAutomaticIds() {
		$handler = $this->getHandler();
		$this->assertTrue( $handler->allowAutomaticIds() );
	}

	public function testCanCreateWithCustomId() {
		$handler = $this->getHandler();
		$id = new PropertyId( 'P7' );
		$this->assertFalse( $handler->canCreateWithCustomId( $id ) );
	}

	protected function getTestItemContent() {
		$item = new Property( null, null, 'string' );
		$item->getFingerprint()->setLabel( 'en', 'Kitten' );
		$item->getStatements()->addNewStatement(
			new PropertyNoValueSnak( new PropertyId( 'P1' ) )
		);

		return PropertyContent::newFromProperty( $item );
	}

	protected function getExpectedFields() {
		return [ 'label_count', 'statement_count' ];
	}

}
