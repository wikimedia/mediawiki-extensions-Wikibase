<?php

namespace Wikibase\Test;

use Diff\Diff;
use Diff\DiffOpChange;
use ValueValidators\Error;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Item;
use Wikibase\Term;
use Wikibase\TermDuplicateDetector;
use Wikibase\Validators\ValidatorErrorLocalizer;

/**
 * @covers Wikibase\LabelDescriptionDuplicateDetector
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class TermDuplicateDetectorTest extends \PHPUnit_Framework_TestCase {

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

	private function getMockTermDetector() {
		$terms = array();

		$terms[] = new Term( array(
			'termType' => Term::TYPE_LABEL,
			'termLanguage' => 'en',
			'entityId' => 42,
			'entityType' => Item::ENTITY_TYPE,
			'termText' => 'label-en',
		) );

		$terms[] = new Term( array(
			'termType' => Term::TYPE_LABEL,
			'termLanguage' => 'de',
			'entityId' => 42,
			'entityType' => Item::ENTITY_TYPE,
			'termText' => 'label-de',
		) );

		$terms[] = new Term( array(
			'termType' => Term::TYPE_DESCRIPTION,
			'termLanguage' => 'en',
			'entityId' => 42,
			'entityType' => Item::ENTITY_TYPE,
			'termText' => 'description-en',
		) );

		return new MockTermIndex( $terms );
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
		$detector = new TermDuplicateDetector( $this->getMockTermDetector() );

		$entityId = new ItemId( "Q1" );

		$terms = array(
			$langCode => array(
				'label' => $label,
				'description' => $description
			)
		);

		$result = $detector->detectTermDuplicates( $entityId, $terms );

		$this->assertNotEquals( $shouldConflict, $result->isValid() );

		$conflicts = $result->getErrors();

		if ( $shouldConflict ) {
			$this->assertEquals( 2, count( $conflicts ) );

			/* @var Error $labelError */
			/* @var Error $descriptionError */
			list( $labelError, $descriptionError ) = $conflicts;

			$this->assertEquals( array( 'label', $langCode, $label, 'Q42' ), $labelError->getParameters() );
			$this->assertEquals( array( 'description', $langCode, $description, 'Q42' ), $descriptionError->getParameters() );

			$errorLocalizer = new ValidatorErrorLocalizer();
			$labelMessage = $errorLocalizer->getErrorMessage( $labelError );
			$descriptionMessage = $errorLocalizer->getErrorMessage( $descriptionError );

			$this->assertTrue( $labelMessage->exists(), 'Message exists: ' . $descriptionMessage->getKey() );
			$this->assertTrue( $descriptionMessage->exists(), 'Message exists: ' . $descriptionMessage->getKey() );
		}
		else {
			$this->assertTrue( empty( $conflicts ) );
		}
	}

}
