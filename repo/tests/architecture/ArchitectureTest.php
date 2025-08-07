<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

/**
 * @coversNothing
 *
 * @license GPL-2.0-or-later
 */
class ArchitectureTest {

	private const DOMAINS_NAMESPACE = 'Wikibase\Repo\Domains';
	private const INFRASTRUCTURE = 'Infrastructure';
	private const REGEX_DOMAIN_USE_CASES = '/^Wikibase\\\\Repo\\\\Domains\\\\.+\\\\Application\\\\UseCases/';
	private const REGEX_DOMAIN_READMODEL = '/^Wikibase\\\\Repo\\\\Domains\\\\.+\\\\Domain\\\\ReadModel/';

	private function domainNamespaces(): array {
		return array_filter(
			array_keys(
				json_decode( file_get_contents( __DIR__ . '/../../../extension-repo.json' ), true )[ 'AutoloadNamespaces' ]
			),
			fn( string $namespace ) => str_starts_with( $namespace, self::DOMAINS_NAMESPACE )
		);
	}

	/**
	 * @return iterable<Rule>
	 */
	public function testCrossDomainDependenciesExceptInfrastructure(): iterable {
		foreach ( $this->domainNamespaces() as $domain ) {
			yield PHPat::rule()
				->classes( Selector::inNamespace( $domain ) )
				// excluded here, see infrastructure dependency rules below
				->excluding( Selector::inNamespace( $domain . self::INFRASTRUCTURE ) )
				->shouldNotDependOn()
				->classes( Selector::inNamespace( self::DOMAINS_NAMESPACE ) )
				->excluding( Selector::inNamespace( $domain ) )
				->because( 'Core classes can only depend on their own domain.' );
		}
	}

	/**
	 * @return iterable<Rule>
	 */
	public function testCrossDomainInfrastructureDependencies(): iterable {
		foreach ( $this->domainNamespaces() as $domain ) {
			yield PHPat::rule()
				->classes( Selector::inNamespace( $domain . self::INFRASTRUCTURE ) )
				->shouldNotDependOn()
				->classes( Selector::inNamespace( self::DOMAINS_NAMESPACE ) )
				->excluding(
					// own domain
					Selector::inNamespace( $domain ),
					...$this->allowedCrossDomainClasses()
				)->because( "Infrastructure classes can only depend on other domains' use cases or domain (read) models." );
		}
	}

	private function allowedCrossDomainClasses(): array {
		return [
			// other domain usecases
			Selector::inNamespace( self::REGEX_DOMAIN_USE_CASES, true ),
			// other domain readmodels
			Selector::inNamespace( self::REGEX_DOMAIN_READMODEL, true ),
			// search domain models (we don't have readmodels there)
			Selector::inNamespace( 'Wikibase\Repo\Domains\Search\Domain\Model' ),
		];
	}

}
