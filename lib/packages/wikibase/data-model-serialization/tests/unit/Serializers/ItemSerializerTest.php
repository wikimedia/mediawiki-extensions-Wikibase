<?php

declare( strict_types = 1 );

namespace Tests\Wikibase\DataModel\Serializers;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Serializers\AliasGroupListSerializer;
use Wikibase\DataModel\Serializers\ItemSerializer;
use Wikibase\DataModel\Serializers\SiteLinkSerializer;
use Wikibase\DataModel\Serializers\StatementListSerializer;
use Wikibase\DataModel\Serializers\TermListSerializer;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\TermList;

/**
 * @covers Wikibase\DataModel\Serializers\ItemSerializer
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 * @author Jan Zerebecki < jan.wikimedia@zerebecki.de >
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ItemSerializerTest extends DispatchableSerializerTestCase {

	protected function buildSerializer( bool $useObjectsForEmptyMaps = false ): ItemSerializer {
		$termListSerializerMock = $this->createMock( TermListSerializer::class );
		$termListSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->willReturnCallback( static function ( TermList $termList ) {
				if ( $termList->isEmpty() ) {
					return [];
				}

				return [
					'en' => [ 'lang' => 'en', 'value' => 'foo' ],
				];
			} );

		$aliasGroupListSerializerMock = $this->createMock( AliasGroupListSerializer::class );
		$aliasGroupListSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->willReturnCallback( static function ( AliasGroupList $aliasGroupList ) {
				if ( $aliasGroupList->isEmpty() ) {
					return [];
				}

				return [
					'en' => [ 'lang' => 'en', 'values' => [ 'foo', 'bar' ] ],
				];
			} );

		$statementListSerializerMock = $this->createMock( StatementListSerializer::class );
		$statementListSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->willReturnCallback( static function ( StatementList $statementList ) use ( $useObjectsForEmptyMaps ) {
				if ( $statementList->isEmpty() ) {
					if ( $useObjectsForEmptyMaps ) {
						return (object)[];
					}
					return [];
				}

				return [
					'P42' => [
						[
							'mainsnak' => [
								'snaktype' => 'novalue',
								'property' => 'P42',
							],
							'type' => 'statement',
							'rank' => 'normal',
						],
					],
				];
			} );

		$siteLinkSerializerMock = $this->createMock( SiteLinkSerializer::class );
		$siteLinkSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->with( new SiteLink( 'enwiki', 'Nyan Cat' ) )
			->willReturn( [
				'site' => 'enwiki',
				'title' => 'Nyan Cat',
				'badges' => [],
			] );

		return new ItemSerializer(
			$termListSerializerMock,
			$aliasGroupListSerializerMock,
			$statementListSerializerMock,
			$siteLinkSerializerMock,
			$useObjectsForEmptyMaps,
		);
	}

	public static function serializableProvider(): array {
		return [
			[ new Item() ],
		];
	}

	public static function nonSerializableProvider(): array {
		return [
			[ 5 ],
			[ [] ],
			[ Property::newFromType( 'string' ) ],
		];
	}

	public static function serializationProvider(): array {
		$provider = [
			[
				[
					'type' => 'item',
					'labels' => [],
					'descriptions' => [],
					'aliases' => [],
					'claims' => [],
					'sitelinks' => [],
				],
				new Item(),
			],
		];

		$entity = new Item( new ItemId( 'Q42' ) );
		$provider[] = [
			[
				'type' => 'item',
				'id' => 'Q42',
				'labels' => [],
				'descriptions' => [],
				'aliases' => [],
				'claims' => [],
				'sitelinks' => [],
			],
			$entity,
		];

		$entity = new Item();
		$entity->setLabel( 'en', 'foo' );
		$provider[] = [
			[
				'type' => 'item',
				'labels' => [
					'en' => [
						'lang' => 'en',
						'value' => 'foo',
					],
				],
				'descriptions' => [],
				'aliases' => [],
				'claims' => [],
				'sitelinks' => [],
			],
			$entity,
		];

		$entity = new Item();
		$entity->setDescription( 'en', 'foo' );
		$provider[] = [
			[
				'type' => 'item',
				'labels' => [],
				'descriptions' => [
					'en' => [
						'lang' => 'en',
						'value' => 'foo',
					],
				],
				'aliases' => [],
				'claims' => [],
				'sitelinks' => [],
			],
			$entity,
		];

		$entity = new Item();
		$entity->setAliases( 'en', [ 'foo', 'bar' ] );
		$provider[] = [
			[
				'type' => 'item',
				'labels' => [],
				'descriptions' => [],
				'aliases' => [
					'en' => [
						'lang' => 'en',
						'values' => [ 'foo', 'bar' ],
					],
				],
				'claims' => [],
				'sitelinks' => [],
			],
			$entity,
		];

		$entity = new Item();
		$entity->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ), null, null, 'test' );
		$provider[] = [
			[
				'type' => 'item',
				'labels' => [],
				'descriptions' => [],
				'aliases' => [],
				'claims' => [
					'P42' => [
						[
							'mainsnak' => [
								'snaktype' => 'novalue',
								'property' => 'P42',
							],
							'type' => 'statement',
							'rank' => 'normal',
						],
					],
				],
				'sitelinks' => [],
			],
			$entity,
		];

		$item = new Item();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Nyan Cat' );
		$provider[] = [
			[
				'type' => 'item',
				'labels' => [],
				'descriptions' => [],
				'aliases' => [],
				'claims' => [],
				'sitelinks' => [
					'enwiki' => [
						'site' => 'enwiki',
						'title' => 'Nyan Cat',
						'badges' => [],
					],
				],
			],
			$item,
		];

		return $provider;
	}

	public function testItemSerializerEmptyMapsSerialization(): void {
		$serializer = $this->buildSerializer( false );

		$item = new Item();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Nyan Cat' );

		$sitelinks = [];
		$sitelinks['enwiki'] = [
			'site' => 'enwiki',
			'title' => 'Nyan Cat',
			'badges' => [],
		];

		$serial = [
			'type' => 'item',
			'labels' => [],
			'descriptions' => [],
			'aliases' => [],
			'claims' => [],
			'sitelinks' => $sitelinks,
		];

		$this->assertEquals( $serial, $serializer->serialize( $item ) );
	}

	public function testItemSerializerUsesObjectsForEmptyMaps(): void {
		$serializer = $this->buildSerializer( true );

		$item = new Item();

		$serial = [
			'type' => 'item',
			'labels' => [],
			'descriptions' => [],
			'aliases' => [],
			'claims' => (object)[],
			'sitelinks' => (object)[],
		];

		$this->assertEquals( $serial, $serializer->serialize( $item ) );
	}
}
