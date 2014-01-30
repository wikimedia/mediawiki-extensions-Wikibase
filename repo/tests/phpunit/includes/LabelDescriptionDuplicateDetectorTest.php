<?php

namespace Wikibase\Test;

use Diff\Diff;
use Diff\DiffOpChange;
use Status;
use Wikibase\EntityId;
use Wikibase\Item;
use Wikibase\LabelDescriptionDuplicateDetector;

/**
 * @covers Wikibase\LabelDescriptionDuplicateDetector
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LabelDescriptionDuplicateDetectorTest extends \PHPUnit_Framework_TestCase {

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
		$detector = new LabelDescriptionDuplicateDetector( new MockTermCache() );

		$entity = Item::newEmpty();
		$entity->setId( new EntityId( Item::ENTITY_TYPE, 1 ) );

		$entity->setDescription( $langCode, $description );
		$entity->setLabel( $langCode, $label );

		$conflicts = $detector->getConflictingTerms( $entity );

		if ( $shouldConflict ) {
			$this->assertEquals( 2, count( $conflicts ) );

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
	public function testAddLabelDescriptionConflicts( $langCode, $label, $description,
		$shouldConflict, Diff $labelsDiff = null, Diff $descriptionDiff = null
	) {
		$termCache = new MockTermCache();

		$detector = new LabelDescriptionDuplicateDetector( $termCache );

		$entity = Item::newEmpty();
		$entity->setId( new EntityId( Item::ENTITY_TYPE, 1 ) );

		$entity->setDescription( $langCode, $description );
		$entity->setLabel( $langCode, $label );

		$status = new Status();

		$detector->addLabelDescriptionConflicts( $entity, $status, $labelsDiff, $descriptionDiff );

		$this->assertEquals( $shouldConflict, !$status->isOK() );
	}

}
