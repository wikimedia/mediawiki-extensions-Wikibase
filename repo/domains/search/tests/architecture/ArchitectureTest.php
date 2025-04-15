<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

/**
 * @coversNothing
 *
 * @license GPL-2.0-or-later
 */
class ArchitectureTest {

	private const DOMAIN_MODEL = 'Wikibase\Repo\Domains\Search\Domain\Model';
	private const DOMAIN_SERVICES = 'Wikibase\Repo\Domains\Search\Domain\Services';
	private const VALIDATION = 'Wikibase\Repo\Domains\Search\Application\Validation';
	private const USE_CASES = 'Wikibase\Repo\Domains\Search\Application\UseCases';

	public function testDomainModel(): Rule {
		return PHPat::rule()
			->classes( Selector::inNamespace( self::DOMAIN_MODEL ) )
			->canOnlyDependOn()
			->classes( ...$this->allowedDomainModelDependencies() );
	}

	/**
	 * Domain models may depend on:
	 *  - DataModel namespaces containing entities and their parts
	 *  - other classes from their own namespace
	 */
	private function allowedDomainModelDependencies(): array {
		return [
			...$this->dataModelNamespaces(),
			Selector::inNamespace( self::DOMAIN_MODEL ),
		];
	}

	private function dataModelNamespaces(): array {
		return [
			// These are listed in such a complicated way so that only DataModel entities and their parts are allowed
			// without the namespaces nested within DataModel like e.g. Wikibase\DataModel\Serializers.
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

	public function testDomainServices(): Rule {
		return PHPat::rule()
			->classes( Selector::inNamespace( self::DOMAIN_SERVICES ) )
			->canOnlyDependOn()
			->classes( ...$this->allowedDomainServicesDependencies() );
	}

	/**
	 * Domain services may depend on:
	 *  - the domain model namespace and everything it depends on
	 *  - other classes from their own namespace
	 */
	private function allowedDomainServicesDependencies(): array {
		return [
			...$this->allowedDomainModelDependencies(),
			Selector::inNamespace( self::DOMAIN_SERVICES ),
		];
	}

	public function testValidation(): Rule {
		return PHPat::rule()
			->classes( Selector::inNamespace( self::VALIDATION ) )
			->canOnlyDependOn()
			->classes( ...$this->allowedValidationDependencies() );
	}

	/**
	 * Validation may depend on:
	 *  - the domain services and everything it depends on
	 *  - other classes from its own namespace
	 */
	private function allowedValidationDependencies(): array {
		return [
			...$this->allowedDomainServicesDependencies(),
			Selector::inNamespace( self::VALIDATION ),
		];
	}

	public function testUseCases(): Rule {
		return PHPat::rule()
			->classes( Selector::inNamespace( self::USE_CASES ) )
			->canOnlyDependOn()
			->classes( ...$this->allowedUseCasesDependencies() );
	}

	/**
	 * Use cases may depend on:
	 *  - the validation namespace and everything it depends on
	 *  - the domain services namespace and everything it depends on
	 *  - other classes from their own namespace
	 */
	private function allowedUseCasesDependencies(): array {
		return [
			...$this->allowedValidationDependencies(),
			...$this->allowedDomainServicesDependencies(),
			Selector::inNamespace( self::USE_CASES ),
		];
	}

}
