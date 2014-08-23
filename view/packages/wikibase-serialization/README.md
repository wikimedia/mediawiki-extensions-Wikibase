# Wikibase Serialization JavaScript

JavaScript library containing serializers and deserializers for the Wikibase DataModel.

## Installation

The recommended way to use this library is via [Composer](http://getcomposer.org/).

To add this package as a local, per-project dependency to your project, simply add a
dependency on `wikibase/serialization-javascript` to your project's `composer.json` file.
Here is a minimal example of a `composer.json` file that just defines a dependency on
version 1.0 of this package:

```json
{
	"require": {
		"wikibase/serialization-javascript": "1.0.*"
	}
}
```

## Release notes

### 1.2 (dev)

* Removed <code>wikibase.serialization.entities</code> ResourceLoader module; use <code>wikibase.serialization.EntityUnserializer</code> instead.
* Added <code>wikibase.serialization.ClaimUnserializer</code>.
* Added <code>wikibase.serialization.ClaimsUnserializer</code>.
* Added <code>wikibase.serialization.EntityIdUnserializer</code>.
* Added <code>wikibase.serialization.MultilingualUnserializer</code>.
* Added <code>wikibase.serialization.ReferenceSerializer</code>.
* Added <code>wikibase.serialization.ReferenceUnserializer</code>.
* Added <code>wikibase.serialization.SnakListSerializer</code>.
* Added <code>wikibase.serialization.SnakListUnserializer</code>.
* Added <code>wikibase.serialization.SnakSerializer</code>.
* Added <code>wikibase.serialization.SnakUnserializer</code>.

### 1.1.3 (2014-09-10)

* Use new version of data-values/javascript

### 1.1.2 (2014-08-20)

* Added serialization.EntityUnserializer.itemExpert unserializing site links.
* Fixed unserializing aliases in EntityUnserializer.

### 1.1.0 (2014-07-10)

* Adapt to changes in wikibase/data-model-javascript@0.3.0.

### 1.0.0 (2014-07-03)

Initial release as a library.
