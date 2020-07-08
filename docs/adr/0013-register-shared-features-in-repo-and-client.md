# 13) Register shared features in Repo and Client {#adr_0013}

Date: 2020-06-26

## Status

accepted

## Context

Some parts of Wikibase are needed in both WikibaseRepo and WikibaseClient extensions. They include:
* PHP Code (Autoloaded)
* MediaWiki Hooks
* ResourceLoader Modules
* i18n Messages

Currently, they are made available to Repo and Client by being stored in a separate, third, extension named WikibaseLib.

This extension has grown over time and become incoherent. When adding some code or functionality that can be shared between
Repo and Client developers see this as the obvious place to put it.

This results in the continued growth and increased lack of structure.

Alternative solutions were considered and rejected:
* Registering shared features in Client and then making Repo depend on Client
    * Appears to go against the general logical flow of data
    * Gives the impression of tighter coupling

* Keeping Lib as an extension and trying to keep the structure more defined
    * Still leaves a place future developers may be tempted to "dump" shared logic

## Decision

We will stop registering WikibaseLib as a separate extension. Parts that are needed in either Wikibase Client or Repo will
be registered in both the Client and Repo `extension.json`. These parts encompass: Autoloaded PHP code; MediaWiki Hooks;
ResourceLoader Modules and i18n Messages.

## Consequences
Any new shared code should no longer be placed in the Lib directory; this is discouraged. No new code should be added to
nor should existing code be modified in Lib that depends on Client or Repo. We will add a test to help enforce this.

Future shared code should be distributed using composer packages.

We anticipated that shared i18n messages may need to remain jointly registered in both the Client and Repo `extension.json`.

Duplicate registration of the WikibaseLib hooks may be challenging. We will try to refactor in order to either:
* remove them
* make joint registration possible.

