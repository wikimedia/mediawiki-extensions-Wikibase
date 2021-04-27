# Services

Since the [Wikibase Service Migration][] of spring 2021, Wikibase uses the MediaWiki service container for practically all of its services.
The services are defined in the wiring files, `repo/WikibaseRepo.ServiceWiring.php` and `client/WikibaseClient.ServiceWiring.php`,
using either the `WikibaseRepo.` or `WikibaseClient.` prefix.
The [WikibaseRepo][] and [WikibaseClient][] classes act as accessors for those services,
getting them from a given service container (falling back to the default MediaWiki one) and adding static return types.

Code that requires a service should always get that service _injected_, rather than ad-hoc calling `WikibaseRepo::getSomeService()`.
(At the time of writing, the WikibaseClient Lua-related code is the largest “area” violating this principle.
One hopes it’ll be fixed eventually.)
API modules, special pages, and hook handlers can directly specify the services they require in the extension JSON file,
using the `"services"` key;
MediaWiki will then directly call the constructor or factory function with all the declared services.
Other classes should usually also take their required services as constructor arguments,
and it’s then up to the code calling the constructor to obtain an instance of the service class.
Often, that code is in one of the wiring files,
in which case it can get the service from the `$services` container.

## Adding new services

The following steps are necessary to add a new service to Wikibase:

Start by a callback to create a service instance to the service wiring file, like this:

```php
'WikibaseRepo.ServiceName' => function ( MediaWikiServices $services ): ServiceClass {
	return new ServiceClass(
		$services->getSomeService(),
		WikibaseRepo::getOtherService( $services ),
		// ...
	);
},
```

The wiring functions should be sorted in each file, but PHPCBF can take care of that for you.

Next, add an accessor method to the [WikibaseRepo][] or [WikibaseClient][] class, like this:

```php
public static function getServiceName( ContainerInterface $services = null ): ServiceClass {
	return ( $services ?: MediaWikiServices::getInstance() )
		->get( 'WikibaseRepo.ServiceName' );
}
```

Then, add a test for the service wiring in either `repo/tests/phpunit/unit/ServiceWiring/` or `client/tests/phpunit/unit/includes/ServiceWiring/`,
in a new class named after the service name.
You can follow the existing tests for guidance;
in many cases, there will only be a single test function, typically named `testConstruction()`, 
with three “sections”: mocking other services, getting an instance of the service being tested, and performing assertions on it.

Mocking all the services used in the service wiring is necessary to ensure
that the test is a proper unit test and doesn’t use any services from the default container.
Wikibase services are mocked like this:

```php
$this->mockService( 'WikibaseRepo.ServiceName',
	$this->createMock( ServiceClass::class ) );
```

(Sometimes, a real implementation is easy enough to use that `createMock()` is not necessary.)
MediaWiki services are mocked like this:

```php
$this->serviceContainer->expects( $this->once() )
	->method( 'getSomeService' )
	->willReturn( ... );
```

The `willReturn()` can be omitted if you don’t have any special requirements for the return value:
all the `MediaWikiServices` methods have static return types,
so PHPUnit automatically generates a mock object of the appropriate type by default.

To get the service instance, use the `getService()` method:

```php
$serviceName = $this->getService( 'WikibaseRepo.ServiceName' );
```

Assertions on the value should at least include a type check:

```php
$this->assertInstanceOf( ServiceClass::class, $serviceName );
```

Sometimes, additional assertions are possible.
Use your own judgment, or discuss in code review,
at which point those additions are really tests for the service class rather than the wiring
(which means they should live elsewhere).
If `assertInstanceOf()` is the only assertion,
the `$serviceName` variable is often inlined.

Finally, use the service wherever you need it.

## Legacy service containers

Certain classes still act as service containers or factories in their own right,
and have not yet been fully migrated to the MediaWiki service container.
These include:

- [Store][] and [ClientStore][]
- [WikibaseServices][], [MultipleEntitySourceServices][] and [SingleEntitySourceServices][]

Please do not add any new services to these classes.

[Wikibase Service Migration]: https://phabricator.wikimedia.org/project/profile/5203/
[WikibaseRepo]: @ref Wikibase::Repo::WikibaseRepo
[WikibaseClient]: @ref Wikibase::Client::WikibaseClient
[Store]: @ref Wikibase::Repo::Store::Store
[ClientStore]: @ref Wikibase::Client::Store::ClientStore
[WikibaseServices]: @ref Wikibase::DataAccess::WikibaseServices
[MultipleEntitySourceServices]: @ref Wikibase::DataAccess::MultipleEntitySourceServices
[SingleEntitySourceServices]: @ref Wikibase::DataAccess::SingleEntitySourceServices
