<?php

namespace Wikibase\Test;

use Wikibase\EntityFactory;

use Wikibase\Lib\Serializers\MultiLangSerializationOptions;
use Wikibase\Lib\Serializers\DescriptionSerializer;
use InvalidArgumentException;

/**
 * @covers Wikibase\Lib\Serializers\DescriptionSerializer
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
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseSerialization
 * @group WikibaseDescriptionSerializer
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class DescriptionSerializerTest extends \PHPUnit_Framework_TestCase {

	public function validProvider() {
		$validArgs = array();

		$options = new MultiLangSerializationOptions();
		$options->setUseKeys( true );
		$descriptions = array(
			"en" => "capital city of Italy",
			"de" => "Hauptstadt von Italien",
			"it" => "",
			"fi" => "kunta Italiassa",
		);
		$expectedSerialization = array(
			"en" => array(
				"language" => "en",
				"value" => "capital city of Italy"
			),
			"de" => array(
				"language" => "de",
				"value" => "Hauptstadt von Italien"
			),
			"it" => array(
				"language" => "it",
				"removed" => ""
			),
			"fi" => array(
				"language" => "fi",
				"value" => "kunta Italiassa"
			),
		);
		$validArgs[] = array( $descriptions, $options, $expectedSerialization );

		$options = new MultiLangSerializationOptions();
		$options->setUseKeys( false );
		$descriptions = array(
			"en" => "capital city of Italy",
			"de" => "Hauptstadt von Italien",
			"it" => "capitale della Repubblica Italiana",
			"fi" => "kunta Italiassa",
		);
		$expectedSerialization = array(
			array(
				"language" => "en",
				"value" => "capital city of Italy"
			),
			array(
				"language" => "de",
				"value" => "Hauptstadt von Italien"
			),
			array(
				"language" => "it",
				"value" => "capitale della Repubblica Italiana"
			),
			array(
				"language" => "fi",
				"value" => "kunta Italiassa"
			),
			"_element" => "description",
		);
		$validArgs[] = array( $descriptions, $options, $expectedSerialization );

		return $validArgs;
	}

	/**
	 * @dataProvider validProvider
	 */
	public function testGetSerialized( $descriptions, $options, $expectedSerialization ) {
		$descriptionSerializer = new DescriptionSerializer( $options );
		$serializedDescriptions = $descriptionSerializer->getSerialized( $descriptions );

		$this->assertEquals( $expectedSerialization, $serializedDescriptions );
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
	public function testInvalidGetSerialized( $descriptions ) {
		$descriptionSerializer = new DescriptionSerializer();
		$serializedDescriptions = $descriptionSerializer->getSerialized( $descriptions );
	}

}
