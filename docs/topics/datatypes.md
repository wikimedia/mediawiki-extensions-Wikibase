# Datatypes

This document describes the concept of property data types as used by Wikibase.

## Overview

Property data types in Wikibase are rather insubstantial: they are modeled by DataType objects, but such objects do not define any functionality of themselves. They merely act as a type safe ID for the data type.

Property data types are used to declare which kinds of values can be associated with a Property in a Snak. For each data type, the following things are defined:

* The type of DataValue to use for values (the “value type”).
* A localized name and description of the data type.
* ValueValidators that impose constraints on the allowed values.
* A ValueParser for parsing user input for a given type of property.
* Formatters for rendering snaks and values of the given type to various target formats.
* RDF mappings for representing snaks and values of the given type in RDF.

## Data Type Definitions

Property data types are defined in definition arrays maintained by the Repo and Client.
These arrays are constructed at bootstrap time in [WikibaseRepo] resp. [WikibaseClient]
based on the information returned when including the files [WikibaseLib.datatypes.php], [WikibaseRepo.datatypes.php], and [WikibaseClient.datatypes.php], respectively.
The definition arrays can be further modified using the <code>WikibaseRepoDataTypes</code> resp. <code>WikibaseClientDataTypes</code> hooks.
They are associative arrays that map property data types and value types to a set of constructor callbacks (aka factory methods).

Property data types and value types are used as keys in the definition arrays.
They are distinguished by the prefixes “VT:” and “PT:”.
For instance, the string value type would use the key “VT:string”, while the url data type would use the key “PT:url”.

Logically, the value type defines the structure of the value, while the property data type defines the interpretation of the value. Property data types may impose additional constraints on the values, or impact how they are rendered or exported.

Each key is associated with a map that provides the following fields:

* value-type (repo and client)
  * The value type to use for this data type (not used for value type keys).
* rdf-uri (repo only)
  * The URI to use in RDF to identify the data type. It is good practice to use a URI that will resolve to an RDF document that contains an OWL description of the data type, defining it to be of rdf:type wikibase:PropertyType, and providing a rdfs:label and a rdfs:comment describing the type. If no URI is defined explicitly, a URI will be composed using the base URI of the Wikibase ontology, and adding a CamelCase version of the datatype ID (so that “foo-bar” would become “FooBar”).
* validator-factory-callback (repo only)
  * A callable that acts as a factory for the list of validators that should be used to check any user supplied values of the given data type. The callable will be called without any arguments, and must return a list of ValueValidator objects.
* parser-factory-callback (repo only)
  * A callable that acts as a factory for a ValueParser for this data type.
* formatter-factory-callback (repo and client)
  * A callable that acts as a factory for ValueFormatters for use with this data type.
* snak-formatter-factory-callback (repo and client)
  * A callable that acts as a factory for SnakFormatters for use with this data type. If not defined, a SnakFormatter is created from the ValueFormatter for the given data type.
* rdf-builder-factory-callback (repo only)
  * A callable that acts as a factory for [ValueSnakRdfBuilder] for use with this data type.
* rdf-data-type (repo only)
  * RDF/OWL data type of a property having this data type (either ObjectProperty or DatatypeProperty, use constants from [PropertyRdfBuilder]). Default is DatatypeProperty. (Can be a callable returning the data type constant, to avoid autoloading problems.)
* normalizer-factory-callback (repo only, optional)
  * A callable that returns one or more [DataValueNormalizer]s (a single instance or an array of them) for use with this data type. Data values are normalized on save according to the normalizers for the property data type and value type (which are combined, rather than overriding one another).
    Normalization takes place between parsing and validation (which are defined using other callbacks, see above). While parsing is based on string input, is usually interactive, and can be fairly lenient (e.g. trimming whitespace), normalization is based on already parsed data values and applied whenever a data value is saved, including non-interactively; normalization should therefore be more conservative than parsing. Validation afterwards ensures that the (normalized) value is valid before it is saved.
    Wikibase currently does not normalize values at any other stage (e.g. post-save, when loading an item), because some normalizations are too expensive to apply when dumping large numbers of entities.

Since for each property data type the associated value type is known, this provides a convenient fallback mechanism: If a desired callback field isn't defined for a given property data type, we can fall back to using the callback that is defined for the value type. For example, if there is no formatter-factory-callback field associated with the PT:url key, we may use the one defined for VT:string, since the url property data type is based on the string value type.

Extensions that wish to register a data type should use the [WikibaseRepoDataTypes] resp. [WikibaseClientDataTypes] hooks to provide additional data type definitions.

## Programmatic Access

Information about data types can be accessed programmatically using the appropriate service objects.

The data type definitions themselves are wrapped by a [DataTypeDefinitions] object; the DataType objects can be obtained from the [DataTypeFactory] service available via [WikibaseRepo::getDataTypeFactory()] and [WikibaseClient::getDataTypeFactory()]

[WikibaseRepo] also has [WikibaseRepo::getDataTypeValidatorFactory()] which returns a [DataTypeValidatorFactory] for obtaining the validators for each data type.

[DataTypeDefinitions]: @ref Wikibase::Lib::DataTypeDefinitions
[DataTypeFactory]: @ref Wikibase::Lib::DataTypeFactory
[DataValueNormalizer]: @ref Wikibase::Lib::Normalization::DataValueNormalizer
[WikibaseRepo]: @ref Wikibase::Repo::WikibaseRepo
[WikibaseClient]: @ref Wikibase::Client::WikibaseClient
[ValueSnakRdfBuilder]: @ref Wikibase::Rdf::ValueSnakRdfBuilder
[PropertyRdfBuilder]: @ref Wikibase::Rdf::PropertyRdfBuilder
[WikibaseClient::getDataTypeFactory()]: @ref Wikibase::Client::WikibaseClient::getDataTypeFactory()
[WikibaseRepo::getDataTypeFactory()]: @ref Wikibase::Repo::WikibaseRepo::getDataTypeFactory()
[WikibaseRepo::getDataTypeValidatorFactory()]: @ref Wikibase::Repo::WikibaseRepo::getDataTypeValidatorFactory()
[DataTypeValidatorFactory]: @ref Wikibase::Repo::DataTypeValidatorFactory
[WikibaseLib.datatypes.php]: @ref WikibaseLib.datatypes.php
[WikibaseRepo.datatypes.php]: @ref WikibaseRepo.datatypes.php
[WikibaseClient.datatypes.php]: @ref WikibaseClient.datatypes.php
[WikibaseRepoDataTypes]: @ref WikibaseRepoDataTypes
[WikibaseClientDataTypes]: @ref WikibaseClientDataTypes
