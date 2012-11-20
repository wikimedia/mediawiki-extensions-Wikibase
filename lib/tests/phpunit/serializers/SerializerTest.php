<?php

namespace Wikibase\Test;
use Wikibase\Serializer;

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
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseApiSerialization
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SerializerTest extends \MediaWikiTestCase {

	public function apiSerializerProvider() {
		$serializers = array();

		$apiResult = new \ApiResult( new \ApiMain() );

		$serializers[] = new \Wikibase\SnakSerializer( $apiResult );
		$serializers[] = new \Wikibase\ClaimSerializer( $apiResult );

		$snakSetailizer = new \Wikibase\SnakSerializer( new \ApiResult( new \ApiMain() ) );
		$serializers[] = new \Wikibase\ByPropertyListSerializer( 'test', $snakSetailizer, new \ApiResult( new \ApiMain() ) );

		return $this->arrayWrap( $serializers );
	}

	/**
	 * @dataProvider apiSerializerProvider
	 * @param Serializer $serializer
	 */
	public function testSetOptions( Serializer $serializer ) {
		$serializer->setOptions( new \Wikibase\SerializationOptions() );
		$this->assertTrue( true );
	}

	/**
	 * @dataProvider apiSerializerProvider
	 * @param Serializer $serializer
	 */
	public function testSetApiResult( Serializer $serializer ) {
		$serializer->setApiResult( new \ApiResult( new \ApiMain() ) );
		$this->assertTrue( true );
	}

}
