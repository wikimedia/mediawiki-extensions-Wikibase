<?php

namespace Wikibase\Test;
use Wikibase\TermSqlCache;
use Wikibase\Term;

/**
 * Tests for the Wikibase\TermSqlCache class.
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
 * @since 0.2
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group WikibaseTerm
 * @group Database
 *
 * Some of the tests takes more time, and needs therefor longer time before they can be aborted
 * as non-functional. The reason why tests are aborted is assumed to be set up of temporal databases
 * that hold the first tests in a pending state awaiting access to the database.
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TermSearchKeyBuilderTest extends \MediaWikiTestCase {

	public function termProvider() {
		$argLists = array();

		$argLists[] = array( 'en', 'FoO', 'fOo', true );
		$argLists[] = array( 'ru', 'Берлин', '  берлин  ', true );

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
	public function testRebuildSearchKey( $languageCode, $termText, $searchText, $matches ) {
		if ( \Wikibase\Settings::get( 'withoutTermSearchKey' ) ) {
			$this->markTestSkipped( "can't test search key if withoutTermSearchKey option is set." );
		}

		// make term in item
		$item = \Wikibase\Item::newEmpty();
		$item->setId( 42 );
		$item->setLabel( $languageCode, $termText );

		// save term
		/* @var TermSqlCache $termCache */
		$termCache = \Wikibase\StoreFactory::getStore( 'sqlstore' )->newTermCache();
		$termCache->clear();
		$termCache->saveTermsOfEntity( $item );

		// remove search key
		$dbw = wfGetDB( DB_MASTER );
		$dbw->update( $termCache->getTableName(), array( 'term_search_key' => '' ), array(), __METHOD__ );

		// rebuild search key
		$builder = new \Wikibase\TermSearchKeyBuilder( $termCache );
		$builder->setRebuildAll( true );
		$builder->rebuildSearchKey();

		// remove search key
		$term = new Term();
		$term->setLanguage( $languageCode );
		$term->setText( $searchText );

		$options = array(
			'caseSensitive' => false,
		);

		$obtainedTerms = $termCache->getMatchingTerms( array( $term ), Term::TYPE_LABEL, \Wikibase\Item::ENTITY_TYPE, $options );

		$this->assertEquals( $matches ? 1 : 0, count( $obtainedTerms ) );

		if ( $matches ) {
			$obtainedTerm = array_shift( $obtainedTerms );

			$this->assertEquals( $termText, $obtainedTerm->getText() );
		}
	}

}
