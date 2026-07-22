<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Statements\Architecture;

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

	private const DOMAIN_READMODEL = 'Wikibase\Repo\Domains\Statements\Domain\ReadModel';
	private const DOMAIN_SERVICES = 'Wikibase\Repo\Domains\Statements\Domain\Services';
	private const SERIALIZATION = 'Wikibase\Repo\Domains\Statements\Application\Serialization';

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

	public function testDomainServices(): Rule {
		return PHPat::rule()
			->classes( Selector::inNamespace( self::DOMAIN_SERVICES ) )
			->canOnlyDependOn()
			->classes( ...$this->allowedDomainServicesDependencies() );
	}

	/**
	 * Domain services may depend on:
	 *  - the domain read models namespace and everything it depends on
	 *  - some hand-picked DataModel services
	 *  - other classes from their own namespace
	 */
	private function allowedDomainServicesDependencies(): array {
		return [
			...$this->allowedDomainReadModelDependencies(),
			...$this->allowedDataModelServices(),
			Selector::inNamespace( self::DOMAIN_SERVICES ),
		];
	}

	public function testSerialization(): Rule {
		return PHPat::rule()
			->classes( Selector::inNamespace( self::SERIALIZATION ) )
			->canOnlyDependOn()
			->classes( ...$this->allowedSerializationDependencies() );
	}

	/**
	 * Serialization may depend on:
	 *  - the domain read models namespace and everything it depends on
	 *  - other classes from its own namespace
	 */
	private function allowedSerializationDependencies(): array {
		return [
			...$this->allowedDomainReadModelDependencies(),
			Selector::inNamespace( self::SERIALIZATION ),
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
