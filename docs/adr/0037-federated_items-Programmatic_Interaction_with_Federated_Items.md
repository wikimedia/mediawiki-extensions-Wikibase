# 37) Federated Items - Programmatic Interaction with Federated Items {#adr_0037}

Date: 2026-09-30

## Status

accepted

## Context

This ADR is one of several referring to Federated Items. Federated Items is an opt-in feature that allows users to select Wikidata items and display them as statement values in their Wikibase instance. The Federated Items MVP supports the use of remote Wikidata items in statements on local items.

## Decisions

### Client

When accessing data from the Wikibase repository as a client via [Lua] or the Parser functions (like Wikipedia consuming from Wikidata), references to remote items used as values will be formatted as Wikitext or plain text, similarly to local values. Further access to or interaction with remote entities is not supported and will fail gracefully.

### API support

Wikibase repo APIs for interacting with individual entities can handle remote `wikibase-item` values used in statements when retrieving data from an entity or while editing entities. APIs providing elaborate querying services, like GraphQL or the integration with WikibaseCirrusSearch, are out of scope for now and will degrade gracefully.

### JSON / RDF representation

The JSON representation for remote items contains their concept URI, as detailed in [ADR #33](@ref adr_0033): `{"entity-type": "item", "id": "http://www.wikidata.org/entity/Q64"}`.

In RDF full concept URIs are used to represent remote items: `<http://www.wikidata.org/entity/Q64>`. These URIs are identical to the ones used in Wikidata’s RDF output. For Turtle (TTL) the `wikidata` prefix is used, whereas wd is used for the local wiki (see also [T419953]).


## Consequences

On wikis where Federated Items are enabled, the API and dump outputs for entities will change as described above. This might require data consumers to adapt to the format changes.

In the client, data from remote items can’t be fetched via Lua, impairing client data visualization. For example, when displaying information about the country something belongs to, one might not only want to display the label of the corresponding item (like "Germany"), but also display the corresponding flag. Typically the flag of the country will be retrieved from the country's item. If the country's item is remote, its information can't be fetched via [Lua] or the Parser functions.

[Lua]: https://doc.wikimedia.org/Wikibase/master/php/docs_topics_lua.html
[T419953]: https://phabricator.wikimedia.org/T419953
