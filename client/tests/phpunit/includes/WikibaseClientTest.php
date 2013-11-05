<?php

namespace Wikibase\Test;

use Language;
use Wikibase\Client\WikibaseClient;
use Wikibase\Settings;
use Wikibase\SettingsArray;

/**
 * Tests for the Wikibase\Client\WikibaseClient class.
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
 * @since 0.4
 *
 * @ingroup WikibaseClient
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseClientTest
 *
 * @group Database
 *        ^---- needed because we rely on Title objects internally
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class WikibaseClientTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return WikibaseClient
	 */
	private function getDefaultInstance() {
		return WikibaseClient::getDefaultInstance();
	}

	public function testGetSettingsReturnType() {
		$returnValue = $this->getDefaultInstance()->getSettings();
		$this->assertInstanceOf( 'Wikibase\SettingsArray', $returnValue );
	}

	public function testGetStoreReturnType() {
		$returnValue = $this->getDefaultInstance()->getStore();
		$this->assertInstanceOf( 'Wikibase\ClientStore', $returnValue );
	}

	public function testGetDataTypeFactoryReturnType() {
		$returnValue = $this->getDefaultInstance()->getDataTypeFactory();
		$this->assertInstanceOf( 'DataTypes\DataTypeFactory', $returnValue );
	}

	public function testGetEntityIdParserReturnType() {
		$returnValue = $this->getDefaultInstance()->getEntityIdParser();
		$this->assertInstanceOf( 'Wikibase\Lib\EntityIdParser', $returnValue );
	}

	public function testEntityIdLabelFormatterReturnType() {
		$returnValue = $this->getDefaultInstance()->newEntityIdLabelFormatter( 'en' );
		$this->assertInstanceOf( 'Wikibase\Lib\EntityIdLabelFormatter', $returnValue );
	}

	public function testGetSnakFormatterFactory() {
		$returnValue = $this->getDefaultInstance()->getSnakFormatterFactory();
		$this->assertInstanceOf( 'Wikibase\Lib\OutputFormatSnakFormatterFactory', $returnValue );
	}

	public function testGetValueFormatterFactory() {
		$returnValue = $this->getDefaultInstance()->getValueFormatterFactory();
		$this->assertInstanceOf( 'Wikibase\Lib\OutputFormatValueFormatterFactory', $returnValue );
	}

	public function testGetDefaultInstance() {
		$this->assertSame(
			WikibaseClient::getDefaultInstance(),
			WikibaseClient::getDefaultInstance() );
	}

	public function testGetLangLinkSiteGroup() {
		$settings = clone Settings::singleton();
		$settings->setSetting( 'languageLinkSiteGroup', null );

		$client = new WikibaseClient( $settings, \Language::factory( 'en' ), true );
		$this->assertNotNull( $client->getLangLinkSiteGroup() );
	}

	public function testGetSiteGroup() {
		$siteStore = $this->getMockBuilder( 'SiteSQLStore' )
				->disableOriginalConstructor()
				->getMock();

		$site = MediaWikiSite::newFromGlobalId( 'enwiki' );
		$site->setGroup( 'wikipedia' );

		$settings = clone Settings::singleton();
		$settings->setSetting( 'siteGroup', null );
		$settings->setSettings( 'siteGlobalID', 'enwiki' );

		$client = new WikibaseClient( $settings, Language::factory( 'en' ), true, $siteStore );
		$this->assertEquals( 'wikipedia', $client->getSiteGroup() );

		$settings->setSetting( 'siteGroup', 'wikivoyage' );
		$client = new WikibaseClient( $settings, Language::factory( 'en' ), true, $siteStore );
		$this->assertEquals( 'wikivoyage', $client->getSiteGroup() );
	}

	public function testGetSite() {
		$client = $this->getDefaultInstance();
		$this->assertNotNull( $client->getSite() );
	}
}
