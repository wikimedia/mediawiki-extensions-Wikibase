<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions\CaptchaChallenge;

/**
 * @coversNothing
 *
 * @license GPL-2.0-or-later
 */
class ArchitectureTest {

	public function testRestApiCannotDependOnDomains(): Rule {
		return PHPat::rule()
			->classes( Selector::inNamespace( 'Wikibase\Repo\RestApi' ) )
			->shouldNot()
			->dependOn()
			->classes( Selector::inNamespace( 'Wikibase\Repo\Domains' ) )
			->excluding(
				// This is a necessary exception while EntityUpdater still lives in the CRUD domain and is being reused by Lexeme.
				// See T434770.
				Selector::classname( CaptchaChallenge::class ),
			)
			->because( 'The REST API must not depend on classes from any Domains namespace' );
	}
}
