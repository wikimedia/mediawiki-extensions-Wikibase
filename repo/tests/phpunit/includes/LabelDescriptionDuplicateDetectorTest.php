<?php

namespace Wikibase\Repo\Test;
use Wikibase\Term;
use Diff\Diff;
use Diff\DiffOpChange;

/**
 * Tests Wikibase\LabelDescriptionDuplicateDetector.
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
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LabelDescriptionDuplicateDetectorTest extends \MediaWikiTestCase {

	public function conflictProvider() {
		$argLists = array();

		$argLists[] = array( 'en', 'label-en', 'description-en', true );

		$argLists[] = array( 'en', 'label-en', 'foobar-en', false );
		$argLists[] = array( 'en', 'foobar-en', 'description-en', false );
		$argLists[] = array( 'de', 'label-en', 'description-en', false );

		return $argLists;
	}

	public function conflictDiffProvider() {
		$argLists = array();

		foreach ( $this->conflictProvider() as $argList ) {
			$argList[] = new Diff( array( $argList[0] => new DiffOpChange( 'a', $argList[1] ) ) );
			$argList[] = new Diff( array( $argList[0] => new DiffOpChange( 'a', $argList[2] ) ) );

			$argLists[] = $argList;
		}

		foreach ( $this->conflictProvider() as $argList ) {
			$argList[] = null;
			$argList[] = null;

			$argLists[] = $argList;
		}

		foreach ( $this->conflictProvider() as $argList ) {
			$argList[] = new Diff( array( 'foo' => new DiffOpChange( 'a', $argList[1] ) ) );
			$argList[] = new Diff( array( 'foo' => new DiffOpChange( 'a', $argList[2] ) ) );
			$argList[3] = false;

			$argLists[] = $argList;
		}

		return $argLists;
	}

	/**
	 * @dataProvider conflictProvider
	 *
	 * @param $langCode
	 * @param $label
	 * @param $description
	 * @param $shouldConflict
	 */
	public function testGetConflictingTerms( $langCode, $label, $description, $shouldConflict ) {
		$termCache = new MockTermCache();

		$detector = new \Wikibase\LabelDescriptionDuplicateDetector();

		$entity = \Wikibase\Item::newEmpty();
		$entity->setId( new \Wikibase\EntityId( \Wikibase\Item::ENTITY_TYPE, 1 ) );

		$entity->setDescription( $langCode, $description );
		$entity->setLabel( $langCode, $label );

		$conflicts = $detector->getConflictingTerms( $entity, $termCache );

		if ( $shouldConflict ) {
			$this->assertEquals( 2, count( $conflicts ) );

			/**
			 * @var Term $conflictingLabel
			 * @var Term $conflictingDescription
			 */
			list( $conflictingLabel, $conflictingDescription ) = $conflicts;

			$this->assertEquals( $label, $conflictingLabel->getText() );
			$this->assertEquals( $langCode, $conflictingLabel->getLanguage() );

			$this->assertEquals( $description, $conflictingDescription->getText() );
			$this->assertEquals( $langCode, $conflictingDescription->getLanguage() );
		}
		else {
			$this->assertTrue( empty( $conflicts ) );
		}
	}

	/**
	 * @dataProvider conflictDiffProvider
	 *
	 * @param $langCode
	 * @param $label
	 * @param $description
	 * @param $shouldConflict
	 * @param Diff|null $labelsDiff
	 * @param Diff|null $descriptionDiff
	 */
	public function testAddLabelDescriptionConflicts( $langCode, $label, $description, $shouldConflict, Diff $labelsDiff = null, Diff $descriptionDiff = null ) {
		$termCache = new MockTermCache();

		$detector = new \Wikibase\LabelDescriptionDuplicateDetector();

		$entity = \Wikibase\Item::newEmpty();
		$entity->setId( new \Wikibase\EntityId( \Wikibase\Item::ENTITY_TYPE, 1 ) );

		$entity->setDescription( $langCode, $description );
		$entity->setLabel( $langCode, $label );

		$status = new \Status();

		$detector->addLabelDescriptionConflicts( $entity, $status, $termCache, $labelsDiff, $descriptionDiff );

		$this->assertEquals( $shouldConflict, !$status->isOK() );
	}

}

class MockTermCache implements \Wikibase\TermCombinationMatchFinder {

	/**
	 * @var Term[]
	 */
	protected $terms;

	public function __construct() {
		$terms = array();

		$terms[] = new \Wikibase\Term( array(
			'termType' => Term::TYPE_LABEL,
			'termLanguage' => 'en',
			'entityId' => 42,
			'entityType' => \Wikibase\Item::ENTITY_TYPE,
			'termText' => 'label-en',
		) );

		$terms[] = new \Wikibase\Term( array(
			'termType' => Term::TYPE_LABEL,
			'termLanguage' => 'de',
			'entityId' => 42,
			'entityType' => \Wikibase\Item::ENTITY_TYPE,
			'termText' => 'label-de',
		) );

		$terms[] = new \Wikibase\Term( array(
			'termType' => Term::TYPE_DESCRIPTION,
			'termLanguage' => 'en',
			'entityId' => 42,
			'entityType' => \Wikibase\Item::ENTITY_TYPE,
			'termText' => 'description-en',
		) );

		$this->terms = $terms;
	}

	/**
	 * @see \Wikibase\TermCombinationMatchFinder::getMatchingTermCombination
	 *
	 * @param array $terms
	 * @param string|null $termType
	 * @param string|null $entityType
	 * @param int|null $excludeId
	 * @param string|null $excludeType
	 *
	 * @return array
	 */
	public function getMatchingTermCombination( array $terms, $termType = null, $entityType = null, $excludeId = null, $excludeType = null ) {
		/**
		 * @var Term[] $termPair
		 * @var Term[] $matchingTerms
		 */
		foreach ( $terms as $termPair ) {
			$matchingTerms = array();

			$id = null;
			$type = null;

			foreach ( $termPair as $term ) {
				foreach ( $this->terms as $storedTerm ) {
					if ( $term->getText() === $storedTerm->getText()
						&& $term->getLanguage() === $storedTerm->getLanguage()
						&& $term->getType() === $storedTerm->getType() ) {

						if ( $id === null ) {
							$id = $term->getEntityId();
							$type = $term->getEntityType();
							$matchingTerms[] = $term;
						}
						elseif ( $id === $term->getEntityId() && $type === $term->getEntityType() ) {
							$matchingTerms[] = $term;
						}
					}
				}
			}

			if ( count( $matchingTerms ) === count( $termPair ) ) {
				return $matchingTerms;
			}
		}

		return array();
	}

}
