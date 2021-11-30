# Hooks JS

This file describes JavaScript hooks defined by the Wikibase extensions.

[TOC]

#### wikibase.entityPage.entityView.rendered ([entityViewInit.js](https://github.com/wikimedia/Wikibase/blob/master/repo/resources/wikibase.ui.entityViewInit.js))
  * Called after the Wikibase UI is initialized.

#### wikibase.entityPage.entityLoaded ([entityLoaded.js](https://github.com/wikimedia/Wikibase/blob/master/repo/resources/wikibase.entityPage.entityLoaded.js))
  * Called as soon as the JSON representing the entity stored on the current entity page is loaded.
  * Listener callbacks should expect the entity as a native JavaScript object (the [parsed JSON serialization](https://doc.wikimedia.org/Wikibase/master/php/md_docs_topics_json.html)) passed as the first argument.

#### wikibase.statement.saved ([StatementsChanger.js](https://github.com/wikimedia/Wikibase/blob/master/view/resources/wikibase/entityChangers/StatementsChanger.js))
  * Called after a statement has been saved. Entity ID and statement ID are strings, and the old statement (null in case of a new statement) and the updated one are [Javascript Wikibase DataModel](https://github.com/wmde/WikibaseDataModelJavaScript) [Statements](https://github.com/wmde/WikibaseDataModelJavaScript/blob/master/src/Statement.js), all passed as arguments.

#### wikibase.statement.removed ([StatementsChanger.js](https://github.com/wikimedia/Wikibase/blob/master/view/resources/wikibase/entityChangers/StatementsChanger.js))
  * Called after a statement has been removed. Entity ID and statement ID are passed as arguments.

#### wikibase.entityselector.search ([entityselector.js](https://github.com/wikimedia/Wikibase/blob/master/view/resources/jquery/wikibase/jquery.wikibase.entityselector.js))
  * Called when entity selector fetches suggestions.
  * An object containing the following elements is passed as first argument :element, term and options. As second argument a function is passed allowing to add promises that return additional suggestion items. Those items will replace existing items with the same ID and will be placed on top of the list. If an item has a property `rating` then this property will be used for sorting the list by it descending (higher `rating` on top). The range of the `rating` should be between 0-1.

#### wikibase.statement.startEditing ([jquery.wikibase.statementview.js](https://github.com/wikimedia/Wikibase/blob/master/view/resources/jquery/wikibase/jquery.wikibase.statementview.js))
  * Called when entering the edit mode for an existing statement. Gets the statement's guid passed as parameter.

#### wikibase.statement.stopEditing ([jquery.wikibase.statementview.js](https://github.com/wikimedia/Wikibase/blob/master/view/resources/jquery/wikibase/jquery.wikibase.statementview.js))
  * Called when leaving the edit mode for an existing statement. Gets the statement's guid passed as parameter.
