<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Validators;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\Store\TermsCollisionDetector;
use Wikibase\Repo\Validators\LabelUniquenessValidator;

/**
 * @covers \Wikibase\Repo\Validators\LabelUniquenessValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LabelUniquenessValidatorTest extends \PHPUnit\Framework\TestCase {

	public function provideErrors() {
		$terms = [];

		yield [
			$terms,
			[],
			[],
		];

		$terms[] = new Term( 'sv', 'derp' );

		yield [
			$terms,
			[
				new TermList( $terms ),
			],
			[], // no conflict
		];

		$terms[] = new Term( 'sv', 'derp' );

		yield [
			$terms,
			[
				new TermList( $terms ),
			],
			[
				'P1' => [ new Term( 'sv', 'derp' ) ],
			],
		];
	}

	/**
	 *
	 * @dataProvider provideErrors
	 * @param array $terms
	 * @param array $expectedLabelCollisionLookups every lookup generates a conflict in this test
	 * @param array $conflicts
	 */
	public function testLabelUniquenessErrors(
		array $terms,
		array $expectedLabelCollisionLookups,
		array $conflicts
	) {
		$propertyId = new NumericPropertyId( 'P1234' );
		$property = new Property(
			$propertyId,
			new Fingerprint( new TermList( $terms ) ),
			'string'
		);

		$collisionDetector = $this->createMock( TermsCollisionDetector::class );
		$collisionDetector
			->expects( $this->once() )
			->method( 'detectLabelsCollision' )
			->with( ...$expectedLabelCollisionLookups )
			->willReturn( $conflicts );

		$validator = new LabelUniquenessValidator(
			$collisionDetector
		);

		$result = $validator->validateEntity( $property );

		$this->assertInstanceOf( Result::class, $result );
		$this->assertCount( count( $conflicts ), $result->getErrors() );

		$this->assertSame( $conflicts === [], $result->isValid() );

		for ( $i = 0; $i < count( $conflicts ); $i++ ) {
			$error = $result->getErrors()[$i];

			$this->assertEquals( 'label-conflict', $error->getCode() );

			// second parameter is conflicting entityID
			$this->assertArrayHasKey( $error->getParameters()[2]->getSerialization(), $conflicts );

			$this->assertEquals( $terms[$i]->getText(), $error->getParameters()[0] );
			$this->assertEquals( $terms[$i]->getLanguageCode(), $error->getParameters()[1] );
		}
	}
}
