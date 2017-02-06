<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use PHPUnit_Framework_Constraint_IsInstanceOf;
use Prophecy\Argument;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOpAliases;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\ChangeOp\Deserialization\AliasesChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\StringNormalizer;

/**
 * @covers Wikibase\Repo\ChangeOp\Deserialization\AliasesChangeOpDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class AliasesChangeOpDeserializerTest extends \PHPUnit_Framework_TestCase {

	public function testGivenAliasesFieldNotAnArray_createEntityChangeOpThrowsError() {
		TermChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() {
				$this->newAliasesChangeOpDeserializer(
					$this->getFingerPrintChangeOpFactory()
				)->createEntityChangeOp( [ 'aliases' => null ] );
			},
			'not-recognized-array'
		);
	}

	public function testGivenInvalidLanguage_createEntityChangeOpThrowsError() {
		TermChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() {
				$this->newAliasesChangeOpDeserializer(
					$this->getFingerPrintChangeOpFactory()
				)->createEntityChangeOp( [ 'aliases' => [ 'en' => [ 'language' => 'de', 'value' => 'foo' ] ] ] );
			},
			'inconsistent-language'
		);
	}

	public function testGivenChangeRequestWithNewAliases_callsNewSetAliasesOp() {
		$termValidatorFactory = $this->getMockBuilder( TermValidatorFactory::class )
			->disableOriginalConstructor()
			->getMock();
		$changeOpFactory = new FingerprintChangeOpFactory( $termValidatorFactory );

		$changeOpDeserializer = $this->newAliasesChangeOpDeserializer( $changeOpFactory );
		$resultChangeOp = $changeOpDeserializer->createEntityChangeOp(
			[ 'aliases' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ] ]
		);

		$this->assertIncludesChangeOp(
			new ChangeOpAliases( 'en', [ 'foo' ], 'set', $termValidatorFactory ),
			$resultChangeOp
		);
	}

	private function assertIncludesChangeOp( ChangeOp $expectedChangeOp, $actual ) {
		$this->assertInstanceOf( ChangeOp::class, $actual );
		$expectedChangeOpClass = get_class( $expectedChangeOp );

		$this->assertThat(
			$actual,
			$this->logicalOr(
				new PHPUnit_Framework_Constraint_IsInstanceOf( $expectedChangeOpClass ),
				new PHPUnit_Framework_Constraint_IsInstanceOf( ChangeOps::class )
			)
		);

		if ( $actual instanceof ChangeOps ) {
			$this->assertContains(
				$expectedChangeOp,
				$actual->getChangeOps(),
				'Must contain similar change op',
				false, // case-sensitive matching does not matter as $expectedChangeOp is not a string
				false, // non-strict equality of objects
				true // strict equality of non-object
			);
		}
	}

	public function testGivenChangeRequestWithRemove_callsNewRemoveAliasesOp() {
		$factory = $this->getFingerPrintChangeOpFactory();
		$factory->expects( $this->once() )
			->method( 'newRemoveAliasesOp' )
			->willReturn( new ChangeOps() );

		$this->newAliasesChangeOpDeserializer( $factory )
			->createEntityChangeOp(
				[ 'aliases' => [
					'en' => [ 'language' => 'en', 'value' => 'foo', 'remove' => '' ] ]
				]
			);
	}

	public function testGivenChangeRequestWithAdd_createsChangeOpsWithChangeOpAddingAliases() {
		$changeOpAddingAliases = $this->prophesize( ChangeOp::class );
		$changeOpAddingAliases->apply( Argument::any(), Argument::any() )->willReturn( null );
		$factory = $this->getFingerPrintChangeOpFactory();
		$factory->method( 'newAddAliasesOp' )->willReturn( $changeOpAddingAliases->reveal() );

		$resultChangeOp = $this->newAliasesChangeOpDeserializer( $factory )
			->createEntityChangeOp(
				[ 'aliases' => [
					'en' => [ 'language' => 'en', 'value' => 'foo', 'add' => '' ] ]
				]
			);

		$resultChangeOp->apply( $this->getMock( EntityDocument::class ) );
		$changeOpAddingAliases->apply( Argument::any(), Argument::any() )->shouldHaveBeenCalled();
	}

	private function newAliasesChangeOpDeserializer(
		FingerprintChangeOpFactory $factory
	) {
		return new AliasesChangeOpDeserializer(
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
