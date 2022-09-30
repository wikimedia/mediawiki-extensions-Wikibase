# JSON

This document describes the canonical JSON format used to represent Wikibase entities in the API, in JSON dumps, as well as by Special:EntityData (when using JSON output).
This format can be expected to be reasonably stable, and is designed with flexibility and robustness in mind.

For an explanation of the terms used in this document, please refer to the Wikibase [Glossary].
For a specification of the semantics of the data structures described here, see the [Wikibase Data Model].

Changes to the JSON format are subject to [Stable Interface Policy].

**NOTE: The canonical copy of this document is in Wikibase.git. Changes can be requested by filing a ticket on** [**Phabricator**](https://phabricator.wikimedia.org).

## JSON Flavor {#json_flavour}

Wikibase follows the JSON specification as given in [RFC 7159], aiming for interoperability in the sense of that RFC.
When encoding the Wikibase data structure as JSON, several choices have been made as to how values are represented:

* Keys in JSON objects are unique, their order is not significant.
* Strings are encoded in one of two ways:
  * using either Unicode escape sequences (like \u0645) resulting in a UTF16 representation when decoded.
  * ...or using native UTF8 encoding.
* Numbers may be given in two ways:
  * integers from -(2^31) to 2^31-1 may be represented as number literals.
  * all numbers may be represented as decimal strings. In particular, quantity values are represented as arbitrary precision decimal strings.
* Entity IDs are given as upper-case strings, e.g. “P29” or “Q623289”.
* In JSON dumps, each entity is encoded in as a single line. This allows consumers to process the dump line by line, decoding each entity separately.

Clients should be ready to process any of the forms given above.

## In Code

The wikibase/data-model-serialization library provides the majority of the serialization of core entities.

For public consumption [ResultBuilder] is used to modify the standard storage serialization.

This includes modifications such as:
 - Adding the MediaWiki page information
 - Filtering entity output by component (don't output labels)
 - Adding URLs to sitelink output
 - Adding property datatypes to snaks
 - Adding Term fallback chain info
 - Supplementing entity json to show removed values after an edit

## Top Level Structure {#json_structure}

Different entities have different top level structures and are made up of different components.
Entity types provided by extensions may have an entirely different structure.

The example below is for an Item.

```json
{
  "id": "Q60",
  "type": "item",
  "labels": {},
  "descriptions": {},
  "aliases": {},
  "claims": {},
  "sitelinks": {},
  "lastrevid": 195301613,
  "modified": "2020-02-10T12:42:02Z"
}
```

Properties will not include sitelinks, but will include a datatype.


```json
{
  "id": "P30",
  "type": "property",
  "datatype": "wikibase-item",
  "labels": {},
  "descriptions": {},
  "aliases": {},
  "claims": {},
  "lastrevid": 195301614,
  "modified": "2020-02-10T12:42:02Z"
}
```

These JSON representations consist of the following fields in the top level structure:

* id
  * The canonical ID of the entity.
* type
  * The entity type identifier. “item” for data items, and “property” for properties.
* datatype
  * The datatype to be used with the Property (Properties only)
* labels
  * Contains the labels in different languages, see [Labels, Descriptions and Aliases].
* descriptions
  * Contains the descriptions in different languages, see [Labels, Descriptions and Aliases].
* aliases
  * Contains aliases in different languages, see [Labels, Descriptions and Aliases].
* claims
  * Contains any number of statements, groups by property. Note: WikibaseMediaInfo uses the "statements" key instead. See [Statements].
* sitelinks
  * Contains sitelinks to pages on different sites describing the item, see [Sitelinks] (Items only).

API modules currently handle the revision and date modified slightly differently using the fields below.

```json
{
  "lastrevid": 195301613,
  "modified": "2020-02-10T12:42:02Z"
}
```

* lastrevid
  * The JSON document's version (this is a MediaWiki revision ID).
* modified
  * The JSON document's publication date (this is a MediaWiki revision timestamp).

API modules also often return extra information related to the entity and the wiki:

```json
{
  "title": "Q60",
  "pageid": 186,
  "ns": 0
}
```

* title
  * The title of the page the entity is stored in (this could also include namespace such as 'Item:Q60')
* pageid
  * The page id the entity is stored in
* ns
  * The namespace id of the page the entity is stored in

## Labels, Descriptions and Aliases {#json_fingerprint}

```json
{
  "labels": {
    "en": {
    "language": "en",
      "value": "New York City"
    },
    "ar": {
      "language": "ar",
      "value": "\u0645\u062f\u064a\u0646\u0629 \u0646\u064a\u0648 \u064a\u0648\u0631\u0643"
    }
  },
  "descriptions": {
    "en": {
      "language": "en",
      "value": "largest city in New York and the United States of America"
    },
    "it": {
      "language": "it",
      "value": "citt\u00e0 degli Stati Uniti d'America"
    }
  },
  "aliases": {
    "en": [
      {
        "language": "en",
        "value": "New York"
      }
    ],
    "fr": [
      {
        "language": "fr",
        "value": "New York City"
      },
      {
        "language": "fr",
        "value": "NYC"
      },
      {
        "language": "fr",
        "value": "The City"
      },
      {
        "language": "fr",
        "value": "La grosse pomme"
      }
    ]
  }
}
```

Labels, descriptions and aliases are represented by the same basic data structure.
For each language, there is a record using the following fields:

* language
  * The language code.
* value
  * The actual label or description.

In the case of aliases, each language is associated with a list of such records,
while for labels and descriptions the record is associated directly with the language.

## Statements {#json_statements}

```json
{
  "claims": {
    "P17": [
      {
        "id": "q60$5083E43C-228B-4E3E-B82A-4CB20A22A3FB",
        "mainsnak": {},
        "type": "statement",
        "rank": "normal",
        "qualifiers": {
          "P580": [],
          "P5436": []
        },
        "references": [
          {
            "hash": "d103e3541cc531fa54adcaffebde6bef28d87d32",
            "snaks": []
          }
        ]
      }
    ]
  }
}
```

A Statement consists of a main Snak, a (possibly empty) list of qualifier Snaks, and a (possibly empty) list of references.
A Statement is always associated with a Property (semantically, the Statement is *about* the Property), and there can be multiple Statements about the same Property in a single Entity.
This is represented by a map structure that uses Property IDs as keys, and maps them to lists of Statement records.

A Statement record uses the following fields:

* id
  * An arbitrary identifier for the Statement, which is unique across the repository. No assumptions can and shall be made about the identifier's structure, and no guarantees are given that the format will stay the same.
* type
  * Always *statement*. (Historically, *claim* used to be another valid value here.)
* mainsnak
  * The Snak representing the value to be associated with the property. See [Snaks] below. The Property specified in the main Snak must be the same as the Property the Statement is associated with.
* rank
  * The rank expresses whether this value will be used in queries, and shown be visible per default on a client system. The value is either *preferred*, *normal* or *deprecated*.
* qualifiers
  * Qualifiers provide a context for the primary value, such as the point in time of measurement. Qualifiers are given as lists of snaks, each associated with one property. See [Qualifiers] below.
* references
  * References record provenance information for the data in the main Snak and qualifiers. They are given as a list of reference records; see [References] below.

(Historically, there was a distinction between Claims, which had only a main snak and qualifiers, and Statements, which also had references.
Traces of this distinction may still be found in the serialization or in outdated documentation.)

### Snaks {#json_snaks}

```json
{
  "claims": {
    "P17": [
      {
        "mainsnak": {
          "snaktype": "value",
          "property": "P17",
          "datatype": "wikibase-item",
          "datavalue": {
            "value": {
              "entity-type": "item",
              "id": "Q30",
              "numeric-id": 30
            },
            "type": "wikibase-entityid"
          }
        }
      },
      {
        "mainsnak": {
          "snaktype": "somevalue",
          "property": "P17"
        }
      }
    ],
    "P356": [
      {
        "mainsnak": {
          "snaktype": "value",
          "property": "P356",
          "datatype": "string",
          "datavalue": {
            "value": "SomePicture.jpg",
            "type": "string"
          }
        }
      }
    ]
  }
}
```

A Snak provides some kind of information about a specific Property of a given Entity. Currently, there are three kinds of Snaks: *value*, *somevalue* or *novalue*.    A *value* snak represents a specific value for the property, which *novalue* and *somevalue* only express that there is no, or respectively some unknown, value.

A Snak is represented by providing the following fields:

* snaktype
  * The type of the snak. Currently, this is one of *value*, *somevalue* or *novalue*.
* property
  * The ID of the property this Snak is about.
* datatype
  * The *datatype* field indicates how the value of the Snak can be interpreted. The datatypes could be any other of the datatypes listed on https://www.wikidata.org/wiki/Special:ListDatatypes.
* datavalue
  * If the snaktype is *value*, there is a *datavalue* field that contains the actual value the Snak associates with the Property. See [Data Values] below.

#### Data Values {#json_datavalues}

```json
{
  "datavalue": {
    "value": {
      "entity-type": "item",
      "id": "Q30",
      "numeric-id": 30
    },
    "type": "wikibase-entityid"
  }
}
```

```json
{
  "datavalue": {
    "value": "SomePicture.jpg",
    "type": "string"
  }
}
```

Data value records represent a value of a specific type. They consist of two fields:

* type
  * The value type. This defines the structure of the *value* field, and is not to be confused with the Snak's data type (which is derived from the Snak's Property's data type). The value type does not allow for interpretation of the value, only for processing of the raw structure. As an example, a link to a web page may use the data type “url”, but have the value type “string”.
* value
  * The actual value. This field may contain a single string, a number, or a complex structure. The structure is defined by the *type* field.

Some value types and their structure are defined in the following sections.

##### string {#json_datavalues_string}

```json
{
  "datavalue": {
    "value": "SomePicture.jpg",
    "type": "string"
  }
}
```

Strings are given as simple string literals.

##### wikibase-entityid {#json_datavalues_entityid}

```json
{
  "datavalue": {
    "value": {
      "entity-type": "item",
      "id": "Q30",
      "numeric-id": 30
    },
    "type": "wikibase-entityid"
  }
}
```

Entity IDs are used to reference entities on the same repository. They are represented
by a map structure containing three fields:

* *entity-type*: defines the type of the entity, such as *item* or *property*.
* *id*: the full entity ID.
* *numeric-id*: for some entity types, the numeric part of the entity ID.

**WARNING**: not all entity IDs have a numeric ID – using the full ID is highly recommended.

##### globecoordinate {#json_datavalues_globe}

```json
{
  "datavalue": {
    "value": {
      "latitude": 52.516666666667,
      "longitude": 13.383333333333,
      "altitude": null,
      "precision": 0.016666666666667,
      "globe": "http:\/\/www.wikidata.org\/entity\/Q2"
    },
    "type": "globecoordinate"
  }
}
```

* latitude
  * The latitude part of the coordinate in degrees, as a float literal (or an equivalent string).
* longitude
  * The longitude part of the coordinate in degrees, as a float literal (or an equivalent string).
* precision
  * The coordinate's precision, in (fractions of) degrees, given as a float literal (or an equivalent string).
* globe
  * The URI of a reference globe. This would typically refer to a data item on wikidata.org. This is usually just an indication of the celestial body (e.g. Q2 = earth), but could be more specific, like WGS 84 or ED50.
* altitude (**DEPRECATED**)
  * No longer used. Will be dropped in the future.

##### quantity {#json_datavalues_quantity}

```json
{
  "datavalue": {
    "value": {
      "amount": "+10.38",
      "upperBound": "+10.375",
      "lowerBound": "+10.385",
      "unit": "http://www.wikidata.org/entity/Q712226"
    },
    "type": "quantity"
  }
}
```

Quantity values are given as a map with the following fields:

* amount
  * The nominal value of the quantity, as an arbitrary precision decimal string. The string always starts with a character indicating the sign of the value, either “+” or “-”.
* upperBound
  * Optionally, the upper bound of the quantity's uncertainty interval, using the same notation as the amount field. If not given or null, the uncertainty (or precision) of the quantity is not known. If the upperBound field is given, the lowerBound field must also be given.
* lowerBound
  * Optionally, the lower bound of the quantity's uncertainty interval, using the same notation as the amount field. If not given or null, the uncertainty (or precision) of the quantity is not known. If the lowerBound field is given, the upperBound field must also be given.
* unit
  * The URI of a unit (or “1” to indicate a unit-less quantity). This would typically refer to a data item on wikidata.org, e.g. http://www.wikidata.org/entity/Q712226 for “square kilometer”.

##### time {#json_datavalues_time}

```json
{
  "datavalue": {
    "value": {
      "time": "+2001-12-31T00:00:00Z",
      "timezone": 0,
      "before": 0,
      "after": 0,
      "precision": 11,
      "calendarmodel": "http:\/\/www.wikidata.org\/entity\/Q1985727"
    },
    "type": "time"
  }
}
```

Time values are given as a map with the following fields:
* time
  * The format and interpretation of this string depends on the calendar model. Currently, only Julian and Gregorian dates are supported.
  * The format used for Gregorian and Julian dates use a notation resembling ISO 8601. E.g. *“+1994-01-01T00:00:00Z”*. The year is represented by at least four digits, zeros are added on the left side as needed. Years BCE are represented as negative numbers, using the historical numbering, in which year 0 is undefined, and the year 1 BCE is represented as *-0001*, the year 44 BCE is represented as *-0044*, etc., like XSD 1.0 (ISO 8601:1988) does. In contrast, the [RDF mapping] relies on XSD 1.1 (ISO 8601:2004) dates that use the [proleptic Gregorian calendar] and [astronomical year numbering], where the year 1 BCE is represented as *+0000* and the year 44 BCE is represented as *-0043*. See Wikipedia for more information about the [year zero and ISO 8601].
  * Month and day may be 00 if they are unknown or insignificant. The day of the month may have values between 0 and 31 for any month, to accommodate “leap dates” like February 30. Hour, minute, and second are currently unused and should always be 00.
  * *Note*: more calendar models using a completely different notation may be supported in the future. Candidates include [Julian day] and the [Hebrew calendar].
  * *Note*: the notation for Julian and Gregorian dates may be changed to omit any unknown or insignificant parts. E.g. if only the year 1952 is known, this may in the future be represented as just *“+1952”* instead of currently *“+1952-00-00T00:00:00Z”* (which some libraries may turn into something like 1951-12-31) and the 19th century may be represented as *“+18”*.
* timezone
  * Signed integer. Currently unused, and should always be 0. In the future, timezone information will be given as an offset from UTC in minutes. For dates before the modern implementation of UTC in 1972, this is the offset of the time zone from UTC. Before the implementation of time zones, this is the longitude of the place of the event, expressed in the range &minus;180° to 180° (positive is east of Greenwich), multiplied by 4 to convert to minutes.
* calendarmodel
  * A URI of a calendar model, such as *gregorian* or *julian*. Typically given as the URI of a data item on the repository
* precision
  * To what unit is the given date/time significant? Given as an integer indicating one of the following units:
    * 0: 1 Gigayear
    * 1: 100 Megayears
    * 2: 10 Megayears
    * 3: Megayear
    * 4: 100 Kiloyears
    * 5: 10 Kiloyears
    * 6: millennium (see [Wikibase/DataModel#Dates and times] for details)
    * 7: century (see [Wikibase/DataModel#Dates and times] for details)
    * 8: 10 years
    * 9: years
    * 10: months
    * 11: days
    * 12: hours (*unused*)
    * 13: minutes (*unused*)
    * 14: seconds (*unused*)
* *before*
  * Begin of an uncertainty range, given in the unit defined by the *precision* field. This cannot be used to represent a duration. (Currently unused, may be dropped in the future)
* *after*
  * End of an uncertainty range, given in the unit defined by the *precision* field. This cannot be used to represent a duration. (Currently unused, may be dropped in the future)

### Qualifiers {#json_qualifiers}

```json
{
  "qualifiers": {
    "P580": [
      {
        "hash": "sssde3541cc531fa54adcaffebde6bef28g6hgjd",
        "snaktype": "value",
        "property": "P580",
        "datatype": "time",
        "datavalue": {
          "value": {
            "time": "+00000001994-01-01T00:00:00Z",
            "timezone": 0,
            "before": 0,
            "after": 0,
            "precision": 11,
            "calendarmodel": "http:\/\/www.wikidata.org\/entity\/Q1985727"
          },
          "type": "time"
        }
      }
    ],
    "P582": [
      {
        "hash": "f803e3541cc531fa54n7a9ffebde6bef28d87ddv",
        "snaktype": "value",
        "property": "P582",
        "datatype": "time",
        "datavalue": {
          "value": {
            "time": "+00000002001-12-31T00:00:00Z",
            "timezone": 0,
            "before": 0,
            "after": 0,
            "precision": 11,
            "calendarmodel": "http:\/\/www.wikidata.org\/entity\/Q1985727"
          },
          "type": "time"
        }
      }
    ]
  }
}
```

Qualifiers provide context for a Statement's value, such as a point in time, a method of measurement, etc.
Qualifiers are given as snaks. The set of qualifiers for a statement is provided grouped by property ID,
resulting in a map which associates property IDs with one list of snaks each.

### References {#json_references}

```json
{
  "references": [
    {
      "hash": "7eb64cf9621d34c54fd4bd040ed4b61a88c4a1a0",
      "snaks": {
        "P143": [
          {
            "snaktype": "value",
            "property": "P143",
            "datatype": "wikibase-item",
            "datavalue": {
              "value": {
                "entity-type": "item",
                "id": "Q328",
                "numeric-id": 328
              },
              "type": "wikibase-entityid"
            }
          }
        ],
        "P854": [
          {
            "snaktype": "value",
            "property": "P854",
            "datatype": "url",
            "datavalue": {
              "value": "http: //www.nytimes.com/2002/01/02/opinion/the-bloomberg-era-begins.html",
              "type": "string"
            }
          }
        ]
      },
      "snaks-order": [
        "P143",
        "P854"
      ]
    }
  ]
}
```

References provide provenance/authority information for the main Snak and qualifiers of an individual Statement.
Each reference is a set of Snaks structured in a similar way to how qualifiers are represented:
Snaks about the same property are grouped together in a list and made accessible by putting all these lists into a map,
using the property IDs as keys. By *snaks-order* the order of those snaks is shown by their property IDs.

## Sitelinks {#json_sitelinks}

```json
{
  "sitelinks": {
    "afwiki": {
      "site": "afwiki",
      "title": "New York Stad",
      "badges": []
    },
    "frwiki": {
      "site": "frwiki",
      "title": "New York City",
      "badges": []
    },
    "nlwiki": {
      "site": "nlwiki",
      "title": "New York City",
      "badges": [
        "Q17437796"
      ]
    },
    "enwiki": {
      "site": "enwiki",
      "title": "New York City",
      "badges": []
    },
    "dewiki": {
      "site": "dewiki",
      "title": "New York City",
      "badges": [
        "Q17437798"
      ]
    }
  }
}
```

Sitelinks are given as records for each site global ID. Each such record contains the following fields:

* site
  * The site global ID.
* title
  * The page title.
* badges
  * Any “badges” associated with the page (such as “featured article”). Badges are given as a list of item IDs.
* url
  * Optionally, the full URL of the page may be included.

## Example {#json_example}

Below is an example of an extract of a complete entity represented in JSON.

```json
{
  "pageid": 186,
  "ns": 0,
  "title": "Q60",
  "lastrevid": 199780882,
  "modified": "2020-02-27T14:37:20Z",
  "id": "Q60",
  "type": "item",
  "aliases": {
    "en": [
      {
        "language": "en",
        "value": "NYC"
      },
      {
        "language": "en",
        "value": "New York"
      }
    ],
    "fr": [
      {
        "language": "fr",
        "value": "New York City"
      },
      {
        "language": "fr",
        "value": "NYC"
      }
    ],
    "zh-mo": [
      {
        "language": "zh-mo",
        "value": "\u7d10\u7d04\u5e02"
      }
    ]
  },
  "labels": {
    "en": {
      "language": "en",
      "value": "New York City"
    },
    "ar": {
      "language": "ar",
      "value": "\u0645\u062f\u064a\u0646\u0629 \u0646\u064a\u0648 \u064a\u0648\u0631\u0643"
    },
    "fr": {
      "language": "fr",
      "value": "New York City"
    },
    "my": {
      "language": "my",
      "value": "\u1014\u101a\u1030\u1038\u101a\u1031\u102c\u1000\u103a\u1019\u103c\u102d\u102f\u1037"
    },
    "ps": {
      "language": "ps",
      "value": "\u0646\u064a\u0648\u064a\u0627\u0631\u06a9"
    }
  },
  "descriptions": {
    "en": {
      "language": "en",
      "value": "largest city in New York and the United States of America"
    },
    "it": {
      "language": "it",
      "value": "citt\u00e0 degli Stati Uniti d'America"
    },
    "pl": {
      "language": "pl",
      "value": "miasto w Stanach Zjednoczonych"
    },
    "ro": {
      "language": "ro",
      "value": "ora\u015ful cel mai mare din SUA"
    }
  },
  "claims": {
    "P1151": [
      {
        "id": "Q60$6f832804-4c3f-6185-38bd-ca00b8517765",
        "mainsnak": {
          "snaktype": "value",
          "property": "P1151",
          "datatype": "wikibase-item",
          "datavalue": {
            "value": {
              "entity-type": "item",
              "id": "Q6342720",
              "numeric-id": 6342720
            },
            "type": "wikibase-entityid"
          }
        },
        "type": "statement",
        "rank": "normal"
      }
    ],
    "P625": [
      {
        "id": "q60$f00c56de-4bac-e259-b146-254897432868",
        "mainsnak": {
          "snaktype": "value",
          "property": "P625",
          "datatype": "globe-coordinate",
          "datavalue": {
            "value": {
              "latitude": 40.67,
              "longitude": -73.94,
              "altitude": null,
              "precision": 0.00027777777777778,
              "globe": "http://www.wikidata.org/entity/Q2"
            },
            "type": "globecoordinate"
          }
        },
        "type": "statement",
        "rank": "normal",
        "references": [
          {
            "hash": "7eb64cf9621d34c54fd4bd040ed4b61a88c4a1a0",
            "snaks": {
              "P143": [
                {
                  "snaktype": "value",
                  "property": "P143",
                  "datatype": "wikibase-item",
                  "datavalue": {
                    "value": {
                      "entity-type": "item",
                      "id": "Q328",
                      "numeric-id": 328
                    },
                    "type": "wikibase-entityid"
                  }
                }
              ]
            },
            "snaks-order": [
              "P143"
            ]
          }
        ]
      }
    ],
    "P150": [
      {
        "id": "Q60$bdddaa06-4e4b-f369-8954-2bb010aaa057",
        "mainsnak": {
          "snaktype": "value",
          "property": "P150",
          "datatype": "wikibase-item",
          "datavalue": {
            "value": {
              "entity-type": "item",
              "id": "Q11299",
              "numeric-id": 11299
            },
            "type": "wikibase-entityid"
          }
        },
        "type": "statement",
        "rank": "normal"
      },
      {
        "id": "Q60$0e484d5b-41a5-1594-7ae1-c3768c6206f6",
        "mainsnak": {
          "snaktype": "value",
          "property": "P150",
          "datatype": "wikibase-item",
          "datavalue": {
            "value": {
              "entity-type": "item",
              "id": "Q18419",
              "numeric-id": 18419
            },
            "type": "wikibase-entityid"
          }
        },
        "type": "statement",
        "rank": "normal"
      },
      {
        "id": "Q60$e5000a60-42fc-2aba-f16d-bade1d2e8a58",
        "mainsnak": {
          "snaktype": "value",
          "property": "P150",
          "datatype": "wikibase-item",
          "datavalue": {
            "value": {
              "entity-type": "item",
              "id": "Q18424",
              "numeric-id": 18424
            },
            "type": "wikibase-entityid"
          }
        },
        "type": "statement",
        "rank": "normal"
      },
      {
        "id": "Q60$4d90d6f4-4ab8-26bd-f2a5-4ac2a6eb48cd",
        "mainsnak": {
          "snaktype": "value",
          "property": "P150",
          "datatype": "wikibase-item",
          "datavalue": {
            "value": {
              "entity-type": "item",
              "id": "Q18426",
              "numeric-id": 18426
            },
            "type": "wikibase-entityid"
          }
        },
        "type": "statement",
        "rank": "normal"
      },
      {
        "id": "Q60$ede49e3c-44f6-75a3-eb74-6a89886e30c9",
        "mainsnak": {
          "snaktype": "value",
          "property": "P150",
          "datatype": "wikibase-item",
          "datavalue": {
            "value": {
              "entity-type": "item",
              "id": "Q18432",
              "numeric-id": 18432
            },
            "type": "wikibase-entityid"
          }
        },
        "type": "statement",
        "rank": "normal"
      }
    ],
    "P6": [
      {
        "id": "Q60$5cc8fc79-4807-9800-dbea-fe9c20ab273b",
        "mainsnak": {
          "snaktype": "value",
          "property": "P6",
          "datatype": "wikibase-item",
          "datavalue": {
            "value": {
              "entity-type": "item",
              "id": "Q4911497",
              "numeric-id": 4911497
            },
            "type": "wikibase-entityid"
          }
        },
        "qualifiers": {
          "P580": [
            {
              "hash": "c53f3ca845b789e543ed45e3e1ecd1dd950e30dc",
              "snaktype": "value",
              "property": "P580",
              "datatype": "time",
              "datavalue": {
                "value": {
                  "time": "+00000002014-01-01T00:00:00Z",
                  "timezone": 0,
                  "before": 0,
                  "after": 0,
                  "precision": 11,
                  "calendarmodel": "http://www.wikidata.org/entity/Q1985727"
                },
                "type": "time"
              }
            }
          ]
        },
        "qualifiers-order": [
          "P580"
        ],
        "type": "statement",
        "rank": "preferred"
      },
      {
        "id": "q60$cad4e313-4b5e-e089-08b9-3b1c7998e762",
        "mainsnak": {
          "snaktype": "value",
          "property": "P6",
          "datatype": "wikibase-item",
          "datavalue": {
            "value": {
              "entity-type": "item",
              "id": "Q607",
              "numeric-id": 607
            },
            "type": "wikibase-entityid"
          }
        },
        "qualifiers": {
          "P580": [
            {
              "hash": "47c515b79f80e24e03375b327f2ac85184765d5b",
              "snaktype": "value",
              "property": "P580",
              "datatype": "time",
              "datavalue": {
                "value": {
                  "time": "+00000002002-01-01T00:00:00Z",
                  "timezone": 0,
                  "before": 0,
                  "after": 0,
                  "precision": 11,
                  "calendarmodel": "http://www.wikidata.org/entity/Q1985727"
                },
                "type": "time"
              }
            }
          ],
          "P582": [
            {
              "hash": "1f463f78538c49ef6adf3a9b18e211af7195240a",
              "snaktype": "value",
              "property": "P582",
              "datatype": "time",
              "datavalue": {
                "value": {
                  "time": "+00000002013-12-31T00:00:00Z",
                  "timezone": 0,
                  "before": 0,
                  "after": 0,
                  "precision": 11,
                  "calendarmodel": "http://www.wikidata.org/entity/Q1985727"
                },
                "type": "time"
              }
            }
          ]
        },
        "qualifiers-order": [
          "P580",
          "P582"
        ]
      }
    ],
    "P856": [
      {
        "id": "Q60$4e3e7a42-4ec4-b7c3-7570-b103eb2bc1ac",
        "mainsnak": {
          "snaktype": "value",
          "property": "P856",
          "datatype": "url",
          "datavalue": {
            "value": "http://nyc.gov/",
            "type": "string"
          }
        },
        "type": "statement",
        "rank": "normal"
      }
    ]
  },
  "sitelinks": {
    "afwiki": {
      "site": "afwiki",
      "title": "New York Stad",
      "badges": []
    },
    "dewiki": {
      "site": "dewiki",
      "title": "New York City",
      "badges": [
        "Q17437798"
      ]
    },
    "dewikinews": {
      "site": "dewikinews",
      "title": "Kategorie:New York",
      "badges": []
    },
    "elwiki": {
      "site": "elwiki",
      "title": "\u039d\u03ad\u03b1 \u03a5\u03cc\u03c1\u03ba\u03b7",
      "badges": []
    },
    "enwiki": {
      "site": "enwiki",
      "title": "New York City",
      "badges": []
    },
    "zhwikivoyage": {
      "site": "zhwikivoyage",
      "title": "\u7d10\u7d04",
      "badges": []
    },
    "zuwiki": {
      "site": "zuwiki",
      "title": "New York (idolobha)",
      "badges": []
    }
  }
}
```

[astronomical year numbering]: https://en.wikipedia.org/wiki/astronomical_year_numbering
[Data Values]: @ref json_datavalues
[Glossary]: https://www.wikidata.org/wiki/Wikidata:Glossary
[Hebrew calendar]: https://en.wikipedia.org/wiki/Hebrew_calendar
[Julian day]: https://en.wikipedia.org/wiki/Julian_day
[Labels, Descriptions and Aliases]: @ref json_fingerprint
[Phabricator]: https://phabricator.wikimedia.org
[proleptic Gregorian calendar]: https://en.wikipedia.org/wiki/Proleptic_Gregorian_calendar
[Qualifiers]: @ref json_qualifiers
[RDF mapping]: https://www.mediawiki.org/wiki/Wikibase/Indexing/RDF_Dump_Format
[References]: @ref json_references
[ResultBuilder]: @ref Wikibase::Repo::Api::ResultBuilder
[RFC 7159]: https://tools.ietf.org/html/rfc7159
[Sitelinks]: @ref json_sitelinks
[Snaks]: @ref json_snaks
[Stable Interface Policy]: https://www.wikidata.org/wiki/Wikidata:Stable_Interface_Policy
[Statements]: @ref json_statements
[Wikibase Data Model]: https://www.mediawiki.org/wiki/Wikibase/DataModel
[Wikibase/DataModel#Dates and times]: https://www.mediawiki.org/wiki/Wikibase/DataModel#Dates_and_times
[year zero and ISO 8601]: https://en.wikipedia.org/wiki/0_(year)#ISO_8601
