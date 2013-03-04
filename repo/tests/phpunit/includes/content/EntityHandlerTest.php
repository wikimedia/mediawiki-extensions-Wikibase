<?php

namespace Wikibase\Test;
use ContentHandler;
use Wikibase\EntityHandler;
use Wikibase\EntityContent;

/**
 *  Tests for the Wikibase\EntityHandler class.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseRepoTest
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
	 * @dataProvider instanceProvider
	 * @param \Wikibase\EntityHandler $entityHandler
	 */
	public function testGetSpecialPageForCreation( EntityHandler $entityHandler ) {
		$specialPageName = $entityHandler->getSpecialPageForCreation();
		$this->assertTrue( $specialPageName === null || is_string( $specialPageName ) );
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

	public function testCanBeUsedOn() {
		$handler = $this->getHandler();

		$this->assertTrue( $handler->canBeUsedOn( \Title::makeTitle( $handler->getEntityNamespace(), "1234" ) ),
							'It should be possible to create this kind of entity in the respective entity namespace!'
						);

		$this->assertFalse( $handler->canBeUsedOn( \Title::makeTitle( NS_MEDIAWIKI, "Foo" ) ),
							'It should be impossible to create an entity outside the respective entity namespace!'
						);
	}

	public function testGetPageLanguage() {
		global $wgContLang;

		$handler = $this->getHandler();
		$title = \Title::makeTitle( $handler->getEntityNamespace(), "1234567" );

		//NOTE: currently, this tests whether getPageLanguage will always return the content language, even
		//      if the user language is different. It's unclear whether this is actually the desired behavior,
		//      since Wikibase Entities are inherently multilingual, so they have no actual "page language".

		// test whatever is there
		$this->assertEquals( $wgContLang->getCode(), $handler->getPageLanguage( $title )->getCode() );

		// test fr
		$this->setMwGlobals( 'wgLang', \Language::factory( "fr" ) );
		$handler = $this->getHandler();
		$this->assertEquals( $wgContLang->getCode(), $handler->getPageLanguage( $title )->getCode() );

		// test nl
		$this->setMwGlobals( 'wgLang', \Language::factory( "nl" ) );
		$this->setMwGlobals( 'wgContLang', \Language::factory( "fr" ) );
		$handler = $this->getHandler();
		$this->assertEquals( $wgContLang->getCode(), $handler->getPageLanguage( $title )->getCode() );
	}

	public function testGetPageViewLanguage() {
		global $wgLang;

		$handler = $this->getHandler();
		$title = \Title::makeTitle( $handler->getEntityNamespace(), "1234567" );

		//NOTE: we expect getPageViewLanguage to return the user language, because Wikibase Entities
		//      are always shown in the user language.

		// test whatever is there
		$this->assertEquals( $wgLang->getCode(), $handler->getPageViewLanguage( $title )->getCode() );

		// test fr
		$this->setMwGlobals( 'wgLang', \Language::factory( "fr" ) );
		$handler = $this->getHandler();
		$this->assertEquals( $wgLang->getCode(), $handler->getPageViewLanguage( $title )->getCode() );

		// test nl
		$this->setMwGlobals( 'wgLang', \Language::factory( "nl" ) );
		$handler = $this->getHandler();
		$this->assertEquals( $wgLang->getCode(), $handler->getPageViewLanguage( $title )->getCode() );
	}

	public function testLocalizedModelName() {
		$name = ContentHandler::getLocalizedName( $this->getModelId() );

		$this->assertNotEquals( $this->getModelId(), $name, "localization of model name" );
	}

}
