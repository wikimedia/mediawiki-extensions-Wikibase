# CRUD Domain

## Development

* @subpage rest_adr_index

### Project structure
This REST API follows the [Hexagonal Architecture](https://alistair.cockburn.us/hexagonal-architecture/) approach and takes inspiration from [an article about Netflix's use of the hexagonal architecture](https://netflixtechblog.com/ready-for-changes-with-hexagonal-architecture-b315ec967749). This decision is documented in [ADR 0001](docs/adr/0001_hexagonal_architecture.md).

\image{inline} html ./hexagonal_architecture.drawio.svg "Hexagonal Architecture Diagram"

The code is divided into three layers: Domain, Application, and Infrastructure. Domain and Application define the core business and application logic of the software, whereas the infrastructure layer deals with any external dependencies and concepts, such as transport or persistence details.

#### Directory structure

- `docs/`
	- `adr/`: [Architectural Decision Records](https://adr.github.io/)
- `../../docs/rest-api/`: the built OpenAPI specification and swagger documentation
- `specs/`: OpenAPI specification source
- `src/`
	- `Domain/`
		- `Model/`: Entities and value objects used when persisting data
		- `ReadModel/`: Entities and value objects used when retrieving data
		- `Services/`: Secondary ports, i.e. persistence interfaces such as retrievers and updaters
	- `Application/`
		- `Serialization/`: Deserializers used for turning user input into write models, serializers used for turning read models into JSON-serializable objects
		- `Validation/`: Generic (not use cases specific) classes for validating user input
		- `UseCases/`: Primary ports of the application core
	- `Infrastructure/`: Secondary adapters, i.e. implementations of interfaces defined in the application core
		- `DataAccess/`: Implementations of persistence services
	- `RouteHandlers/`: Web controllers acting as primary adapters
- `tests/` @anchor restApiTestDirs
	- `mocha/`: tests using the mocha framework
		- `api-testing/`: end-to-end tests using [MediaWiki's api-testing][1] library
		- `openapi-validation/`: tests against the OpenAPI spec
	- `phpunit/`: integration and unit tests using the phpunit framework

[1]: https://www.mediawiki.org/wiki/MediaWiki_API_integration_tests
