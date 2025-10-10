# Reuse Domain

Making Wikimedia's linked open data accessible to the world, enabling the knowledge to be used to build impactful products and projects ✨

## Features

The functionality described here is provided by the Wikibase GraphQL API which is primarily intended for data reusers. The endpoint can be found on the wiki's `Special:WikibaseGraphQL` special page.

### Labels of linked entities

Queries of this type allow users to request labels of entities that are used in Statements, such as the Statement Property or Items in Statement values.

Example request:
The following query fetches the Item with the ID Q64, including its English label, all P31 statements, the English label of the Statement Property and the IDs and labels of items used as the Statement value.
```graphql
{
	item(id: "Q64") {
		label(languageCode: "en")
		statements(propertyId: "P31") {
			property {
				label(languageCode: "en")
			}
			value {
				... on ItemIdValue {
					content {
						id
						label(languageCode: "en")
					}
				}
			}
		}
	}
}
```

## Development

### Configuration

During this initial development phase, the GraphQL API can be enabled with the `tmpEnableGraphQL` feature toggle setting.

```
$wgWBRepoSettings['tmpEnableGraphQL'] = true;
```

### Directory Structure

- `src/`
	- `Application/`
		- `UseCases/`: Primary ports of the application core
	- `Domain/`
		- `Model/`: Entities and value objects
		- `Services/`: Secondary ports, i.e. persistence interfaces such as retrievers
    - `Infrastructure/`: Secondary adapters, i.e. implementations of interfaces defined in the application core
		- `DataAccess/`: Implementations of persistence services
		- `GraphQL/`: The GraphQL service implementation, resolvers, schema and types
- `tests/`
	- `phpunit/`: integration and unit tests using the phpunit framework

### Useful commands

Please run the following commands from the Wikibase repository's root directory using [mwcli](https://www.mediawiki.org/wiki/Cli):
* running all tests: `mw dev mw exec -- composer -d ../.. phpunit:entrypoint extensions/Wikibase/repo/domains/reuse/tests/phpunit/`
* generating the GraphQL schema SDL: `mw dev mediawiki mwscript ./extensions/Wikibase/repo/domains/reuse/src/Infrastructure/GraphQL/GenerateSDL.php`
* linting:
  * `mw dev mw composer phpcs:prpl`
  * `mw dev mw composer phpcs repo/domains/reuse/`
