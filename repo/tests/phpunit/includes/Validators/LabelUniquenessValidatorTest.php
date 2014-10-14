<?php

namespace Wikibase\Test\Validators;

use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\Test\ChangeOpTestMockProvider;
use Wikibase\Validators\LabelUniquenessValidator;

/**
 * @covers Wikibase\Validators\LabelUniquenessValidator
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseContent
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class LabelUniquenessValidatorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return LabelDescriptionDuplicateDetector
	 */
	private function getMockDupeDetector() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		return $mockProvider->getMockLabelDescriptionDuplicateDetector();
	}

	public function validFingerprintProvider() {
		$p99 = new PropertyId( 'P99' );

		return array(
			'no conflict' => array(
				new Fingerprint(
					new TermList( array( new Term( 'de', 'Foo' ) ) ),
					new TermList( array() ),
					new AliasGroupList( array() )
				),
				$p99
			),
			'self conflict' => array(
				// the mock considers "DUPE" a dupe with P666
				new Fingerprint(
					new TermList( array( new Term( 'de', 'DUPE' ) ) ),
					new TermList( array() ),
					new AliasGroupList( array() )
				),
				new PropertyId( 'P666' ) // ignore conflicts with P666
			),
			'ignored conflict' => array(
				// the mock considers "DUPE" a dupe with P666
				new Fingerprint(
					new TermList( array( new Term( 'de', 'DUPE' ) ) ),
					new TermList( array() ),
					new AliasGroupList( array() )
				),
				$p99,
				array( 'en' ) // only consider conflicts in english
			),
		);
	}

	private function fingerprintCaseToEntityCase( $fingerprintCase, $id ) {
		$fingerprint = reset( $fingerprintCase );

		$item = Property::newFromType( 'string' );
		$item->setFingerprint( $fingerprint );
		$item->setId( $id );

		$entityCase = $fingerprintCase;
		$entityCase[0] = $item;

		return $entityCase;
	}

	public function validEntityProvider() {
		$cases = array();

		foreach ( $this->validFingerprintProvider() as $name => $fingerprintCase ) {
			// if the case has a non-null languageCodes or a strange entityId param, skip it
			if ( isset( $fingerprintCase[2] ) || !( $fingerprintCase[1] instanceof PropertyId ) ) {
				continue;
			}

			$id = $fingerprintCase[1];
			$cases[$name] = $this->fingerprintCaseToEntityCase( $fingerprintCase, $id );
		}

		return $cases;
	}

	public function invalidFingerprintProvider() {
		$badFingerprint = new Fingerprint(
			new TermList( array( new Term( 'de', 'DUPE' ) ) ),
			new TermList( array( ) ),
			new AliasGroupList( array() )
		);

		return array(
			array( $badFingerprint, 'label-conflict' ),
		);
	}

	public function invalidEntityProvider() {
		$cases = array();

		$i = 1;
		foreach ( $this->invalidFingerprintProvider() as $name => $fingerprintCase ) {
			$id = new PropertyId( 'P' . $i++ );
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
		$validator = new LabelUniquenessValidator( $dupeDetector );

		$result = $validator->validateEntity( $entity );

		$this->assertTrue( $result->isValid(), 'isValid' );
	}

	/**
	 * @dataProvider validFingerprintProvider
	 *
	 * @param Fingerprint $fingerprint
	 * @param EntityId|null $entityId
	 * @param string[]|null $languageCodes
	 */
	public function testValidateFingerprint(
		Fingerprint $fingerprint,
		EntityId $entityId,
		$languageCodes = null
	) {
		$dupeDetector = $this->getMockDupeDetector();
		$validator = new LabelUniquenessValidator( $dupeDetector );

		$result = $validator->validateFingerprint( $fingerprint, $entityId, $languageCodes );

		$this->assertTrue( $result->isValid(), 'isValid' );
	}

	/**
	 * @dataProvider invalidEntityProvider
	 *
	 * @param Entity $entity
	 * @param string|null $error
	 */
	public function testValidateEntity_failure( Entity $entity, $error ) {
		$dupeDetector = $this->getMockDupeDetector();
		$validator = new LabelUniquenessValidator( $dupeDetector );

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
		$validator = new LabelUniquenessValidator( $dupeDetector );

		$result = $validator->validateFingerprint( $fingerprint, new PropertyId( 'P99' ) );

		$this->assertFalse( $result->isValid(), 'isValid' );

		$errors = $result->getErrors();
		$this->assertEquals( $error, $errors[0]->getCode() );
	}

}
