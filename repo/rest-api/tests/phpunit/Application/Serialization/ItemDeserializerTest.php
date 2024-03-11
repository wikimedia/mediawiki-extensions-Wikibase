<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Serialization;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\ItemDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyValuePairDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\ReferenceDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinksDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementDeserializer;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\DummyItemRevisionMetaDataRetriever;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\SameTitleSitelinkTargetResolver;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Serialization\ItemDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemDeserializerTest extends TestCase {

	public function testDeserialize(): void {
		$itemSerialization = [
			'id' => 'Q123',
			'labels' => [ 'en' => 'english-label' ],
			'descriptions' => [ 'en' => 'english-description' ],
			'aliases' => [ 'en' => [ 'en-alias-1', 'en-alias-2' ] ],
			'sitelinks' => [ 'somewiki' => [ 'title' => 'test-title' ] ],
			'statements' => [
				[
					'property' => [ 'id' => 'P123' ],
					'value' => [ 'type' => 'somevalue' ],
				],
				[
					'property' => [ 'id' => 'P321' ],
					'value' => [ 'type' => 'somevalue' ],
				],
			],
		];

		$this->assertEquals(
			new Item(
				new ItemId( 'Q123' ),
				new Fingerprint(
					new TermList( [ new Term( 'en', 'english-label' ) ] ),
					new TermList( [ new Term( 'en', 'english-description' ) ] ),
					new AliasGroupList( [ new AliasGroup( 'en', [ 'en-alias-1', 'en-alias-2' ] ) ] )
				),
				new SiteLinkList( [ new SiteLink( 'somewiki', 'test-title' ) ] ),
				new StatementList(
					NewStatement::someValueFor( 'P123' )->build(),
					NewStatement::someValueFor( 'P321' )->build()
				)
			),
			$this->newDeserializer()->deserialize( $itemSerialization )
		);
	}

	public function testDeserializeEmptySerialization(): void {
		$this->assertEquals( new Item(), $this->newDeserializer()->deserialize( [] ) );
	}

	private function newDeserializer(): ItemDeserializer {
		$propValPairDeserializer = $this->createStub( PropertyValuePairDeserializer::class );
		$propValPairDeserializer->method( 'deserialize' )
			->willReturnCallback( fn( array $p ) => new PropertySomeValueSnak( new NumericPropertyId( $p['property']['id'] ) ) );

		$referenceDeserializer = $this->createStub( ReferenceDeserializer::class );

		return new ItemDeserializer(
			new LabelsDeserializer(),
			new DescriptionsDeserializer(),
			new AliasesDeserializer(),
			new SitelinksDeserializer(
				new SitelinkDeserializer(
					'/\?/',
					[ 'Q123' ],
					new SameTitleSitelinkTargetResolver(),
					new DummyItemRevisionMetaDataRetriever()
				)
			),
			new StatementDeserializer( $propValPairDeserializer, $referenceDeserializer )
		);
	}

}
