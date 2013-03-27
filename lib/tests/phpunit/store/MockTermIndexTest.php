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
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class MockTermIndexTest extends TermIndexTest {

	public function getTermIndex() {
		return new MockTermIndex( );
	}

	public function provideMatch() {
		$cases = array();

		$term1 = array(
			'termType' => Term::TYPE_LABEL,
			'termLanguage' => 'en',
			'entityId' => 23,
			'entityType' => Item::ENTITY_TYPE,
			'termText' => 'Fnord',
		);

		$term2 = array(
			'termType' => Term::TYPE_DESCRIPTION,
			'termLanguage' => 'de',
			'entityId' => 42,
			'entityType' => \Wikibase\Property::ENTITY_TYPE,
			'termText' => 'somethingsomething',
		);

		$cases[] = array( // #0
			$term1,
			array( 'termType' => Term::TYPE_LABEL, ),
			null,
			true
		);

		$cases[] = array( // #1
			$term2,
			array( 'termType' => Term::TYPE_LABEL, ),
			null,
			false
		);

		$cases[] = array( // #2
			$term1,
			array( 'termLanguage' => 'en', ),
			null,
			true
		);

		$cases[] = array( // #3
			$term1,
			array( 'termLanguage' => 'de', ),
			null,
			false
		);

		$cases[] = array( // #4
			$term1,
			array( 'entityId' => 23, ),
			null,
			true
		);

		$cases[] = array( // #5
			$term1,
			array( 'entityId' => 33, ),
			null,
			false
		);

		$cases[] = array( // #6
			$term2,
			array( 'entityType' => \Wikibase\Property::ENTITY_TYPE, ),
			null,
			true
		);

		$cases[] = array( // #7
			$term2,
			array( 'entityType' => Item::ENTITY_TYPE, ),
			null,
			false
		);

		$cases[] = array( // #8
			$term2,
			array( 'entityType' => \Wikibase\Property::ENTITY_TYPE,
				'entityId' => 23 ),
			null,
			false
		);

		$cases[] = array( // #9
			$term2,
			array( 'entityType' => \Wikibase\Property::ENTITY_TYPE,
			       'entityId' => 42, ),
			null,
			true
		);

		$cases[] = array( // #10
			$term2,
			array( 'termText' => ' sOmEthingSOMEthing ' ),
			array(
				'caseSensitive' => false,
				'prefixSearch' => false,
			),
			true
		);

		$cases[] = array( // #11
			$term2,
			array( 'termText' => ' sOmEthing ' ),
			array(
				'caseSensitive' => false,
				'prefixSearch' => false,
			),
			false
		);

		$cases[] = array( // #12
			$term2,
			array( 'termText' => ' sOmEthing ' ),
			array(
				'caseSensitive' => false,
				'prefixSearch' => true,
			),
			true
		);

		$cases[] = array( // #13
			$term2,
			array( 'termText' => ' sOmEthing ' ),
			array(
				'caseSensitive' => true,
				'prefixSearch' => true,
			),
			false
		);

		return $cases;
	}

	/**
	 * @dataProvider provideMatch
	 */
	public function testMatch( $term, $qterm, $options, $expected ) {
		if ( is_array( $term ) ) {
			$term = new Term( $term );
		}

		if ( is_array( $qterm ) ) {
			$qterm = new Term( $qterm );
		}

		$index = $this->getTermIndex();
		$result = $index->match( $term, $qterm, $options );

		$this->assertEquals( $expected, $result );
	}
}
