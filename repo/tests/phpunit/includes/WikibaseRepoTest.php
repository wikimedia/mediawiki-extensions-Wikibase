<?php

namespace Wikibase\Tests\Repo;

use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\WikibaseRepo
 *
 * @since 0.4
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
	private function getDefaultInstance() {
		return WikibaseRepo::getDefaultInstance();
	}

	public function testGetSettingsReturnType() {
		$returnValue = $this->getDefaultInstance()->getSettings();
		$this->assertInstanceOf( 'Wikibase\SettingsArray', $returnValue );
	}

	public function testGetDataTypeFactoryReturnType() {
		$returnValue = $this->getDefaultInstance()->getDataTypeFactory();
		$this->assertInstanceOf( 'DataTypes\DataTypeFactory', $returnValue );
	}

	public function testGetEntityIdParserReturnType() {
		$returnValue = $this->getDefaultInstance()->getEntityIdParser();
		$this->assertInstanceOf( 'Wikibase\Lib\EntityIdParser', $returnValue );
	}

	public function testGetEntityIdFormatterReturnType() {
		$returnValue = $this->getDefaultInstance()->getEntityIdFormatter();
		$this->assertInstanceOf( 'Wikibase\Lib\EntityIdFormatter', $returnValue );
	}

	public function testGetClaimGuidValidator() {
		$returnValue = $this->getDefaultInstance()->getClaimGuidValidator();
		$this->assertInstanceOf( 'Wikibase\Lib\ClaimGuidValidator', $returnValue );
	}

	public function testGetSnakFormatterFactory() {
		$returnValue = $this->getDefaultInstance()->getSnakFormatterFactory();
		$this->assertInstanceOf( 'Wikibase\Lib\OutputFormatSnakFormatterFactory', $returnValue );
	}

	public function testGetValueFormatterFactory() {
		$returnValue = $this->getDefaultInstance()->getValueFormatterFactory();
		$this->assertInstanceOf( 'Wikibase\Lib\OutputFormatValueFormatterFactory', $returnValue );
	}

	public function testGetSummaryFormatter() {
		$returnValue = $this->getDefaultInstance()->getSummaryFormatter();
		$this->assertInstanceOf( 'Wikibase\SummaryFormatter', $returnValue );
	}

	public function testGetEntityTitleLookup() {
		$returnValue = $this->getDefaultInstance()->getEntityTitleLookup();
		$this->assertInstanceOf( 'Wikibase\EntityTitleLookup', $returnValue );
	}

	public function testGetEntityRevisionLookup() {
		$returnValue = $this->getDefaultInstance()->getEntityRevisionLookup();
		$this->assertInstanceOf( 'Wikibase\EntityRevisionLookup', $returnValue );
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

		$returnValue = $this->getDefaultInstance()->getRdfBaseURI();
		$this->assertEquals( $expected, $returnValue );
	}
}
