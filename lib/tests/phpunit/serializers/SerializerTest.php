<?php

namespace Wikibase\Test;
use Wikibase\Lib\Serializers\Serializer;
use Wikibase\Lib\Serializers\SerializationOptions;

/**
 * Tests for the Wikibase\Serializer implementing classes.
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
 * @since 0.2
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SerializerTest extends \MediaWikiTestCase {

	public function apiSerializerProvider() {
		$serializers = array();

		$serializers[] = new \Wikibase\Lib\Serializers\SnakSerializer();
		$serializers[] = new \Wikibase\Lib\Serializers\ClaimSerializer();

		$snakSetailizer = new \Wikibase\Lib\Serializers\SnakSerializer();
		$serializers[] = new \Wikibase\Lib\Serializers\ByPropertyListSerializer( 'test', $snakSetailizer );

		return $this->arrayWrap( $serializers );
	}

	/**
	 * @dataProvider apiSerializerProvider
	 * @param Serializer $serializer
	 */
	public function testSetOptions( Serializer $serializer ) {
		$serializer->setOptions( new SerializationOptions() );
		$this->assertTrue( true );
	}

	/**
	 * @dataProvider apiSerializerProvider
	 * @param Serializer $serializer
	 */
	public function testSetApiResult( Serializer $serializer ) {
		$this->assertTrue( true );
	}

}
