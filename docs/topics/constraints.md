# Constraints

This document describes how, when and where constraints on Wikibase
Entities are enforced and defined.

## Soft Constraints

Soft constraints (aka input constraints) are constraints imposed on changes to entities, that is, they validate user input. They are checked only upon ''direct modification'' of ''that part'' of the Entity, and are not enforced for (parts of) Entities already in the database.

Furthermore, (local) input constraints can impose constraints on modifications, that is, constraints that take into account an Entity's ''previous state'' (such as ''main snak continuity'' below).

**NOTE**: It shall be possible to modify any part of an Entity even if some other part of that Entity is violating a soft constraint. This allows soft constraints to be added or tightened without rendering existing entities invalid; more importantly, it allows partially corrupt entities to be handled by the system, e.g. for manual repair.

**NOTE**: Checking a soft constraint may require knowledge of the current state of the database; however, violating such a constraint does not violate the integrity of the database. For instance, data values that reference other entities should be checked to be referencing an ''existing'' entity, but it is not a violation of data integrity if an entity contains a “broken” reference.

Soft constraints are enforced by the respective ChangeOps. Snak validation is based on the validators provided by a DataTypeValidatorFactory (see datatypes.wiki for details), while the validators for terms come from the TermValidatorFactory.

Note that some soft constraints only require local knowledge of the value in questions, while others are “global” in that they apply to the state of the entire database at a given time.

Examples of local constraints:

* Data value validity (referencing only existing Items, range checks, no empty strings, valid unicode, etc).
* Snak integrity (referencing an existing Property, using the correct data value type).
* Main snak continuity: A Claim's main Snak can not be changed to a Snak about a different Property than the current main Snak.
* Property labels must not be valid (well-formed) Property IDs. ''NOTE'': For technical reasons, this may be implements as a global constraint, even though this is not necessary.

Examples of global constraints which consider the state of the entire database:

* If an Item has a label and a description for a given language, no other Item may have that same combination for the same language.
* Term (fingerprint) validity (labels are not too long, language codes are known, etc).

## Hard Global Constraints

Hard constraints are global constraints that are required by the data model. Violating a hard constraint means breaking the integrity of the database and may render the respective entity (or even the entire system) unusable.

Hard constraints are enforced on ''every'' save of an Entity. They are checked during the database transaction that modifies the page table. The EntityConstraintProvider class defines the hard constraints for the different kinds of entities, which are eventually returned by the method <code>EntityContent::getOnSaveValidators()</code> to be applied during saving.

**NOTE**: Hard constraints must always consider the full Entity, and compare it to the actual current state of the data model, as stored in the database.

Currently, the following hard global constraints are enforced for the standard entity types:

* The labels of Properties (but not Items) must be unique per language.
* Site links are unique, that is, only one Item can contain a given sitelink.
* Any Item can only contain one SiteLink for any given site.
