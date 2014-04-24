<?php

namespace Wikibase\Test\Validators;

use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\Test\ChangeOpTestMockProvider;
use Wikibase\Validators\LabelDescriptionUniquenessValidator;

/**
 * @covers Wikibase\Validators\LabelDescriptionUniquenessValidator
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseContent
 *
 * @licence GNU GPL v2+
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
		$goodFingerprint1 = new Fingerprint(
			new TermList( array( new Term( 'de', 'DUPE' ) ) ),
			new TermList( array() ),
			new AliasGroupList( array() )
		);

		$goodFingerprint2 = new Fingerprint(
			new TermList( array() ),
			new TermList( array( new Term( 'de', 'Foo' ) ) ),
			new AliasGroupList( array() )
		);

		return array(
			array( $goodFingerprint1 ),
			array( $goodFingerprint2 ),
		);
	}

	private function fingerprintCaseToEntityCase( $fingerprintCase, $id ) {
		$fingerprint = reset( $fingerprintCase );

		$item = Item::newEmpty();
		$item->setFingerprint( $fingerprint );
		$item->setId( $id );

		$entityCase = $fingerprintCase;
		$entityCase[0] = $item;

		return $entityCase;
	}

	public function validEntityProvider() {
		$cases = array();

		$i = 1;
		foreach ( $this->validFingerprintProvider() as $name => $fingerprintCase ) {
			$id = new ItemId( 'Q' . $i++ );
			$cases[$name] = $this->fingerprintCaseToEntityCase( $fingerprintCase, $id );
		}

		return $cases;
	}

	/**
	 * @dataProvider validEntityProvider
	 *
	 * @param Entity $entity
	 */
	public function testValidateEntity( Entity $entity ) {
		$dupeDetector = $this->getMockDupeDetector();
		$validator = new LabelDescriptionUniquenessValidator( $dupeDetector );

		$result = $validator->validateEntity( $entity );

		$this->assertTrue( $result->isValid(), 'isValid' );
	}

	/**
	 * @dataProvider validFingerprintProvider
	 *
	 * @param Fingerprint $fingerprint
	 */
	public function testValidateFingerprint( Fingerprint $fingerprint ) {
		$dupeDetector = $this->getMockDupeDetector();
		$validator = new LabelDescriptionUniquenessValidator( $dupeDetector );

		$result = $validator->validateFingerprint( $fingerprint );

		$this->assertTrue( $result->isValid(), 'isValid' );
	}

	public function invalidFingerprintProvider() {
		$badFingerprint = new Fingerprint(
			new TermList( array( new Term( 'de', 'DUPE' ) ) ),
			new TermList( array( new Term( 'de', 'DUPE' ) ) ),
			new AliasGroupList( array() )
		);

		return array(
			array( $badFingerprint, 'label-with-description-conflict' ),
		);
	}

	public function invalidEntityProvider() {
		$cases = array();

		$i = 1;
		foreach ( $this->invalidFingerprintProvider() as $name => $fingerprintCase ) {
			$id = new ItemId( 'Q' . $i++ );
			$cases[$name] = $this->fingerprintCaseToEntityCase( $fingerprintCase, $id );
		}

		return $cases;
	}

	/**
	 * @dataProvider invalidEntityProvider
	 *
	 * @param Entity $entity
	 * @param string|null $error
	 */
	public function testValidateEntity_failure( Entity $entity, $error ) {
		$dupeDetector = $this->getMockDupeDetector();
		$validator = new LabelDescriptionUniquenessValidator( $dupeDetector );

		$result = $validator->validateEntity( $entity );

		$this->assertFalse( $result->isValid(), 'isValid' );

		$errors = $result->getErrors();
		$this->assertEquals( $error, $errors[0]->getCode() );
	}

	/**
	 * @dataProvider invalidFingerprintProvider
	 *
	 * @param Fingerprint $fingerprint
	 * @param string|null $error
	 */
	public function testValidateFingerprint_failure( Fingerprint $fingerprint, $error ) {
		$dupeDetector = $this->getMockDupeDetector();
		$validator = new LabelDescriptionUniquenessValidator( $dupeDetector );

		$result = $validator->validateFingerprint( $fingerprint );

		$this->assertFalse( $result->isValid(), 'isValid' );

		$errors = $result->getErrors();
		$this->assertEquals( $error, $errors[0]->getCode() );
	}

}
