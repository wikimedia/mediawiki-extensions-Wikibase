<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Wikibase\DataModel\Deserializers\ItemDeserializer;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * @covers Wikibase\DataModel\Deserializers\ItemDeserializer
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ItemDeserializerTest extends DispatchableDeserializerTest {

	protected function buildDeserializer() {
		$entityIdDeserializerMock = $this->createMock( Deserializer::class );
		$entityIdDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( 'Q42' ) )
			->will( $this->returnValue( new ItemId( 'Q42' ) ) );

		$termListDeserializerMock = $this->createMock( Deserializer::class );
		$termListDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( [
				'en' => [
					'lang' => 'en',
					'value' => 'foo',
				],
			] ) )
			->will( $this->returnValue( new TermList( [ new Term( 'en', 'foo' ) ] ) ) );

		$aliasGroupListDeserializerMock = $this->createMock( Deserializer::class );
		$aliasGroupListDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( [
				'en' => [
					'lang' => 'en',
					'values' => [ 'foo', 'bar' ],
				],
			] ) )
			->will( $this->returnValue(
				new AliasGroupList( [ new AliasGroup( 'en', [ 'foo', 'bar' ] ) ] ) )
			);

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'test' );

		$statementListDeserializerMock = $this->createMock( Deserializer::class );
		$statementListDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( [
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
			] ) )
			->will( $this->returnValue( new StatementList( $statement ) ) );

		$siteLinkDeserializerMock = $this->createMock( Deserializer::class );
		$siteLinkDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( [
				'site' => 'enwiki',
				'title' => 'Nyan Cat',
				'badges' => [],
			] ) )
			->will( $this->returnValue( new SiteLink( 'enwiki', 'Nyan Cat' ) ) );

		return new ItemDeserializer(
			$entityIdDeserializerMock,
			$termListDeserializerMock,
			$aliasGroupListDeserializerMock,
			$statementListDeserializerMock,
			$siteLinkDeserializerMock
		);
	}

	public function deserializableProvider() {
		return [
			[
				[
					'type' => 'item',
				],
			],
		];
	}

	public function nonDeserializableProvider() {
		return [
			[
				5,
			],
			[
				[],
			],
			[
				[
					'type' => 'property',
				],
			],
		];
	}

	public function deserializationProvider() {
		$provider = [
			[
				new Item(),
				[
					'type' => 'item',
				],
			],
		];

		$item = new Item( new ItemId( 'Q42' ) );
		$provider[] = [
			$item,
			[
				'type' => 'item',
				'id' => 'Q42',
			],
		];

		$item = new Item();
		$item->setLabel( 'en', 'foo' );
		$provider[] = [
			$item,
			[
				'type' => 'item',
				'labels' => [
					'en' => [
						'lang' => 'en',
						'value' => 'foo',
					],
				],
			],
		];

		$item = new Item();
		$item->setDescription( 'en', 'foo' );
		$provider[] = [
			$item,
			[
				'type' => 'item',
				'descriptions' => [
					'en' => [
						'lang' => 'en',
						'value' => 'foo',
					],
				],
			],
		];

		$item = new Item();
		$item->setAliases( 'en', [ 'foo', 'bar' ] );
		$provider[] = [
			$item,
			[
				'type' => 'item',
				'aliases' => [
					'en' => [
						'lang' => 'en',
						'values' => [ 'foo', 'bar' ],
					],
				],
			],
		];

		$item = new Item();
		$item->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ), null, null, 'test' );
		$provider[] = [
			$item,
			[
				'type' => 'item',
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
			],
		];

		$item = new Item();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Nyan Cat' );
		$provider[] = [
			$item,
			[
				'type' => 'item',
				'sitelinks' => [
					'enwiki' => [
						'site' => 'enwiki',
						'title' => 'Nyan Cat',
						'badges' => [],
					],
				],
			],
		];

		return $provider;
	}

}
