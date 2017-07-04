<?php

namespace Wikibase\Repo\Tests\Validators;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\Repo\Validators\LabelDescriptionUniquenessValidator;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpTestMockProvider;

/**
 * @covers Wikibase\Repo\Validators\LabelDescriptionUniquenessValidator
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseContent
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class LabelDescriptionUniquenessValidatorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return LabelDescriptionDuplicateDetector
	 */
	private function getMockDupeDetector() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		return $mockProvider->getMockLabelDescriptionDuplicateDetector();
	}

	public function validFingerprintProvider() {
		$q99 = new ItemId( 'Q99' );

		return [
			'no description' => [
				new Fingerprint(
					new TermList( [ new Term( 'de', 'DUPE' ) ] ),
					new TermList(),
					new AliasGroupList()
				),
				$q99
			],
			'non-dupe description' => [
				new Fingerprint(
					new TermList( [ new Term( 'de', 'DUPE' ) ] ),
					new TermList( [ new Term( 'de', 'Foo' ) ] ),
					new AliasGroupList()
				),
				$q99
			],

			'self conflict' => [
				// the mock considers "DUPE" a dupe with P666
				new Fingerprint(
					new TermList( [ new Term( 'de', 'DUPE' ) ] ),
					new TermList( [ new Term( 'de', 'DUPE' ) ] ),
					new AliasGroupList()
				),
				new PropertyId( 'P666' ) // ignore conflicts with P666
			],
			'ignored conflict' => [
				// the mock considers "DUPE" a dupe with P666
				new Fingerprint(
					new TermList( [ new Term( 'de', 'DUPE' ) ] ),
					new TermList( [ new Term( 'de', 'DUPE' ) ] ),
					new AliasGroupList()
				),
				$q99,
				[ 'en' ] // only consider conflicts in english
			],
		];
	}

	private function fingerprintCaseToEntityCase( array $fingerprintCase, ItemId $id ) {
		$fingerprint = reset( $fingerprintCase );

		$item = new Item( $id );
		$item->setFingerprint( $fingerprint );

		$entityCase = $fingerprintCase;
		$entityCase[0] = $item;

		return $entityCase;
	}

	public function validEntityProvider() {
		$cases = [];

		foreach ( $this->validFingerprintProvider() as $name => $fingerprintCase ) {
			// if the case has a non-null languageCodes or a strange entityId param, skip it
			if ( isset( $fingerprintCase[2] ) || !( $fingerprintCase[1] instanceof ItemId ) ) {
				continue;
			}

			$id = $fingerprintCase[1];
			$cases[$name] = $this->fingerprintCaseToEntityCase( $fingerprintCase, $id );
		}

		// check validation without entity id
		$cases["no id"] = [
			new Item(),
		];

		return $cases;
	}

	/**
	 * @dataProvider validEntityProvider
	 * @param EntityDocument $entity
	 */
	public function testValidateEntity( EntityDocument $entity ) {
		$dupeDetector = $this->getMockDupeDetector();
		$validator = new LabelDescriptionUniquenessValidator( $dupeDetector );

		$result = $validator->validateEntity( $entity );

		$this->assertTrue( $result->isValid(), 'isValid' );
	}

	/**
	 * @dataProvider validFingerprintProvider
	 * @param Fingerprint $fingerprint
	 * @param EntityId $entityId
	 * @param array|null $languageCodes
	 */
	public function testValidateFingerprint(
		Fingerprint $fingerprint,
		EntityId $entityId,
		array $languageCodes = null
	) {
		$dupeDetector = $this->getMockDupeDetector();
		$validator = new LabelDescriptionUniquenessValidator( $dupeDetector );

		$result = $validator->validateFingerprint(
			$fingerprint->getLabels(),
			$fingerprint->getDescriptions(),
			$entityId,
			$languageCodes
		);

		$this->assertTrue( $result->isValid(), 'isValid' );
	}

	public function invalidFingerprintProvider() {
		$badFingerprint = new Fingerprint(
			new TermList( [ new Term( 'de', 'DUPE' ) ] ),
			new TermList( [ new Term( 'de', 'DUPE' ) ] ),
			new AliasGroupList()
		);

		return [
			[ $badFingerprint, 'label-with-description-conflict' ],
		];
	}

	public function invalidEntityProvider() {
		$cases = [];

		$i = 1;
		foreach ( $this->invalidFingerprintProvider() as $name => $fingerprintCase ) {
			$id = new ItemId( 'Q' . $i++ );
			$cases[$name] = $this->fingerprintCaseToEntityCase( $fingerprintCase, $id );
		}

		return $cases;
	}

	/**
	 * @dataProvider invalidEntityProvider
	 * @param EntityDocument $entity
	 * @param string|null $error
	 */
	public function testValidateEntity_failure( EntityDocument $entity, $error ) {
		$dupeDetector = $this->getMockDupeDetector();
		$validator = new LabelDescriptionUniquenessValidator( $dupeDetector );

		$result = $validator->validateEntity( $entity );

		$this->assertFalse( $result->isValid(), 'isValid' );

		$errors = $result->getErrors();
		$this->assertEquals( $error, $errors[0]->getCode() );
	}

	/**
	 * @dataProvider invalidFingerprintProvider
	 * @param Fingerprint $fingerprint
	 * @param string|null $error
	 */
	public function testValidateFingerprint_failure( Fingerprint $fingerprint, $error ) {
		$dupeDetector = $this->getMockDupeDetector();
		$validator = new LabelDescriptionUniquenessValidator( $dupeDetector );

		$result = $validator->validateFingerprint(
			$fingerprint->getLabels(),
			$fingerprint->getDescriptions(),
			new ItemId( 'Q99' )
		);

		$this->assertFalse( $result->isValid(), 'isValid' );

		$errors = $result->getErrors();
		$this->assertEquals( $error, $errors[0]->getCode() );
	}

}
