# CHANGELOG {#wb_rest_api_changelog}

## Version 1.5

* Wikibase REST API search endpoints v1 released

## Version 1.4

* Added endpoint `GET /wikibase/v0/suggest/properties` ([T397838](https://phabricator.wikimedia.org/T397838))

## Version 1.3

* Added endpoint `GET /wikibase/v0/suggest/items` ([T388209](https://phabricator.wikimedia.org/T388209))

## Version 1.2

* Added endpoint `GET /wikibase/v0/search/properties` ([T386377](https://phabricator.wikimedia.org/T386377))
* Added endpoint `GET /wikibase/v0/search/items` ([T383132](https://phabricator.wikimedia.org/T383132))


## Version 1.1

* Added endpoint `POST /wikibase/v1/entities/properties` ([T342992](https://phabricator.wikimedia.org/T342992))
* Added missing `Content-Language` header to `DELETE /entities/items/{item_id}/sitelinks/{site_id}` responses

## Version 1.0

* Wikibase REST API v1 released

## Version 0.5

* Added label/description endpoints with language fallback ([T371605](https://phabricator.wikimedia.org/T371605))
  * `GET /entities/items/{item_id}/labels_with_language_fallback/{language_code}`
  * `GET /entities/items/{item_id}/descriptions_with_language_fallback/{language_code}`
  * `GET /entities/properties/{property_id}/labels_with_language_fallback/{language_code}`
  * `GET /entities/properties/{property_id}/descriptions_with_language_fallback/{language_code}`
* Added `request-limit-reached` error for when a client makes too many edit requests ([T366594](https://phabricator.wikimedia.org/T366594))
* Added `permission-denied` error for unauthorized or prevented edits ([T366581](https://phabricator.wikimedia.org/T366581), [T330914](https://phabricator.wikimedia.org/T330914))
* Added `resource-too-large` error for edits that make a resource larger than the configured limit ([T330739](https://phabricator.wikimedia.org/T330739))
* Generalized several errors into a single `patch-result-referenced-resource-not-found` error ([T366257](https://phabricator.wikimedia.org/T366257))
* Generalized several errors into a single `referenced-resource-not-found` error ([T366247](https://phabricator.wikimedia.org/T366247))
* Generalized several errors into a single `resource-not-found` error ([T366258](https://phabricator.wikimedia.org/T366258))
* Removed `patched-duplicate-alias` and `duplicate-alias` errors - duplicate aliases are now ignored ([T366902](https://phabricator.wikimedia.org/T366902))
* Removed `unexpected-field`, `patched-property-unexpected-field` and `patched-item-unexpected-field` errors - unexpected fields are now ignored ([T370623](https://phabricator.wikimedia.org/T370623))
* Generalized several errors into a single `patch-result-modified-read-only-value` error ([T366255](https://phabricator.wikimedia.org/T366255))
* Generalized several errors into a single `patch-result-invalid-value` error ([T370626](https://phabricator.wikimedia.org/T370626))
* Replaced kebab case keys with snake case in requests and responses ([T368130](https://phabricator.wikimedia.org/T368130))
* Generalized several errors into a single `data-policy-violation` error ([T366908](https://phabricator.wikimedia.org/T366908))
* Generalized several errors into a single `patch-result-value-too-long` error ([T366252](https://phabricator.wikimedia.org/T366252))
* Generalized several errors into a single `value-too-long` error ([T366238](https://phabricator.wikimedia.org/T366238))
* Generalized several errors into a single `invalid-key` error ([T370781](https://phabricator.wikimedia.org/T370781))
* Generalized several errors into a single `cannot-modify-read-only-value` error ([T366239](https://phabricator.wikimedia.org/T366239))
* Generalized several errors into a single `invalid-value` error ([T366181](https://phabricator.wikimedia.org/T366181))
* Generalized several errors into a single `missing-field` error ([T366177](https://phabricator.wikimedia.org/T366177))
* Modified the `patch-target-not-found` error ([T366911](https://phabricator.wikimedia.org/T366911))
  * Changed message to `Target not found on resource`
  * Changed `context` object - replaced `operation` and `field` fields with `path`
* Modified the `redirected-item` error ([T366910](https://phabricator.wikimedia.org/T366910))
  * Changed message to `Item {item_id} has been redirected to {redirect_target_id}`
  * Added a `context` object with a `redirect-target` field
* Modified the `patch-test-failed` error ([T366905](https://phabricator.wikimedia.org/T366905))
  * Changed message to `Test operation in the provided patch failed`
  * Changed `context` object - replaced `operation` field with `path`

## Version 0.4

* Added endpoint `PATCH /wikibase/v0/entities/items/{item_id}` ([T342993](https://phabricator.wikimedia.org/T342993))
* Generalized several errors into a single `invalid-path-parameter` error ([T366172](https://phabricator.wikimedia.org/T366172))
* Generalized several errors into a single `invalid-query-parameter` error ([T366175](https://phabricator.wikimedia.org/T366175))

## Version 0.3

* Added endpoint `POST /entities/items` ([T342990](https://phabricator.wikimedia.org/T342990))

## Version 0.2

* Added endpoint `PATCH /entities/properties/{property_id}` ([T347394](https://phabricator.wikimedia.org/T347394))

## Version 0.1

* The majority of endpoints were created during version 0.1
