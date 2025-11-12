# 33) Federated Values — MVP

Date: 2025-07-15  
Status: Draft  

## Context

As part of the broader Wikibase federation effort, this ADR defines the first minimum-viable feature that allows users to select and display Items from remote Wikibase repositories (initially Wikidata only) as statement values.

When editing a statement whose property expects an Item type, users should be able to search across both local and remote Items. Remote results are fetched from Wikidata via the standard `wbsearchentities` API and shown below local results in the autocomplete dropdown.

The selected remote Item becomes a value represented locally as a `RemoteEntityId`, e.g. `wikidata:Q42` 

## Decision

### Search and selection

- Extend the existing `EntitySearchHelper` with a decorator that merges remote search results into the local search list.
- Local Items appear first, followed by remote Items (currently only from Wikidata).
- Remote results are identified by a prefixed ID such as `wikidata:Q42`.
- Feature is controlled by the configuration flag:

  ```php
  $wgWBRepoSettings['federatedValuesEnabled'] = true;
  ```

- Remote sources are derived from existing `entitySources` configuration used elsewhere in Wikibase; Wikidata is the default source.

### Value storage and persistence

- When a remote Item is first used, its data is fetched via `wbgetentities` and stored locally (see ADR 34 for details).
- The stored snapshot is used for all future reads until an explicit refresh occurs.
- No automatic background synchronization or cache invalidation is performed in this MVP.

### Display

- Remote Items display as HTML links pointing back to their conceptURI, opening in a new tab or window.
- Each link includes a small badge showing the source base conceptURI (e.g. “www.wikidata.org”).
- Display relies on the `conceptURI` key from the `entitySources` configuration.

## Consequences

- Enables merged search and selection of remote Items within statement editors.
- Introduces the concept of a stable, locally cached “remote value”.
- Adds no background synchronization load to Wikidata.
- Depends only on `federatedValuesEnabled` and the existing `entitySources` configuration.
