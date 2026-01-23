# Reuse Domain

Making Wikimedia's linked open data accessible to the world, enabling the knowledge to be used to build impactful products and projects ✨

## Features

The functionality described here is provided by the Wikibase GraphQL API which is primarily intended for data reusers. The endpoint can be found on the wiki's `Special:WikibaseGraphQL` special page.

### Labels of linked entities

Queries of this type allow users to request labels of entities that are used in Statements, such as the Statement Property or Items in Statement values.

Example: The following query fetches the Item with the ID Q64, including its English label, all P31 statements, the English label of the Statement Property and the IDs and labels of items used as the Statement value.
```graphql
{
	item(id: "Q64") {
		label(languageCode: "en")
		statements(propertyId: "P31") {
			property {
				label(languageCode: "en")
			}
			value {
				... on ItemValue {
					id
					label(languageCode: "en")
				}
			}
		}
	}
}
```

### Batch reading items

Queries of this type allow users to retrieve multiple items in one request.

Note: All subfields that are available in the single `item` field are also available in the `itemsById` field.


Example: The following query fetches the items with the IDs Q1 and Q2, including their English labels. A maximum of 50 items can be requested at once.

```graphql
{
  itemsById(ids: ["Q1", "Q2"]) {
    id
    label(languageCode: "en")
  }
}
```
###  Searching items by statement property and value
Queries of this type allows users to find a particular set of items to match statement properties and values.

Example: The following query searches for items that have a statement using property P1 with value Q1 and a statement using property P2 with value Q5.

```graphql
{
  searchItems(
    query: {
      and: [
        { property: "P1", value: "Q1" },
        { property: "P2", value: "Q5" }
      ]
    }
  ) {
    edges {
      node {
        id
        label(languageCode: "en")
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
* linting:
  * `mw dev mw composer phpcs:reuse`
  * `mw dev mw composer phpcs repo/domains/reuse/`
* generating the GraphQL schema SDL: `mw dev mediawiki mwscript ./extensions/Wikibase/repo/domains/reuse/src/Infrastructure/GraphQL/GenerateSDL.php`
  * To generate the GraphQL schema SDL, your workspace must include at least one extension that provides an `EntityValue` type (e.g. EntitySchema or Lexeme).
  This ensures that there is at least one data type using the generic `EntityValue` type, so the schema generator does not produce different schemas depending on which extensions are installed.
