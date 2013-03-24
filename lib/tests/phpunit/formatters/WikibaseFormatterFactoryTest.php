<?php
namespace Wikibase\Lib\Test;
use Wikibase\WikibaseFormatterFactory;

/**
 * Tests for the Wikibase\WikibaseFormatterFactory class.
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
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class WikibaseFormatterFactoryTest extends \MediaWikiTestCase {

	protected static $dataTypeFormatters = array(
		'wikibase-entityid' => 'wikibase-entityid',
		'commonsMedia' => 'string',
		'string' => 'string'
	);

	protected static $valueFormatters = array(
		'wikibase-entityid' => 'Wikibase\EntityIdFormatter',
		'string' => 'Wikibase\StringFormatter'
	);

	public static function constructorProvider() {
		$lang = 'en';

		return array(
			array( self::$dataTypeFormatters, self::$valueFormatters, $lang )
		);
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testConstructor( $dataTypeFormatters, $valueFormatters, $lang ) {
		$factory = new WikibaseFormatterFactory( $dataTypeFormatters, $valueFormatters, $lang );

		$this->assertInstanceOf( '\Wikibase\WikibaseFormatterFactory', $factory );
	}

	public static function newValueFormatterForDataTypeProvider() {
		$entityOptions = array(
			'wikibase-entityid' => array(
				'entityLookup' => new \Wikibase\Test\MockRepository()
			)
		);

		return array(
			array( 'en', 'string', array(), 'Wikibase\StringFormatter', true ),
			array( 'es', 'wikibase-entityid', $entityOptions, 'Wikibase\EntityIdFormatter', true )
		);
	}

	/**
	 * @dataProvider newValueFormatterForDataTypeProvider
	 */
	public function testNewValueFormatterForDataType( $lang, $dataType, $options, $class, $valid ) {
		$factory = new WikibaseFormatterFactory( self::$dataTypeFormatters, self::$valueFormatters, $lang );
		$valueFormatter = $factory->newValueFormatterForDataType( $dataType, $options );

		$this->assertEquals( $class, get_class( $valueFormatter ) );
	}

}
