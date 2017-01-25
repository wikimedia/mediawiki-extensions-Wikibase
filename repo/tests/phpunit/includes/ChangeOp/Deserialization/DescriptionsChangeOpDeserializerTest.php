<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\ItemContent;
use Wikibase\Lib\StaticContentLanguages;
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
		TermChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() {
				$this->newDescriptionsChangeOpDeserializer()->createEntityChangeOp( [ 'descriptions' => null ] );
			},
			'not-recognized-array'
		);
	}

	public function testGivenInvalidLanguage_createEntityChangeOpThrowsError() {
		TermChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() {
				$this->newDescriptionsChangeOpDeserializer()->createEntityChangeOp( [
					'descriptions' => [ 'en' => [ 'language' => 'de', 'value' => 'foo' ] ]
				] );
			},
			'inconsistent-language'
		);
	}

	public function testGivenChangeRequestWithNewDescription_overridesExistingDescription() {
		$item = $this->getItemWithEnDescription();
		$newDescription = 'foo';
		$changeOp = $this->newDescriptionsChangeOpDeserializer()
			->createEntityChangeOp( [
				'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => $newDescription ] ]
			] );

		$changeOp->apply( $item, new Summary() );
		$this->assertSame( $newDescription, $item->getDescriptions()->getByLanguage( 'en' )->getText() );
	}

	public function testGivenChangeRequestWithRemove_removesDescription() {
		$item = $this->getItemWithEnDescription();
		$changeOp = $this->newDescriptionsChangeOpDeserializer()
			->createEntityChangeOp( [ 'descriptions' => [ 'en' => [ 'language' => 'en', 'remove' => '' ] ] ] );

		$changeOp->apply( $item, new Summary() );
		$this->assertFalse( $item->getFingerprint()->hasDescription( 'en' ) );
	}

	private function newDescriptionsChangeOpDeserializer() {
		return new DescriptionsChangeOpDeserializer(
			$this->getFingerPrintChangeOpFactory(),
			$this->getStringNormalizer(),
			$this->getTermChangeOpValidator()
		);
	}

	private function getItemWithEnDescription() {
		$itemContent = ItemContent::newEmpty();
		$item = $itemContent->getEntity();
		$item->setDescription( 'en', 'en-description' );

		return $item->copy();
	}

	private function getStringNormalizer() {
		return new StringNormalizer();
	}

	private function getFingerPrintChangeOpFactory() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		return new FingerprintChangeOpFactory( $mockProvider->getMockTermValidatorFactory() );
	}

	private function getTermChangeOpValidator() {
		return new TermChangeOpSerializationValidator( new StaticContentLanguages( [ 'en', 'de' ] ) );
	}

}
