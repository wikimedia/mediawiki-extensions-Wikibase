<?php

namespace Wikibase\Test;

use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use MediaWikiTestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\Entity\NullEntityPrefetcher;
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
use Wikibase\DumpJson;
use Wikibase\Lib\Tests\MockRepository;
use Wikibase\DataModel\Services\EntityId\EntityIdPager;
use Wikibase\Repo\Store\Sql\SqlEntityIdPagerFactory;
use Wikibase\Repo\Tests\Store\MockEntityIdPager;

/**
 * @covers Wikibase\DumpJson
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class DumpJsonTest extends MediaWikiTestCase {

	public function testScript() {
		$dumpScript = new DumpJson();

		$mockRepo = new MockRepository();
		$mockEntityIdPager = new MockEntityIdPager();

		$snakList = new SnakList();
		$snakList->addSnak( new PropertySomeValueSnak( new PropertyId( 'P12' ) ) );
		$snakList->addSnak( new PropertyValueSnak( new PropertyId( 'P12' ), new StringValue( 'stringVal' ) ) );
		/** @var EntityDocument[] $testEntities */
		$testEntities = [
			new Item( new ItemId( 'Q1' ) ),
			new Property( new PropertyId( 'P1' ), null, 'string' ),
			new Property(
				new PropertyId( 'P12' ),
				null,
				'string',
				new StatementList( [
					new Statement(
						// P999 is non existent thus the datatype will not be present
						new PropertySomeValueSnak( new PropertyId( 'P999' ) ),
						null,
						null,
						'GUID1'
					)
				] )
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
						new AliasGroup( 'dv', [ 'ali11', 'ali22' ] )
					] )
				),
				new SiteLinkList( [
					new SiteLink( 'enwiki', 'Berlin' ),
					new SiteLink( 'dewiki', 'England', [ new ItemId( 'Q1' ) ] )
				] ),
				new StatementList( [
					new Statement(
						new PropertySomeValueSnak( new PropertyId( 'P12' ) ),
						null,
						null,
						'GUID1'
					),
					new Statement(
						new PropertySomeValueSnak( new PropertyId( 'P12' ) ),
						$snakList,
						new ReferenceList( [
							new Reference( [
								new PropertyValueSnak( new PropertyId( 'P12' ), new StringValue( 'refSnakVal' ) ),
								new PropertyNoValueSnak( new PropertyId( 'P12' ) ),
							] ),
						] ),
						'GUID2'
					)
				] )
			)
		];

		foreach ( $testEntities as $testEntity ) {
			$mockRepo->putEntity( $testEntity );
			$mockEntityIdPager->addEntityId( $testEntity->getId() );
		}

		$serializerFactory = new SerializerFactory(
			new DataValueSerializer(),
			SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH +
			SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH
		);

		$sqlEntityIdPagerFactory = $this->getMockBuilder( SqlEntityIdPagerFactory::class )
			->disableOriginalConstructor()
			->getMock();
		$sqlEntityIdPagerFactory->expects( $this->once() )
			->method( 'newSqlEntityIdPager' )
			->with( null, EntityIdPager::NO_REDIRECTS )
			->will( $this->returnValue( $mockEntityIdPager ) );

		$dumpScript->setServices(
			$sqlEntityIdPagerFactory,
			new NullEntityPrefetcher(),
			$this->getMockPropertyDataTypeLookup(),
			$mockRepo,
			$serializerFactory->newEntitySerializer()
		);

		$logFileName = tempnam( sys_get_temp_dir(), "Wikibase-DumpJsonTest" );
		$outFileName = tempnam( sys_get_temp_dir(), "Wikibase-DumpJsonTest" );

		$dumpScript->loadParamsAndArgs(
			null,
			[
				'log' => $logFileName,
				'output' => $outFileName,
			]
		);

		$dumpScript->execute();

		$expectedLog = file_get_contents( __DIR__ . '/../data/maintenance/dumpJson-log.txt' );
		$expectedOut = file_get_contents( __DIR__ . '/../data/maintenance/dumpJson-out.txt' );

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
		$mockDataTypeLookup = $this->getMock( PropertyDataTypeLookup::class );
		$mockDataTypeLookup->expects( $this->any() )
			->method( 'getDataTypeIdForProperty' )
			->will( $this->returnCallback( function( PropertyId $id ) {
				if ( $id->getSerialization() === 'P999' ) {
					throw new PropertyDataTypeLookupException( $id );
				}
				return 'DtIdFor_' . $id->getSerialization();
			} ) );
		return $mockDataTypeLookup;
	}

	private function fixLineEndings( $string ) {
		return preg_replace( '~(*BSR_ANYCRLF)\R~', "\n", $string );
	}

}
