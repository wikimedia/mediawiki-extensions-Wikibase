<?php

namespace Wikibase\Test;

use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikimedia\Purtle\RdfWriterFactory;

/**
 * @covers Wikibase\Repo\LinkedData\EntityDataFormatProvider
 *
 * @group Wikibase
 * @group WikibaseEntityData
 * @group WikibaseRepo
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class EntityDataFormatProviderTest extends \MediaWikiTestCase {

	public function getMimeTypesProvider() {
		$allFormats = array(
			'application/json' => 'json',
			'application/jsonfm' => 'jsonfm',
			'application/vnd.php.serialized' => 'php',
			'application/phpfm' => 'phpfm',
			'application/wddx' => 'wddx',
			'application/wddxfm' => 'wddxfm',
			'application/xml' => 'xml',
			'application/xmlfm' => 'xmlfm',
			'application/yaml' => 'yaml',
			'application/yamlfm' => 'yamlfm',
			'application/rawfm' => 'rawfm',
			'text/text' => 'txt',
			'text/plain' => 'txt',
			'application/txtfm' => 'txtfm',
			'application/dbg' => 'dbg',
			'application/dbgfm' => 'dbgfm',
			'application/dump' => 'dump',
			'application/dumpfm' => 'dumpfm',
			'application/none' => 'none',
			'text/n3' => 'n3',
			'text/rdf+n3' => 'n3',
			'text/turtle' => 'turtle',
			'application/x-turtle' => 'turtle',
			'application/n-triples' => 'ntriples',
			'text/n-triples' => 'ntriples',
			'application/rdf+xml' => 'rdfxml',
			'text/xml' => 'rdfxml',
		);

		return array(
			"No types whitelisted" => array(
				array(),
				array()
			),
			"No whitelist" => array(
				$allFormats,
				null
			),
			"Only turtle and a format which doesn't exist" => array(
				array_filter(
					$allFormats,
					function( $val ) {
						return $val === 'turtle';
					}
				),
				array( 'turtle', 'kitten-rdf' )
			)
		);
	}

	/**
	 * @dataProvider getMimeTypesProvider
	 */
	public function testGetMimeTypes( array $expected, array $whitelist = null ) {
		$provider = new EntityDataFormatProvider( new RdfWriterFactory() );

		$types = $provider->getMimeTypes( $whitelist );

		$this->assertEquals(
			$expected,
			$types
		);
	}

	public function getFileExtensionsProvider() {
		$allFormats = array(
			'json' => 'json',
			'jsonfm' => 'jsonfm',
			'php' => 'php',
			'phpfm' => 'phpfm',
			'wddx' => 'wddx',
			'wddxfm' => 'wddxfm',
			'xml' => 'xml',
			'xmlfm' => 'xmlfm',
			'yaml' => 'yaml',
			'yamlfm' => 'yamlfm',
			'rawfm' => 'rawfm',
			'txt' => 'txt',
			'txtfm' => 'txtfm',
			'dbg' => 'dbg',
			'dbgfm' => 'dbgfm',
			'dump' => 'dump',
			'dumpfm' => 'dumpfm',
			'none' => 'none',
			'n3' => 'n3',
			'ttl' => 'turtle',
			'nt' => 'ntriples',
			'rdf' => 'rdfxml'
		);

		return array(
			"No types whitelisted" => array(
				array(),
				array()
			),
			"No whitelist" => array(
				$allFormats,
				null
			),
			"Only turtle and a format which doesn't exist" => array(
				array( 'ttl' => 'turtle' ),
				array( 'turtle', 'kitten-rdf' )
			)
		);
	}

	/**
	 * @dataProvider getFileExtensionsProvider
	 */
	public function testGetFileExtensions( array $expected, array $whitelist = null ) {
		$provider = new EntityDataFormatProvider( new RdfWriterFactory() );

		$extensions = $provider->getFileExtensions( $whitelist );

		$this->assertEquals(
			$expected,
			$extensions
		);
	}
}
