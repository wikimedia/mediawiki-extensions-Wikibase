# Entity Storage

This page describes how Wikibase entities are represented and stored inside MediaWiki.

### Storage

Entities (or more precisely representations of entities, conceptually called entity description documents) are stored as MediaWiki page content, serialized according to JSON binding of the Wikibase data model.
The JSON structure encodes an EntityDocument.
EntityContent and EntityHandler provide the necessary glue to allow MediaWiki to handle EntityDocuments as page content.

In order to store entities on wiki pages, a mapping between entity IDs and page titles is required.
In the simplest case, the entity ID is used as the page title directly, with the appropriate namespace prefix.
For this purpose, an entity type is associated with a namespace.
Since entity IDs encode the entity type, this in principle allows multiple entity types to share the same namespace.
However, support for multiple entity types sharing the same namespace directly is currently not implemented, at least not for top-level entities (see below for details).

In some cases, it may be desirable to store multiple entities on the same page – conceptually, this may be interpreted as a top-level entity containing several sub-entities.
This is similar to the idea of section links in MediaWiki (but not entirely the same, as page sections can change their name).
Just as with section links, the ID of a sub-entity has to contain the ID of the parent (top-level) entity, so it can be used as an address for finding the inner entity in the database.

This implies that the relationship between entity IDs and page titles is unique only in one direction: each entity ID is associated with a single page, but multiple entity IDs can map to the same page (or sections of the same page, depending on interpretation).

The same is true for the relationship between entity types and MediaWiki namespaces; each entity type is associated with a single namespace, but multiple entity types can map to the same namespace.

### Identification

Neither top-level nor sub-entities can be renamed.
In other words, no entity can change its only identifying feature: its ID.
If the title of the MediaWiki page that holds an entity directly derives from the entity ID, the page cannot be renamed either.

### Enumeration

As a consequence of the above principles, efficient enumeration of all entity IDs can be achieved by enumerating all titles in a namespace, but this is only possible for top-level entities.
In order to enumerate sub-entities contained within top-level entities, it may be required to load each top-level entity, or use a secondary index.

### Redirects

Some entity types may support redirects.
A MediaWiki redirect from one page title to another shall be interpreted as the two entity IDs referring to the same entity (conceptually: to the same entity description).
The titles of redirect pages correspond to secondary IDs of the entity, while the title of the page that actually contains the entity description corresponds to the canonical entity ID.
The entity description will typically only contain the canonical ID.

### Versioning

Wikibase supports the concepts of versioning through EntityRevisions.
These roughly correspond to MediaWiki page revisions, with one notable difference: the revision ID is considered to be unique only relative to a given entity ID, not globally, as in MediaWiki.

In particular, updating a single sub-entity will create a new revision of the page that contains that sub-entity.
This implies that it also creates a new revision for every other entity contained on that page, including the top-level entity.
When looking at such revisions from the perspective of an entity not affected by the edit the revision represents, such “incidental” revisions correspond to the concept of “null revisions” in MediaWiki: between the new revision and its parent, only the (sub-)entities touched by the intentional edit change; all other entities on the page remain unchanged between the old and the new revision.
This is an undesired artifact of the storage mechanism, but acceptable, since revision IDs alone are never used to identify an entity.

### Permissions

The permission to perform actions on entities are mapped to MediaWiki page permissions by an EntityPermissionChecker.
This means that the same permissions apply to all top-level as well as sub-entities on a page, and page protection (restrictions) also apply to all entities.
However, the same operation (like changing a label) can be mapped to different actions for different entity types.
For example, items and properties use two different actions “item-term” and “property-term” for operations on terms.
In effect, this allows permissions and restrictions to be managed per entity type on a page, but not per individual entity.
