<?php

namespace Wikibase\Test;
use ContentHandler;
use Wikibase\EntityHandler as EntityHandler;
use Wikibase\EntityContent as EntityContent;

/**
 *  Tests for the Wikibase\EntityHandler class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseEntity
 * @group WikibaseEntityHandler
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class EntityHandlerTest extends \MediaWikiTestCase {

	public abstract function getModelId();

	public abstract function getClassName();

	/**
	 * Returns instances of the EntityHandler deriving class.
	 * @return array
	 */
	public function instanceProvider() {
		$class = $this->getClassName();
		return array(
			array( new $class ),
			array( $this->getHandler() ),
		);
	}

	/**
	 * @return EntityHandler
	 */
	protected function getHandler() {
		return ContentHandler::getForModelID( $this->getModelId() );
	}

	/**
	 * Returns EntityContents that can be handled by the EntityHandler deriving class.
	 * @return array
	 */
	public function contentProvider() {
		/**
		 * @var EntityContent $content
		 */
		$content = $this->getHandler()->makeEmptyContent();
		$content->getEntity()->addAliases( 'en', array( 'foo' ) );
		$content->getEntity()->setDescription( 'de', 'foobar' );
		$content->getEntity()->setDescription( 'en', 'baz' );
		$content->getEntity()->setLabel( 'nl', 'o_O' );

		return array(
			array( $this->getHandler()->makeEmptyContent() ),
			array( $content ),
		);
	}

	/**
	 * @dataProvider instanceProvider
	 * @param \Wikibase\EntityHandler $entityHandler
	 */
	public function testGetModelName( EntityHandler $entityHandler )  {
		$this->assertEquals( $this->getModelId(), $entityHandler->getModelID() );
		$this->assertInstanceOf( '\ContentHandler', $entityHandler );
		$this->assertInstanceOf( $this->getClassName(), $entityHandler );
	}

	/**
	 * @dataProvider contentProvider
	 * @param EntityContent $content
	 */
	public function testSerialization( EntityContent $content ) {
		$handler = $this->getHandler();

		foreach ( array( CONTENT_FORMAT_JSON,  CONTENT_FORMAT_SERIALIZED ) as $format ) {
			$this->assertTrue( $content->equals(
				$handler->unserializeContent( $handler->serializeContent( $content, $format ), $format )
			) );
		}
	}

	public function testGetPageLanguage() {
		global $wgLang;
		$oldLang = $wgLang;

		$handler = $this->getHandler();
		$title = \Title::makeTitle( $handler->getEntityNamespace(), "1234567" );

		// test whatever is there
		$this->assertEquals( $wgLang->getCode(), $handler->getPageLanguage( $title )->getCode() );

		// test fr
		$wgLang = \Language::factory( "fr" );
		$handler = $this->getHandler();
		$this->assertEquals( $wgLang->getCode(), $handler->getPageLanguage( $title )->getCode() );

		// test nl
		$wgLang = \Language::factory( "nl" );
		$handler = $this->getHandler();
		$this->assertEquals( $wgLang->getCode(), $handler->getPageLanguage( $title )->getCode() );

		// restore
		$wgLang = $oldLang;
	}

}
