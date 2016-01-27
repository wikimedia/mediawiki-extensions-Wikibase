# Wikibase Serialization JavaScript

JavaScript library containing serializers and deserializers for the Wikibase DataModel.

## Installation

The recommended way to use this library is via [Composer](http://getcomposer.org/).

To add this package as a local, per-project dependency to your project, simply add a
dependency on `wikibase/serialization-javascript` to your project's `composer.json` file.
Here is a minimal example of a `composer.json` file that just defines a dependency on
version 2.0 of this package:

```json
{
	"require": {
		"wikibase/serialization-javascript": "2.0.*"
	}
}
```

## Release notes

### 2.0.6 (2016-01-27)

* Added compatibility with DataValues JavaScript 0.8.0.

### 2.0.5 (2016-01-27)

* Tests are now compatible with QUnit's requireExpects enabled.

### 2.0.4 (2016-01-18)

* Added compatibility with DataModel JavaScript 2.0.0.

### 2.0.3 (2015-06-03)

* Updated to DataValues JavaScript 0.7.0.

### 2.0.2 (2014-12-17)

#### Bugfixes
* Fixed parameter order when instantiating `dataValues.UnUnserializableValue` in `SnakSerializer`.

#### Enhancements
* Updated code documentation for being able to automatically generate a proper documentation using JSDuck.

### 2.0.1 (2014-11-05)
* Fixed the required DataModel JavaScript version.

### 2.0.0 (2014-11-05)

* Removed <code>wikibase.serialization.entities</code> ResourceLoader module; use <code>wikibase.serialization.EntityDeserializer</code> instead.
* Removed options from Serializer/Deserializer as it was never used and there is no intention to use options.
* Renamed <code>*Unserializer</code> to <code>*Deserializer</code>.
* Added <code>wikibase.serialization.ClaimGroupSetSerializer</code>.
* Added <code>wikibase.serialization.ClaimGroupSetDeserializer</code>.
* Added <code>wikibase.serialization.ClaimGroupSerializer</code>.
* Added <code>wikibase.serialization.ClaimGroupDeserializer</code>.
* Added <code>wikibase.serialization.ClaimListSerializer</code>.
* Added <code>wikibase.serialization.ClaimListDeserializer</code>.
* Added <code>wikibase.serialization.ClaimSerializer</code>.
* Added <code>wikibase.serialization.ClaimDeserializer</code>.
* Added <code>wikibase.serialization.EntitySerializer</code>.
* Added <code>wikibase.serialization.FingerprintSerializer</code>.
* Added <code>wikibase.serialization.FingerprintDeserializer</code>.
* Added <code>wikibase.serialization.ItemSerializer</code>.
* Added <code>wikibase.serialization.ItemDeserializer</code>.
* Added <code>wikibase.serialization.MultiTermSerializer</code>.
* Added <code>wikibase.serialization.MultiTermDeserializer</code>.
* Added <code>wikibase.serialization.MultiTermMapSerializer</code>.
* Added <code>wikibase.serialization.MultiTermMapDeserializer</code>.
* Added <code>wikibase.serialization.PropertySerializer</code>.
* Added <code>wikibase.serialization.PropertyDeserializer</code>.
* Added <code>wikibase.serialization.ReferenceListSerializer</code>.
* Added <code>wikibase.serialization.ReferenceListDeserializer</code>.
* Added <code>wikibase.serialization.ReferenceSerializer</code>.
* Added <code>wikibase.serialization.ReferenceDeserializer</code>.
* Added <code>wikibase.serialization.SiteLinkSerializer</code>.
* Added <code>wikibase.serialization.SiteLinkDeserializer</code>.
* Added <code>wikibase.serialization.SiteLinkSetSerializer</code>.
* Added <code>wikibase.serialization.SiteLinkSetDeserializer</code>.
* Added <code>wikibase.serialization.SnakListSerializer</code>.
* Added <code>wikibase.serialization.SnakListDeserializer</code>.
* Added <code>wikibase.serialization.SnakSerializer</code>.
* Added <code>wikibase.serialization.SnakDeserializer</code>.
* Added <code>wikibase.serialization.StatementGroupSerializer</code>.
* Added <code>wikibase.serialization.StatementGroupDeserializer</code>.
* Added <code>wikibase.serialization.StatementGroupSetSerializer</code>.
* Added <code>wikibase.serialization.StatementGroupSetDeserializer</code>.
* Added <code>wikibase.serialization.StatementListSerializer</code>.
* Added <code>wikibase.serialization.StatementListDeserializer</code>.
* Added <code>wikibase.serialization.StatementSerializer</code>.
* Added <code>wikibase.serialization.StatementDeserializer</code>.
* Added <code>wikibase.serialization.TermSerializer</code>.
* Added <code>wikibase.serialization.TermDeserializer</code>.
* Added <code>wikibase.serialization.TermMapSerializer</code>.
* Added <code>wikibase.serialization.TermMapDeserializer</code>.

### 1.1.3 (2014-09-10)

* Use new version of data-values/javascript

### 1.1.2 (2014-08-20)

* Added serialization.EntityUnserializer.itemExpert unserializing site links.
* Fixed unserializing aliases in EntityUnserializer.

### 1.1.0 (2014-07-10)

* Adapt to changes in wikibase/data-model-javascript@0.3.0.

### 1.0.0 (2014-07-03)

Initial release as a library.

# Bugs on Phabricator

https://phabricator.wikimedia.org/project/view/919/
