# 1) Implement the Wikibase GraphQL API endpoint as an Action API module {#reuse_adr_0001}

Date: 2026-02-13

## Status

accepted

## Context

The Wikibase GraphQL endpoint is an experimental API in the Wikibase Reuse domain. It currently has no obvious entry point in the MediaWiki framework, as:
- index.php is used for HTML pages
- api.php is used for Action API modules
- rest.php is used for REST API endpoints

## Considered Actions

We evaluated alternative approaches:

**Action API**
- Pros:
  - Integrates into existing MediaWiki API infrastructure.
  - Provides many features out of the box (authentication, routing, CORS handling, etc.).
  - Lower implementation effort compared to building a new entry point.
- Cons:
  - The Action API is not ideal architecturally.
  - It is not primarily designed for `application/json` POST request bodies.

**REST API**
- Similar pros and cons as the Action API.
- Conceptually inappropriate, as GraphQL is clearly not RESTful.

**Special Page**
- Pros:
  - The GraphQL endpoint doesn't quite fit the Action API or the REST API
  - The existing MediaWiki GraphQL extension did it that way
- Cons:
  - We’re not getting many features for free (CORS handling, Authentication, POST-only limitation)
  - Traffic would probably not be routed to the API servers

**Dedicated `graphql.php` entry point**
- Does not currently exist.
- Would require substantial effort to implement properly in MediaWiki core.
- Probably the most correct long-term architectural solution.

The team concluded that the REST API is clearly off-limits and both the Special Page and the Action API are temporary, less-than-ideal solutions. Between these two, the Action API is more robust, requires less effort, and avoids reimplementing infrastructure concerns that are already solved within MediaWiki’s API framework.

## Decision

We will implement the Wikibase GraphQL endpoint as an Action API module (`action=wbgraphql`).

A dedicated `graphql.php` entry point may be introduced in the future as a more appropriate long-term architectural solution. Additionally, we are trying to create a URL rewrite rule on the production server to http://www.wikidata.org/graphql.

## Consequences

During its experimental phase, the GraphQL endpoint will be available as a read-only Action API module under `/w/api.php?action=wbgraphql&format=json`, expecting POST requests with JSON bodies. It is marked as internal in order to signal that the schema and the endpoint URL are not yet final.
