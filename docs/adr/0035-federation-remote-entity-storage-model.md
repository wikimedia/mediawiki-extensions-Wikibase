# 24) Remote Entity Storage Model {#adr_0035}

Date: 2025-07-25

## Status

Draft

## Context

Federated features in Wikibase require access to entity data that resides on remote repositories such as Wikidata.  
To support predictable display behaviour and stable performance, the system needs a clear method for retrieving and reusing that remote data locally.  

This ADR documents the first step in defining that approach.  
For the initial implementation, remote entities are cached locally in a standard database table rather than through any in-memory or ephemeral cache layer such as Redis.

## Decision

Remote entity data is stored in a database-backed mirror named `wb_remote_entity`.  
When a remote entity, for example `wikidata:Q42`, is requested for the first time, the system retrieves its JSON from the remote repository via `wbgetentities` and stores it in this table.  
Subsequent reads use the local copy until it is explicitly refreshed.  
No automatic background synchronization or invalidation is performed.

This design represents an explicit decision to prioritise durable, easily inspectable storage over temporary caching.  
It also establishes a model in which refresh actions are expected to be explicit, initiated by administrators or later maintenance utilities, rather than scheduled or implicit.

## Consequences

- Introduces a dedicated database table `wb_remote_entity` for mirrored remote entities.  
- Provides a persistent and predictable storage layer across deployments and upgrades.  
- Avoids performance spikes or remote API load that could occur with volatile caches.  
- Establishes a clear snapshot model: remote entities remain stable until explicitly refreshed.  
- Serves as the foundation for future ADRs that will describe explicit refresh and maintenance mechanisms.
- 