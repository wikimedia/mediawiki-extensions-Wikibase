<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use MediaWiki\Languages\LanguageNameUtils;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\Validation\OldItemLabelValidator;
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
		$this->assertNull( $this->newValidator()->validate( 'potato', 'en' ) );
	}

	public function testEmptyLabel_returnsValidationError(): void {
		$this->assertEquals(
			new ValidationError( OldItemLabelValidator::CODE_EMPTY, [ OldItemLabelValidator::CONTEXT_LANGUAGE => 'en' ] ),
			$this->newValidator()->validate( '', 'en' )
		);
	}

	public function testLabelTooLong_returnsValidationError(): void {
		$tooLongLabel = str_repeat( 'a', self::MAX_LENGTH + 1 );
		$this->assertEquals(
			new ValidationError(
				OldItemLabelValidator::CODE_TOO_LONG,
				[
					OldItemLabelValidator::CONTEXT_LABEL => $tooLongLabel,
					OldItemLabelValidator::CONTEXT_LANGUAGE => 'en',
					OldItemLabelValidator::CONTEXT_LIMIT => self::MAX_LENGTH,
				]
			),
			$this->newValidator()->validate( $tooLongLabel, 'en' )
		);
	}

	public function testInvalidLabel_returnsValidationError(): void {
		$invalidLabel = "item with tab character \t not allowed";
		$this->assertEquals(
			new ValidationError(
				OldItemLabelValidator::CODE_INVALID,
				[ OldItemLabelValidator::CONTEXT_LABEL => $invalidLabel, OldItemLabelValidator::CONTEXT_LANGUAGE => 'en' ]
			),
			$this->newValidator()->validate( $invalidLabel, 'en' )
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
