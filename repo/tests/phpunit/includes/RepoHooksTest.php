<?php

namespace Wikibase\Tests;

use ConfigFactory;
use Exception;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\RepoHooks;
use WikiImporter;

/**
 * @covers Wikibase\RepoHooks
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class RepoHooksTest extends \MediaWikiTestCase {

	private $saveAllowImport = false;

	public function setup() {
		parent::setup();

		$this->saveAllowImport = WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( 'allowEntityImport' );
	}

	public function tearDown() {
		WikibaseRepo::getDefaultInstance()->getSettings()->setSetting( 'allowEntityImport', $this->saveAllowImport );

		parent::tearDown();
	}

	public function revisionInfoProvider() {
		return array(
			'empty' => array( array() ),
			'wikitext' => array( array( 'model' => CONTENT_MODEL_WIKITEXT ) ),
			'item' => array( array( 'model' => CONTENT_MODEL_WIKIBASE_ITEM ), 'MWException' ),
		);
	}

	/**
	 * @dataProvider revisionInfoProvider
	 * @param $revisionInfo
	 * @param null $expectedException
	 */
	public function testOnImportHandleRevisionXMLTag( $revisionInfo, $expectedException = null ) {
		//NOTE: class is unclear, see Bug 64657. But we don't use that object anyway.
		$importer = $this->getMockBuilder( 'Import' )
			->disableOriginalConstructor()
			->getMock();

		if ( $expectedException !== null ) {
			$this->setExpectedException( $expectedException );
		}

		RepoHooks::onImportHandleRevisionXMLTag( $importer, array(), $revisionInfo );
		$this->assertTrue( true ); // make PHPUnit happy
	}

	private function getMockImportStream( $xml ) {
		$source = $this->getMockBuilder( 'ImportStreamSource' )
			->disableOriginalConstructor()
			->getMock();

		$atEnd = new \stdClass();
		$atEnd->atEnd = false;

		$source->expects( $this->any() )
			->method( 'atEnd' )
			->will( $this->returnCallback( function() use ( $atEnd ) {
				return $atEnd->atEnd;
			} ) );

		$source->expects( $this->any() )
			->method( 'readChunk' )
			->will( $this->returnCallback( function() use ( $atEnd, $xml ) {
				$atEnd->atEnd = true;
				return $xml;
			} ) );

		return $source;
	}

	public function importProvider() {
		return array(
			'wikitext' => array( <<<XML
<mediawiki>
  <siteinfo>
    <sitename>TestWiki</sitename>
    <case>first-letter</case>
  </siteinfo>
  <page>
    <title>Bla</title><ns>0</ns>
    <revision>
      <contributor><username>Tester</username><id>0</id></contributor>
      <comment>Test</comment>
      <text>Hallo Welt</text>
      <model>wikitext</model>
      <format>text/x-wiki</format>
    </revision>
  </page>
 </mediawiki>
XML
				,
				false
			),
			'item' => array( <<<XML
<mediawiki>
  <siteinfo>
    <sitename>TestWiki</sitename>
    <case>first-letter</case>
  </siteinfo>
  <page>
    <title>Q123</title><ns>1234</ns>
    <revision>
      <contributor><username>Tester</username><id>0</id></contributor>
      <comment>Test</comment>
      <text>{ "id":"Q123" }</text>
      <model>wikibase-item</model>
      <format>application/json</format>
    </revision>
  </page>
 </mediawiki>
XML
				,
				false,
				'MWException'
			),
			'item (allow)' => array( <<<XML
<mediawiki>
  <siteinfo>
    <sitename>TestWiki</sitename>
    <case>first-letter</case>
  </siteinfo>
  <page>
    <title>Q123</title><ns>1234</ns>
    <revision>
      <contributor><username>Tester</username><id>0</id></contributor>
      <comment>Test</comment>
      <text>{ "id":"Q123" }</text>
      <model>wikibase-item</model>
      <format>application/json</format>
    </revision>
  </page>
 </mediawiki>
XML
			,
				true
			),
		);
	}

	/**
	 * @dataProvider importProvider
	 *
	 * @param string $xml
	 * @param bool $allowImport
	 * @param Exception|null $expectedException
	 */
	public function testImportHandleRevisionXMLTag_hook( $xml, $allowImport, $expectedException = null ) {
		// WikiImporter tried to register this protocol every time, so unregister first to avoid errors.
		wfSuppressWarnings();
		stream_wrapper_unregister( 'uploadsource' );
		wfRestoreWarnings();

		WikibaseRepo::getDefaultInstance()->getSettings()->setSetting( 'allowEntityImport', $allowImport );

		$source = $this->getMockImportStream( $xml );
		$importer = new WikiImporter( $source, ConfigFactory::getDefaultInstance()->makeConfig( 'main' ) );

		$importer->setNoticeCallback( function() {
			// Do nothing for now. Could collect and compare notices.
		} );

		if ( $expectedException !== null ) {
			$this->setExpectedException( $expectedException );
		}

		$importer->doImport();
		$this->assertTrue( true ); // make PHPUnit happy
	}

}
