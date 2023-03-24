<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Architecture;

use ArrayIterator;
use ArrayObject;
use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;

/**
 * @coversNothing
 *
 * @license GPL-2.0-or-later
 */
class ArchitectureTest {

	private const DOMAIN_MODEL = 'Wikibase\Repo\RestApi\Domain\Model';
	private const DOMAIN_READMODEL = 'Wikibase\Repo\RestApi\Domain\ReadModel';
	private const DOMAIN_SERVICES = 'Wikibase\Repo\RestApi\Domain\Services';
	private const SERIALIZATION = 'Wikibase\Repo\RestApi\Serialization';
	private const VALIDATION = 'Wikibase\Repo\RestApi\Validation';
	private const USE_CASES = 'Wikibase\Repo\RestApi\UseCases';

	public function testDomainModel(): Rule {
		return PHPat::rule()
			->classes(
				Selector::namespace( self::DOMAIN_MODEL ),
				Selector::namespace( self::DOMAIN_READMODEL )
			)
			->shouldNotDependOn()
			->classes( Selector::all() )
			->excluding( ...$this->allowedDomainModelDependencies() );
	}

	/**
	 * Domain models may depend on:
	 *  - DataModel namespaces containing entities and their parts
	 *  - other classes from their own namespace
	 */
	private function allowedDomainModelDependencies(): array {
		return [
			...$this->dataModelEntityNamespaces(),
			Selector::namespace( self::DOMAIN_MODEL ),
			Selector::namespace( self::DOMAIN_READMODEL ),
			...$this->phpCoreClasses(),
		];
	}

	public function testDomainServices(): Rule {
		return PHPat::rule()
			->classes( Selector::namespace( self::DOMAIN_SERVICES ) )
			->shouldNotDependOn()
			->classes( Selector::all() )
			->excluding( ...$this->allowedDomainServicesDependencies() );
	}

	/**
	 * Domain services may depend on:
	 *  - the domain models namespace and everything it depends on
	 *  - some hand-picked DataModel services
	 *  - other classes from their own namespace
	 */
	private function allowedDomainServicesDependencies(): array {
		return array_merge( $this->allowedDomainModelDependencies(), [
			...$this->allowedDataModelServices(),
			Selector::namespace( self::DOMAIN_SERVICES ),
		] );
	}

	public function testSerialization(): Rule {
		return PHPat::rule()
			->classes( Selector::namespace( self::SERIALIZATION ) )
			->shouldNotDependOn()
			->classes( Selector::all() )
			->excluding( ...$this->allowedSerializationDependencies() );
	}

	/**
	 * Serialization may depend on:
	 *  - the domain services namespace and everything it depends on
	 *  - the DataValues namespace
	 *  - other classes from its own namespace
	 */
	private function allowedSerializationDependencies(): array {
		return array_merge( $this->allowedDomainServicesDependencies(), [
			Selector::namespace( self::SERIALIZATION ),
		] );
	}

	public function testUseCases(): Rule {
		return PHPat::rule()
			->classes( Selector::namespace( self::USE_CASES ) )
			->shouldNotDependOn()
			->classes( Selector::all() )
			->excluding( ...$this->allowedUseCasesDependencies() );
	}

	/**
	 * Use cases may depend on:
	 *  - the serialization namespace and everything it depends on
	 *  - validation
	 *  - other classes from their own namespace
	 */
	private function allowedUseCasesDependencies(): array {
		return array_merge( $this->allowedSerializationDependencies(), [
			Selector::namespace( self::VALIDATION ),
			Selector::namespace( self::USE_CASES ),
		] );
	}

	// TODO validation

	// TODO presentation

	private function allowedDataModelServices(): array {
		return [
			Selector::classname( PropertyDataTypeLookup::class ),
			Selector::classname( StatementGuidParser::class ),
			Selector::classname( GuidGenerator::class ),
		];
	}

	private function dataModelEntityNamespaces(): array {
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
					'Wikibase\DataModel\Snak',
					'Wikibase\DataModel\Statement',
					'Wikibase\DataModel\Term',
				]
			),
			Selector::namespace( 'DataValues' ),
		];
	}

	private function phpCoreClasses(): array {
		return [
			Selector::classname( ArrayObject::class ),
			Selector::classname( ArrayIterator::class ),
			Selector::classname( \Throwable::class ),
			Selector::classname( '/^\w*Exception$/', true ),
		];
	}

}
