<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Services\Statement\StatementGuidValidator;

/**
 * @coversNothing
 *
 * @license GPL-2.0-or-later
 */
class ArchitectureTest {

	private const DOMAIN_MODEL = 'Wikibase\Repo\RestApi\Domain\Model';
	private const DOMAIN_READMODEL = 'Wikibase\Repo\RestApi\Domain\ReadModel';
	private const DOMAIN_SERVICES = 'Wikibase\Repo\RestApi\Domain\Services';
	private const SERIALIZATION = 'Wikibase\Repo\RestApi\Application\Serialization';
	private const VALIDATION = 'Wikibase\Repo\RestApi\Application\Validation';
	private const USE_CASES = 'Wikibase\Repo\RestApi\Application\UseCases';
	private const USE_CASE_REQUEST_VALIDATION = 'Wikibase\Repo\RestApi\Application\UseCaseRequestValidation';

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

	public function testValidation(): Rule {
		return PHPat::rule()
			->classes( Selector::namespace( self::VALIDATION ) )
			->shouldNotDependOn()
			->classes( Selector::all() )
			->excluding( ...$this->allowedValidationDependencies() );
	}

	/**
	 * Validation may depend on:
	 *  - the serialization namespace and everything it depends on
	 *  - other classes from its own namespace
	 */
	private function allowedValidationDependencies(): array {
		return array_merge( $this->allowedSerializationDependencies(), [
			Selector::namespace( self::VALIDATION ),
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
	 *  - the validation namespace and everything it depends on
	 *  - the use case request validation namespace
	 *  - other classes from their own namespace
	 */
	private function allowedUseCasesDependencies(): array {
		return array_merge( $this->allowedValidationDependencies(), [
			Selector::namespace( self::USE_CASE_REQUEST_VALIDATION ),
			Selector::namespace( self::USE_CASES ),
		] );
	}

	public function testUseCaseRequestValidation(): Rule {
		return PHPat::rule()
			->classes( Selector::namespace( self::USE_CASE_REQUEST_VALIDATION ) )
			->shouldNotDependOn()
			->classes( Selector::all() )
			->excluding( ...$this->allowedUseCaseRequestValidationDependencies() );
	}

	/**
	 * Use case request validation may depend on:
	 *  - the validation namespace and everything it depends on
	 *  - the use case namespace
	 *  - other classes from their own namespace
	 */
	private function allowedUseCaseRequestValidationDependencies(): array {
		return array_merge( $this->allowedValidationDependencies(), [
			Selector::namespace( self::USE_CASES ),
			Selector::namespace( self::USE_CASE_REQUEST_VALIDATION ),
		] );
	}

	private function allowedDataModelServices(): array {
		return [
			Selector::classname( PropertyDataTypeLookup::class ),
			Selector::classname( StatementGuidParser::class ),
			Selector::classname( StatementGuidValidator::class ),
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

}
