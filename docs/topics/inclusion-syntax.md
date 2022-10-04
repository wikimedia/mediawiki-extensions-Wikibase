# Inclusion Syntax

This page describes the data inclusion syntax for the Wikibase client, by which the properties of data items can be included and rendered on a wiki page using templates.

The inclusion syntax presented here is meant to work for very simple cases, and only for them.

Complicated cases are to be realized using [LUA].

### Accessing Item Data

#### Properties

Properties of a Wikidata Item can be used via the #property parser function:

```
{{#property:P36}}
{{#property:capital}}
```

This will provide a representation of the value of the <tt>capital</tt> property of the page's default item. The default item is the Wikidata item that is associated with this page via language links.

The property label is case sensitive. It is also possible to use the identifier of the property (this is more stable against changes of the label of a property).

#### Properties of different items

To access properties of a different item, it has to be specified explicitly by Q-ID (not by label, because that label may be used for multiple items).

```
{{#property:capital|from=Q183}}
{{#property:P17|from=Q64}}
```

[LUA]: @ref docs_topics_lua
