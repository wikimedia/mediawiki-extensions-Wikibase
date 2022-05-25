# 3) Fetch revision metadata and entity data separately {#rest_adr_0003}

Date: 2022-05-24

## Status

accepted

## Context

In order to provide entity data in the REST API response body as well as revision metadata (revision ID and last-modified date) in the response headers, we need to fetch both the actual data and the metadata for a requested resource from the database. This opens up two possible approaches:

  1. fetch both entity data and metadata with a single database request using the same lookup, e.g. `EntityRevisionLookup`
  2. fetch the revision metadata in a first step and the entity data separately, possibly using a different lookup e.g. `EntityLookup`

While the first approach looks more efficient in terms of database access, it requires combining entity data and revision metadata in a single result object. In contrast, the second approach allows a more flexible handling of the lookup results, as metadata and entity data can be handled in separate results.

## Decision

We decided to follow the second approach of fetching revision metadata separately from the entity data, for the following reasons:
  * The MetadataRetriever, e.g. for items, can be re-used across several use cases.
  * Separating revision metadata from entity data avoids complex result objects, which would have to combine item data, metadata as well as information for failure results (item not found, item is a redirect). Specific result objects would have to be created for every use case separately.

## Consequences

We have separate data access services for retrieving entity data and revision metadata, which are used in a two-step process. The metadata retrieval result contains the revision id and timestamp as well as information about a potential redirect or not-found error. This can be used to handle various failure cases, so that the second step will only happen in the success case and simply returns entity data.
