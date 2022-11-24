# Differences in the JSON data format between Wikibase REST API and ActionAPI {#rest_data_format_differences}

## Item data

* Uses `statements` instead of `claims`

```
GET /w/api.php?action=wbgetentities&ids=Q11&format=json                    GET /w/rest.php/wikibase/v0/entities/items/Q11

{                                                                         |
    "entities": {                                                         |
        "Q11": {                                                          |          {
            "type": "item",                                               |              "type": "item",
            "id": "Q11",                                                  |              "id": "Q11",
            "labels": { ... },                                            |              "labels": { ... },
            "descriptions": { ... },                                      |              "descriptions": { ... },
            "aliases": { ... },                                           |              "aliases": { ... },
            "claims": {                                                   |              "statements": {
                "P31": [                                                  |                  "P31": [
                    { ... }                                               |                      { ... }
                ]                                                         |                  ]
            },                                                            |              },
            "sitelinks": {}                                               |              "sitelinks": {}
        }                                                                 |          }
    }                                                                     |
}                                                                         |
```
## Labels, Descriptions, Aliases

* Flat structure ([T305362](https://phabricator.wikimedia.org/T305362))

```
GET /w/api.php?action=wbgetentities&ids=Q11&format=json                    GET /w/rest.php/wikibase/v0/entities/items/Q11

{                                                                         |
    "entities": {                                                         |
        "Q11": {                                                          |          {
            "type": "item",                                               |              "type": "item",
            "id": "Q11",                                                  |              "id": "Q11",
            "labels": {                                                   |              "labels": {
                "en": {                                                   |                  "en": "non-empty-item-R5Gt64V3Eg"
                    "language": "en",                                     |
                    "value": "non-empty-item-R5Gt64V3Eg"                  |
                }                                                         |
            },                                                            |              },
            "descriptions": {                                             |              "descriptions": {
                "en": {                                                   |                  "en": "non-empty-item-description"
                    "language": "en",                                     |
                    "value": "non-empty-item-description"                 |
                }                                                         |
            },                                                            |              },
            "aliases": {                                                  |              "aliases": {
                "en": [                                                   |                  "en": [
                    {                                                     |                      "non-empty-item-alias"
                        "language": "en",                                 |
                        "value": "non-empty-item-alias"                   |
                    }                                                     |
                ]                                                         |                  ]
            },                                                            |              },
            "claims": { ... },                                            |              "statements": { ... },
            "sitelinks": {}                                               |              "sitelinks": {}
        }                                                                 |          }
    }                                                                     |
}                                                                         |
```


## Statement data

The REST API's response format for statements has been re-structured, compared to the Action API, see [T321459](https://phabricator.wikimedia.org/T321459).

```
old                                                                         new

{                                                                         |    {
  "claims": {                                                             |      "statements": {
    "P31": [                                                              |       "P31": [
      {                                                                   |          {
        "mainsnak": {                                                     |            "id": "Q11$4A2F60EA-C779-42D5-8516-A8C26E3ED571",
          "snaktype": "value",                                            |            "rank": "normal",
          "property": "P31",                                              |            "qualifiers": [],
          "hash": "884531f6c60d8fbf3030857f2abd2086337af23c",             |            "references": [],
          "datavalue": {                                                  |            "property": {
            "value": "something",                                         |              "id": "P31",
            "type": "string"                                              |              "data-type": "string"
          },                                                              |            },
          "datatype": "string"                                            |            "value": {
        },                                                                |              "type": "value",
        "type": "statement",                                              |              "content": "something"
        "id": "Q11$4A2F60EA-C779-42D5-8516-A8C26E3ED571",                 |            }
        "rank": "normal"                                                  |          }
       }                                                                  |        ]
     ]                                                                    |      }
   }                                                                      |    }
}                                                                         |
```
* The `mainsnak` field is removed
* A top level `property` field is introduced, holding both the `id` and previous `mainsnak.datatype` as `data-type` (note: NOT `datatype`)
* A top level `value` field is added, consisting of two fields:
  * `content` (string or JSON object) – capturing the value of the statement (previously `mainsnak.datavalue.value`)
  * `type` accepting values `novalue`, `somevalue`, `value` (previously `mainsnak.snaktype`)
  * The `content` field is omitted if the value is known to not be possible to be defined (`type: novalue`) or known to be unknown (`type: somevalue`)
  * Values of reference and qualifier objects ("snaks") are also represented using a `value` object
* The field `mainsnak.hash` and `datavalue.type` have no equivalent representations, as they were deemed internal information not necessary for API consumers
* Redundant `type` field is not included ([T317866](https://phabricator.wikimedia.org/T316077))
* All fields (`id`,`property`,`value`,`rank`,`qualifiers`,`references`) are included in the response, even if they only contain an "empty" value ([T308110](https://phabricator.wikimedia.org/T308110))

### Statement Qualifiers

The qualifiers are turned from a map of lists of snak objects into a list of property-value pair objects. The field `qualifiers-order` is removed.
```
old                                                                         new

{                                                                         |  {
  ...                                                                     |    ...
  "qualifiers": {                                                         |    "qualifiers": [
      "P2": [                                                             |        {
          {                                                               |            "property": {
              "snaktype": "value",                                        |                "id": "P2",
              "property": "P2",                                           |                "data-type": "string"
              "hash": "7979ff19997f8cc25524b0636f577e38753559ff",         |            },
              "datavalue": {                                              |            "value": {
                  "value": "qualified",                                   |                "type": "value",
                  "type": "string"                                        |                "content": "qualified"
              },                                                          |
              "datatype": "string"                                        |
          }                                                               |            }
      ]                                                                   |        }
  },                                                                      |
  "qualifiers-order": [                                                   |
      "P2"                                                                |
  ],                                                                      |    ],
  ...                                                                     |    ...
}                                                                         |  }
```

### Statement References

The field `references.snaks` is renamed to `references.parts` and turned from a map of lists of snak objects into a list of property-value pair objects. The field `references.snaks-order` is removed.
```
old                                                                         new

{                                                                         |  {
  ...                                                                     |    ...
  "references": [                                                         |    "references": [
      {                                                                   |        {
          "hash": "04bcde9d3f150481174687cb901bc2c1ce4da73b",             |            "hash": "04bcde9d3f150481174687cb901bc2c1ce4da73b",
          "snaks": {                                                      |            "parts": [
              "P3": [                                                     |                {
                  {                                                       |                    "property": {
                      "snaktype": "value",                                |                        "id": "P3",
                      "property": "P3",                                   |                        "data-type": "string"
                      "hash": "282704dd2827b7f05a7da4861d918b9b835f6ce5", |                    },
                      "datavalue": {                                      |                    "value": {
                          "value": "referenced",                          |                        "type": "value",
                          "type": "string"                                |                        "content": "referenced"
                      },                                                  |
                      "datatype": "string"                                |
                  }                                                       |                    }
              ]                                                           |                }
          },                                                              |
          "snaks-order": [                                                |
              "P3"                                                        |
          ]                                                               |            ]
      }                                                                   |        }
  ]                                                                       |    ],
  ...                                                                     |    ...
}                                                                         |  }
```
## Metadata

* `pageid`, `ns`, and `title` fields are omitted
* revision ID and last modified date are included in HTTP response headers
* success state is represented in HTTP response code (2xx vs 4xx)

```
GET /w/api.php?action=wbgetentities&ids=Q11&format=json                    GET /w/rest.php/wikibase/v0/entities/items/Q11

{                                                                         |
    "entities": {                                                         |
        "Q11": {                                                          |          {
            "pageid": 17,                                                 |
            "ns": 120,                                                    |
            "title": "Item:Q11",                                          |
            "lastrevid": 1484,                                            |
            "modified": "2022-10-10T13:21:24Z",                           |
            "type": "item",                                               |              "type": "item",
            "id": "Q11",                                                  |              "id": "Q11",
            "labels": { ... },                                            |              "labels": { ... },
            "descriptions": { ... },                                      |              "descriptions": { ... },
            "aliases": { ... },                                           |              "aliases": { ... },
            "claims": { ... },                                            |              "statements": { ... },
            "sitelinks": {}                                               |              "sitelinks": {}
        }                                                                 |          }
    },                                                                    |
    "success": 1                                                          |
}                                                                         |
```
