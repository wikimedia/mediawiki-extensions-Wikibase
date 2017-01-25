<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\Deserialization\DescriptionsChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpTestMockProvider;
use Wikibase\StringNormalizer;
use Wikibase\Summary;

/**
 * @covers Wikibase\Repo\ChangeOp\Deserialization\DescriptionsChangeOpDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class DescriptionsChangeOpDeserializerTest extends \PHPUnit_Framework_TestCase {

	public function testGivenDescriptionsFieldNotAnArray_createEntityChangeOpThrowsError() {
		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() {
				$deseralizer = $this->newDescriptionsChangeOpDeserializer( $this->getTermChangeOpValidator() );
				$deseralizer->createEntityChangeOp( [ 'descriptions' => null ] );
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

				$deserializer = $this->newDescriptionsChangeOpDeserializer( $validator );
				$deserializer->createEntityChangeOp( [
					'descriptions' => [ 'en' => [ 'language' => 'de', 'value' => 'foo' ] ]
				] );
			},
			'test-error'
		);
	}

	public function testGivenChangeRequestWithDescription_addsDescription() {
		$item = $this->getItemWithoutEnDescription();
		$description = 'foo';
		$changeOp = $this->newDescriptionsChangeOpDeserializer( $this->getTermChangeOpValidator() )
			->createEntityChangeOp( [
				'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => $description ] ]
			] );

		$changeOp->apply( $item, new Summary() );
		$this->assertSame( $description, $item->getDescriptions()->getByLanguage( 'en' )->getText() );
	}

	public function testGivenChangeRequestWithNewDescription_overridesExistingDescription() {
		$item = $this->getItemWithEnDescription();
		$newDescription = 'foo';
		$changeOp = $this->newDescriptionsChangeOpDeserializer( $this->getTermChangeOpValidator() )
			->createEntityChangeOp( [
				'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => $newDescription ] ]
			] );

		$changeOp->apply( $item, new Summary() );
		$this->assertSame( $newDescription, $item->getDescriptions()->getByLanguage( 'en' )->getText() );
	}

	public function testGivenChangeRequestWithRemove_removesDescription() {
		$item = $this->getItemWithEnDescription();
		$changeOp = $this->newDescriptionsChangeOpDeserializer( $this->getTermChangeOpValidator() )
			->createEntityChangeOp( [ 'descriptions' => [ 'en' => [ 'language' => 'en', 'remove' => '' ] ] ] );

		$changeOp->apply( $item, new Summary() );
		$this->assertFalse( $item->getDescriptions()->hasTermForLanguage( 'en' ) );
	}

	public function testGivenChangeRequestWithEmptyValue_removesDescription() {
		$item = $this->getItemWithEnDescription();
		$changeOp = $this->newDescriptionsChangeOpDeserializer( $this->getTermChangeOpValidator() )
			->createEntityChangeOp( [ 'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => '' ] ] ] );

		$changeOp->apply( $item, new Summary() );
		$this->assertFalse( $item->getDescriptions()->hasTermForLanguage( 'en' ) );
	}

	private function newDescriptionsChangeOpDeserializer( TermChangeOpSerializationValidator $validator ) {
		return new DescriptionsChangeOpDeserializer(
			$this->getFingerPrintChangeOpFactory(),
			$this->getStringNormalizer(),
			$validator
		);
	}

	private function getItemWithoutEnDescription() {
		return new Item();
	}

	private function getItemWithEnDescription() {
		$item = new Item();
		$item->setDescription( 'en', 'en-description' );

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
