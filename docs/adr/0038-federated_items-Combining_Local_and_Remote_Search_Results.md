# 38) Federated Items - Combining Local and Remote Search Results {#adr_0038}

Date: 2026-10-26

## Status

accepted

## Context

When editing a statement with values of data type `wikibase-item`, UI performs an item search using the editor's input. When Federated Items are enabled, search should include both local and Wikidata items. Remote search results from Wikidata should appear in the autocomplete dropdown alongside local items. Remote results should be identified by their concept URI (e.g. `https://www.wikidata.org/entity/Q42`) and displayed with a visual indication of their origin.

Server-side searching for remote entities can be implemented either in wbsearchentities or using a new API endpoint. Alternatively, Wikidata’s API can be directly used in the browser.

## Considered Actions

### Adding a new Action API module
Create a new action API module which would serve either only remote or a mix of remote and local results for search queries.

If Federated Items is not enabled on a wiki, this API is not going to be available. Client-side code will need to be adjusted to use it only on wikis where Federated Items is enabled.

### Adding a new REST API module

Create a new REST API module which would serve either only remote or a mix of remote and local results for search queries.

If Federated Items is not enabled on a Wiki, this API is not going to be available. Client-side code will need to be adjusted to use it only on wikis where Federated Items is enabled.

Compared to adding an action API, higher onboarding needs would make this more time consuming to implement.

### Adding a parameter to wbsearchentities

Unlike approaches involving introduction of a new search API module that would enable search for remote entities only, this approach would only require minimal changes to the client-side entity suggester. Additionally it would require a single HTTP request in the client, and leave the search result merging logic out of the client code.

A new request parameter can be added to `wbsearchentities` to make it include remote entities. The documentation for this parameter will note that this functionality is experimental. This would require interaction with wbsearchentities' entity type specific controllers ([T413961]).

### Direct client-side requests to Wikidata.org

Directly make CORS requests to Wikidata’s search API from the client UI code (in the browser). These search results would then be merged with local item search results for presentation in the entity selector.

This has the advantage of being straightforward to implement, only requiring changes to the client-side entity selector, thus adding little complexity to Wikibase.

Downsides of this approach are the higher number of http requests needed for editing wikibase-item values and the further implementation work which will eventually be needed to adapt this for MEX/wbui2025. Additionally this could pose privacy concerns for some Wikibase operators, as their users will directly connect to Wikimedia services ([T439900]).

See also: [T406226], [T409951]

## Decision

We find the direct client-side requests to Wikidata.org approach to be the least effort to implement and will pursue it over the server-side options.


## Consequences

Wikibase’s UI will make API requests to `wikidata.org` if Federated Items is enabled.


 [T413961]: https://phabricator.wikimedia.org/T413961
 [T439900]: https://phabricator.wikimedia.org/T439900
 [T406226]: https://phabricator.wikimedia.org/T406226
 [T409951]: https://phabricator.wikimedia.org/T409951