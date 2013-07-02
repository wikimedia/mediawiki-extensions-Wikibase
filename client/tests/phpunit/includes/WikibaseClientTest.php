<?php

namespace Wikibase\Test;

use Wikibase\Client\WikibaseClient;

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
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
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

	public function testGetDefaultInstance() {
		$this->assertSame(
			WikibaseClient::getDefaultInstance(),
			WikibaseClient::getDefaultInstance() );
	}

}
