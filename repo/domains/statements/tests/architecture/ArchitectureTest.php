<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Statements\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

/**
 * @coversNothing
 *
 * @license GPL-2.0-or-later
 */
class ArchitectureTest {

	private const DOMAIN_READMODEL = 'Wikibase\Repo\Domains\Statements\Domain\ReadModel';

	public function testDomainReadModel(): Rule {
		return PHPat::rule()
			->classes( Selector::inNamespace( self::DOMAIN_READMODEL ) )
			->canOnlyDependOn()
			->classes( ...$this->allowedDomainReadModelDependencies() );
	}

	/**
	 * Domain read models may depend on:
	 *  - DataModel namespaces containing entities and their parts
	 *  - other classes from their own namespace
	 */
	private function allowedDomainReadModelDependencies(): array {
		return [
			...$this->dataModelNamespaces(),
			Selector::inNamespace( self::DOMAIN_READMODEL ),
		];
	}

	private function dataModelNamespaces(): array {
		return [
			// These are listed in such a complicated way so that only DataModel entities and their parts are allowed without the
			// namespaces nested within DataModel like e.g. Wikibase\DataModel\Serializers.
			...array_map(
				fn( string $escapedNamespace ) => Selector::classname(
					'/^' . preg_quote( $escapedNamespace ) . '\\\\\w+$/',
					true
				),
				[
					'Wikibase\DataModel',
					'Wikibase\DataModel\Entity',
					'Wikibase\DataModel\Exception',
					'Wikibase\DataModel\Snak',
					'Wikibase\DataModel\Statement',
					'Wikibase\DataModel\Term',
				]
			),
			Selector::inNamespace( 'DataValues' ),
		];
	}

}
