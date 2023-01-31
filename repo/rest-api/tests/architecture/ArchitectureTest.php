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
	private const VALIDATION = 'Wikibase\Repo\RestApi\Validation';
	private const SERIALIZATION = 'Wikibase\Repo\RestApi\Serialization';
	private const USE_CASES = 'Wikibase\Repo\RestApi\UseCases';

	/**
	 * Domain models may depend on:
	 *  - DataModel namespaces containing entities and their parts
	 *  - other classes from their own namespace
	 */
	public function testDomainModel(): Rule {
		return PHPat::rule()
			->classes(
				Selector::namespace( self::DOMAIN_MODEL ),
				Selector::namespace( self::DOMAIN_READMODEL )
			)
			->shouldNotDependOn()
			->classes( Selector::all() )
			->excluding(
				Selector::namespace( self::DOMAIN_MODEL ),
				Selector::namespace( self::DOMAIN_READMODEL ),
				...$this->dataModelEntityNamespaces(),
				...$this->phpCoreClasses(),
			);
	}

	/**
	 * Domain services may depend on:
	 *  - domain models
	 *  - DataModel namespaces containing entities and their parts
	 *  - some hand-picked DataModel services
	 *  - other classes from their own namespace
	 */
	public function testDomainServices(): Rule {
		return PHPat::rule()
			->classes( Selector::namespace( self::DOMAIN_SERVICES ) )
			->shouldNotDependOn()
			->classes( Selector::all() )
			->excluding(
				Selector::namespace( self::DOMAIN_MODEL ),
				Selector::namespace( self::DOMAIN_READMODEL ),
				Selector::namespace( self::DOMAIN_SERVICES ),
				Selector::namespace( 'Wikibase\Repo\RestApi\Domain\Exceptions' ), // consider moving into services namespace?
				...$this->allowedDataModelServices(),
				...$this->dataModelEntityNamespaces(),
				...$this->phpCoreClasses(),
			);
	}

	/**
	 * Use cases may depend on:
	 *  - validation
	 *  - serialization
	 *  - domain services
	 *  - domain models
	 *  - DataModel namespaces containing entities and their parts
	 *  - some hand-picked DataModel services
	 *  - other classes from their own namespace
	 */
	public function testUseCases(): Rule {
		return PHPat::rule()
			->classes( Selector::namespace( self::USE_CASES ) )
			->shouldNotDependOn()
			->classes( Selector::all() )
			->excluding(
				Selector::namespace( self::VALIDATION ),
				Selector::namespace( self::SERIALIZATION ),
				Selector::namespace( self::USE_CASES ),
				Selector::namespace( self::DOMAIN_MODEL ),
				Selector::namespace( self::DOMAIN_READMODEL ),
				Selector::namespace( self::DOMAIN_SERVICES ),
				Selector::namespace( 'Wikibase\Repo\RestApi\Domain\Exceptions' ), // consider moving into services namespace?
				...$this->allowedDataModelServices(),
				...$this->dataModelEntityNamespaces(),
				...$this->phpCoreClasses(),
			);
	}

	// TODO validation

	// TODO serialization

	// TODO presentation

	private function allowedDataModelServices(): array {
		return [
			Selector::classname( PropertyDataTypeLookup::class ),
			Selector::classname( StatementGuidParser::class ),
			Selector::classname( GuidGenerator::class ),
		];
	}

	/**
	 * These are listed in such a complicated way so that only DataModel entities and their parts are allowed without the
	 * namespaces nested within DataModel like e.g. Wikibase\DataModel\Serializers.
	 */
	private function dataModelEntityNamespaces(): array {
		return array_map(
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
		);
	}

	private function phpCoreClasses(): array {
		return [
			Selector::classname( ArrayObject::class ),
			Selector::classname( ArrayIterator::class ),
			Selector::classname( '/^\w*Exception$/', true ),
		];
	}

}
