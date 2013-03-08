<?php

namespace Wikibase\Test;

use Wikibase\Term;
use Wikibase\Lib\TermsToClaimsTranslator;

/**
 * Tests for the Wikibase\Lib\TermsToClaimsTranslator class.
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
 * @since 0.4
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TermsToClaimsTranslatorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return TermsToClaimsTranslator
	 */
	private function newInstance() {
		return new TermsToClaimsTranslator( array(
			Term::TYPE_LABEL => 1000001,
			Term::TYPE_DESCRIPTION => 1000002,
			Term::TYPE_ALIAS => 1000003,
		) );
	}

	/**
	 * @dataProvider termsProvider
	 *
	 * @param Term[] $terms
	 */
	public function testTermsToClaim( array $terms ) {
		$claim = $this->newInstance()->termsToClaim( $terms );

		$this->assertInstanceOf( 'Wikibase\Claim', $claim );
		$this->assertInstanceOf( 'Wikibase\PropertyValueSnak', $claim->getMainSnak() );
		$this->assertInstanceOf( 'DataValues\MultilingualTextValue', $claim->getMainSnak()->getDataValue() );
		$this->assertEquals( count( $terms ), count( $claim->getMainSnak()->getDataValue()->getTexts() ) );
	}

	/**
	 * @dataProvider termProvider
	 *
	 * @param Term $term
	 */
	public function testTermToClaim( Term $term ) {
		$claim = $this->newInstance()->termToClaim( $term );

		$this->assertInstanceOf( 'Wikibase\Claim', $claim );
		$this->assertInstanceOf( 'Wikibase\PropertyValueSnak', $claim->getMainSnak() );
		$this->assertInstanceOf( 'DataValues\MonolingualTextValue', $claim->getMainSnak()->getDataValue() );
		$this->assertEquals( $term->getLanguage(), $claim->getMainSnak()->getDataValue()->getLanguageCode() );
		$this->assertEquals( $term->getText(), $claim->getMainSnak()->getDataValue()->getText() );
	}

	public function termsProvider() {
		$argLists = array();

		$argLists[] = array( array(
			new Term( array(
				'termType' => Term::TYPE_LABEL,
				'termLanguage' => 'en',
				'termText' => 'foo',
			) ),
		) );

		$argLists[] = array( array(
			new Term( array(
				'termType' => Term::TYPE_LABEL,
				'termLanguage' => 'de',
				'termText' => 'foo',
			) ),
			new Term( array(
				'termType' => Term::TYPE_LABEL,
				'termLanguage' => 'nl',
				'termText' => 'bar',
			) ),
			new Term( array(
				'termType' => Term::TYPE_LABEL,
				'termLanguage' => 'en',
				'termText' => 'baz',
			) ),
		) );

		$argLists[] = array( array(
			new Term( array(
				'termType' => Term::TYPE_DESCRIPTION,
				'termLanguage' => 'en',
				'termText' => 'foo',
			) ),
		) );

		$argLists[] = array( array(
			new Term( array(
				'termType' => Term::TYPE_ALIAS,
				'termLanguage' => 'en',
				'termText' => 'foo',
			) ),
		) );

		$argLists[] = array( array(
			new Term( array(
				'termType' => Term::TYPE_ALIAS,
				'termLanguage' => 'de',
				'termText' => 'foo',
			) ),
			new Term( array(
				'termType' => Term::TYPE_ALIAS,
				'termLanguage' => 'en',
				'termText' => 'baz',
			) ),
			new Term( array(
				'termType' => Term::TYPE_ALIAS,
				'termLanguage' => 'nl',
				'termText' => 'nyan',
			) ),
		) );

		return $argLists;
	}

	public function termProvider() {
		$terms = array();

		foreach ( $this->termsProvider() as $argList ) {
			$termList = $argList[0];

			foreach ( $termList as $term ) {
				$terms[] = array( $term );
			}
		}

		return $terms;
	}

}
