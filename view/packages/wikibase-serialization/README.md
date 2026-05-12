# Wikibase Serialization JavaScript

JavaScript library containing serializers and deserializers for the [Wikibase DataModel](https://github.com/wmde/WikibaseDataModelJavaScript).

## Release notes
### 5.0.0 (2019-10-21)
* Removed hooking into global variable wikibase.serialization

### 4.0.0 (2019-10-08)
* Added index.js as the public interface
* Removed:
  * DeserializerFactory
  * SerializerFactory
  * EntitySerializer
  * FingerprintSerializer
  * ItemSerializer
  * MultiTermMapSerializer
  * MultiTermSerializer
  * PropertySerializer
  * SiteLinkSerializer
  * SiteLinkSetSerializer
  * StatementGroupSerializer
  * StatementGroupSetSerializer

### 3.0.0 (2017-10-10)

* Made the library a pure JavaScript library.
* Removed MediaWiki extension registration.
* Removed MediaWiki ResourceLoader module definitions.
* Raised DataValues JavaScript library version requirement to 0.10.0.
* Raised Wikibase DataModel JavaScript library version requirement to 4.0.0.
* Removed all serializers and deserializers for Claim collections:
  * Removed ClaimGroupDeserializer
  * Removed ClaimGroupSerializer
  * Removed ClaimGroupSetDeserializer
  * Removed ClaimGroupSetSerializer
  * Removed ClaimListDeserializer
  * Removed ClaimListSerializer
* Removed WIKIBASE_SERIALIZATION_JAVASCRIPT_VERSION constant.

### 2.1.0 (2017-09-04)

* Updated the MediaWiki entry point to use the extension.json format.
* Added code sniffers for JavaScript as well as PHP.
* Dropped compatibility with PHP 5.3.
* Added support for deserializing snak hashes.

### 2.0.8 (2016-09-09)

* Fix an issue with MediaWiki loading (init.mw.php)

### 2.0.7 (2016-08-01)

* Added compatibility with DataModel JavaScript 3.0.0.

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
