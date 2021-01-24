# 17) Wikibase Extension release notes content {#adr_0017}

Date: 2021-01-14

## Status

accepted

## Context

With Wikibase soon to have official releases there is a need to present the changes to current and potential users. The release strategy team has reviewed the release process of MediaWiki and decided to use this as a model for what to cover in the release notes of Wikibase.

The release notes of MediaWiki are kept within the source repository and worked on throughout the development process. Each release branch contain a document describing the changes that was added from the previous version.

These are the different topics covered in the MediaWiki release notes [template](https://gerrit.wikimedia.org/r/c/mediawiki/core/+/611247/3/RELEASE-NOTES-1.36):

- **Configurations**: Configuration settings for system administrators with a link to the documentation of the setting that has changed.
  - **New configurations**: A list of new settings describing what setting has been introduced, and a short description on how to use it
  - **Changed configurations**: A list of settings that has changed behavior some way that needs announcement. This could be due to a change in the datatype, deprecation, changed default value etc.
  - **Removed Configurations**: List containing the settings that has now been removed and was announced deprecated in the previous version
- **Features**
  - **New user-facing features**: List of changed users-facing features, changes to the user interface, changes to user accessible pages etc.
  - **New developer features**: Changes to the codebase that requires announcement, function signature changes, new hooks etc.
- **External Library changes**: Changes to library dependencies, npm, composer etc.
  - **New external libraries**
  - **New development-only external libraries**
  - **Changed external libraries**
  - **Changed development-only external libraries**
  - **Removed external libraries**
- **Action API changes**: Changes to the action api describing what endpoint has changed and what the new behavior is.
- **Languages updated**: New languages added or other changes to the internationalization of the software.
- **Breaking changes**: Changes to the software that was available in the previous version but now is no longer accessible due to changed visibility or being removed.
- **Deprecations**: Parts of the codebase now marked as deprecated with a descriptive text suggesting what alternatives to use.
- **Other changes**: Changes to this version that does not fit under any other topic but still needs announcement.

This template is a good start for what to include in the release notes, however as there are some fundamental differences between MediaWiki and Wikibase the release strategy team decided it's a good idea not to include some aspects of the template as they might not bring that much value to it's potential readers.

## Decision

We will adopt the template but with the following changes.

##### Include "REST API changes"

As there is an ongoing effort to introduce support for the REST API we also think it's a good idea to include this topic already to announce any progress that is made on upcoming releases.

##### Don't include "New developer features" or changes to "development-only external libraries"

One main difference between MediaWiki and Wikibase is that Wikibase is an extension to MediaWiki and our external developer community is not as big. To reduce the burden on the team we think it's a good idea to not include these.

The final template will cover these topics.

- **Configurations**
  - New configurations
  - Changed configurations
  - Removed Configurations
- **Features**
  - New user-facing features
- **External Library changes**
  - New external libraries
  - Changed external libraries
  - Removed external libraries
- **Action API changes**
- **REST API changes**
- **Languages updated**
- **Breaking changes**
- **Deprecations**
- **Other changes**

## Consequences

- A template release notes document will be introduced in the Wikibase release pipeline repository to be used in Wikibase releases

