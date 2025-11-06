<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;

/**
 * @coversNothing
 *
 * @license GPL-2.0-or-later
 */
class ArchitectureTest {

	private const REUSE_DOMAIN = 'Wikibase\Repo\Domains\Reuse';
	private const DOMAIN_MODEL = self::REUSE_DOMAIN . '\Domain\Model';
	private const DOMAIN_SERVICES = self::REUSE_DOMAIN . '\Domain\Services';
	private const USE_CASES = self::REUSE_DOMAIN . '\Application\UseCases';

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

	public function testDomainServices(): Rule {
		return PHPat::rule()
			->classes( Selector::inNamespace( self::DOMAIN_SERVICES ) )
			->canOnlyDependOn()
			->classes( ...$this->allowedDomainServicesDependencies() );
	}

	/**
	 * Domain services may depend on:
	 *  - the domain models namespace and everything it depends on
	 *  - some hand-picked DataModel services
	 *  - other classes from their own namespace
	 */
	private function allowedDomainServicesDependencies(): array {
		return [
			...$this->allowedDomainModelDependencies(),
			...$this->allowedDataModelServices(),
			Selector::inNamespace( self::DOMAIN_SERVICES ),
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
	 *  - the domain services namespace and everything it depends on
	 *  - other classes from their own namespace
	 */
	private function allowedUseCasesDependencies(): array {
		return [
			...$this->allowedDomainServicesDependencies(),
			Selector::inNamespace( self::USE_CASES ),
		];
	}

	private function allowedDataModelServices(): array {
		return [
			Selector::classname( PropertyDataTypeLookup::class ),
			Selector::classname( PropertyDataTypeLookupException::class ),
			Selector::classname( StatementGuidParser::class ),
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
