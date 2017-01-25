<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\ItemContent;
use Wikibase\Lib\StaticContentLanguages;
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
				$this->newLabelsChangeOpDeserializer()->createEntityChangeOp( [ 'labels' => null ] );
			},
			'not-recognized-array'
		);
	}

	public function testGivenInvalidLanguage_createEntityChangeOpThrowsError() {
		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() {
				$this->newLabelsChangeOpDeserializer()->createEntityChangeOp( [
					'labels' => [ 'en' => [ 'language' => 'de', 'value' => 'foo' ] ]
				] );
			},
			'inconsistent-language'
		);
	}

	public function testGivenChangeRequestWithNewLabel_overridesExistingLabel() {
		$item = $this->getItemWithEnLabel();
		$newLabel = 'foo';
		$changeOp = $this->newLabelsChangeOpDeserializer()
			->createEntityChangeOp( [ 'labels' => [ 'en' => [ 'language' => 'en', 'value' => $newLabel ] ] ] );

		$changeOp->apply( $item, new Summary() );
		$this->assertSame( $newLabel, $item->getLabels()->getByLanguage( 'en' )->getText() );
	}

	public function testGivenChangeRequestWithRemove_removesLabel() {
		$item = $this->getItemWithEnLabel();
		$changeOp = $this->newLabelsChangeOpDeserializer()
			->createEntityChangeOp( [ 'labels' => [ 'en' => [ 'language' => 'en', 'remove' => '' ] ] ] );

		$changeOp->apply( $item, new Summary() );
		$this->assertFalse( $item->getFingerprint()->hasLabel( 'en' ) );
	}

	private function newLabelsChangeOpDeserializer() {
		return new LabelsChangeOpDeserializer(
			$this->getFingerPrintChangeOpFactory(),
			$this->getStringNormalizer(),
			$this->getTermChangeOpValidator()
		);
	}

	private function getItemWithEnLabel() {
		$itemContent = ItemContent::newEmpty();
		$item = $itemContent->getEntity();
		$item->setLabel( 'en', 'en-label' );

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
