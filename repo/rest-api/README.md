# Wikibase REST API

## Configuration

Enable the feature toggle:
```php
$wgWBRepoSettings['restApiEnabled'] = true;
```

## OpenAPI Specification

REST API specification is provided using OpenAPI specification in `specs` directory.

Specification can "built" (i.e. compiled to a single JSON OpenAPI specs file) and validated using provided npm scripts.

To modify API specs, install npm dependencies first, e.g. using the following command:

```
docker run --rm --user $(id -u):$(id -g) -v $PWD:/app -w /app node:16 npm install
```

API specs can be validated using npm `test` script, e.g. by running:

```
docker run --rm --user $(id -u):$(id -g) -v $PWD:/app -w /app node:16 npm test
```

API specs can be bundled into a single file using npm `build:spec` script, e.g. by running:

```
docker run --rm --user $(id -u):$(id -g) -v $PWD:/app -w /app node:16 npm run build:spec
```

Autodocs can be generated from the API specification using npm `build:docs` script, e.g. by running:

```
docker run --rm --user $(id -u):$(id -g) -v $PWD:/app -w /app node:16 npm run build:docs
```

The autodocs and/or bundled specification OpenAPI files are generated to the `dist` directory.

## Development

* @subpage rest_adr_index

### Project structure
This REST API follows the [Hexagonal Architecture](https://alistair.cockburn.us/hexagonal-architecture/) and takes inspiration from [an article about Netflix's use of the hexagonal architecture](https://netflixtechblog.com/ready-for-changes-with-hexagonal-architecture-b315ec967749). This decision is documented in an ADR: @subpage rest_adr_0001.

![Hexagonal Architecture Diagram](./hexagonal_architecture.drawio.svg)

#### Directory structure

- `docs/`
  - `adr/`: [Architectural Decision Records](https://adr.github.io/)
- `dist/`: the built OpenAPI specification and swagger documentation
- `specs/`: source of the OpenAPI specification
- `src/`
  - `DataAccess/`: implementations of services that bind to persistent storage
  - `Domain/`: domain models and services
  - `Presentation/`: presenter and converter classes to manipulate the output as part of the transport layer
  - `RouteHandlers/` create and pass request DTO into use cases and return HTTP responses
  - `UseCases/`: one directory per use case
- `tests/`
  - `mocha/`: tests using the mocha framework
    - `api-testing/`: end-to-end tests using [MediaWiki's api-testing][1] library
	- `openapi-validation/`: tests against the OpenAPI spec
  - `phpunit/`: integration and unit tests using the phpunit framework

[1]: https://www.mediawiki.org/wiki/MediaWiki_API_integration_tests
