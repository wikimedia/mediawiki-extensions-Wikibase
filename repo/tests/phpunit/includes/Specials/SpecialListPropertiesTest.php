<?php

namespace Wikibase\Repo\Tests\Specials;

use DataTypes\DataTypeFactory;
use Language;
use SpecialPageTestBase;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\PropertyInfoStore;
use Wikibase\Repo\EntityIdHtmlLinkFormatterFactory;
use Wikibase\Repo\Specials\SpecialListProperties;
use Wikibase\Store\BufferingTermLookup;
use Wikibase\Test\MockPropertyInfoStore;

/**
 * @covers Wikibase\Repo\Specials\SpecialListProperties
 * @covers Wikibase\Repo\Specials\SpecialWikibaseQueryPage
 * @covers Wikibase\Repo\Specials\SpecialWikibasePage
 *
 * @group Database
 * @group SpecialPage
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseSpecialPage
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Addshore
 */
class SpecialListPropertiesTest extends SpecialPageTestBase {

	private function getDataTypeFactory() {
		$dataTypeFactory = new DataTypeFactory( array(
			'wikibase-item' => 'wikibase-item',
			'string' => 'string',
			'quantity' => 'quantity',
		) );

		return $dataTypeFactory;
	}

	private function getPropertyInfoStore() {
		$propertyInfoStore = new MockPropertyInfoStore();

		$propertyInfoStore->setPropertyInfo(
			new PropertyId( 'P789' ),
			array( PropertyInfoStore::KEY_DATA_TYPE => 'string' )
		);

		$propertyInfoStore->setPropertyInfo(
			new PropertyId( 'P456' ),
			array( PropertyInfoStore::KEY_DATA_TYPE => 'wikibase-item' )
		);

		$propertyInfoStore->setPropertyInfo(
			new PropertyId( 'P123' ),
			array( PropertyInfoStore::KEY_DATA_TYPE => 'wikibase-item' )
		);

		return $propertyInfoStore;
	}

	/**
	 * @return BufferingTermLookup
	 */
	private function getBufferingTermLookup() {
		$lookup = $this->getMockBuilder( 'Wikibase\Store\BufferingTermLookup' )
			->disableOriginalConstructor()
			->getMock();
		$lookup->expects( $this->any() )
			->method( 'prefetchTerms' );
		$lookup->expects( $this->any() )
			->method( 'getLabels' )
			->will( $this->returnCallback( function( PropertyId $id ) {
				return array( 'en' => 'Property with label ' . $id->getSerialization() );
			} ) );
		return $lookup;
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getEntityTitleLookup() {
		$entityTitleLookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );
		$entityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback(
				function ( EntityId $id ) {
					return Title::makeTitle( NS_MAIN, $id->getSerialization() );
				}
			) );

		return $entityTitleLookup;
	}

	protected function newSpecialPage() {
		$specialPage = new SpecialListProperties();
		$specialPage->getContext()->setLanguage( Language::factory( 'en' ) );

		$languageNameLookup = $this->getMock( 'Wikibase\Lib\LanguageNameLookup' );
		$languageNameLookup->expects( $this->never() )
			->method( 'getName' );

		$specialPage->initServices(
			$this->getDataTypeFactory(),
			$this->getPropertyInfoStore(),
			new EntityIdHtmlLinkFormatterFactory( $this->getEntityTitleLookup(), $languageNameLookup ),
			new LanguageFallbackChainFactory(),
			$this->getEntityTitleLookup(),
			$this->getBufferingTermLookup()
		);

		return $specialPage;
	}

	public function testExecute() {
		// This also tests that there is no fatal error, that the restriction handling is working
		// and doesn't block. That is, the default should let the user execute the page.
		list( $output, ) = $this->executeSpecialPage( '', null, 'qqx' );

		$this->assertInternalType( 'string', $output );
		$this->assertContains( 'wikibase-listproperties-summary', $output );
		$this->assertContains( 'wikibase-listproperties-legend', $output );
		$this->assertNotContains( 'wikibase-listproperties-invalid-datatype', $output );
		$this->assertRegExp( '/P123.*P456.*P789/', $output ); // order is relevant
	}

	public function testOffsetAndLimit() {
		$request = new \FauxRequest( array( 'limit' => '1', 'offset' => '1' ) );
		list( $output, ) = $this->executeSpecialPage( '', $request, 'qqx' );

		$this->assertNotContains( 'P123', $output );
		$this->assertContains( 'P456', $output );
		$this->assertNotContains( 'P789', $output );
	}

	public function testExecute_empty() {
		list( $output, ) = $this->executeSpecialPage( 'quantity', null, 'qqx' );

		$this->assertContains( 'specialpage-empty', $output );
	}

	public function testExecute_error() {
		list( $output, ) = $this->executeSpecialPage( 'test<>', null, 'qqx' );

		$this->assertContains( 'wikibase-listproperties-invalid-datatype', $output );
		$this->assertContains( 'test&lt;&gt;', $output );
	}

	public function testExecute_wikibase_item() {
		// Use en-gb as language to test language fallback
		list( $output, ) = $this->executeSpecialPage( 'wikibase-item', null, 'en-gb' );

		$this->assertContains( 'Property with label P123', $output );
		$this->assertContains( 'Property with label P456', $output );
		$this->assertNotContains( 'P789', $output );
	}

	public function testExecute_string() {
		list( $output, ) = $this->executeSpecialPage( 'string', null, 'en-gb' );

		$this->assertNotContains( 'P123', $output );
		$this->assertNotContains( 'P456', $output );
		$this->assertContains( 'Property with label P789', $output );
	}

	public function testSearchSubpages() {
		$specialPage = $this->newSpecialPage();
		$this->assertEmpty(
			$specialPage->prefixSearchSubpages( 'g', 10, 0 )
		);
		$this->assertEquals(
			array( 'string' ),
			$specialPage->prefixSearchSubpages( 'st', 10, 0 )
		);
		$this->assertEquals(
			array( 'wikibase-item' ),
			$specialPage->prefixSearchSubpages( 'wik', 10, 0 )
		);
	}

}
