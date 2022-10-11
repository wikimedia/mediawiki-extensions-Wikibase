# Differences in the JSON data format between Wikibase REST API and ActionAPI

## Item data

* Uses `statements` instead of `claims`

```
GET /w/api.php?action=wbgetentities&ids=Q11&format=json                    GET /w/rest.php/wikibase/v0/entities/items/Q11

{                                                                         |
    "entities": {                                                         |
        "Q11": {                                                          |          {
            "type": "item",                                                              "type": "item",
            "id": "Q11",                                                                 "id": "Q11",
            "labels": { ... },                                                           "labels": { ... },
            "descriptions": { ... },                                                     "descriptions": { ... },
            "aliases": { ... },                                                          "aliases": { ... },
            "claims": {                                                   |              "statements": {
                "P31": [                                                                     "P31": [
                    { ... }                                                                      { ... }
                ]                                                                            ]
            },                                                                           },
            "sitelinks": {}                                                              "sitelinks": {}
        }                                                                            }
    }                                                                     |
}                                                                         |                                                                   }
```
## Labels, Descriptions, Aliases

* Flat structure ([T305362](https://phabricator.wikimedia.org/T305362))

```
GET /w/api.php?action=wbgetentities&ids=Q11&format=json                    GET /w/rest.php/wikibase/v0/entities/items/Q11

{                                                                         |
    "entities": {                                                         |
        "Q11": {                                                          |          {
            "type": "item",                                                              "type": "item",
            "id": "Q11",                                                                 "id": "Q11",
            "labels": {                                                                  "labels": {
                "en": {                                                   |                  "en": "non-empty-item-R5Gt64V3Eg"
                    "language": "en",                                     <
                    "value": "non-empty-item-R5Gt64V3Eg"                  <
                }                                                         <
            },                                                                           },
            "descriptions": {                                                            "descriptions": {
                "en": {                                                   |                  "en": "non-empty-item-description"
                    "language": "en",                                     <
                    "value": "non-empty-item-description"                 <
                }                                                         <
            },                                                                           },
            "aliases": {                                                                 "aliases": {
                "en": [                                                                      "en": [
                    {                                                     |                      "non-empty-item-alias"
                        "language": "en",                                 <
                        "value": "non-empty-item-alias"                   <
                    }                                                     <
                ]                                                                            ]
            },                                                                           },
            "claims": { ... },                                            |              "statements": { ... },
            "sitelinks": {}                                                              "sitelinks": {}
        }                                                                            }
    }                                                                     |
}                                                                         |
```


## Statement data

* Fixed structure: all fields are included in the response, even if they only contain an "empty" value ([T308110](https://phabricator.wikimedia.org/T308110))
* Redundant "type" field not included ([T317866](https://phabricator.wikimedia.org/T316077))

```
GET /w/api.php?action=wbgetclaims&entity=Q11&format=json                   GET /w/rest.php/wikibase/v0/entities/items/Q11/statements

{                                                                         |
    "claims": {                                                           |      {
        "P31": [                                                                     "P31": [
            {                                                                            {
                                                                          >                  "qualifiers": {},
                                                                          >                  "qualifiers-order": [],
                                                                          >                  "references": [],
                "mainsnak": {                                                                "mainsnak": {
                    "snaktype": "somevalue",                                                     "snaktype": "somevalue",
                    "property": "P31",                                                           "property": "P31",
                    "hash": "f12b7021de898dea3b51036f1f419aec6eacb383",                          "hash": "f12b7021de898dea3b51036f1f419aec6eacb383",
                    "datatype": "string"                                                         "datatype": "string"
                },                                                                           },
                "type": "statement",                                      <
                "id": "Q11$17B9CFE8-9F8C-46C9-B06D-6D07F28492B1",                            "id": "Q11$17B9CFE8-9F8C-46C9-B06D-6D07F28492B1",
                "rank": "normal"                                                             "rank": "normal"
            }                                                                            }
        ]                                                                            ]
    }                                                                            }
}                                                                         <
                                                                          <
```

## Metadata

* `pageid`, `ns`, `title` fields are ommitted
* revision ID, last modified date are included in HTTP response headers
* success state is represented in HTTP response code (2xx vs 4xx)
```
GET /w/api.php?action=wbgetentities&ids=Q11&format=json                    GET /w/rest.php/wikibase/v0/entities/items/Q11

{                                                                         |
    "entities": {                                                         |
        "Q11": {                                                          |          {
            "pageid": 17,                                                 <
            "ns": 120,                                                    <
            "title": "Item:Q11",                                          <
            "lastrevid": 1484,                                            <
            "modified": "2022-10-10T13:21:24Z",                           <
            "type": "item",                                                              "type": "item",
            "id": "Q11",                                                                 "id": "Q11",
            "labels": { ... },                                                           "labels": { ... },
            "descriptions": { ... },                                                     "descriptions": { ... },
            "aliases": { ... },                                                          "aliases": { ... },
            "claims": { ... },                                            |              "statements": { ... },
            "sitelinks": {}                                                              "sitelinks": {}
        }                                                                            }
    },                                                                    <
    "success": 1                                                          <
}                                                                         <
```
