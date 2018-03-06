<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use InvalidArgumentException;
use MediaWikiTestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Store\Sql\SqlEntitiesWithoutTermFinder;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Store\Sql\SqlEntitiesWithoutTermFinder
 *
 * @group Database
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class SqlEntitiesWithoutTermFinderTest extends MediaWikiTestCase {

	public function setUp() {
		parent::setUp();

		static $setUp = false;
		if ( !$setUp ) {
			$setUp = true;

			$dbw = wfGetDB( DB_MASTER );

			$dbw->delete( 'page', '*' );
			$dbw->delete( 'wb_terms', '*' );

			// Create page row for Q100, ..., Q103 and P100, …, P103
			$pages = [];
			for ( $i = 0; $i < 4; $i++ ) {
				$n = 100 + $i;

				// Make Q101 a redirect
				$isRedirect = ( $n === 101 ) ? 1 : 0;

				$pages[] = $this->getPageRow( 200, $n, 'Q', $isRedirect );
				$pages[] = $this->getPageRow( 400, $n, 'P', false );
			}

			// Wrong namespace (but title is a valid Item id)
			$pages[] = $this->getPageRow( 0, 5, 'Q', false );

			$dbw->insert(
				'page',
				$pages,
				__METHOD__
			);

			$termsRows = [];
			$termsRows[] = $this->getTermRow( 'Q100', 'item', 'en', 'label' );
			$termsRows[] = $this->getTermRow( 'Q100', 'item', 'en', 'description' );
			$termsRows[] = $this->getTermRow( 'Q102', 'item', 'es', 'label' );
			$termsRows[] = $this->getTermRow( 'Q102', 'item', 'en', 'alias' );
			$termsRows[] = $this->getTermRow( 'Q102', 'item', 'es', 'alias' );
			$termsRows[] = $this->getTermRow( 'P102', 'property', 'de', 'description' );
			$termsRows[] = $this->getTermRow( 'P103', 'property', 'de', 'label' );

			$dbw->insert(
				'wb_terms',
				$termsRows,
				__METHOD__
			);
		}
	}

	/**
	 * @dataProvider getEntitiesWithoutTermProvider
	 */
	public function testGetEntitiesWithoutTerm(
		array $expected,
		$termType,
		$language,
		array $entityTypes = null,
		$limit,
		$offset
	) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$finder = new SqlEntitiesWithoutTermFinder(
			$wikibaseRepo->getEntityIdParser(),
			new EntityNamespaceLookup( [
				'item' => 200,
				'property' => 400,
			] ),
			[
				Item::ENTITY_TYPE => 'Q',
				Property::ENTITY_TYPE => 'P'
			]
		);

		$result = $finder->getEntitiesWithoutTerm( $termType, $language, $entityTypes, $limit, $offset );
		$this->assertArrayEquals( $expected, $result, true, true );
	}

	public function getEntitiesWithoutTermProvider() {
		$q103 = new ItemId( 'Q103' );
		$q100 = new ItemId( 'Q100' );
		$p103 = new PropertyId( 'P103' );
		$p102 = new PropertyId( 'P102' );
		$p101 = new PropertyId( 'P101' );
		$p100 = new PropertyId( 'P100' );

		return [
			'Get the newest item without "es" label' => [
				[ $q103 ],
				'label',
				'es',
				[ 'item' ],
				1,
				0
			],
			'Get the next item without "es" label (offset)' => [
				// Q102 has a Spanish label and Q101 is a redirect,
				// thus they're omitted.
				[ $q100 ],
				'label',
				'es',
				[ 'item' ],
				1,
				1
			],
			'Get the newest items+properties without "es" label' => [
				[ $p103, $q103 ],
				'label',
				'es',
				null,
				2,
				0
			],
			'Get the next (and last) items+properties without "es" label (offset)' => [
				// Q102 has a Spanish label and Q101 is a redirect,
				// thus they're omitted.
				[ $p102, $p101, $p100, $q100 ],
				'label',
				'es',
				null,
				40,
				2
			],
			'Get the newest items+properties without "es" label (null is equivalent to ["item", "property"])' => [
				[ $p103, $q103, $p102 ],
				'label',
				'es',
				[ 'property', 'item' ],
				3,
				0
			],
			'Get the newest properties without "de" description' => [
				// P102 has a German description and has thus been omitted.
				[ $p103, $p101, $p100 ],
				'description',
				'de',
				[ 'property' ],
				3,
				0
			],
			'Get the newest properties without a description in any language' => [
				// P102 has a German description and has thus been omitted.
				[ $p103, $p101 ],
				'description',
				null,
				[ 'property' ],
				2,
				0
			],
			'Empty result (large offset)' => [
				[],
				'description',
				null,
				[ 'property' ],
				10,
				100
			],
		];
	}

	/**
	 * @dataProvider unSupportedEntityTypesProvider
	 */
	public function testGetEntitiesWithoutTerm_unSupportedEntityTypes( array $entityTypes ) {
		$this->setExpectedException( InvalidArgumentException::class );

		$finder = new SqlEntitiesWithoutTermFinder(
			new ItemIdParser(),
			new EntityNamespaceLookup( [] ),
			[
				Item::ENTITY_TYPE => 'Q',
				Property::ENTITY_TYPE => 'P'
			]
		);
		$finder->getEntitiesWithoutTerm( 'label', null, $entityTypes );
	}

	public function unSupportedEntityTypesProvider() {
		return [
			[ [ 12 ] ],
			[ [ 'bar' ] ],
			[ [ 'item', null ] ],
		];
	}

	private function getTermRow(
		$fullEntityId,
		$entityType,
		$languageCode,
		$termType
	) {
		return [
			'term_entity_id' => 0,
			'term_full_entity_id' => $fullEntityId,
			'term_entity_type' => $entityType,
			'term_language' => $languageCode,
			'term_type' => $termType,
			'term_text' => '',
			'term_search_key' => ''
		];
	}

	private function getPageRow( $entityNamespace, $numericEntityId, $entityIdPrefix, $isRedirect ) {
		return [
			'page_namespace' => $entityNamespace,
			'page_title' => $entityIdPrefix . $numericEntityId,
			'page_restrictions' => '',
			'page_random' => 0,
			'page_latest' => 0,
			'page_len' => 1,
			'page_is_redirect' => $isRedirect ? 1 : 0
		];
	}

}
