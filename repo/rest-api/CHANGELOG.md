# CHANGELOG {#wb_rest_api_changelog}

## Version TBD

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

* The majority of endpoints where created during version 0.1
