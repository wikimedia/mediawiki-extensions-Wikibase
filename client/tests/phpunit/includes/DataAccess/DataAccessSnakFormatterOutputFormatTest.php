<?php

namespace Wikibase\Client\Tests\DataAccess;

use Language;
use DataValues\DecimalValue;
use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\Geo\Values\LatLongValue;
use DataValues\MonolingualTextValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use PHPUnit_Framework_TestCase;
use Title;
use Wikibase\Client\WikibaseClient;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Services\Lookup\EntityRetrievingTermLookup;
use Wikibase\PropertyInfoStore;
use Wikibase\Test\MockClientStore;

/**
 * Regression tests for the output produced by data access functionality.
 * Technically this tests the SnakFormatters outputted by DataAccessSnakFormatterFactory.
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseDataAccess
 *
 * @license GPL-2.0+
 * @author Marius Hoch
 */
class DataAccessSnakFormatterOutputFormatTest extends PHPUnit_Framework_TestCase {

	/**
	 * Makes sure WikibaseClient uses our ClientStore mock
	 */
	public function setUp() {
		$wikibaseClient = WikibaseClient::getDefaultInstance( 'reset' );

		$store = new MockClientStore( 'de' );
		$wikibaseClient->overrideStore( $store );

		// Create a term lookup from the ovewritten EntityLookup or the MockClientStore one
		$wikibaseClient->overrideTermLookup(
			new EntityRetrievingTermLookup( $store->getEntityLookup() )
		);

		$siteId = $wikibaseClient->getSettings()->getSetting( 'siteGlobalID' );
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
		];

		foreach ( $dataTypeIds as $id => $dataTypeId ) {
			$property = Property::newFromType( $dataTypeId );
			$property->setId( new PropertyId( $id ) );

			$mockRepository->putEntity( $property );
		}

		// Add a formatter URL for P10
		$p10 = new PropertyId( 'P10' );
		$propertyInfo = [
			PropertyInfoStore::KEY_DATA_TYPE => 'external-id',
			PropertyInfoStore::KEY_FORMATTER_URL => 'https://dataAccessSnakFormatterOutputFormatTest/P10/$1'
		];
		$propertyInfoStore = $store->getPropertyInfoStore();
		$propertyInfoStore->setPropertyInfo(
			$p10,
			$propertyInfo
		);

		$item = new Item( new ItemId( 'Q12' ) );
		$item->setLabel( 'en', 'label [[with]] wikitext' );

		$mockRepository->putEntity( $item );

		$item = new Item( new ItemId( 'Q13' ) );
		$item->setLabel( 'en', 'This item has a sitelink' );
		$item->getSiteLinkList()->addNewSiteLink( $siteId, 'Linked page' );

		$mockRepository->putEntity( $item );
	}

	public function tearDown() {
		WikibaseClient::getDefaultInstance( 'reset' );
	}

	/**
	 * @dataProvider richWikitextSnakProvider
	 */
	public function testRichWikitextOutput( $expected, $snak ) {
		// This is an integration test, use the global factory
		$factory = WikibaseClient::getDefaultInstance()->getDataAccessSnakFormatterFactory();
		$formatter = $factory->newSnakFormatter(
			'rich-wikitext',
			Language::factory( 'en' ),
			$this->getMock( UsageAccumulator::class )
		);

		$this->assertSame( $expected, $formatter->formatSnak( $snak ) );
	}

	public function richWikitextSnakProvider() {
		$namespacedFileName = Title::newFromText( 'A_file name.jpg', NS_FILE )->getPrefixedText();

		return [
			'commonsMedia' => [
				'<span>[[' . $namespacedFileName . '|frameless]]</span>',
				new PropertyValueSnak(
					new PropertyId( 'P1' ),
					new StringValue( 'A_file name.jpg' )
				)
			],
			'string' => [
				'<span>A string!</span>',
				new PropertyValueSnak(
					new PropertyId( 'P5' ),
					new StringValue( 'A string!' )
				)
			],
			'external-id' => [
				'<span>An identifier</span>',
				new PropertyValueSnak(
					new PropertyId( 'P8' ),
					new StringValue( 'An identifier' )
				)
			],
			'external-id with formatter url' => [
				'<span>[https://dataAccessSnakFormatterOutputFormatTest/P10/a+b+c a b c]</span>',
				new PropertyValueSnak(
					new PropertyId( 'P10' ),
					new StringValue( 'a b c' )
				)
			],
			'wikibase-entityid without sitelink' => [
				'<span>label &#91;&#91;with&#93;&#93; wikitext</span>',
				new PropertyValueSnak(
					new PropertyId( 'P9' ),
					new EntityIdValue( new ItemId( 'Q12' ) )
				)
			],
			'wikibase-entityid with sitelink' => [
				'<span>[[Linked page|This item has a sitelink]]</span>',
				new PropertyValueSnak(
					new PropertyId( 'P9' ),
					new EntityIdValue( new ItemId( 'Q13' ) )
				)
			],
		];
	}

	/**
	 * @dataProvider escapedPlainTextSnakProvider
	 */
	public function testEscapedPlainTextOutput( $expected, $snak ) {
		// This is an integration test, use the global factory
		$factory = WikibaseClient::getDefaultInstance()->getDataAccessSnakFormatterFactory();
		$formatter = $factory->newSnakFormatter(
			'plain-text',
			Language::factory( 'en' ),
			$this->getMock( UsageAccumulator::class )
		);

		$this->assertSame( $expected, $formatter->formatSnak( $snak ) );
	}

	public function escapedPlainTextSnakProvider() {
		$settings = WikibaseClient::getDefaultInstance()->getSettings();
		$repoConceptBaseUri = $settings->getSetting( 'repoConceptBaseUri' );

		$p4 = new PropertyId( 'P4' );
		$sampleUrl = 'https://www.wikidata.org/w/index.php?title=Q2013&action=history';

		return [
			'commonsMedia' => [
				'A_file name.jpg',
				new PropertyValueSnak(
					new PropertyId( 'P1' ),
					new StringValue( 'A_file name.jpg' )
				)
			],
			'globecoordinate' => [
				'12°0&#39;0&#34;N, 34°0&#39;0&#34;E',
				new PropertyValueSnak(
					new PropertyId( 'P2' ),
					new GlobeCoordinateValue( new LatLongValue( 12, 34 ), null )
				)
			],
			'monolingualtext' => [
				'a &#91;&#91;b&#93;&#93; c',
				new PropertyValueSnak(
					new PropertyId( 'P3' ),
					new MonolingualTextValue( 'es', 'a [[b]] c' )
				)
			],
			'quantity' => [
				'42 a &#91;&#91;b&#93;&#93; c',
				new PropertyValueSnak(
					$p4,
					new QuantityValue(
						new DecimalValue( 42 ),
						'a [[b]] c',
						new DecimalValue( 42 ),
						new DecimalValue( 42 )
					)
				)
			],
			'quantity with unit' => [
				'42 label &#91;&#91;with&#93;&#93; wikitext',
				new PropertyValueSnak(
					$p4,
					new QuantityValue(
						new DecimalValue( 42 ),
						$repoConceptBaseUri . 'Q12',
						new DecimalValue( 42 ),
						new DecimalValue( 42 )
					)
				)
			],
			'string including wikitext' => [
				'a &#91;&#91;b&#93;&#93; c',
				new PropertyValueSnak(
					new PropertyId( 'P5' ),
					new StringValue( 'a [[b]] c' )
				)
			],
			'time with PRECISION_SECOND' => [
				'+2013-01-01T00:00:00Z',
				new PropertyValueSnak(
					new PropertyId( 'P6' ),
					new TimeValue(
						'+2013-01-01T00:00:00Z',
						0, 0, 0,
						TimeValue::PRECISION_SECOND,
						TimeValue::CALENDAR_GREGORIAN
					)
				)
			],
			'time with PRECISION DAY' => [
				'1 January 2013',
				new PropertyValueSnak(
					new PropertyId( 'P6' ),
					new TimeValue(
						'+2013-01-01T00:00:00Z',
						0, 0, 0,
						TimeValue::PRECISION_DAY,
						TimeValue::CALENDAR_GREGORIAN
					)
				)
			],
			'url' => [
				$sampleUrl,
				new PropertyValueSnak(
					new PropertyId( 'P7' ),
					new StringValue( $sampleUrl )
				)
			],
			'external-id' => [
				'abc',
				new PropertyValueSnak(
					new PropertyId( 'P8' ),
					new StringValue( 'abc' )
				)
			],
			'external-id including wikitext' => [
				'a &#91;&#91;b&#93;&#93; c',
				new PropertyValueSnak(
					new PropertyId( 'P8' ),
					new StringValue( 'a [[b]] c' )
				)
			],
			'external-id with formatter URL' => [
				'a b c',
				new PropertyValueSnak(
					new PropertyId( 'P10' ),
					new StringValue( 'a b c' )
				)
			],
			'wikibase-entityid without sitelink' => [
				'label &#91;&#91;with&#93;&#93; wikitext',
				new PropertyValueSnak(
					new PropertyId( 'P9' ),
					new EntityIdValue( new ItemId( 'Q12' ) )
				)
			],
			'wikibase-entityid with sitelink' => [
				'This item has a sitelink',
				new PropertyValueSnak(
					new PropertyId( 'P9' ),
					new EntityIdValue( new ItemId( 'Q13' ) )
				)
			],
			'novalue' => [
				'no value',
				new PropertyNoValueSnak( $p4 )
			],
			'somevalue' => [
				'unknown value',
				new PropertySomeValueSnak( $p4 )
			],
		];
	}

}
