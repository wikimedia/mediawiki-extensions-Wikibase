<?php

namespace Wikibase\Test;

use Wikibase\Item;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Term;
use Wikibase\TermSearchKeyBuilder;
use Wikibase\TermSqlIndex;

/**
 * @covers Wikibase\TermSearchKeyBuilder
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group WikibaseRepo
 * @group WikibaseTerm
 * @group Database
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
		$withoutTermSearchKey = WikibaseRepo::getDefaultInstance()->
			getSettings()->getSetting( 'withoutTermSearchKey' );

		if ( $withoutTermSearchKey ) {
			$this->markTestSkipped( "can't test search key if withoutTermSearchKey option is set." );
		}

		// make term in item
		$item = Item::newEmpty();
		$item->setId( 42 );
		$item->setLabel( $languageCode, $termText );

		// save term
		/* @var TermSqlIndex $termCache */
		$termCache = WikibaseRepo::getDefaultInstance()->getStore()->getTermIndex();
		$termCache->clear();
		$termCache->saveTermsOfEntity( $item );

		// remove search key
		$dbw = wfGetDB( DB_MASTER );
		$dbw->update( $termCache->getTableName(), array( 'term_search_key' => '' ), array(), __METHOD__ );

		// rebuild search key
		$builder = new TermSearchKeyBuilder( $termCache );
		$builder->setRebuildAll( true );
		$builder->rebuildSearchKey();

		// remove search key
		$term = new Term();
		$term->setLanguage( $languageCode );
		$term->setText( $searchText );

		$options = array(
			'caseSensitive' => false,
		);

		$obtainedTerms = $termCache->getMatchingTerms( array( $term ), Term::TYPE_LABEL, Item::ENTITY_TYPE, $options );

		$this->assertEquals( $matches ? 1 : 0, count( $obtainedTerms ) );

		if ( $matches ) {
			$obtainedTerm = array_shift( $obtainedTerms );

			$this->assertEquals( $termText, $obtainedTerm->getText() );
		}
	}

}
