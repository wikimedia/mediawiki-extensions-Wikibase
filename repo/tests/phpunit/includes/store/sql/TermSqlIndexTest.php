<?php

namespace Wikibase\Test;
use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\Item;
use Wikibase\StringNormalizer;
use Wikibase\Term;

/**
 * Tests for the Wikibase\TermSqlIndex class.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class TermSqlIndexTest extends TermIndexTest {

	public function getTermIndex() {
		$normalizer = new StringNormalizer();
		return new \Wikibase\TermSqlIndex( $normalizer );
	}

	public function termProvider() {
		$argLists = array();

		$argLists[] = array( 'en', 'FoO', 'fOo', true );
		$argLists[] = array( 'ru', 'Берлин', 'берлин', true );

		$argLists[] = array( 'en', 'FoO', 'bar', false );
		$argLists[] = array( 'ru', 'Берлин', 'бе55585рлин', false );

		return $argLists;
	}

	/**
	 * @dataProvider termProvider
	 * @param $languageCode
	 * @param $termText
	 * @param $searchText
	 * @param boolean $matches
	 */
	public function testGetMatchingTerms2( $languageCode, $termText, $searchText, $matches ) {
		if ( \Wikibase\Settings::get( 'withoutTermSearchKey' ) ) {
			$this->markTestSkipped( "can't test search key if withoutTermSearchKey option is set." );
		}

		/**
		 * @var \Wikibase\TermSqlIndex $termIndex
		 */
		$termIndex = $this->getTermIndex();

		$termIndex->clear();

		$item = \Wikibase\Item::newEmpty();
		$item->setId( 42 );

		$item->setLabel( $languageCode, $termText );

		$termIndex->saveTermsOfEntity( $item );

		$term = new Term();
		$term->setLanguage( $languageCode );
		$term->setText( $searchText );

		$options = array(
			'caseSensitive' => false,
		);

		$obtainedTerms = $termIndex->getMatchingTerms( array( $term ), Term::TYPE_LABEL, \Wikibase\Item::ENTITY_TYPE, $options );

		$this->assertEquals( $matches ? 1 : 0, count( $obtainedTerms ) );

		if ( $matches ) {
			$obtainedTerm = array_shift( $obtainedTerms );

			$this->assertEquals( $termText, $obtainedTerm->getText() );
		}
	}

	/**
	 * @dataProvider termProvider
	 * @param $languageCode
	 * @param $termText
	 * @param $searchText
	 * @param boolean $matches
	 */
	public function testGetMatchingTermsWeights( $languageCode, $termText, $searchText, $matches ) {
		/**
		 * @var \Wikibase\TermSqlIndex $termIndex
		 */
		$termIndex = $this->getTermIndex();

		if ( !$termIndex->supportsWeight() ) {
			$this->markTestSkipped( "can't test search weight if withoutTermWeight option is set." );
		}

		$termIndex->clear();

		$item1 = \Wikibase\Item::newEmpty();
		$item1->setId( 42 );

		$item1->setLabel( $languageCode, $termText );
		$item1->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'A' ) );

		$termIndex->saveTermsOfEntity( $item1 );

		$item2 = \Wikibase\Item::newEmpty();
		$item2->setId( 23 );

		$item2->setLabel( $languageCode, $termText );
		$item2->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'B' ) );
		$item2->addSimpleSiteLink( new SimpleSiteLink( 'dewiki', 'B' ) );
		$item2->addSimpleSiteLink( new SimpleSiteLink( 'hrwiki', 'B' ) );
		$item2->addSimpleSiteLink( new SimpleSiteLink( 'uzwiki', 'B' ) );

		$termIndex->saveTermsOfEntity( $item2 );

		$item3 = \Wikibase\Item::newEmpty();
		$item3->setId( 108 );

		$item3->setLabel( $languageCode, $termText );
		$item3->addSimpleSiteLink( new SimpleSiteLink( 'hrwiki', 'C' ) );
		$item3->addSimpleSiteLink( new SimpleSiteLink( 'uzwiki', 'C' ) );

		$termIndex->saveTermsOfEntity( $item3 );

		$term = new Term();
		$term->setLanguage( $languageCode );
		$term->setText( $searchText );

		$options = array(
			'caseSensitive' => false,
		);

		$obtainedIDs = $termIndex->getMatchingIDs( array( $term ), \Wikibase\Item::ENTITY_TYPE, $options );

		$this->assertEquals( $matches ? 3 : 0, count( $obtainedIDs ) );

		$expectedResult = array( $item2->getId(), $item3->getId(), $item1->getId() );
		$this->assertArrayEquals( $expectedResult, $obtainedIDs, true );

		if ( $matches ) {
			$obtainedTerm = array_shift( $obtainedTerms );

			$this->assertEquals( $termText, $obtainedTerm->getText() );
		}
	}

	public static function provideGetSearchKey() {
		return array(
			array( // #0
				'foo', // raw
				'en',  // lang
				'foo', // normalized
			),

			array( // #1
				'  foo  ', // raw
				'en',  // lang
				'foo', // normalized
			),

			array( // #2: lower case of non-ascii character
				'ÄpFEl', // raw
				'de',    // lang
				'äpfel', // normalized
			),

			array( // #3: lower case of decomposed character
				"A\xCC\x88pfel", // raw
				'de',    // lang
				'äpfel', // normalized
			),

			array( // #4: lower case of cyrillic character
				'Берлин', // raw
				'ru',     // lang
				'берлин', // normalized
			),

			array( // #5: lower case of greek character
				'Τάχιστη', // raw
				'he',      // lang
				'τάχιστη', // normalized
			),

			array( // #6: nasty unicode whitespace
				// ZWNJ: U+200C \xE2\x80\x8C
				// RTLM: U+200F \xE2\x80\x8F
				// PSEP: U+2029 \xE2\x80\xA9
				"\xE2\x80\x8F\xE2\x80\x8Cfoo\xE2\x80\x8Cbar\xE2\x80\xA9", // raw
				'en',      // lang
				"foo bar", // normalized
			),
		);
	}

	/**
	 * @dataProvider provideGetSearchKey
	 */
	public function testGetSearchKey( $raw, $lang, $normalized ) {
		$index = $this->getTermIndex();

		$key = $index->getSearchKey( $raw, $lang );
		$this->assertEquals( $normalized, $key );
	}
}
