# 2) Faceted search backend database technology {#reuse_adr_0002}

Date: 2026-10-20

## Status

accepted

## Context

The Wikibase GraphQL `searchItems` field ([T403576](https://phabricator.wikimedia.org/T403576)) supports faceted search by property and value. Its current backend uses WikibaseCirrusSearch and `haswbstatement`, which cannot support all required query operations, such as range queries and sorting by value (see [T416170](https://phabricator.wikimedia.org/T416170)). We want to find ways to achieve this, using the existing data stores except the Query Service, like MariaDB or ElasticSearch or possibly even alternative storage systems such as document databases.

The choice of database technology also determines how independently the Wikibase team can develop and maintain the backend, and which infrastructure and expertise are required.

## Considered Options

### Elasticsearch / OpenSearch

Elasticsearch and OpenSearch already power WikibaseCirrusSearch and work well for basic `haswbstatement` use cases. They could also be combined with label, description and alias text search.

However, the existing index does not support the required range queries because of how statement data is stored. Modelling statements as nested fields would introduce scalability concerns, while flattened fields do not support numeric range queries. The implementation would also depend heavily on support by the search team, and the scalability of this approach was considered questionable. Furthermore, Elasticsearch and OpenSearch are primarily designed for text search rather than structured data, and their `must` and `should` logical operators do not correspond directly to the required AND and OR semantics.

For these reasons, Elasticsearch / OpenSearch was not chosen.

### MariaDB

MariaDB offers the greatest autonomy for the Wikibase team: the team has the necessary expertise, can test the implementation independently, and would need only limited support to deploy it. It follows the principle of choosing boring technology and was also recommended by the WMF DBA.

A relational database can support structured queries over statement data, including the query operations required by faceted search. The main drawback is that combining statement queries with full-text search over labels, descriptions and aliases is less straightforward, although exact-match searches would be feasible. MariaDB was also considered a potential foundation for accessing individual statements without loading and deserializing entire entities. This is a possible additional benefit, rather than a requirement of the `searchItems` backend.

The proposed infrastructure is the existing x3 MariaDB cluster, which also stores the secondary terms tables. According to the DBA, this would not incur additional infrastructure costs.

### Other database technologies

Graph databases may be a natural fit for Wikibase data. Document databases could also offer advantages over a relational model for representing dynamically typed statement data. However, investigating and adopting another database technology would require substantial effort, and obtaining approval for additional database infrastructure was considered unlikely. Neither option was pursued for this use case.

## Decision

We will use MariaDB as the backend database technology for the Wikibase GraphQL `searchItems` field, replacing its current CirrusSearch-based implementation. The secondary statement store will be implemented in the existing x3 MariaDB cluster, reusing the infrastructure and expertise already available to the Wikibase team.

The detailed schema design and the scope of data stored in the secondary statement store are covered by [Reuse ADR #3: Secondary statement store schema](@ref reuse_adr_0003).

## Consequences

- The Wikibase GraphQL `searchItems` backend will use a secondary statement store in the existing MariaDB infrastructure instead of relying on WikibaseCirrusSearch for statement-based filtering.
- The implementation can be developed and tested largely independently by the Wikibase team, with limited assistance from the search and database administration teams.
- The decision does not introduce a new database technology or require separate database infrastructure.
- The relational representation of dynamically typed statement values introduces a modelling challenge. The secondary store's schema, including the representation of different value types, is proposed in [Reuse ADR #3](@ref reuse_adr_0003).
- The store may provide a foundation for future use cases that need to access individual statements or groups of statements without loading entire entities. Supporting those use cases is not part of this decision.
