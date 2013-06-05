<?php

namespace Wikibase\Test;

use DataTypes\DataTypeFactory;
use Revision;
use ValueFormatters\FormatterOptions;
use Wikibase\Entity;
use Wikibase\EntityDataSerializationService;
use \Wikibase\Item;
use \Wikibase\ItemContent;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Property;

/**
 * @covers \Wikibase\EntityDataSerializationService
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
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @group Database
 *
 * @group Wikibase
 * @group WikibaseEntityData
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityDataSerializationServiceTest extends \MediaWikiTestCase {

	const URI_BASE = 'http://acme.test/';
	const URI_DATA = 'http://data.acme.test/';

	public static $dataTypes = array(
		'commonsMedia' => array(
			'datavalue' => 'string',
		),
		'string' => array(
			'datavalue' => 'string',
		),
		'geo-coordinate' => array(
			'datavalue' => 'geocoordinate',
		),
		'quantity' => array(
			'datavalue' => 'quantity',
		),
		'monolingual-text' => array(
			'datavalue' => 'monolingualtext',
		),
		'multilingual-text' => array(
			'datavalue' => 'multilingualtext',
		),
		'time' => array(
			'datavalue' => 'time',
		),
	);

	protected function newService() {
		$entityLookup = new MockRepository();
		$dataTypeFactory = new DataTypeFactory( self::$dataTypes );
		$idFormatter = new EntityIdFormatter( new FormatterOptions( array(
			EntityIdFormatter::OPT_PREFIX_MAP => array(
				Item::ENTITY_TYPE => 'Q',
				Property::ENTITY_TYPE => 'P',
			)
		) ) );

		$service = new EntityDataSerializationService(
			self::URI_BASE,
			self::URI_DATA,
			$entityLookup,
			$dataTypeFactory,
			$idFormatter
		);

		$service->setFormatWhiteList(
			array(
				// using the API
				'json', // default
				'php',
				'xml',

				// using easyRdf
				'rdfxml',
				'n3',
				'turtle',
				'ntriples',
			)
		);

		return $service;
	}


	public static function provideGetSerializedData() {
		$entity = Item::newEmpty();
		$entity->setId( 23 );
		$entity->setLabel( 'en', "ACME" );

		$revisions = new Revision( array(
			'id' => 123,
			'page' => 23,
			'user_text' => 'TestUser',
			'user' => 13,
			'timestamp' => '20130505010101',
			'content_model' => CONTENT_MODEL_WIKIBASE_ITEM,
			'comment' => 'just testing',
		) );

		//TODO: set up...

		$cases = array();

		$cases[] = array( // #0:
			'json',       // format
			$entity,      // entity
			null,         // revision
			'!^\{.*ACME!', // output regex
			'application/json', // mime type
		);

		return $cases;
	}

	/**
	 * @dataProvider provideGetSerializedData
	 *
	 */
	public function testGetSerializedData(
		$format,
		Entity $entity,
		Revision $rev = null,
		$expectedDataRegex,
		$expectedMimeType
	) {
		$service = $this->newService();
		list( $data, $mimeType ) = $service->getSerializedData( $format, $entity, $rev );

		$this->assertEquals( $expectedMimeType, $mimeType );
		$this->assertRegExp( $expectedDataRegex, $data, "outpout" );
	}

	static $apiMimeTypes = array(
		'application/vnd.php.serialized',
		'application/json',
		'text/xml'
	);

	static $apiExtensions = array(
		'php',
		'json',
		'xml'
	);

	static $apiFormats = array(
		'php',
		'json',
		'xml'
	);

	static $rdfMimeTypes = array(
		'application/rdf+xml',
		'text/n3',
		'text/rdf+n3',
		'text/turtle',
		'application/turtle',
		'application/ntriples',
	);

	static $rdfExtensions = array(
		'rdf',
		'n3',
		'ttl',
		'nt'
	);

	static $rdfFormats = array(
		'rdfxml',
		'n3',
		'turtle',
		'ntriples'
	);

	static $badMimeTypes = array(
		'text/html',
		'text/text',
		// 'text/plain', // ntriples presents as text/plain!
	);

	static $badExtensions = array(
		'html',
		'text',
		'txt',
	);

	static $badFormats = array(
		'html',
		'text',
	);

	public function testGetSupportedMimeTypes() {
		$service = $this->newService();

		$types = $service->getSupportedMimeTypes();

		foreach ( self::$apiMimeTypes as $type ) {
			$this->assertTrue( in_array( $type, $types), $type );
		}

		if ( $service->isRdfSupported() ) {
			foreach ( self::$rdfMimeTypes as $type ) {
				$this->assertTrue( in_array( $type, $types), $type );
			}
		}

		foreach ( self::$badMimeTypes as $type ) {
			$this->assertFalse( in_array( $type, $types), $type );
		}
	}

	public function testGetSupportedExtensions() {
		$service = $this->newService();

		$types = $service->getSupportedExtensions();

		foreach ( self::$apiExtensions as $type ) {
			$this->assertTrue( in_array( $type, $types), $type );
		}

		if ( $service->isRdfSupported() ) {
			foreach ( self::$rdfExtensions as $type ) {
				$this->assertTrue( in_array( $type, $types), $type );
			}
		}

		foreach ( self::$badExtensions as $type ) {
			$this->assertFalse( in_array( $type, $types), $type );
		}
	}

	public function testGetSupportedFormats() {
		$service = $this->newService();

		$types = $service->getSupportedFormats();

		foreach ( self::$apiFormats as $type ) {
			$this->assertTrue( in_array( $type, $types), $type );
		}

		if ( $service->isRdfSupported() ) {
			foreach ( self::$rdfFormats as $type ) {
				$this->assertTrue( in_array( $type, $types), $type );
			}
		}

		foreach ( self::$badFormats as $type ) {
			$this->assertFalse( in_array( $type, $types), $type );
		}
	}

	public function testGetFormatName() {
		$service = $this->newService();

		$types = $service->getSupportedMimeTypes();

		foreach ( $types as $type ) {
			$format = $service->getFormatName( $type );
			$this->assertNotNull( $format, $type );
		}

		$types = $service->getSupportedExtensions();

		foreach ( $types as $type ) {
			$format = $service->getFormatName( $type );
			$this->assertNotNull( $format, $type );
		}

		$types = $service->getSupportedFormats();

		foreach ( $types as $type ) {
			$format = $service->getFormatName( $type );
			$this->assertNotNull( $format, $type );
		}
	}

	public function testGetExtension() {
		$service = $this->newService();

		$extensions = $service->getSupportedExtensions();
		foreach ( $extensions as $expected ) {
			$format = $service->getFormatName( $expected );
			$actual = $service->getExtension( $format );

			$this->assertType( 'string', $actual, $expected );
		}

		foreach ( self::$badFormats as $format ) {
			$actual = $service->getExtension( $format );

			$this->assertNull( $actual, $format );
		}
	}

	public function testGetMimeType() {
		$service = $this->newService();

		$extensions = $service->getSupportedMimeTypes();
		foreach ( $extensions as $expected ) {
			$format = $service->getFormatName( $expected );
			$actual = $service->getMimeType( $format );

			$this->assertType( 'string', $actual, $expected );
		}

		foreach ( self::$badFormats as $format ) {
			$actual = $service->getMimeType( $format );

			$this->assertNull( $actual, $format );
		}
	}
}
