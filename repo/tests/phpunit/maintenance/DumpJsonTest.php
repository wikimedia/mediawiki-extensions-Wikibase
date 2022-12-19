<?php

namespace Wikibase\Repo\Tests\Maintenance;

use DataValues\StringValue;
use MediaWikiIntegrationTestCase;
use Title;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Services\Entity\NullEntityPrefetcher;
use Wikibase\DataModel\Services\EntityId\EntityIdPager;
use Wikibase\DataModel\Services\EntityId\InMemoryEntityIdPager;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Tests\MockRepository;
use Wikibase\Repo\Maintenance\DumpJson;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Store\Sql\SqlEntityIdPagerFactory;
use Wikibase\Repo\WikibaseRepo;

// files in maintenance/ are not autoloaded to avoid accidental usage, so load explicitly
require_once __DIR__ . '/../../../maintenance/dumpJson.php';

/**
 * @covers \Wikibase\Repo\Maintenance\DumpJson
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class DumpJsonTest extends MediaWikiIntegrationTestCase {

	private function getDumpJson() {
		$dumpScript = new DumpJson();

		$mockRepo = new MockRepository();
		$mockEntityIdPager = new InMemoryEntityIdPager();

		$snakList = new SnakList();
		$snakList->addSnak( new PropertySomeValueSnak( new NumericPropertyId( 'P12' ) ) );
		$snakList->addSnak( new PropertyValueSnak( new NumericPropertyId( 'P12' ), new StringValue( 'stringVal' ) ) );
		/** @var EntityDocument[] $testEntities */
		$testEntities = [
			new Item( new ItemId( 'Q1' ) ),
			new Property( new NumericPropertyId( 'P1' ), null, 'string' ),
			new Property(
				new NumericPropertyId( 'P12' ),
				null,
				'string',
				new StatementList(
					new Statement(
						// P999 is non existent thus the datatype will not be present
						new PropertySomeValueSnak( new NumericPropertyId( 'P999' ) ),
						null,
						null,
						'GUID1'
					)
				)
			),
			new Item(
				new ItemId( 'Q2' ),
				new Fingerprint(
					new TermList( [
						new Term( 'en', 'en-label' ),
						new Term( 'de', 'de-label' ),
					] ),
					new TermList( [
						new Term( 'fr', 'en-desc' ),
						new Term( 'de', 'de-desc' ),
					] ),
					new AliasGroupList( [
						new AliasGroup( 'en', [ 'ali1', 'ali2' ] ),
						new AliasGroup( 'dv', [ 'ali11', 'ali22' ] ),
					] )
				),
				new SiteLinkList( [
					new SiteLink( 'enwiki', 'Berlin' ),
					new SiteLink( 'dewiki', 'England', [ new ItemId( 'Q1' ) ] ),
				] ),
				new StatementList(
					new Statement(
						new PropertySomeValueSnak( new NumericPropertyId( 'P12' ) ),
						null,
						null,
						'GUID1'
					),
					new Statement(
						new PropertySomeValueSnak( new NumericPropertyId( 'P12' ) ),
						$snakList,
						new ReferenceList( [
							new Reference( [
								new PropertyValueSnak( new NumericPropertyId( 'P12' ), new StringValue( 'refSnakVal' ) ),
								new PropertyNoValueSnak( new NumericPropertyId( 'P12' ) ),
							] ),
						] ),
						'GUID2'
					)
				)
			),
		];

		foreach ( $testEntities as $testEntity ) {
			$mockRepo->putEntity( $testEntity, 0, '20201111070707' );
			$mockEntityIdPager->addEntityId( $testEntity->getId() );
		}

		$sqlEntityIdPagerFactory = $this->createMock( SqlEntityIdPagerFactory::class );
		$sqlEntityIdPagerFactory->expects( $this->once() )
			->method( 'newSqlEntityIdPager' )
			->with( [ 'item', 'property' ], EntityIdPager::NO_REDIRECTS )
			->willReturn( $mockEntityIdPager );

		$dumpScript->setServices(
			$sqlEntityIdPagerFactory,
			[ 'item', 'property' ],
			new NullEntityPrefetcher(),
			$this->getMockPropertyDataTypeLookup(),
			$mockRepo,
			WikibaseRepo::getCompactEntitySerializer(),
			WikibaseRepo::getEntityIdParser(),
			$this->getMockEntityTitleStoreLookup()
		);

		return $dumpScript;
	}

	public function dumpParameterProvider() {
		return [
			'dump everything' => [
				[],
				__DIR__ . '/../data/maintenance/dumpJson-log.txt',
				__DIR__ . '/../data/maintenance/dumpJson-out.txt',
			],
			'dump with limit 2' => [
				[
					'limit' => 2,
				],
				__DIR__ . '/../data/maintenance/dumpJson-limit2-log.txt',
				__DIR__ . '/../data/maintenance/dumpJson-limit2-out.txt',
			],
			'dump with limit 2 and page metadata' => [
				[
					'limit' => 2,
					'page-metadata' => true,
				],
				__DIR__ . '/../data/maintenance/dumpJson-limit2-pagemetadata-log.txt',
				__DIR__ . '/../data/maintenance/dumpJson-limit2-pagemetadata-out.txt',
			],
		];
	}

	/**
	 * @dataProvider dumpParameterProvider
	 */
	public function testScript( array $opts, $expectedLogFile, $expectedOutFile ) {
		$dumpScript = $this->getDumpJson();

		$logFileName = tempnam( sys_get_temp_dir(), "Wikibase-DumpJsonTest" );
		$outFileName = tempnam( sys_get_temp_dir(), "Wikibase-DumpJsonTest" );

		$opts = $opts + [ 'log' => $logFileName, 'output' => $outFileName ];
		$dumpScript->loadParamsAndArgs( null, $opts );

		$dumpScript->execute();

		$expectedLog = file_get_contents( $expectedLogFile );
		$expectedOut = file_get_contents( $expectedOutFile );

		$this->assertEquals(
			$this->fixLineEndings( $expectedLog ),
			$this->fixLineEndings( file_get_contents( $logFileName ) )
		);
		$this->assertEquals(
			$this->fixLineEndings( $expectedOut ),
			$this->fixLineEndings( file_get_contents( $outFileName ) )
		);
	}

	/**
	 * @return PropertyDataTypeLookup
	 */
	private function getMockPropertyDataTypeLookup() {
		$mockDataTypeLookup = $this->createMock( PropertyDataTypeLookup::class );
		$mockDataTypeLookup->method( 'getDataTypeIdForProperty' )
			->willReturnCallback( function( PropertyId $id ) {
				if ( $id->getSerialization() === 'P999' ) {
					throw new PropertyDataTypeLookupException( $id );
				}
				return 'DtIdFor_' . $id->getSerialization();
			} );
		return $mockDataTypeLookup;
	}

	/**
	 * @return EntityTitleStoreLookup
	 */
	private function getMockEntityTitleStoreLookup() {
		$entityTitleStoreLookup = $this->createMock( EntityTitleStoreLookup::class );
		$entityTitleStoreLookup->method( 'getTitleForId' )
			->willReturn( $this->getMockTitle() );

		return $entityTitleStoreLookup;
	}

	/**
	 * @return Title
	 */
	public function getMockTitle() {
		$mock = $this->createMock( Title::class );
		$mock->method( 'getArticleID' )
			->willReturn( 42 );
		$mock->method( 'getNamespace' )
			->willReturn( 0 );
		$mock->method( 'getPrefixedText' )
			->willReturn( 'Prefixed:Title' );

		return $mock;
	}

	private function fixLineEndings( $string ) {
		return preg_replace( '~(*BSR_ANYCRLF)\R~', "\n", $string );
	}

}
