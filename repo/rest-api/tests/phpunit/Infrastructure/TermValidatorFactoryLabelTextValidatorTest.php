<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use MediaWiki\Languages\LanguageNameUtils;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Infrastructure\TermValidatorFactoryLabelTextValidator;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\TermValidatorFactoryLabelTextValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermValidatorFactoryLabelTextValidatorTest extends TestCase {

	private const MAX_LENGTH = 50;

	public function testGivenValidLabel_returnsNull(): void {
		$this->assertNull( $this->newValidator()->validate( 'potato' ) );
	}

	public function testEmptyLabel_returnsValidationError(): void {
		$this->assertEquals(
			new ValidationError( ItemLabelValidator::CODE_EMPTY ),
			$this->newValidator()->validate( '' )
		);
	}

	public function testLabelTooLong_returnsValidationError(): void {
		$tooLonglabel = str_repeat( 'a', self::MAX_LENGTH + 1 );
		$this->assertEquals(
			new ValidationError(
				ItemLabelValidator::CODE_TOO_LONG,
				[
					ItemLabelValidator::CONTEXT_VALUE => $tooLonglabel,
					ItemLabelValidator::CONTEXT_LIMIT => self::MAX_LENGTH,
				]
			),
			$this->newValidator()->validate( $tooLonglabel )
		);
	}

	public function testInvalidLabel_returnsValidationError(): void {
		$invalidLabel = "item with tab character \t not allowed";
		$this->assertEquals(
			new ValidationError(
				ItemLabelValidator::CODE_INVALID,
				[ ItemLabelValidator::CONTEXT_VALUE => $invalidLabel ]
			),
			$this->newValidator()->validate( $invalidLabel )
		);
	}

	private function newValidator(): TermValidatorFactoryLabelTextValidator {
		return new TermValidatorFactoryLabelTextValidator( new TermValidatorFactory(
			self::MAX_LENGTH,
			WikibaseRepo::getTermsLanguages()->getLanguages(),
			WikibaseRepo::getEntityIdParser(),
			WikibaseRepo::getTermsCollisionDetectorFactory(),
			WikibaseRepo::getTermLookup(),
			$this->createStub( LanguageNameUtils::class )
		) );
	}

}
