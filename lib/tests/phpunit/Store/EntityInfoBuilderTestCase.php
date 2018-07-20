<?php

namespace Wikibase\Lib\Tests\Store;

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

	const REDIRECT_SOURCE_ID = 'Q12';
	const REDIRECT_TARGET_ID = 'Q2';

	/**
	 * @return ItemId[]
	 */
	protected function getKnownRedirects() {
		return [
			self::REDIRECT_SOURCE_ID => new ItemId( self::REDIRECT_TARGET_ID ),
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
	 * @return EntityInfoBuilder
	 */
	abstract protected function newEntityInfoBuilder();

	public function testGivenEmptyIdList_returnsEmptyEntityInfo() {
		$builder = $this->newEntityInfoBuilder();

		$this->assertEmpty( $builder->collectEntityInfo( [], [] )->asArray() );
	}

	public function testGivenDuplicateIds_eachIdsOnlyIncludedOnceInResult() {
		$id = new ItemId( 'Q1' );

		$builder = $this->newEntityInfoBuilder();

		$info = $builder->collectEntityInfo( [ $id, $id ], [] )->asArray();

		$this->assertCount( 1, array_keys( $info ) );
		$this->assertArrayHasKey( 'Q1', $info );
	}

	public function testGivenEmptyLanguageCodeList_returnsNoLabelsAndDescriptionsInEntityInfo() {
		$id = new ItemId( 'Q1' );

		$builder = $this->newEntityInfoBuilder();

		$info = $builder->collectEntityInfo( [ $id ], [] )->asArray();

		$this->assertEmpty( $info['Q1']['labels'] );
		$this->assertEmpty( $info['Q1']['descriptions'] );
	}

	public function testGivenLanguageCode_returnsOnlyTermsInTheLanguage() {
		$id = new ItemId( 'Q1' );

		$builder = $this->newEntityInfoBuilder();

		$info = $builder->collectEntityInfo( [ $id ], [ 'de' ] )->asArray();

		$this->assertEquals( $this->makeLanguageValueRecords( [ 'de' => 'label:Q1/de' ] ), $info['Q1']['labels'] );
		$this->assertEquals(
			$this->makeLanguageValueRecords( [ 'de' => 'description:Q1/de' ] ),
			$info['Q1']['descriptions']
		);
	}

	public function testGivenMultipleLanguageCodes_returnsTermsInTheLanguagesGiven() {
		$id = new ItemId( 'Q1' );

		$builder = $this->newEntityInfoBuilder();

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

	public function testGivenRedirectId_returnsTermsOfTheTarget() {
		$redirectId = new ItemId( self::REDIRECT_SOURCE_ID );

		$builder = $this->newEntityInfoBuilder();

		$info = $builder->collectEntityInfo( [ $redirectId ], [ 'de' ] )->asArray();

		$this->assertEquals( $this->makeLanguageValueRecords( [ 'de' => 'label:Q2/de' ] ), $info[self::REDIRECT_SOURCE_ID]['labels'] );
	}

	public function testGivenRedirect_entityInfoUsesRedirectSourceAsKey() {
		$redirectId = new ItemId( self::REDIRECT_SOURCE_ID );

		$builder = $this->newEntityInfoBuilder();

		$info = $builder->collectEntityInfo( [ $redirectId ], [] )->asArray();

		$this->assertArrayHasKey( self::REDIRECT_SOURCE_ID, $info );
		$this->assertArrayNotHasKey( self::REDIRECT_TARGET_ID, $info );
	}

	public function testGivenNonExistingIds_nonExistingIdsSkippedInResult() {
		$existingId = new ItemId( 'Q1' );
		$nonExistingId = new ItemId( 'Q1000' );

		$builder = $this->newEntityInfoBuilder();

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
