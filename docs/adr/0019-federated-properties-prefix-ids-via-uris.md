# 19) Add source information to Property IDs for Federated Properties v2 {#adr_0019}

Date: 2021-06-21

## Status

proposed

## Context

The Federated Properties MVP introduced a [federatedPropertiesEnabled] setting which enables a Wikibase instance (local wiki) to read all Properties from a remote Wikibase (source wiki). A wiki with this setting enabled can only use federated Properties and disallows the creation or use of any local Properties.
Federated Properties v2 aims to make it possible for users to enable Federated Properties even if their Wikibase already contains data, so that they can choose to use both remote & local Properties to make statements.

To achieve that we need to be able to differentiate between local and federated Properties.

### Considered Actions

We considered two options:

1. Build source information into the Property IDs via repository prefixes.
2. Build source information into the Property IDs via Concept URIs.

#### Prefixes

The administrator of the local Wikibase needs to configure a prefix for the source wiki, which will be used only for federated properties. Local properties will not have a prefix.

**Pros**:
 - Easier to parse
 - Easier to type

**Cons**:
 - Ambiguous without knowing the config

#### Concept URIs

Concept URIs can be set for any Wikibase and also have a default value. An example of referencing a federated property from Wikidata is `http://www.wikidata.org/entity/P31`.
The administrator of the local Wikibase needs to configure the concept URI for the source wiki in case it's not Wikidata.

Since concept URIs guarantee to end in the entity's ID we can think of them as prefixes. In the above example `http://www.wikidata.org/entity/` is the prefix. This way we don't need to worry about parsing them to extract the entity ID.

**Pros**:
 - Unambiguous
 - Standard practice in the linked data world

**Cons**:
- Typing urls in the browser when containing e.g. '&' is not nice. People might not remember to url encode.
We think having symbols like '&' in the URL in unlikely. Also, API Sandbox can be used to test the API requests and figure out the right format if needed.
- Hard to change / Out of “our” control if they change
If we use the URI only as a prefix to the entity, and not as an actual URL that will be used as such, then this shouldn't be a problem. We acknowledge it's possible for people who use the API to treat it like an URL.
We feel responsible only if Wikidata's URI changes, as we anticipate shipping an example configuration for Wikidata which would then need updating. URIs configured for a source wiki different than Wikidata will have to be updated by the administrator of the local wiki in case they change.

## Decision

Build source information into the Property IDs via Concept URIs, treating them like a prefix.

## Consequences

It's out of our control when/if URIs change. We acknowledge that this is a real problem and can potentially be reported by people who use the API and treat the URIs as URLs.


[federatedPropertiesEnabled]: @ref repo_federatedPropertiesEnabled
