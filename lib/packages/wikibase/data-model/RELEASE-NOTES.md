# Wikibase DataModel release notes

## Version 0.5

Under development.

#### Additions

* Added ItemId and PropertyId classes.
* Added BasicEntityIdParser that allows for parsing of serializations of entity ids defined
  by Wikibase DataModel.

#### Improvements

* EntityId no longer is a DataValue. A new EntityIdValue was added to function as a DataValue
  representing the identity of an entity.

#### Removals

* ObjectComparer has been removed from the public namespace.
* SnakFactory has been moved out of this component.

#### Deprecations

* Constructing an EntityId (rather then one of its derivatives) is now deprecated.
* Wikibase\EntityId has been renamed to Wikibase\DataModel\Entity\EntityId. The old name is deprecated.

## Version 0.4 (2013-06-17)

Initial release as Wikibase DataModel component.

## Version 0.1 (2012-11-01)

Initial release as part of Wikibase.
