<?php

namespace Wikibase\Lib\Tests\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityInfoBuilder;

/**
 * Base class for tests of EntityInfoBuilder implementation.
 * This abstract test case tests conformance to the contract of the EntityInfoBuilder interface.
 *
 * @covers \Wikibase\Lib\Store\EntityInfoBuilder
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
abstract class EntityInfoBuilderTestCase extends \MediaWikiTestCase {

	/**
	 * @return ItemId[]
	 */
	protected function getKnownRedirects() {
		return [
			'Q7' => new ItemId( 'Q2' ),
			'Q12' => new ItemId( 'Q2' ),
			'Q22' => new ItemId( 'Q2' ),
		];
	}

	/**
	 * @return Item[]|Property[]
	 */
	protected function getKnownEntities() {
		$q1 = new Item( new ItemId( 'Q1' ) );
		$q1->setLabel( 'en', 'label:Q1/en' );
		$q1->setLabel( 'de', 'label:Q1/de' );
		$q1->setDescription( 'en', 'description:Q1/en' );
		$q1->setDescription( 'de', 'description:Q1/de' );

		$q2 = new Item( new ItemId( 'Q2' ) );
		$q2->setLabel( 'en', 'label:Q2/en' );
		$q2->setLabel( 'de', 'label:Q2/de' );

		$p2 = Property::newFromType( 'string' );
		$p2->setId( new PropertyId( 'P2' ) );
		$p2->setLabel( 'en', 'label:P2/en' );
		$p2->setLabel( 'de', 'label:P2/de' );
		$p2->setDescription( 'en', 'description:P2/en' );
		$p2->setDescription( 'de', 'description:P2/de' );

		$p3 = Property::newFromType( 'string' );
		$p3->setId( new PropertyId( 'P3' ) );
		$p3->setLabel( 'en', 'label:P3/en' );
		$p3->setLabel( 'de', 'label:P3/de' );
		$p3->setDescription( 'en', 'description:P3/en' );
		$p3->setDescription( 'de', 'description:P3/de' );

		return [ $q1, $q2, $p2, $p3 ];
	}

	/**
	 * @param EntityId[] $ids
	 *
	 * @return EntityInfoBuilder
	 */
	abstract protected function newEntityInfoBuilder( array $ids );

	public function testGivenEmptyIdList_returnsEmptyEntityInfo() {
		$builder = $this->newEntityInfoBuilder( [] );

		$this->assertEmpty( $builder->collectEntityInfo( [], [] )->asArray() );
	}

	public function testGivenIds_returnsEntityInfoWithIdAndType() {
		$ids = [
			new ItemId( 'Q1' ),
			new PropertyId( 'P3' )
		];

		$builder = $this->newEntityInfoBuilder( $ids );

		$info = $builder->collectEntityInfo( $ids, [] )->asArray();

		$this->assertEquals( 'Q1', $info['Q1']['id'] );
		$this->assertEquals( 'item', $info['Q1']['type'] );

		$this->assertEquals( 'P3', $info['P3']['id'] );
		$this->assertEquals( 'property', $info['P3']['type'] );
	}

	public function testGivenDuplicateIds_eachIdsOnlyIncludedOnceInResult() {
		$id = new ItemId( 'Q1' );

		$builder = $this->newEntityInfoBuilder( [ $id ] );

		$info = $builder->collectEntityInfo( [ $id, $id ], [] )->asArray();

		$this->assertCount( 1, array_keys( $info ) );
		$this->assertArrayHasKey( 'Q1', $info );
	}

	public function testGivenEmptyLanguageCodeList_returnsNoLabelsAndDescriptionsInEntityInfo() {
		$id = new ItemId( 'Q1' );

		$builder = $this->newEntityInfoBuilder( [ $id ] );

		$info = $builder->collectEntityInfo( [ $id ], [] )->asArray();

		$this->assertEmpty( $info['Q1']['labels'] );
		$this->assertEmpty( $info['Q1']['descriptions'] );
	}

	public function testGivenLanguageCode_returnsOnlyTermsInTheLanguage() {
		$id = new ItemId( 'Q1' );

		$builder = $this->newEntityInfoBuilder( [ $id ] );

		$info = $builder->collectEntityInfo( [ $id ], [ 'de' ] )->asArray();

		$this->assertEquals( $this->makeLanguageValueRecords( [ 'de' => 'label:Q1/de' ] ), $info['Q1']['labels'] );
		$this->assertEquals(
			$this->makeLanguageValueRecords( [ 'de' => 'description:Q1/de' ] ),
			$info['Q1']['descriptions']
		);
	}

	public function testGivenMultipleLanguageCodes_returnsTermsInTheLanguagesGiven() {
		$id = new ItemId( 'Q1' );

		$builder = $this->newEntityInfoBuilder( [ $id ] );

		$info = $builder->collectEntityInfo( [ $id ], [ 'en', 'de' ] )->asArray();

		$this->assertEquals(
			$this->makeLanguageValueRecords(
				[ 'en' => 'label:Q1/en', 'de' => 'label:Q1/de' ]
			),
			$info['Q1']['labels']
		);
		$this->assertEquals(
			$this->makeLanguageValueRecords(
				[ 'en' => 'description:Q1/en', 'de' => 'description:Q1/de' ]
			),
			$info['Q1']['descriptions']
		);
	}

	public function testGivenRedirect_returnsTargetIdInEntityInfo() {
		$redirectId = new ItemId( 'Q12' );

		$builder = $this->newEntityInfoBuilder( [ $redirectId ] );

		$info = $builder->collectEntityInfo( [ $redirectId ], [] )->asArray();

		$this->assertEquals( 'Q2', $info['Q12']['id'] );
	}

	public function testGivenRedirectId_returnsTermsOfTheTarget() {
		$redirectId = new ItemId( 'Q12' );

		$builder = $this->newEntityInfoBuilder( [ $redirectId ] );

		$info = $builder->collectEntityInfo( [ $redirectId ], [ 'de' ] )->asArray();

		$this->assertEquals( $this->makeLanguageValueRecords( [ 'de' => 'label:Q2/de' ] ), $info['Q12']['labels'] );
	}

	public function testGivenNonExistingIds_nonExistingIdsSkippedInResult() {
		$existingId = new ItemId( 'Q1' );
		$nonExistingId = new ItemId( 'Q1000' );

		$builder = $this->newEntityInfoBuilder( [ $existingId ] );

		$info = $builder->collectEntityInfo( [ $existingId, $nonExistingId ], [] )->asArray();

		$this->assertArrayHasKey( 'Q1', $info );
		$this->assertArrayNotHasKey( 'Q1000', $info );
	}

	/**
	 * Converts a map of the form $language => $value into a ist of records
	 * of the form $language => [ 'language' => $language, 'value' => $value ].
	 *
	 * @param array $map map if the form $language => $value
	 * @param string|null $language For the language for all entries. Useful if $map is a list, not an associative array.
	 *
	 * @return array map if the form $language => [ 'language' => $language, 'value' => $value ]
	 */
	protected function makeLanguageValueRecords( array $map, $language = null ) {
		$records = [];

		foreach ( $map as $key => $value ) {
			if ( $language !== null ) {
				$lang = $language;
			} else {
				$lang = $key;
			}

			if ( is_array( $value ) ) {
				$records[$key] = $this->makeLanguageValueRecords( $value, $lang );
			} else {
				$records[$key] = [
					'language' => $lang,
					'value' => $value
				];
			}
		}

		return $records;
	}

}
