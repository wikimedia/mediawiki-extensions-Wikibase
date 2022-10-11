# Change op serializations

The following document describes the JSON syntax the wbeditentity API understands. The code responsible for this is structured in ChangeOpDeserializer implementations, one for each entity type.

The overall syntax of the JSON “data” blob you must provide in an edit request is as follows. Pretty much all of the elements are optional.

```json
{
    "labels": {
        "de": { "language": "de", "value": "…" },
        "en": { … }
    },
    "descriptions": {
        "de": { "language": "de", "value": "…" },
        "en": { … }
    },
    "aliases": {
        "de": [ { "language": "de", "value": "…" }, … ],
        "en": [ { … }, … ]
    },
    "claims": {
        "P31": [ { "mainsnak": …, "qualifiers": …, "references": …, … }, … ],
        "P136": [ { … }, … ]
    },
    "sitelinks": {
        "dewiki": { "site": "dewiki", "title": "…", … },
        "enwiki": { … }
    }
}
```

## Elements common for items and properties

### Terms

* When providing “labels”, “descriptions”, as well as “aliases”, the “language” must be provided twice as a key and a value, e.g. <code>"en": { "language": "en", "value": "…" }</code>. This is to make sure that no language fallback terms are accidentally submitted as part of an edit request.
* Another way of providing “labels”, “descriptions”, and “aliases” is to not use a map, e.g. <code>{ "de": { "language": "de", "value": "…" }, … }</code>, but an array, e.g. <code>[ { "language": "de", "value": "…" }, … ]</code>.

#### “labels” and “descriptions”

* To add or edit a label or description, simply provide it's “language” and “value”.
* There are two ways to remove a label or description:
  * You can add the key “remove”, e.g. <code>{ "language": "en", "remove": "" }</code>. The content of the “remove” element can be whatever you want, typically an empty string.
  * You can set the “value” to an empty string, e.g. <code>{ "language": "en", "value": "" }</code>.

#### “aliases”

* You can either add or remove aliases individually, or send the full set of aliases for a language.
* To remove an alias, provide the old “language” and “value” of the alias you want to remove, as well as the key “remove”, e.g. <code>{ "language": "en", "value": "…", "remove": "" }</code>. The content of the “remove” element can be whatever you want, typically an empty string.
* To add an alias without touching the existing ones, provide a single alias and add the key “add”, e.g. <code>{ "language": "en", "value": "…", "add": "" }</code>. The content of the “add” element can be whatever you want, typically an empty string.

### Statements (“claims”)

Statements must be provided via the element key “claims”. This is for compatibility with older versions of the Wikibase software.

* To add a statement, provide a full statement serialization as supported by the StatementDeserializer in the [Wikibase DataModel Serialization] component. See the [JSON topic].
* To edit an existing statement, do as above and make sure to include the “id” of the existing statement.
* To remove a statement, you must provide its “id” and the key “remove”. The content of the “remove” element can be whatever you want, typically an empty string.

## Property specific elements

The only element specific for properties, their data type, can not be edited.

## Item specific elements

### “sitelinks”

* To add a new sitelink, provide it's “site” ID, “title” and optional “badges”, e.g. <code>{ "site": "enwiki", "title": "…", "badges": [] }</code>.
* Editing the “site” ID of an existing sitelink is not possible. You must remove the old sitelink and add the new sitelink instead.
* To edit the page name of an existing sitelink, provide the “site” ID with the new “title”, e.g. <code>{ "site": "enwiki", "title": "…" }</code>.
* There are three ways to remove a sitelink:
  * You can add the key “remove”. The “title” is optional in this case. The content of the “remove” element can be whatever you want, typically an empty string.
  * You can provide a sitelink with no title and no badges, e.g. <code>{ "site": "enwiki" }</code>.
  * You can set the “title” to an empty string, e.g. <code>{ "site": "enwiki", "title": "" }</code>.

#### “badges”

Each sitelink can contain as many badges as you want, but typically contains at most one.

* Avoid adding the key “badges” to your edit request if you do not want to edit badges, to avoid running into conflicts.
* There are no dedicated operations to add or remove a badge from an existing sitelink. You must always send the full set of badges for a sitelink.
* To edit the badges of an existing sitelink, include the key “badges” with an array of item ID strings representing the badges for the sitelink, e.g. <code>{ "site": "enwiki", "badges": [ "Q17437798" ] }</code>. The “title” is optional in this case.

## See also

* The [JSON topic] for a detailed description of the canonical JSON format used to represent Wikibase entities.
* https://www.wikidata.org/wiki/Wikidata:Stable_Interface_Policy

[Wikibase DataModel Serialization]: https://github.com/wmde/WikibaseDataModelSerialization
[JSON topic]: @ref docs_topics_json
