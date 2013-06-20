<?php

namespace Wikibase\Test;

use Wikibase\Lib\Serializers\MultiLangSerializationOptions;
use Wikibase\Lib\Serializers\AliasSerializer;

/**
 * @covers Wikibase\Lib\Serializers\AliasSerializer
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
 * @group Wikibase
 * @group WikibaseSerialization
 * @group WikibaseAliasSerializer
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class AliasSerializerTest extends \PHPUnit_Framework_TestCase {

	public function validProvider() {
		$validArgs = array();

		$options = new MultiLangSerializationOptions();
		$options->setUseKeys( true );
		$aliases = array(
			"en" => array( "Roma", "Rome, Italy", "The Eternal City" ),
			"de" => array( "Die ewige Stadt", "" ),
			"it" => array( "Urbe", "Città eterna" ),
		);
		$expectedSerialization = array(
			"en" => array(
				array( "language" => "en", "value" => "Roma" ),
				array( "language" => "en", "value" => "Rome, Italy" ),
				array( "language" => "en", "value" => "The Eternal City" ),
			),
			"de" => array(
				array( "language" => "de", "value" => "Die ewige Stadt" ),
			),
			"it" => array(
				array( "language" => "it", "value" => "Urbe" ),
				array( "language" => "it", "value" => "Città eterna" ),
			),
		);
		$validArgs[] = array( $aliases, $options, $expectedSerialization );

		$options = new MultiLangSerializationOptions();
		$options->setUseKeys( false );
		$aliases = array(
				"en" => array( "Roma", "Rome, Italy", "The Eternal City" ),
				"de" => array( "Die ewige Stadt", "" ),
				"it" => array( "Urbe", "Città eterna" ),
		);
		$expectedSerialization = array(
			array( "language" => "en", "value" => "Roma" ),
			array( "language" => "en", "value" => "Rome, Italy" ),
			array( "language" => "en", "value" => "The Eternal City" ),
			array( "language" => "de", "value" => "Die ewige Stadt" ),
			array( "language" => "it", "value" => "Urbe" ),
			array( "language" => "it", "value" => "Città eterna" ),
			"_element" => "alias",
		);
		$validArgs[] = array( $aliases, $options, $expectedSerialization );

		return $validArgs;
	}

	/**
	 * @dataProvider validProvider
	 */
	public function testGetSerialized( $aliases, $options, $expectedSerialization ) {
		$aliasSerializer = new AliasSerializer( $options );
		$serializedAliases = $aliasSerializer->getSerialized( $aliases );

		$this->assertEquals( $expectedSerialization, $serializedAliases );
	}

	public function invalidProvider() {
		$invalidArgs = array();

		$invalidArgs[] = array( 'foo' );
		$invalidArgs[] = array( 42 );

		return $invalidArgs;
	}

	/**
	 * @dataProvider invalidProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidGetSerialized( $aliases ) {
		$aliasSerializer = new AliasSerializer();
		$serializedAliases = $aliasSerializer->getSerialized( $aliases );
	}
}
