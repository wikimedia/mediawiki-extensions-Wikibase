# LUA

Wikibase Client provides a Lua [Scribunto](https://www.mediawiki.org/wiki/Scribunto) interface that implements functions to access data from the Wikibase repository, if the Wikibase Client configuration enables `allowDataTransclusion`. Lua modules and wiki templates can invoke these functions.

Changes to the Wikibase Lua interface are subject to the [Stable Interface Policy](https://www.wikidata.org/wiki/Wikidata:Stable_Interface_Policy).

For ease of access and convenience Wikibase Lua library provides access to aspects (labels, descriptions, statements) either directly or by loading the whole entity.
For improved performance, when accessing only specific aspects it is recommended to access them directly (without getEntity).
On multilingual wikis accessing labels is based on user's language rather than Wiki's language. The following table summarizes the most common functions:

|Aspect|mw.wikibase.FUNCTION|entity:FUNCTION|
|------|--------------------|---------------|
|Label in content or user language|[getLabel](#mw.wikibase.getLabel "wikilink")/[getLabelWithLang](#mw.wikibase.getLabelWithLang "wikilink")|[getLabel](#mw.wikibase.entity:getLabel "wikilink")/[getLabelWithLang](#mw.wikibase.entity:getLabelWithLang "wikilink")|
|Label by language, without fallbacks|[getLabelByLang](#mw.wikibase.getLabelByLang "wikilink")|NA|
|Description by language, without fallbacks|[getDescriptionByLang](#mw.wikibase.getDescriptionByLang "wikilink")|NA|
|Sitelinks|[getSitelink](#mw.wikibase.getSitelink "wikilink")|[getSitelink](#mw.wikibase.entity:getSitelink "wikilink")|
|Badges|[getBadges](#mw.wikibase.getBadges "wikilink")|NA|
|Descriptions|[getDescription](#mw.wikibase.getDescription "wikilink")/[getDescriptionWithLang](#mw.wikibase.getDescriptionWithLang "wikilink")|[getDescription](#mw.wikibase.entity:getDescription "wikilink")/[getDescriptionWithLang](#mw.wikibase.entity:getDescriptionWithLang "wikilink")|
|Statements|[getBestStatements](#mw.wikibase.getBestStatements "wikilink")|[getBestStatements](#mw.wikibase.entity:getBestStatements "wikilink")|
||[getAllStatements](#mw.wikibase.getAllStatements "wikilink")|[getAllStatements](#mw.wikibase.entity:getAllStatements "wikilink")|

mw.wikibase
-----------

`mw.wikibase` has general Lua functionality for working with Wikibase data.

### mw.wikibase.getEntity

`wikibase.getEntity()`  
`wikibase.getEntity( id )`

<span style="color: red;">This function is [expensive](https://www.mediawiki.org/wiki/Manual:\$wgExpensiveParserFunctionLimit) when called with the ID of an entity not connected to the current page.</span> Loading entities doesn't count as expensive if the same entity is loaded twice during a module run. However, due to restrictions in the caching, if more than 14 other entities are loaded inbetween, the entity must be fetched again, which then counts as expensive.

Gets a [mw.wikibase.entity](#mw.wikibase.entity "wikilink") table with data of the Wikibase item requested by id. If no id was given, the item connected to the current page will be returned. Requesting an item by id is only supported if arbitrary access is enabled.

An example call might look like this:

``` {.lua}
mw.wikibase.getEntity( 'Q42' ) -- Returns a mw.wikibase.entity table for the item with the id Q42
```

### mw.wikibase.getEntityIdForCurrentPage

`wikibase.getEntityIdForCurrentPage()`

 Gets the item id of the item connected to the current page. Returns nil if no item is connected.

An example call might look like this:

``` {.lua}
mw.wikibase.getEntityIdForCurrentPage() -- Returns the item id as string, like "Q42"
```

### mw.wikibase.getEntityIdForTitle

`wikibase.getEntityIdForTitle( pageTitle )`  
`wikibase.getEntityIdForTitle( pageTitle, globalSiteId )`

Takes a page title string either in the local wiki or an other wiki on the same cluster specified by the site global ID, and returns the item ID connected via a sitelink, if one exists. Returns nil if there's no linked item.

``` {.lua}
mw.wikibase.getEntityIdForTitle( 'Berlin' ) -- Returns the ID of the item linked with the "Berlin" page on the local wiki, like "Q64"
mw.wikibase.getEntityIdForTitle( 'Berlin', 'enwikivoyage' ) -- Returns the ID of the item linked with the "Berlin" page on English Wikivoyage, like "Q64"
```

### mw.wikibase.getEntityUrl

`wikibase.getEntityUrl()`  
`wikibase.getEntityUrl( id )`

Takes an entity ID and returns the canonical URL to the entity in the repo.

If no ID was specified, the URL of the item connected to the current page will be returned, if the page is connected. An example call might look like this:

``` {.lua}
mw.wikibase.getEntityUrl( 'Q42' ) -- Returns the URL to the item as a string, like "https://www.wikidata.org/wiki/Special:EntityPage/Q42".
```

### mw.wikibase.getLabel

`wikibase.getLabel()`  
`wikibase.getLabel( id )`

Takes an item ID and returns the label in the language of the local Wiki.

If no ID was specified, then the label of the item connected to the current page will be returned, if the page is indeed connected and a label exists. The label will either be in the Wiki's language (on monolingual wikis) or the user's language (on multilingual Wikis), furthermore language fallbacks will be applied. See also [`mw.wikibase.getLabelWithLang`](#mw.wikibase.getLabelWithLang "wikilink").

An example call might look like this:

``` {.lua}
mw.wikibase.getLabel( 'Q42' ) -- Returns the label of the item as a string, like "Berlin".
```

### mw.wikibase.getLabelWithLang

`wikibase.getLabelWithLang()`  
`wikibase.getLabelWithLang( id )`

Like [`mw.wikibase.getLabel`](#mw.wikibase.getLabel "wikilink"), but has the language the returned label is in as an additional second return parameter.

An example call might look like this:

``` {.lua}
local label, lang = mw.wikibase.getLabelWithLang( 'Q42' ) -- label contains the text of the label. lang is the language the returned label is in, like "de".
```

### mw.wikibase.getLabelByLang

`wikibase.getLabelByLang( id, languageCode )`

Get the label from an entity for a specific language, returns the label as string or nil if it couldn't be found. This doesn't apply any language fallbacks.

**Note**: This should not be used to get the label in the user's language on multilingual wikis, use [`mw.wikibase.getLabel`](#mw.wikibase.getLabel "wikilink") for that if by any means possible.

An example call might look like this:

``` {.lua}
mw.wikibase.getLabelByLang( 'Q42', 'es' ) -- Returns the Spanish label of the item as a string, like "Berlín".
```

### mw.wikibase.getDescriptionByLang

`wikibase.getDescriptionByLang( id, languageCode )`

Get the description from an entity for a specific language, returns the label as string or nil if it couldn't be found. This doesn't apply any language fallbacks.

**Note**: This should not be used to get the description in the user's language on multilingual wikis, use [`mw.wikibase.getDescription`](#mw.wikibase.getDescription "wikilink") for that if by any means possible.

An example call might look like this:

``` {.lua}
mw.wikibase.getDescriptionByLang( 'Q42', 'es' ) -- Returns the Spanish description of the item as a string, like "capital de Alemania".
```

### mw.wikibase.getSitelink

`wikibase.getSitelink( itemId )`  
`wikibase.getSitelink( itemId, globalSiteId )`

Takes an item ID and returns the title of the corresponding page on the local Wiki or nil if it doesn't exist. This page title can be used to link to the given page.

When `globalSiteId` is given, the page title on the specified wiki is returned, rather than the one on the local wiki.

An example call might look like this:

``` {.lua}
mw.wikibase.getSitelink( 'Q42' ) -- Returns the given item's page title in the current Wiki as a string, like "Berlin"..
```

### mw.wikibase.getBadges

`wikibase.getBadges( itemId )`  
`wikibase.getBadges( itemId, globalSiteId )`

Takes an item ID and returns a list of all badges assigned to a site link.

When `globalSiteId` is given, the badges for the site link to the specified wiki are returned. This defaults to the local wiki.

An example call might look like this:

``` {.lua}
mw.wikibase.getBadges( 'Q64', 'dewiki' ) -- Returns the badges set on the site link to dewiki as a list, like { 'Q17437798' }
```
### mw.wikibase.getDescription

`wikibase.getDescription()`  
`wikibase.getDescription( id )`

Takes an item ID and returns the description in the language of the local Wiki.

If no ID was specified, then the description of the item connected to the current page will be returned, if the page is indeed connected and a description exists. The description will either be in the Wiki's language (on monolingual wikis) or the user's language (on multilingual Wikis), furthermore language fallbacks will be applied. See also [`mw.wikibase.getDescriptionWithLang`](#mw.wikibase.getDescriptionWithLang "wikilink").

An example call might look like this:

``` {.lua}
mw.wikibase.getDescription( 'Q42' ) -- Returns the description of the item as a string, like "capital of Germany".
```

### mw.wikibase.getDescriptionWithLang

`wikibase.getDescriptionWithLang()`  
`wikibase.getDescriptionWithLang( id )`

Like [`mw.wikibase.getDescription`](#mw.wikibase.getDescription "wikilink"), but has the language the returned description is in as an additional second return parameter.

An example call might look like this:

``` {.lua}
local description, lang = mw.wikibase.getDescriptionWithLang( 'Q42' ) -- description contains the text of the description. lang is the language the returned description is in, like "de".
```

### mw.wikibase.isValidEntityId

`wikibase.isValidEntityId( entityIdSerialization )`

Returns whether this a valid entity id. This does not check whether the entity in question exists, it just checks that the entity id in question is valid.

An example call might look like this:

``` {.lua}
mw.wikibase.isValidEntityId( 'Q12' ) -- Returns true.
mw.wikibase.isValidEntityId( 'Q0-invalid-id' ) -- Returns false.
```

### mw.wikibase.entityExists

`wikibase.entityExists( id )`

Returns whether the entity in question exists. Redirected entities are reported as existing too.

An example call might look like this:

``` {.lua}
mw.wikibase.entityExists( 'Q42' ) -- Returns true, if the item Q42 exists.
```

### mw.wikibase.renderSnak

`wikibase.renderSnak( snakSerialization )`

Renders a serialized Snak value to wikitext escaped plain text. This is useful for displaying References or Qualifiers.

An example call might look like this:

``` {.lua}
local entity = mw.wikibase.getEntity()
local snak = entity['claims']['P342'][1]['qualifiers']['P342'][1]

mw.wikibase.renderSnak( snak ) -- Returns the given Snak value formatted as wikitext escaped plain text.
```

### mw.wikibase.formatValue

`wikibase.formatValue( snakSerialization )`

Renders a serialized Snak value to rich wikitext. This is useful for displaying References or Qualifiers.

An example call might look like this:

``` {.lua}
local entity = mw.wikibase.getEntity()
local snak = entity['claims']['P342'][1]['qualifiers']['P342'][1]

mw.wikibase.formatValue( snak ) -- Returns the given Snak value formatted as rich wikitext.
```

### mw.wikibase.renderSnaks

`wikibase.renderSnaks( snaksSerialization )`

Renders a list of serialized Snak values to wikitext escaped plain text. This is useful for displaying References or Qualifiers.

An example call might look like this:

``` {.lua}
local entity = mw.wikibase.getEntity()
local snaks = entity['claims']['P342'][1]['qualifiers']

mw.wikibase.renderSnaks( snaks ) -- Returns the given Snak values formatted as wikitext escaped plain text.
```

### mw.wikibase.formatValues

`wikibase.formatValues( snaksSerialization )`

Renders a list of serialized Snak values to rich wikitext. This is useful for displaying References or Qualifiers.

An example call might look like this:

``` {.lua}
local entity = mw.wikibase.getEntity()
local snaks = entity['claims']['P342'][1]['qualifiers']

mw.wikibase.formatValues( snaks ) -- Returns the given Snak values formatted as rich wikitext.
```

### mw.wikibase.resolvePropertyId

`wikibase.resolvePropertyId( propertyLabelOrId )`

Returns a property id for the given label or id. This allows using the property's labels instead of ids in all places. If no property was found for the label, a nil value is returned.

An example call might look like this:

``` {.lua}
mw.wikibase.resolvePropertyId( 'father' ) -- Returns the property id for the property with label "father", like "P12".
```

### mw.wikibase.getPropertyOrder

`wikibase.getPropertyOrder()`

Returns a table with the order of property IDs as provided by the page MediaWiki:Wikibase-SortedProperties (<d:MediaWiki:Wikibase-SortedProperties> on Wikimedia operated sites). If the page does not exist, a nil value is returned.

An example call might look like this:

``` {.lua}
mw.wikibase.getPropertyOrder() -- Returns a table with the order of the property IDs such as { ['P1'] = 0, ['P31'] = 1, ['P5'] = 2 }
```

### mw.wikibase.orderProperties

`wikibase.orderProperties( tableOfPropertyIds )`

Returns a table with the given property IDs ordered according to the page MediaWiki:Wikibase-SortedProperties (<d:MediaWiki:Wikibase-SortedProperties> on Wikimedia operated sites).

An example call might look like this:

``` {.lua}
propertyIds = { 'P1', 'P5', 'P31' }
mw.wikibase.orderProperties( propertyIds ) -- Returns a table with ordered property IDs such as { 'P5', 'P1', 'P31' }
```

### mw.wikibase.getBestStatements

`wikibase.getBestStatements( entityId, propertyId )`

Returns a table with the "best" statements matching the given property ID on the given entity ID. The definition of "best" is that the function will return "preferred" statements, if there are any, otherwise "normal" ranked statements. It will never return "deprecated" statements. This is what you usually want when surfacing values to an ordinary reader.

An empty table is returned if the entity doesn't exist or no statements with the requested property ID could be found.

An example call might look like this:

``` {.lua}
mw.wikibase.getBestStatements( 'Q1', 'P12' ) -- Returns a table containing the serialization of P12 statements from Q1
```

#### Structure

The returned structure is very similar to the [Wikibase DataModel JSON schema for statements](https://www.mediawiki.org/wiki/Wikibase/DataModel/JSON#Claims_and_Statements), and equivalent to the statement structures in [mw.wikibase.entity](#mw.wikibase.entity "wikilink").

An example might look like this:

``` {.lua}
mw.logObject( mw.wikibase.getBestStatements( 'Q16354758', 'P1324' ) )

{
  {
    ["id"] = "Q16354758$d09b1475-46d7-bbd3-ce7a-4698212a4a99",
    ["rank"] = "normal",
    ["mainsnak"] = {
      ["datatype"] = "url",
      ["datavalue"] = {
        ["type"] = "string",
        ["value"] = "https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Wikibase.git",
      },
      ["property"] = "P1324",
      ["snaktype"] = "value",
    },
    ["qualifiers"] = {
      ["P2700"] = {
        {
          ["datatype"] = "wikibase-item",
          ["datavalue"] = {
            ["type"] = "wikibase-entityid",
            ["value"] = {
              ["entity-type"] = "item",
              ["id"] = "Q186055",
              ["numeric-id"] = 186055,
            },
          },
          ["hash"] = "c9653c60db2ac354cb25a08c14c306afd1238d7c",
          ["property"] = "P2700",
          ["snaktype"] = "value",
        },
      },
    },
    ["qualifiers-order"] = {
      "P2700",
    },
    ["references"] = {
      {
        ["hash"] = "ebb5373bbff11d9abd156aadbcf65ad5f97035dd",
        ["snaks"] = {
          ["P813"] = {
            {
              ["datatype"] = "time",
              ["datavalue"] = {
                ["type"] = "time",
                ["value"] = {
                  ["after"] = 0,
                  ["before"] = 0,
                  ["calendarmodel"] = "http://www.wikidata.org/entity/Q1985727",
                  ["precision"] = 11,
                  ["time"] = "+2016-10-05T00:00:00Z",
                  ["timezone"] = 0,
                },
              },
              ["property"] = "P813",
              ["snaktype"] = "value",
            },
          },
          ["P854"] = {
            {
              ["datatype"] = "url",
              ["datavalue"] = {
                ["type"] = "string",
                ["value"] = "https://www.mediawiki.org/wiki/Extension:Wikibase_Repository#Download",
              },
              ["property"] = "P854",
              ["snaktype"] = "value",
            },
          },
        },
        ["snaks-order"] = {
          "P854",
          "P813",
        },
      },
    },
    ["type"] = "statement",
  },
}
```

### mw.wikibase.getAllStatements

`wikibase.getAllStatements( entityId, propertyId )`

Returns a table with all statements (including all ranks, even "deprecated") matching the given property ID on the given entity ID.

An empty table is returned if the entity doesn't exist or no statements with the requested property ID could be found.

An example call might look like this:

``` {.lua}
mw.wikibase.getAllStatements( 'Q1', 'P12' ) -- Returns a table containing the serialization of P12 statements from Q1
```

The structure of the returned table is identical to those obtained via [mw.wikibase.getBestStatements](#mw.wikibase.getBestStatements "wikilink").

If statements with the requested property ID exist, the table returned is equivalent to the content of `mw.wikibase.getEntity( entityId ).claims[propertyId]`.

### mw.wikibase.getReferencedEntityId

`wikibase.getReferencedEntityId( fromEntityId, propertyId, toIds )`

Get one referenced entity (out of toIds), from a given entity. The starting entity, and the target entities are (potentially indirectly, via intermediate entities) linked by statements with the given property ID, pointing from the starting entity to one of the target entities.

Returns one id of a referenced entity id, if it could be found. Returns nil if none of the given entities is referenced. Returns false if the search for a referenced entity had to be aborted due to resource limits, thus the result is inconclusive.

Example calls might look like this:

``` {.lua}
mw.wikibase.getReferencedEntityId( 'Q341', 'P279', { 'Q7397', 'Q2095' } ) -- Returns "Q7397", as "free software" is an indirect "subclass of" "software"
mw.wikibase.getReferencedEntityId( 'Q59', 'P31', { 'Q7366', 'Q2095' } ) -- Returns nil
```

### mw.wikibase.getGlobalSiteId

`wikibase.getGlobalSiteId()`

Returns the site global ID (the site code used for site links) of the current wiki.

An example call might look like this:

``` {.lua}
mw.wikibase.getGlobalSiteId() -- Returns a value like "dewiki" or "eswikibooks"
```

### Legacy aliases

These functions exist solely for backward compatibility, they should not be used in new code.

#### mw.wikibase.getEntityObject

Alias for [mw.wikibase.getEntity](#mw.wikibase.getEntity "wikilink").

#### mw.wikibase.label

Alias for [mw.wikibase.getLabel](#mw.wikibase.getLabel "wikilink").

#### mw.wikibase.description

Alias for [mw.wikibase.getDescription](#mw.wikibase.getDescription "wikilink").

#### mw.wikibase.sitelink

Alias for [mw.wikibase.getSitelink](#mw.wikibase.getSitelink "wikilink").

mw.wikibase.entity {#mw_wikibase_entity}
------------------

`mw.wikibase.entity` represents a Wikibase entity in Lua. A `mw.wikibase.entity` table for the item which is linked with the current page can be obtained with [`mw.wikibase.getEntity`](#mw.wikibase.getEntity "wikilink").

Functions documented as `mw.wikibase.entity.name` are available on the global `mw.wikibase.entity` table; functions documented as `mw.wikibase.entity:name` are methods of an `mw.wikibase.entity` object (see [`mw.wikibase.entity.create`](#mw.wikibase.entity.create "wikilink")).

### Structure

The structure of this is very similar to the [Wikibase DataModel JSON](https://www.mediawiki.org/wiki/Wikibase/DataModel/JSON) schema. This can be handily viewed with

``` {.lua}
mw.logObject( mw.wikibase.getEntity( 'Q123' ) )
```

in the Scribunto [**Debug console**](https://www.mediawiki.org/wiki/Extension:Scribunto#Debug_console).

The following is a (heavily shortened) example, from the Wikidata item about Wikibase:

``` {.lua}
{
  ["id"] = "Q16354758",
  ["type"] = "item",
  ["schemaVersion"] = 2,
  ["labels"] = {
    { ["language"] = "en", ["value"] = "Wikibase", },
  },
  ["descriptions"] = {
    ["de"] = { ["language"] = "de", ["value"] = "Sammlung von Software (Anwendungen und Bibliotheken) zum Erstellen, Verwalten und Austauschen strukturierter Daten", },
  },
  ["aliases"] = {
    ["ru"] = { { ["language"] = "ru", ["value"] = "Викибаза", }, },
  },
  ["sitelinks"] = {
    ["enwiki"] = { ["badges"] = { }, ["site"] = "enwiki", ["title"] = "Wikibase", },
  },
  ["claims"] = {
    ["P1324"] = {
      {
        ["id"] = "Q16354758$d09b1475-46d7-bbd3-ce7a-4698212a4a99",
        ["mainsnak"] = {
          ["datatype"] = "url",
          ["datavalue"] = { ["type"] = "string", ["value"] = "https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Wikibase.git", },
          ["property"] = "P1324",
          ["snaktype"] = "value",
        },
        ["qualifiers"] = {
          ["P2700"] = {
            {
              ["datatype"] = "wikibase-item",
                ["datavalue"] = { ["type"] = "wikibase-entityid", ["value"] = { ["entity-type"] = "item", ["id"] = "Q186055", ["numeric-id"] = 186055, }, },
                ["hash"] = "c9653c60db2ac354cb25a08c14c306afd1238d7c",
                ["property"] = "P2700",
                ["snaktype"] = "value",
            },
          },
        },
        ["qualifiers-order"] = { "P2700", },
        ["rank"] = "normal",
        ["references"] = {
          {
            ["hash"] = "ebb5373bbff11d9abd156aadbcf65ad5f97035dd",
            ["snaks"] = {
              ["P854"] = {
                { ["datatype"] = "url", ["datavalue"] = { ["type"] = "string", ["value"] = "https://www.mediawiki.org/wiki/Extension:Wikibase_Repository#Download", }, ["property"] = "P854", ["snaktype"] = "value", },
              },
            },
            ["snaks-order"] = { "P854", "P813", },
          },
        },
        ["type"] = "statement",
      },
    },
  },
}
```

### mw.wikibase.entity:getId

`entity:getId()`

Returns the ID of the entity, as a string. This is equivalent to the content of `entity.id`.

An example call might look like this:

``` {.lua}
entity:getId() -- Returns a string like "Q123"
```

### mw.wikibase.entity:getLabel

`entity:getLabel()`
`entity:getLabel( langCode )`
Returns the label of the entity in the language given as `langCode` or in the Wiki's content language (on monolingual wikis) or the user's language (on multilingual wikis). See also [`mw.wikibase.entity:getLabelWithLang`](#mw.wikibase.entity:getLabelWithLang "wikilink").

An example call might look like this:

``` {.lua}
entity:getLabel( 'de' ) -- Returns a string like "Berlin"
```

### mw.wikibase.entity:getDescription

`entity:getDescription()`  
`entity:getDescription( langCode )`

Returns the description of the entity in the language given as `langCode` or in the Wiki's content language (on monolingual wikis) or the user's language (on multilingual wikis). See also [`mw.wikibase.entity:getDescriptionWithLang`](#mw.wikibase.entity:getDescriptionWithLang "wikilink").

An example call might look like this:

``` {.lua}
entity:getDescription( 'de' ) -- Returns a string like "capital and city-state of Germany"
```

### mw.wikibase.entity:getLabelWithLang

`entity:getLabelWithLang()`  
`entity:getLabelWithLang( langCode )`

Like [`mw.wikibase.entity:getLabel`](#mw.wikibase.entity:getLabel "wikilink"), but has the language the returned label is in as an additional second return parameter.

An example call might look like this:

``` {.lua}
local label, lang = entity:getLabelWithLang( 'de' ) -- label contains the text of the label. lang is the language the returned label is in, like "de".
```

### mw.wikibase.entity:getDescriptionWithLang

`entity:getDescriptionWithLang()`  
`entity:getDescriptionWithLang( langCode )`

Like [`mw.wikibase.entity:getDescription`](#mw.wikibase.entity:getDescription "wikilink"), but has the language the returned description is in as an additional second return parameter.

An example call might look like this:

``` {.lua}
local desc, lang = entity:getDescriptionWithLang( 'de' ) -- desc contains the text of the description. lang is the language the returned description is in, like "de".
```

### mw.wikibase.entity:getSitelink

`entity:getSitelink()`  
`entity:getSitelink( globalSiteId )`

Get the title with which the entity is linked in the current Wiki. If `globalSiteId` is given, the title the item is linked with in the given Wiki will be returned.

An example call might look like this:

``` {.lua}
entity:getSitelink() -- Returns the item's page title in the current Wiki as a string, like "Moskow"
entity:getSitelink( 'ruwiki' ) -- Returns the item's page title in the Russian Wikipedia as a string, like "Москва"
```

### mw.wikibase.entity:getProperties

`entity:getProperties()`

:<span style="color: red;">This adds a statement usage for all statements present, therefore it should only be used if absolutely necessary.</span>

Get a table with all property ids attached to the item.

An example call might look like this:

``` {.lua}
entity:getProperties() -- Returns a table like: { "P123", "P1337" }
```

### mw.wikibase.entity:getBestStatements

`entity:getBestStatements( propertyIdOrLabel )`

Get the best statements with the given property id or label. This includes all statements with rank "preferred" or, if no preferred ones exist, all statements with rank "normal". Statements with rank "deprecated" are never included.

An example call with property ID might look like this:

``` {.lua}
entity:getBestStatements( 'P12' ) -- Returns a table containing the serialization of the best statements with the property id P12
```

An example call with property label might look like this:

``` {.lua}
entity:getBestStatements( 'instance of' ) -- Returns a table containing the serialization of the best statements with the "instance of" property
```

### mw.wikibase.entity:getAllStatements

`entity:getAllStatements( propertyIdOrLabel )`

Returns a table with all statements (including all ranks, even "deprecated") matching the given property ID or property label.

An example call might look like this:

``` {.lua}
entity:getAllStatements( 'P12' ) -- Returns a table containing the serialization of P12 statements
```

An example call with property label might look like this:

``` {.lua}
entity:getAllStatements( 'instance of' ) -- Returns a table containing the serialization of the statements with the "instance of" property
```

### mw.wikibase.entity:formatPropertyValues

`entity:formatPropertyValues( propertyLabelOrId )`  
`entity:formatPropertyValues( propertyLabelOrId, acceptableRanks )`

Get the values of the Statements with the given property (which is either identified by a property id, or by the label of the property), formatted as wikitext escaped plain text. Per default only the best claims will be returned. Alternatively a table with acceptable ranks can be given as second parameter (a mapping table with all ranks can be found in [`mw.wikibase.entity.claimRanks`](#mw.wikibase.entity.claimRanks "wikilink")).

An example call might look like this:

``` {.lua}
-- Return a table like: { value = "Formatted claim value", label = "Label of the Property" }
entity:formatPropertyValues( 'P12' )

-- As above, but uses the label of the Property instead of the id
entity:formatPropertyValues( 'father' )

-- Return the normal ranked claims with the property Id 42 (same format as above)
entity:formatPropertyValues( 'P42', { mw.wikibase.entity.claimRanks.RANK_NORMAL } )
```

`value` is an empty string ('') if there's no statement with the given property on the entity. `value` will be nil if the given property doesn't exist.

### mw.wikibase.entity:formatStatements

`entity:formatStatements( propertyLabelOrId )`  
`entity:formatStatements( propertyLabelOrId, acceptableRanks )`

 Like [`mw.wikibase.entity:formatPropertyValues`](#mw.wikibase.entity:formatPropertyValues "wikilink"), but the returned values will be formatted as rich wikitext, rather than just wikitext escaped plain text.

### mw.wikibase.entity.claimRanks

The `mw.wikibase.entity.claimRanks` table contains a map of all available claim ranks.

The available ranks are:

1.  RANK_TRUTH
2.  RANK_PREFERRED
3.  RANK_NORMAL
4.  RANK_DEPRECATED

This can for example be used like this:

``` {.lua}
-- Return the normal ranked claims with the property id P5
entity:formatPropertyValues( 'P5', { mw.wikibase.entity.claimRanks.RANK_NORMAL } )

 -- Return all claims with id P123 (as the table passed contains all possible claim ranks)
entity:formatPropertyValues( 'P123', mw.wikibase.entity.claimRanks )
```
