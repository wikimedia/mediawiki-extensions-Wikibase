# Wikibase DataModel JavaScript

**Wikibase DataModel JavaScript** is the JavaScript implementation of the
basic [Wikibase DataModel](https://www.mediawiki.org/wiki/Wikibase/DataModel)
at the heart of the [Wikibase software](http://wikiba.se/).
As the [PHP implementation of the DataModel](https://github.com/wmde/WikibaseDataModel), this
library only implements the basic Item and Property entity types, and the components they are made
of.

Wikibase uses this library together with the
[Wikibase Serialization JavaScript library](https://github.com/wmde/WikibaseSerializationJavaScript)
to deserialize API responses from serialized JSON to actual DataModel objects. These objects can
then be consumed and manipulated via convenient getter and setter methods, turned back into JSON via
the corresponding serializer, and send back to the API.

[![Wikibase JavaScript Data Model UML diagram](https://upload.wikimedia.org/wikipedia/commons/thumb/c/c2/Wikibase_JavaScript_Data_Model_1.0.svg/600px-Wikibase_JavaScript_Data_Model_1.0.svg.png)](https://commons.wikimedia.org/wiki/File:Wikibase_JavaScript_Data_Model_1.0.svg)

## Release notes

### 6.1.0 (2019-10-24)
* Bundle some very basic Typescript types

### 6.0.0 (2019-10-21)
* Using CommonJS modules instead of global namespaces for all files

### 5.1.0 (2019-10-01)
* Added `index.js` exporting all public data model parts

### 5.0.0 (2018-07-06)
* Remove references to fingerprint from Entity:
  * Removed `Entity.getFingerprint`
  * Removed `Entity.setFingerprint`

### 4.1.0 (2018-06-25)
* Added new FingerprintableEntity class
 * Make Item and Property inherit from it 

### 4.0.0 (2017-10-09)

* Made the library a pure JavaScript library.
* Removed MediaWiki extension registration.
* Removed MediaWiki ResourceLoader module definitions.
* Removed WIKIBASE_DATAMODEL_JAVASCRIPT_VERSION constant.
* Raised DataValues JavaScript library version requirement to 0.10.0.
* Removed all Claim collections:
  * Removed `ClaimGroup`
  * Removed `ClaimGroupSet`
  * Removed `ClaimList`
* Removed all move-related methods from SnakList:
  * Removed `SnakList.getValidMoveIndices`
  * Removed `SnakList.move`
  * Removed `SnakList.moveDown`
  * Removed `SnakList.moveUp`
  * Declared `SnakList.getFilteredSnakList` private
* Removed dysfunctional methods from all Entity classes:
  * Removed `Item.addStatement`
  * Removed `Item.removeStatement`
  * Removed `Property.addStatement`
  * Removed `Property.removeStatement`

### 3.1.0 (2017-09-04)

* Added an optional `hash` constructor parameter and a `getHash` method to `Snak`,
  `PropertyValueSnak`, `PropertySomeValueSnak`, and `PropertyNoValueSnak`.
* Travis now runs the QUnit tests.

### 3.0.1 (2016-09-09)

* Fix an issue with MediaWiki loading (init.php)

### 3.0.0 (2016-08-02)

* Added `Set::toArray`.
* `Fingerprint::setLabel`, `setDescription` and `setAliases` remove the element when null or an
  empty Term or MultiTerm is given.
* Removed cloning from `MultiTerm.getTexts`.
* Turned EntityId into a simple wrapper around an opaque serialization string
  * Removed EntityId::getNumericId
  * Removed EntityId::getEntityType
  * Removed EntityId::getPrefixedId
  * Introduced EntityId::getSerialization
  * Removed `numeric-id` and `entity-type` fields from `toJSON` return value
  * Introduced `id` field to `toJSON` return value
  * Removed `numeric-id` and `entity-type` arguments from EntityId constructor
  * Introduced `id` argument to EntityId constructor
* Deprecated the `WIKIBASE_DATAMODEL_JAVASCRIPT_VERSION` PHP constant.
* The (optional) extension registration in `init.php` now depends on MediaWiki >=1.25.

### 2.0.1 (2016-01-27)

* Added compatibility with DataValues JavaScript 0.8.0.

### 2.0.0 (2016-01-12)

#### Breaking changes
* `Term` and `MultiTerm` do not accept empty language codes any more.
* Removed cloning from the following methods:
  * `GroupableCollection.toArray` and `List.toArray`
  * `Group.getItemContainer` and `setItemContainer`
* Removed `propertyId` parameter from `Claim.getQualifiers`.
* `SnakList.getFilteredSnakList` can not be called with `null` any more.

#### Other changes
* Fixed possible performance issues due to cloning in `Group.equals` and the `List`, `Map`and `Set` constructors.

### 1.0.2 (2015-05-28)

#### Enhancements
* Adapt to DataValuesJavaScript 0.7.0.
* SnakList.merge() accepts null.
* Updated code documentation for being able to automatically generate a proper documentation using JSDuck.

### 1.0.1 (2014-11-05)
* Using DataValues JavaScript 0.6.x.

### 1.0.0 (2014-11-05)

#### Breaking changes
* Removed wikibase.datamodel.Reference.setSnaks(). Generate new Reference objects when interacting with the API to reflect hash changes performed in the back-end.
* Removed wikibase.datamodel.Entity.equals().
* Removed wikibase.datamodel.Reference.setSnaks().
* wikibase.datamodel.Reference constructor does not accept Snak object(s) any more.
* An entity cannot be constructed by passing internal object representation to Entity constructor anymore; Use entity specific constructors instead.
* Removed useless Entity.isNew(), Entity.newEmpty().
* Removed Entity.getLabel(), Entity.getLabels(), Entity.getDescription(), Entity.getDescription(), Entity.getAliases(), Entity.getAllAliases(); Acquire data via Entity.getFingerprint() instead.
* Removed Entity.getClaims(); Acquire claims/statements via Entity specific implementation.
* Item.getSiteLinks() returns a SiteLinkSet object instead of an array of SiteLink objects.
* Renamed Property.getDataType() to Property.getDataTypeId().
* Removed all toJSON(), newFromJSON(), toMap() and newFromMap() functions; Use serializers and unserializers of wikibase.serialization instead.
* Statement does not accept a plain array of references anymore; Supply a ReferenceList instead.
* Remove Claim.TYPE and Statement.TYPE attributes.
* Instead of inheriting from Claim, Statement now features a Claim instance that needs to be passed to the Statement constructor.
* Reference constructor does not accept a plain list of Snak objects anymore; Supply a proper SnakList object instead.
* SnakList constructor only accepts arrays of Snak objects.

#### Enhancements
* Added ClaimGroup.
* Added ClaimGroupSet.
* Added ClaimList.
* Added Fingerprint.
* Added Group.
* Added GroupableCollection.
* Added List.
* Added Map.
* Added MultiTerm.
* Added MultiTermMap.
* Added ReferenceList.
* Added SiteLinkSet.
* Added StatementGroup.
* Added StatementGroupSet.
* Added StatementList.
* Added Term.
* Added TermMap.
* Added Set.
* Added individual constructors for Item and Property.
* Added Entity.getFingerprint(), Entity.setFingerprint().
* Added SiteLink and Statement specific functionality to Item.
* Added Statement specific functionality to Property.
* Added isEmpty() and equals() functions to Item and Property.

### 0.3.2 (2014-08-19)

* Added wikibase.datamodel.SiteLink.
* Added wikibase.datamodel.Item.getSiteLinks().

### 0.3.1 (2014-08-14)

* Remove ResourceLoader dependencies on jquery and mediawiki (bug 69468)

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
