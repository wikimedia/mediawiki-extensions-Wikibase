<?php

namespace Wikibase\Test;

use Wikibase\Term;
use Wikibase\Lib\TermsToClaimsTranslator;

/**
 * @covers Wikibase\Lib\TermsToClaimsTranslator
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
