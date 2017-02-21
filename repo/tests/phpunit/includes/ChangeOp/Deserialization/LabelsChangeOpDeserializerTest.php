<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\Deserialization\LabelsChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpTestMockProvider;
use Wikibase\StringNormalizer;
use Wikibase\Summary;

/**
 * @covers Wikibase\Repo\ChangeOp\Deserialization\LabelsChangeOpDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class LabelsChangeOpDeserializerTest extends \PHPUnit_Framework_TestCase {

	public function testGivenLabelsFieldNotAnArray_createEntityChangeOpThrowsError() {
		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() {
				$deserializer = $this->newLabelsChangeOpDeserializer( $this->getTermChangeOpValidator() );
				$deserializer->createEntityChangeOp( [ 'labels' => null ] );
			},
			'not-recognized-array'
		);
	}

	public function testGivenInvalidChangeRequest_createEntityChangeOpThrowsError() {
		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() {
				$validator = $this->getMockBuilder( TermChangeOpSerializationValidator::class )
					->disableOriginalConstructor()
					->getMock();

				$validator->method( $this->anything() )
					->will(
						$this->throwException(
							new ChangeOpDeserializationException( 'invalid serialization', 'test-error' )
						)
					);

				$deserializer = $this->newLabelsChangeOpDeserializer( $validator );
				$deserializer->createEntityChangeOp( [
					'labels' => [ 'en' => [ 'language' => 'de', 'value' => 'foo' ] ]
				] );
			},
			'test-error'
		);
	}

	public function testGivenChangeRequestWithLabel_addsLabel() {
		$item = $this->getItemWithoutEnLabel();
		$label = 'foo';
		$changeOp = $this->newLabelsChangeOpDeserializer( $this->getTermChangeOpValidator() )
			->createEntityChangeOp( [ 'labels' => [ 'en' => [ 'language' => 'en', 'value' => $label ] ] ] );

		$changeOp->apply( $item, new Summary() );
		$this->assertSame( $label, $item->getLabels()->getByLanguage( 'en' )->getText() );
	}

	public function testGivenChangeRequestWithNewLabel_overridesExistingLabel() {
		$item = $this->getItemWithEnLabel();
		$newLabel = 'foo';
		$changeOp = $this->newLabelsChangeOpDeserializer( $this->getTermChangeOpValidator() )
			->createEntityChangeOp( [ 'labels' => [ 'en' => [ 'language' => 'en', 'value' => $newLabel ] ] ] );

		$changeOp->apply( $item, new Summary() );
		$this->assertSame( $newLabel, $item->getLabels()->getByLanguage( 'en' )->getText() );
	}

	public function testGivenChangeRequestWithRemove_removesLabel() {
		$item = $this->getItemWithEnLabel();
		$changeOp = $this->newLabelsChangeOpDeserializer( $this->getTermChangeOpValidator() )
			->createEntityChangeOp( [ 'labels' => [ 'en' => [ 'language' => 'en', 'remove' => '' ] ] ] );

		$changeOp->apply( $item, new Summary() );
		$this->assertFalse( $item->getLabels()->hasTermForLanguage( 'en' ) );
	}

	public function testGivenChangeRequestWithEmptyLabel_removesLabel() {
		$item = $this->getItemWithEnLabel();
		$changeOp = $this->newLabelsChangeOpDeserializer( $this->getTermChangeOpValidator() )
			->createEntityChangeOp( [ 'labels' => [ 'en' => [ 'language' => 'en', 'value' => '' ] ] ] );

		$changeOp->apply( $item, new Summary() );
		$this->assertFalse( $item->getLabels()->hasTermForLanguage( 'en' ) );
	}

	private function newLabelsChangeOpDeserializer( TermChangeOpSerializationValidator $validator ) {
		return new LabelsChangeOpDeserializer(
			$this->getFingerPrintChangeOpFactory(),
			$this->getStringNormalizer(),
			$validator
		);
	}

	private function getItemWithoutEnLabel() {
		return new Item();
	}

	private function getItemWithEnLabel() {
		$item = new Item();
		$item->setLabel( 'en', 'en-label' );

		return $item;
	}

	private function getStringNormalizer() {
		return new StringNormalizer();
	}

	private function getFingerPrintChangeOpFactory() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		return new FingerprintChangeOpFactory( $mockProvider->getMockTermValidatorFactory() );
	}

	private function getTermChangeOpValidator() {
		return new TermChangeOpSerializationValidator( new StaticContentLanguages( [ 'en' ] ) );
	}

}
