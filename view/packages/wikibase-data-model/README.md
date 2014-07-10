# Wikibase DataModel JavaScript

**Wikibase DataModel JavaScript** is the JavaScript implementation of the
[Data Model](https://meta.wikimedia.org/wiki/Wikidata/Data_model)
at the heart of the [Wikibase software](http://wikiba.se/).

## Release notes

### 0.3.0 (2014-07-10)

* Remove methods isSameAs and equals from wikibase.Entity
* Move all classes from wikibase to wikibase.datamodel, e. g.
	wikibase.Claim becomes wikibase.datamodel.Claim

### 0.2.0 (2014-06-26)

* Let Entity.newFromMap expect a string instead of a DataType instance as
	datatype attribute when creating a Property.
* Let Property.getDataType return the data type's string identifier instead of
	a DataType instance.
* Fix regular expressions in resource loader definitions

### 0.1.0 (2014-06-18)

Initial release.
