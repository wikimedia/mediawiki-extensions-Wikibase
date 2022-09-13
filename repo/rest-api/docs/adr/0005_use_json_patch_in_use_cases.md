# 5) Use the JSON Patch format in use case code {#rest_adr_0005}

Date: 2022-09-12

## Status

accepted

## Context

JSON Patch was chosen as a flexible and expressive format for modifying (parts of) entities via the Wikibase REST API. We considered the following two approaches for implementing this functionality.

### 1) Apply the patch as it is to the JSON representation of the object that is being modified

The major advantage of this approach is that it's comparatively low effort. We can use an off-the-shelf library to apply the patch, and all that's left to do is to deserialize the resulting object and store it in the database. It also has two major downsides:
* Serialization becomes a use case concern. In all other "edit" use cases we only construct domain objects from user input, but the fact that we're patching JSON means that use cases also need to be aware of some canonical JSON representation. This serialization used for patching is notably not the same as the one generated in the presentation layer.
* Modifications happen in a black box. We have no control over the change process itself which means that we only have the raw data, the entity's pre- and post-patch state to work with, e.g. to tell whether the modification was valid, or to generate a fine-grained edit summary.
* Error handling is more complex, because we will have to rely on errors thrown by the third-party library, which are potentially less differentiated or less expressive than the error messages we would like to send back to our users. Additionally, it is probably more difficult to deal with multiple errors triggered by a single PATCH request.

### 2) Translate the user-provided patch into events and implement an event handler for each type of event

In this strategy we would build a service that takes a list of patch operations and turns them into events. Each event would have a corresponding event handler which performs the modifications on a domain object. This seems tempting for various reasons:
* We fully control every modification. The existing rules of our domain logic apply, and we can choose how to deal with any kind of change at a very low level.
* We can derive additional data (e.g. for edit summaries) in the process.
* It's agnostic to the format. We can choose to use another format in the future and all that needs to be changed is the service that translates JSON Patch operations to events.
* We have more fine-grained insight into the cause of possible exceptions when applying patch operations and can use them for our error handling and expressive error messages.

It also comes with big disadvantages:
* It's a lot of implementation effort, and we would forego the opportunity to use a third-party library for a standard format.
* Patch operations may temporarily result in "broken" state (e.g. when switching from a novalue statement to a value statement in one operation and only add the value in the next one). This is not impossible to work around, but makes everything more complicated.
* The code to translate from patch operations to events and the event handlers would need to be defined beyond the boundaries of Wikibase.git, since some Property data types are defined in other extensions. This would require additional work and possibly coordination with other extension developers.

## Decision

We chose the first option of simply applying the patch as it is to the JSON representation of the resource that is being modified. We found the drawbacks more acceptable in comparison, especially since "blindly" applying a patch isn't too different from replacing whole (parts of) entities using a user-provided JSON object, which is what already happens in our replace (PUT) endpoints.

## Consequences

We can use a third-party library for doing the heavy lifting (patching) now, and accept the aforementioned trade-offs. We will carefully distinguish between presentational serialization, and serialization used for applying JSON patches wherever necessary.
