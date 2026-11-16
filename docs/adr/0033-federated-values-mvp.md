# 33) Federated Items - MVP
 {#adr_0033}

Date: 2026-11-11

## Status

accepted

## Context

This ADR defines the Federated Items feature that allows users to select Wikidata items and display them as statement values in their Wikibase instance. This MVP supports the use of remote Wikidata items in statements on local items.

When editing a statement, users can search for items. Remote results from Wikidata appear in the autocomplete drop-down alongside local items. Users can select a remote item and save the statement. The remote item is displayed with a visual indication that it originates from Wikidata. The value renders as a link that, when clicked, opens the source entity page in a new window based on the source’s concept URI.


## Considered Actions

### Display

When saved as statement values on entity pages, remote entities are displayed similarly to local items, with a visual indication that they originate from Wikidata. Clicking on the item opens the source Wikidata page in a new tab.

See also: [T408520], [T409948]

### Data Model

Remote entity ids will be represented using a `RemoteEntityId` class that implements `EntityId`.

### Storage

When a user selects a Wikidata item as a statement value, the relevant parts of the item’s JSON data are saved in a local table (`wb_remote_entity`). The MVP implementation defers storage until the page is saved. The statement then contains the item’s `RemoteEntityId` serialization (e.g. `http://www.wikidata.org/entity/Q42`). This storage is for internal use only and the format is subject to change.

For the MVP, a partial snapshot of the entity is saved at the time of page saving; there is no mechanism implemented for updating the snapshot based on changes in Wikidata. This may lead to a persistence of outdated data - ie, a reference to a country that has since dissolved - in remote Wikibases, and we intend to fix the issue in the future, however that is out of scope for the MVP.

### Serialization

The internal serialization format of remote values used for entity persistence matches the JSON format exposed via the API, as documented in [ADR #37](@ref adr_0037).

Example data value containing a remote item:
`{"value":{"entity-type":"item","id":"http:\/\/www.wikidata.org\/entity\/Q42"},"type":"wikibase-entityid"}`

Phabricator: [T408517]

### Data type

The current scope of federation is limited to Wikidata items used as values with the data type `wikibase-item`. That means that remote items are treated as `wikibase-item`s similarly to local items. This approach enables us to reuse existing infrastructure and is in line with user expectations.

We considered creating a new data type for remote items. As each property has a single set data type, this would require creating additional, separate properties for remote values, which is not desirable.

### Extension

We considered implementing Federated Items as an extension to Wikibase, in order to reduce complexity in some areas of the code. This approach would require creating new extension interfaces to Wikibase, such as Federated Item specific UI adjustments or for item stub RDF production (`ItemStubRdfBuilder`). It would also require regular alignment with changes in Wikibase. Implementing Federated Items as a Wikibase feature instead keeps the scope limited and easier to maintain Federated Items long-term.

Development will happen within Wikibase `master`, with Federated Items changes in scope of this ADR, [ADR #36](@ref adr_0036), [ADR #37](@ref adr_0037), and [ADR #38](@ref adr_0038) being backported to the `REL1_47` branch. The business logic for Federated Items will be in its own PHP namespace, with minimal coupling to other parts of Wikibase. The coupling will be implemented primarily through the data type registration mechanism and guarded by a feature flag.

## Consequences

Fetching remote item data from Wikidata via the API is done only when searching for statement values and adding them. No background synchronization is performed as part of the MVP, which means there’s no significant increase in load.

Phabricator: [T408518]

[T408517]: https://phabricator.wikimedia.org/T408517
[T408518]: https://phabricator.wikimedia.org/T408518
[T408520]: https://phabricator.wikimedia.org/T408520
[T409948]: https://phabricator.wikimedia.org/T409948