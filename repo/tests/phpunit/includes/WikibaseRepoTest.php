<?php

namespace Wikibase\Tests\Repo;

use Wikibase\Repo\WikibaseRepo;

/**
 * Tests for the Wikibase\Repo\WikibaseRepo class.
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
 * @ingroup WikibaseRepo
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRepoTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class WikibaseRepoTest extends \MediaWikiTestCase {

	/**
	 * @return WikibaseRepo
	 */
	private function newInstance() {
		return WikibaseRepo::newInstance();
	}

	public function testGetSettingsReturnType() {
		$returnValue = $this->newInstance()->getSettings();
		$this->assertInstanceOf( 'Wikibase\SettingsArray', $returnValue );
	}

	public function testGetDataTypeFactoryReturnType() {
		$returnValue = $this->newInstance()->getDataTypeFactory();
		$this->assertInstanceOf( 'DataTypes\DataTypeFactory', $returnValue );
	}

	public function testGetEntityIdParserReturnType() {
		$returnValue = $this->newInstance()->getEntityIdParser();
		$this->assertInstanceOf( 'Wikibase\Lib\EntityIdParser', $returnValue );
	}

	public static function provideGetRdfBaseURI() {
		return array(
			array ( 'http://acme.test', 'http://acme.test/entity/' ),
			array ( 'https://acme.test', 'https://acme.test/entity/' ),
			array ( '//acme.test', 'http://acme.test/entity/' ),
		);
	}

	/**
	 * @dataProvider provideGetRdfBaseURI
	 */
	public function testGetRdfBaseURI( $server, $expected ) {
		$this->setMwGlobals( 'wgServer', $server );

		$returnValue = $this->newInstance()->getRdfBaseURI();
		$this->assertEquals( $expected, $returnValue );
	}
}
