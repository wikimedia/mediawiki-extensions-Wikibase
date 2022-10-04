# 10) Do not add source information to Property IDs for the Federated Properties MVP {#adr_0010}

Date: 2020-04-07

## Status

accepted

## Context
The Federated Properties MVP introduces a [federatedPropertiesEnabled] setting which enables a Wikibase instance (local wiki) to read all Properties from a remote Wikibase (source wiki). A wiki with this setting enabled can only use federated Properties and disallows the creation or use of any local Properties. Switching from Federated Properties mode back to using local Properties or vice versa is not supported.

Federated Properties use API-based implementations of entity data access services. This ADR documents our decision regarding the dispatching mechanism for the respective services, i.e. how Wikibase determines whether to use a service for handling a local Property or a federated Property.

## Considered Actions
We considered two options:
1. Building the source information into the Property IDs via repository prefixes and changing all relevant services into dispatching services that delegate to either the local database-backed services in case of local IDs or API-based services in the case of foreign IDs. This is equivalent to the [approach that was previously envisioned](https://phabricator.wikimedia.org/T133381) to enable federation for Structured Data on Commons.
2. Swapping out the services depending on the `federatedPropertiesEnabled` setting. This heavily relies on the assumption that there can only be a single source for Properties and that the `federatedPropertiesEnabled` setting won't ever be changed during the life span of the wiki.

The benefit of the first option is that it is likely the solution that we will implement in the future when both local and federated Properties co-exist on the same wiki. Choosing this option early on limits the effort of having to rewrite parts of the code that are specific to the less future-proof solution. On the other hand, it would require us to deal with conceptual problems that are not part of the MVP. The extra up front work includes adding the prefixes on retrieval from the source Wikibase, mapping the source information from Property IDs to their implementations (maybe even having to think about multiple foreign sources), and building the dispatching layer into the relevant services.

Option 2 requires less work at this stage and delays the decision of how to handle the coexistence of both local and federated Properties on a single wiki. The single source of federated Properties is determined via the configured API endpoint, IDs of federated Properties remain indistinguishable from local ones, and the system only supports one or the other at a time.

## Decision
We decided that the second option outlined in the previous section is the better solution at this point in time, but we expect the decision to be revisited and likely superseded in the future. We are positive that most of the work we do now will be reusable if in the future we choose to go with the repository prefix dispatching approach. Any API-based service implementations can likely be reused without modification and only the surrounding wiring code will need adjustment. Choosing this simpler path leaves the decision of how to handle multiple Property sources with the responsible journey team.

## Consequences
We do not add any source information to federated Properties. The relevant property services will be overridden via `repo/WikibaseRepo.FederatedProperties.entitytypes.php` or in their [factory methods]. The `federatedPropertiesSourceScriptUrl` determines the source wiki for federated Properties.


[factory methods]: https://gerrit.wikimedia.org/g/mediawiki/extensions/Wikibase/+/refs/changes/40/585740/5/repo/includes/WikibaseRepo.php#904
[federatedPropertiesEnabled]: @ref repo_federatedPropertiesEnabled
