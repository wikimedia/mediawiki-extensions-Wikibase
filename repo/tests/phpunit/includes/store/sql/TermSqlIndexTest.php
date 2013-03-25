<?php

namespace Wikibase\Test;
use Wikibase\Item;
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
		$index = new \Wikibase\TermSqlIndex( 'wb_terms' );

		return $index;
	}

	protected function fillTestIndex( \Wikibase\TermIndex $index, $reset = false ) {
		static $ids = null;

		// since this gets stored in the DB, we only need to do this once
		if ( $ids === null || $reset ) {
			$index->clear(); // make sure the DB is empty
			$ids = parent::fillTestIndex( $index );
		}

		return $ids;
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
		$termIndex = \Wikibase\StoreFactory::getStore( 'sqlstore' )->getTermIndex();

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
}
