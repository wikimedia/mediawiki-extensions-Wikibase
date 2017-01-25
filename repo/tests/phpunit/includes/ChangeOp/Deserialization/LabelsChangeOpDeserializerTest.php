<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\ChangeOp\Deserialization\LabelsChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator;
use Wikibase\StringNormalizer;

/**
 * @covers Wikibase\Repo\ChangeOp\Deserialization\LabelsChangeOpDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class LabelsChangeOpDeserializerTest extends \PHPUnit_Framework_TestCase {

	public function testGivenLabelsFieldNotAnArray_createEntityChangeOpThrowsError() {
		TermChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() {
				$this->newLabelsChangeOpDeserializer(
					$this->getFingerPrintChangeOpFactory()
				)->createEntityChangeOp( [ 'labels' => null ] );
			},
			'not-recognized-array'
		);
	}

	public function testGivenInvalidLanguage_createEntityChangeOpThrowsError() {
		TermChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() {
				$this->newLabelsChangeOpDeserializer(
					$this->getFingerPrintChangeOpFactory()
				)->createEntityChangeOp( [ 'labels' => [ 'en' => [ 'language' => 'de', 'value' => 'foo' ] ] ] );
			},
			'inconsistent-language'
		);
	}

	public function testGivenChangeRequestWithNewLabels_callsNewSetLabelOp() {
		$factory = $this->getFingerPrintChangeOpFactory();
		$factory->expects( $this->once() )
			->method( 'newSetLabelOp' )
			->willReturn( new ChangeOps() );

		$this->newLabelsChangeOpDeserializer( $factory )
			->createEntityChangeOp( [ 'labels' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ] ] );
	}

	public function testGivenChangeRequestWithRemove_callsNewRemoveLabelOp() {
		$factory = $this->getFingerPrintChangeOpFactory();
		$factory->expects( $this->once() )
			->method( 'newRemoveLabelOp' )
			->willReturn( new ChangeOps() );

		$this->newLabelsChangeOpDeserializer( $factory )
			->createEntityChangeOp( [ 'labels' => [ 'en' => [ 'language' => 'en', 'remove' => '' ] ] ] );
	}

	private function newLabelsChangeOpDeserializer(
		FingerprintChangeOpFactory $factory
	) {
		return new LabelsChangeOpDeserializer(
			$factory,
			$this->getStringNormalizer(),
			$this->getTermChangeOpValidator()
		);
	}

	private function getStringNormalizer() {
		return new StringNormalizer();
	}

	private function getFingerPrintChangeOpFactory() {
		return $this->getMockBuilder( FingerprintChangeOpFactory::class )
			->disableOriginalConstructor()
			->getMock();
	}

	private function getTermChangeOpValidator() {
		return new TermChangeOpSerializationValidator( new StaticContentLanguages( [ 'en', 'de' ] ) );
	}

}
