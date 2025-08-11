<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

/**
 * @coversNothing
 *
 * @license GPL-2.0-or-later
 */
class ArchitectureTest {

	public function testRestApiCannotDependOnDomains(): Rule {
		return PHPat::rule()
			->classes( Selector::inNamespace( 'Wikibase\Repo\RestApi' ) )
			->shouldNotDependOn()
			->classes( Selector::inNamespace( 'Wikibase\Repo\Domains' ) )
			->because( 'The REST API must not depend on classes from any Domains namespace' );
	}
}
