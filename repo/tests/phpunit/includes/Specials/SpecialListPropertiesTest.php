<?php

namespace Wikibase\Repo\Tests\Specials;

use Wikibase\Lib\DataTypeFactory;
use Language;
use SpecialPageTestBase;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Repo\EntityIdHtmlLinkFormatterFactory;
use Wikibase\Repo\Specials\SpecialListProperties;
use Wikibase\Store\BufferingTermLookup;
use Wikibase\Lib\Tests\Store\MockPropertyInfoLookup;

/**
 * @covers Wikibase\Repo\Specials\SpecialListProperties
 * @covers Wikibase\Repo\Specials\SpecialWikibaseQueryPage
 * @covers Wikibase\Repo\Specials\SpecialWikibasePage
 *
 * @group Database
 * @group SpecialPage
 * @group Wikibase
 * @group WikibaseSpecialPage
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Addshore
 */
class SpecialListPropertiesTest extends SpecialPageTestBase {

	private function getDataTypeFactory() {
		$dataTypeFactory = new DataTypeFactory( [
			'wikibase-item' => 'wikibase-item',
			'string' => 'string',
			'quantity' => 'quantity',
		] );

		return $dataTypeFactory;
	}

	private function getPropertyInfoStore() {
		$propertyInfoLookup = new MockPropertyInfoLookup( [
			'P789' => [ PropertyInfoLookup::KEY_DATA_TYPE => 'string' ],
			'P45' => [ PropertyInfoLookup::KEY_DATA_TYPE => 'wikibase-item' ],
			'P123' => [ PropertyInfoLookup::KEY_DATA_TYPE => 'wikibase-item' ],
		] );

		return $propertyInfoLookup;
	}

	/**
	 * @return BufferingTermLookup
	 */
	private function getBufferingTermLookup() {
		$lookup = $this->getMockBuilder( BufferingTermLookup::class )
			->disableOriginalConstructor()
			->getMock();
		$lookup->expects( $this->any() )
			->method( 'prefetchTerms' );
		$lookup->expects( $this->any() )
			->method( 'getLabels' )
			->will( $this->returnCallback( function( PropertyId $id ) {
				return [ 'en' => 'Property with label ' . $id->getSerialization() ];
			} ) );
		return $lookup;
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getEntityTitleLookup() {
		$entityTitleLookup = $this->getMock( EntityTitleLookup::class );
		$entityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback(
				function ( EntityId $id ) {
					$title = $this->getMock( Title::class );
					$title->expects( $this->any() )
						->method( 'exists' )
						->will( $this->returnValue( true ) );
					return $title;
				}
			) );

		return $entityTitleLookup;
	}

	protected function newSpecialPage() {
		$language = Language::factory( 'en-gb' );

		$languageNameLookup = $this->getMock( LanguageNameLookup::class );
		$languageNameLookup->expects( $this->never() )
			->method( 'getName' );
		$entityIdFormatterFactory = new EntityIdHtmlLinkFormatterFactory(
			$this->getEntityTitleLookup(),
			$languageNameLookup
		);
		$bufferingTermLookup = $this->getBufferingTermLookup();
		$languageFallbackChainFactory = new LanguageFallbackChainFactory();
		$labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup(
			$bufferingTermLookup,
			$languageFallbackChainFactory->newFromLanguage(
				$language,
				LanguageFallbackChainFactory::FALLBACK_ALL
			)
		);
		$entityIdFormatter = $entityIdFormatterFactory->getEntityIdFormatter(
			$labelDescriptionLookup
		);
		$specialPage = new SpecialListProperties(
			$this->getDataTypeFactory(),
			$this->getPropertyInfoStore(),
			$labelDescriptionLookup,
			$entityIdFormatter,
			$this->getEntityTitleLookup(),
			$bufferingTermLookup
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
		$this->assertRegExp( '/P45.*P123.*P789/', $output ); // order is relevant
	}

	public function testOffsetAndLimit() {
		$request = new \FauxRequest( [ 'limit' => '1', 'offset' => '1' ] );
		list( $output, ) = $this->executeSpecialPage( '', $request, 'qqx' );

		$this->assertNotContains( 'P45', $output );
		$this->assertContains( 'P123', $output );
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

		$this->assertContains( 'Property with label P45', $output );
		$this->assertContains( 'Property with label P123', $output );
		$this->assertNotContains( 'P789', $output );

		$this->assertContains( 'lang="en"', $output );
		$this->assertNotContains( 'lang="en-gb"', $output );
	}

	public function testExecute_string() {
		list( $output, ) = $this->executeSpecialPage( 'string', null, 'en-gb' );

		$this->assertNotContains( 'P45', $output );
		$this->assertNotContains( 'P123', $output );
		$this->assertContains( 'Property with label P789', $output );
	}

	public function testSearchSubpages() {
		$specialPage = $this->newSpecialPage();
		$this->assertEmpty(
			$specialPage->prefixSearchSubpages( 'g', 10, 0 )
		);
		$this->assertEquals(
			[ 'string' ],
			$specialPage->prefixSearchSubpages( 'st', 10, 0 )
		);
		$this->assertEquals(
			[ 'wikibase-item' ],
			$specialPage->prefixSearchSubpages( 'wik', 10, 0 )
		);
	}

}
