<?php

namespace Wikibase\Client\Tests\DataAccess;

use DataValues\DecimalValue;
use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\Geo\Values\LatLongValue;
use DataValues\MonolingualTextValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use MediaWikiIntegrationTestCase;
use Title;
use Wikibase\Client\DataAccess\DataAccessSnakFormatterFactory;
use Wikibase\Client\Tests\Mocks\MockClientStore;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Lookup\EntityRetrievingTermLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Tests\Store\MockPropertyInfoLookup;

/**
 * Regression tests for the output produced by data access functionality.
 * Technically this tests the SnakFormatters outputted by DataAccessSnakFormatterFactory.
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseDataAccess
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 * @covers \Wikibase\Client\DataAccess\DataAccessSnakFormatterFactory
 */
class DataAccessSnakFormatterOutputFormatTest extends MediaWikiIntegrationTestCase {

	/**
	 * Makes sure WikibaseClient uses our ClientStore mock
	 */
	protected function setUp(): void {
		parent::setUp();

		$store = new MockClientStore( 'de' );
		$this->setService( 'WikibaseClient.Store', $store );

		// Create a term lookup from the overwritten EntityLookup or the MockClientStore one
		$this->setService( 'WikibaseClient.TermLookup',
			new EntityRetrievingTermLookup( $store->getEntityLookup() ) );

		$settings = WikibaseClient::getSettings();
		$siteId = $settings->getSetting( 'siteGlobalID' );

		$settings->setSetting( 'geoShapeStorageBaseUrl', 'https://media.something/view/' );
		$settings->setSetting( 'tabularDataStorageBaseUrl', 'https://tabular.data/view/' );
		$settings->setSetting( 'useKartographerMaplinkInWikitext', false );
		$this->setUpDummyData( $store, $siteId );
	}

	private function setUpDummyData( MockClientStore $store, $siteId ) {
		$mockRepository = $store->getEntityRevisionLookup();
		$dataTypeIds = [
			'P1' => 'commonsMedia',
			'P2' => 'globe-coordinate',
			'P3' => 'monolingualtext',
			'P4' => 'quantity',
			'P5' => 'string',
			'P6' => 'time',
			'P7' => 'url',
			'P8' => 'external-id',
			'P9' => 'wikibase-item',
			'P10' => 'external-id', // with formatter
			'P11' => 'geo-shape',
			'P12' => 'tabular-data',
		];

		foreach ( $dataTypeIds as $id => $dataTypeId ) {
			$property = Property::newFromType( $dataTypeId );
			$property->setId( new NumericPropertyId( $id ) );

			$mockRepository->putEntity( $property );
		}

		// Add a formatter URL for P10
		$p10 = new NumericPropertyId( 'P10' );
		$propertyInfo = [
			PropertyInfoLookup::KEY_DATA_TYPE => 'external-id',
			PropertyInfoLookup::KEY_FORMATTER_URL => 'https://dataAccessSnakFormatterOutputFormatTest/P10/$1',
		];

		$propertyInfoLookup = new MockPropertyInfoLookup( [
			$p10->getSerialization() => $propertyInfo,
		] );
		$store->setPropertyInfoLookup( $propertyInfoLookup );

		$item = new Item( new ItemId( 'Q12' ) );
		$item->setLabel( 'en', 'label [[with]] wikitext' );

		$mockRepository->putEntity( $item );

		$item = new Item( new ItemId( 'Q13' ) );
		$item->setLabel( 'en', 'This item has a sitelink' );
		$item->getSiteLinkList()->addNewSiteLink( $siteId, 'Linked page' );

		$mockRepository->putEntity( $item );
	}

	private function getGlobalConceptBaseUriForUnits(): string {
		$itemSource = WikibaseClient::getItemSource();
		return $itemSource->getConceptBaseUri();
	}

	/**
	 * Snaks which are formatted the same in the wikitext escaped plain text
	 * and in the rich wikitext formatting.
	 *
	 * @return array[]
	 */
	private function getGenericSnaks() {
		$conceptBaseUriForUnits = $this->getGlobalConceptBaseUriForUnits();

		$p4 = new NumericPropertyId( 'P4' );
		$sampleUrl = 'https://www.wikidata.org/w/index.php?title=Q2013&action=history';

		return [
			'globecoordinate' => [
				'12°0&#39;0&#34;N, 34°0&#39;0&#34;E',
				new PropertyValueSnak(
					new NumericPropertyId( 'P2' ),
					new GlobeCoordinateValue( new LatLongValue( 12, 34 ), null )
				),
			],
			'quantity' => [
				'42±0 a &#91;&#91;b&#93;&#93; c',
				new PropertyValueSnak(
					$p4,
					new QuantityValue(
						new DecimalValue( 42 ),
						'a [[b]] c',
						new DecimalValue( 42 ),
						new DecimalValue( 42 )
					)
				),
			],
			'quantity with unit' => [
				'42±0 label &#91;&#91;with&#93;&#93; wikitext',
				new PropertyValueSnak(
					$p4,
					new QuantityValue(
						new DecimalValue( 42 ),
						$conceptBaseUriForUnits . 'Q12',
						new DecimalValue( 42 ),
						new DecimalValue( 42 )
					)
				),
			],
			'string including wikitext' => [
				'a &#91;&#91;b&#93;&#93; c',
				new PropertyValueSnak(
					new NumericPropertyId( 'P5' ),
					new StringValue( 'a [[b]] c' )
				),
			],
			'time with PRECISION_SECOND' => [
				'+2013-01-01T00:00:00Z',
				new PropertyValueSnak(
					new NumericPropertyId( 'P6' ),
					new TimeValue(
						'+2013-01-01T00:00:00Z',
						0, 0, 0,
						TimeValue::PRECISION_SECOND,
						TimeValue::CALENDAR_GREGORIAN
					)
				),
			],
			'time with PRECISION DAY' => [
				'1 January 2013',
				new PropertyValueSnak(
					new NumericPropertyId( 'P6' ),
					new TimeValue(
						'+2013-01-01T00:00:00Z',
						0, 0, 0,
						TimeValue::PRECISION_DAY,
						TimeValue::CALENDAR_GREGORIAN
					)
				),
			],
			'url' => [
				$sampleUrl,
				new PropertyValueSnak(
					new NumericPropertyId( 'P7' ),
					new StringValue( $sampleUrl )
				),
			],
			'external-id' => [
				'abc',
				new PropertyValueSnak(
					new NumericPropertyId( 'P8' ),
					new StringValue( 'abc' )
				),
			],
			'external-id including wikitext' => [
				'a &#91;&#91;b&#93;&#93; c',
				new PropertyValueSnak(
					new NumericPropertyId( 'P8' ),
					new StringValue( 'a [[b]] c' )
				),
			],
			'wikibase-entityid without sitelink' => [
				'label &#91;&#91;with&#93;&#93; wikitext',
				new PropertyValueSnak(
					new NumericPropertyId( 'P9' ),
					new EntityIdValue( new ItemId( 'Q12' ) )
				),
			],
			'novalue' => [
				'no value',
				new PropertyNoValueSnak( $p4 ),
			],
			'somevalue' => [
				'unknown value',
				new PropertySomeValueSnak( $p4 ),
			],
		];
	}

	/**
	 * @dataProvider richWikitextSnakProvider
	 */
	public function testRichWikitextOutput( $expected, $snak ) {
		// This is an integration test, use the global factory
		$factory = WikibaseClient::getDataAccessSnakFormatterFactory();
		$formatter = $factory->newWikitextSnakFormatter(
			$this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' ),
			$this->createMock( UsageAccumulator::class ),
			DataAccessSnakFormatterFactory::TYPE_RICH_WIKITEXT
		);

		$this->assertSame( $expected, $formatter->formatSnak( $snak ) );
	}

	public function richWikitextSnakProvider() {
		$genericSnaks = $this->getGenericSnaks();
		$namespacedFileName = Title::makeTitle( NS_FILE, 'A_file name.jpg' )->getPrefixedText();

		$cases = [
			'monolingualtext' => [
				'<span><span lang="es">a &#91;&#91;b&#93;&#93; c</span></span>',
				new PropertyValueSnak(
					new NumericPropertyId( 'P3' ),
					new MonolingualTextValue( 'es', 'a [[b]] c' )
				),
			],
			'commonsMedia' => [
				'<span>[[' . $namespacedFileName . '|frameless]]</span>',
				new PropertyValueSnak(
					new NumericPropertyId( 'P1' ),
					new StringValue( 'A_file name.jpg' )
				),
			],
			'external-id with formatter url' => [
				'<span>[https://dataAccessSnakFormatterOutputFormatTest/P10/a%20b%20c a b c]</span>',
				new PropertyValueSnak(
					new NumericPropertyId( 'P10' ),
					new StringValue( 'a b c' )
				),
			],
			'wikibase-entityid with sitelink' => [
				'<span>[[Linked page|This item has a sitelink]]</span>',
				new PropertyValueSnak(
					new NumericPropertyId( 'P9' ),
					new EntityIdValue( new ItemId( 'Q13' ) )
				),
			],
			'geo-shape' => [
				'<span>[https://media.something/view/April_2017 April 2017]</span>',
				new PropertyValueSnak(
					new NumericPropertyId( 'P11' ),
					new StringValue( 'April 2017' )
				),
			],
			'tabular-data' => [
				'<span>[https://tabular.data/view/In_data_we_trust In data we trust]</span>',
				new PropertyValueSnak(
					new NumericPropertyId( 'P12' ),
					new StringValue( 'In data we trust' )
				),
			],
		];

		foreach ( $genericSnaks as $testName => $case ) {
			// This output is always wrapped in spans.
			$case[0] = '<span>' . $case[0] . '</span>';
			$cases[$testName] = $case;
		}

		return $cases;
	}

	/**
	 * @dataProvider escapedPlainTextSnakProvider
	 */
	public function testEscapedPlainTextOutput( $expected, $snak ) {
		// This is an integration test, use the global factory
		$factory = WikibaseClient::getDataAccessSnakFormatterFactory();
		$formatter = $factory->newWikitextSnakFormatter(
			$this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' ),
			$this->createMock( UsageAccumulator::class )
		);

		$this->assertSame( $expected, $formatter->formatSnak( $snak ) );
	}

	public function escapedPlainTextSnakProvider() {
		$cases = [
			'monolingualtext' => [
				'a &#91;&#91;b&#93;&#93; c',
				new PropertyValueSnak(
					new NumericPropertyId( 'P3' ),
					new MonolingualTextValue( 'es', 'a [[b]] c' )
				),
			],
			'commonsMedia' => [
				'A_file name.jpg',
				new PropertyValueSnak(
					new NumericPropertyId( 'P1' ),
					new StringValue( 'A_file name.jpg' )
				),
			],
			'external-id with formatter URL' => [
				'a b c',
				new PropertyValueSnak(
					new NumericPropertyId( 'P10' ),
					new StringValue( 'a b c' )
				),
			],
			'wikibase-entityid with sitelink' => [
				'This item has a sitelink',
				new PropertyValueSnak(
					new NumericPropertyId( 'P9' ),
					new EntityIdValue( new ItemId( 'Q13' ) )
				),
			],
			'geo-shape' => [
				'April 2017',
				new PropertyValueSnak(
					new NumericPropertyId( 'P11' ),
					new StringValue( 'April 2017' )
				),
			],
			'tabular-data' => [
				'In data we trust',
				new PropertyValueSnak(
					new NumericPropertyId( 'P12' ),
					new StringValue( 'In data we trust' )
				),
			],
		];

		return $cases + $this->getGenericSnaks();
	}

}
