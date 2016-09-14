<?php

namespace Wikibase\Client\Tests\Specials;

use FakeResultWrapper;
use SpecialPageTestBase;
use Title;
use WikiPage;
use Wikibase\Client\Specials\SpecialEntityUsage;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;

/**
 * @covers Wikibase\Client\Specials\SpecialEntityUsage
 *
 * @group WikibaseClient
 * @group SpecialPage
 * @group WikibaseSpecialPage
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class SpecialEntityUsageTest extends SpecialPageTestBase {

	protected function setUp() {
		parent::setUp();
	}

	public function reallyDoQueryMock() {
		$rows = [
			[
				'value' => 11,
				'namespace' => 0,
				'title' => 'Tehran',
				'aspects' => 'S|O',
				'eu_page_id' => 11,
				'eu_entity_id' => 'Q3',
			]
		];

		$res = new FakeResultWrapper( json_decode(json_encode($rows), false) );
		return $res;
	}

	protected function newSpecialPage() {
		$specialPage = $this->getMockBuilder( SpecialEntityUsage::class )
			->setMethods( [ 'reallyDoQuery' ] )
			->getMock();

		$specialPage->expects( $this->any() )
			->method( 'reallyDoQuery' )
			->will( $this->returnValue( $this->reallyDoQueryMock() ) );

		return $specialPage;
	}

	public function testExecuteWithValidParam() {
		list( $result, ) = $this->executeSpecialPage( 'Q3' );

		$this->assertContains( 'Tehran', $result );
	}

	public function testExecuteWithInvalidParam() {
		list( $result, ) = $this->executeSpecialPage( 'FooBar' );

		$this->assertContains( '<p class="error"', $result );
		$this->assertContains(
			wfMessage( 'wikibase-entityusage-invalid-id', 'FooBar' )->text(),
			$result
		);
	}

}
