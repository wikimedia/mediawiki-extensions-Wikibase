<?php

namespace Wikibase\Test;

use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\PropertyContent;
use Wikibase\PropertyHandler;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\SettingsArray;

/**
 * @covers Wikibase\PropertyHandler
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseProperty
 * @group WikibaseEntity
 * @group WikibaseEntityHandler
 *
 * @licence GNU GPL v2+
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

	/**
	 * @see EntityHandlerTest::getClassName
	 * @return string
	 */
	public function getClassName() {
		return '\Wikibase\PropertyHandler';
	}

	/**
	 * @see EntityHandlerTest::contentProvider
	 */
	public function contentProvider() {
		$contents = parent::contentProvider();

		/**
		 * @var PropertyContent $content
		 */
		$content = clone $contents[1][0];
		// TODO: add some prop-specific stuff: $content->getProperty()->;
		$contents[] = array( $content );

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

	protected function newEntity( EntityId $propertyId = null ) {
		if ( $propertyId === null ) {
			$propertyId = new PropertyId( 'P7' );
		}

		$property = Property::newFromType( 'string' );
		$property->setId( $propertyId );
		return $property;
	}

	/**
	 * @param SettingsArray $settings
	 * @param EntityContentDataCodec $codec
	 *
	 * @return PropertyHandler
	 */
	protected function getHandler(
		SettingsArray $settings = null,
		EntityContentDataCodec $codec = null
	) {
		$repo = WikibaseRepo::getDefaultInstance();
		$validator = $repo->getEntityConstraintProvider()->getConstraints( Property::ENTITY_TYPE );
		$entityPerPage = $repo->getStore()->newEntityPerPage();
		$termIndex = $repo->getStore()->getTermIndex();
		$codec = $repo->getEntityContentDataCodec();
		$errorLocalizer = $repo->getValidatorErrorLocalizer();
		$propertyInfoStore = $repo->getStore()->getPropertyInfoStore();

		if ( !$settings ) {
			$settings = $repo->getSettings();
		}

		if ( !$codec ) {
			$codec = $repo->getEntityContentDataCodec();
		}

		$transformOnExport = $settings->getSetting( 'transformLegacyFormatOnExport' );

		return new PropertyHandler(
			$entityPerPage,
			$termIndex,
			$codec,
			array( $validator ),
			$errorLocalizer,
			$propertyInfoStore,
			$transformOnExport
		);
	}

}
