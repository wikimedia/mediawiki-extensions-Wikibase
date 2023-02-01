# 7) Wikibase REST API work-in-progress endpoints {#rest_adr_0007}

Date: 2023-02-01

## Status

accepted

## Context

At the time of writing, Wikibase REST API endpoints are not defined by default when loading the Wikibase extension. Endpoints are defined by developers configuring them in the [`routes.json`] file and Wikibase admins adding the file to the [`$wgRestAPIAdditionalRouteFiles`] mediawiki configuration variable. We also maintain an [OpenAPI Definition] of the Wikibase REST API which, among other things, is used to create visual and interactive documentation hosted on [doc.wikimedia.org][swagger-docs]. During development, new endpoints need to be defined in the route file and OpenAPI Definition before they can be considered ready for use, as they need to be available on local instances, CI systems, and test systems (e.g. Beta Wikidata).

As well as configuring what endpoints are defined in mediawiki, a route file also configures which factory method (or handler class) should be used. This means a route file is coupled with the implementation. For example, renaming a factory would also require updating the route file to match. So, while you can enable multiple route files with `$wgRestAPIAdditionalRouteFiles`, it is **not** recommended to have the _same_ endpoint configuration duplicated in multiple route files. In other words, the route file should not be used as a mechanism for Wikibase admins to configure which endpoints are defined.

We were concerned that deployers of the REST API would want control over what endpoints are defined. The stakeholders we spoke to aren't currently concerned about this and are happy for us to decide what endpoints are defined and when. There is also a desire from some stakeholders to have the same endpoints enabled across all Wikibase instances in order to provide consistent functionality.

REST API endpoints can also be defined by adding them to an extension's JSON configuration file ([`extension-repo.json`] in this case) under `"RestRoutes"` (see [REST_API/Extensions#Defining_routes]). This might be the best place for production-ready endpoints in the future, once the REST API is no longer 'v0' and considered stable, as they can be enabled by default when the Wikibase extension is loaded. WIP endpoints can still be enabled where needed (e.g. CI and test systems) via `$wgRestAPIAdditionalRouteFiles`.

How to manage new features or changes to existing endpoints are outside the scope of this ADR.

## Decision

- We will maintain two route files stored in the `repo/rest-api` directory of the Wikibase extension:
  - `routes.json` that will contain routes completed to a functional state
  - `routes.dev.json` that will contain WIP routes which is meant for local development, CI, and test systems
- We will mark WIP routes in the OpenAPI Definition with a `[WIP]` prefix in the summary and `This endpoint is currently in development and is not recommended for production use.` in the description.
- Wikibase admins should not create their own route files or modify the existing ones

## Consequences

- We have control over the route files and can update them at the same time if any factory methods change
- The Wikibase REST API can still be enabled separately to the Wikibase extension
- Wikibase admins can decide which route file(s) they want to enable via the [`$wgRestAPIAdditionalRouteFiles`] configuration option
- All Wikibase instances that have enabled the REST API via the `Wikibase/repo/rest-api/routes.json` file will have the same routes available. Wikibase admins will have limited control over what routes are enabled and when. This is good for consistency across the Wikibase ecosystem.
- Wikibase admins that create their own route file risk broken routes if our factories change.
- We will be able to add new routes to the OpenAPI definition before they are ready, while still indicating the status of each route.

[doc.wikimedia.org]: https://doc.wikimedia.org
[`extension-repo.json`]: https://gerrit.wikimedia.org/g/mediawiki/extensions/Wikibase/+/758065e4967fcb9575a06302a80a84ccb762d373/extension-repo.json
[OpenAPI Definition]: https://swagger.io/specification/
[REST_API/Extensions#Defining_routes]: https://www.mediawiki.org/wiki/API:REST_API/Extensions#Defining_routes
[REST_API#Versioning]: https://www.mediawiki.org/wiki/API:REST_API#Versioning
[`routes.json`]: https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/Wikibase/+/758065e4967fcb9575a06302a80a84ccb762d373/repo/rest-api/routes.json
[semantic versioning]: https://semver.org/
[swagger-docs]: https://doc.wikimedia.org/Wikibase/master/js/rest-api/
[wikidata.org]: https://www.wikidata.org
[Wikidata:REST_API]: https://www.wikidata.org/wiki/Wikidata:REST_API
[`$wgRestAPIAdditionalRouteFiles`]: https://www.mediawiki.org/wiki/Manual:$wgRestAPIAdditionalRouteFiles
