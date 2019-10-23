# ValueView release notes

## 1.0.0 (2019-10-23)

* Using CommonJS modules instead of global namespaces for these files:
  * ExpertExtender/ExpertExtender.LanguageSelector.js 
  * ExpertExtender/ExpertExtender.UnitSelector.js 
  * jquery.valueview.ViewState.js 
  * jquery.valueview.valueview.js 
  * experts/MonolingualText.js 
  * experts/GeoShape.js 
  * experts/TimeInput.js 
  * experts/GlobeCoordinateInput.js 
  * experts/TabularData.js 
  * experts/QuantityInput.js 
  * experts/CommonsMediaType.js 
  * experts/StringValue.js 
* Updated i18n message translations.

## 0.22.4 (2018-11-19)

* Re-render "globecoordinate" values for preview.

## 0.22.3 (2018-11-08)

* Updated some deprecated `jQuery.expr` statements.
* Updated i18n message translations.

## 0.22.2 (2018-04-17)

* Updated i18n message translations.

## 0.22.1 (2017-11-16)
* Fixed `jQuery.ui.commonssuggester` not always displaying all thumbnails.

## 0.22.0 (2017-10-16)
* `jQuery.valueview.ExpertExtender.Listrotator` constructor requires a `MessageProvider` now.
* `jQuery.ui.listrotator` requires a `messageProvider` option now.
* Made the library a pure JavaScript library.
* Removed MediaWiki extension credits registration.
* Removed MediaWiki i18n message registration.
* Removed MediaWiki ResourceLoader module definitions.
* Removed `VALUEVIEW_VERSION` constant.
* Raised DataValues JavaScript library version requirement to 0.10.0.

## 0.21.0 (2017-10-12)
* Removed `jQuery.valueview.ExpertExtender.CalendarHint`.
* Removed dependency on `globeCoordinate.Formatter`.
* Removed dependency on the [Parameterize](https://github.com/AStepaniuk/qunit-parameterize) QUnit plugin.
* Fixed `jQuery.valueview.ExpertExtender.Preview.destroy` failing when called twice.
* Fixed incomplete `jQuery.valueview.experts.GlobeCoordinateInput.destroy`.
* Fixed incomplete `jQuery.valueview.experts.TimeInput.destroy`.
* Made all tests compatible with QUnit 2.

## 0.20.1 (2017-08-03)
* Fixed `jQuery.ui.suggester` and `jQuery.animateWithEvent` tests.

## 0.20.0 (2017-08-02)
* Removed `jQuery.valueview.ExpertExtender.Toggler`.

## 0.19.2 (2017-06-28)
* All relevant `jQuery.valueview.experts.…` classes are now exported via `module.exports`.
* Raised required PHP version from 5.3 to 5.5.9.
* Replaces JSCS and JSLint with ESLint.

## 0.19.1 (2017-04-18)
* Added support for the `tabular-data` data type.
* Adjusted `jQuery.ui.listrotator` to obey the Wikimedia color scheme.

## 0.19.0 (2017-03-14)
* Major changes to `jQuery.ui.commonssuggester`:
  * Now uses MediaWiki `search` API instead of OpenSearch.
  * Now searches all namespaces by default, instead of only the File namespace.
  * Now shows thumbnails when searching the File namespace.
  * Added required `apiUrl` option.
  * Added optional `contentModel` option.
  * Added `ui-commonssuggester-list` CSS class.
* `GeoShape` expert now only suggests GeoJSON pages.
* Changes to `jQuery.valueview.valueview`:
  * Fixed a bug where certain consecutive changes were considered invalid.
  * `getTextValue` and `getFormattedValue` never return null any more, but empty strings when not up
    to date, and the formatter API failed or did not responded yet.
* Maximized developer dependency ranges.
* Tests do not use `CompletenessTest` any more.

## 0.18.2 (2017-02-02)
* Fixed `jQuery.ui.ooMenu`'s `customItems` initialization.

## 0.18.1 (2017-02-01)
* Added support for `geo-shape` data type.
* The close icon on inputextenders now follows MediaWiki standard.
* Fixed `jQuery.ui.ooMenu`'s `customItems` sharing state.
* Fixed text overflow in listrotator dropdowns.

## 0.18.0 (2016-10-14)
* `jQuery.ui.commonssuggester` now allows pasting full and partial Wikimedia Commons URLs.
* Added support for `null` precision to `GlobeCoordinateInput`.

## 0.17.4 (2016-10-05)
* Handle null upstream values in `jQuery.valueview.ExpertExtender.LanguageSelector::onInitialShow`.
* Properly forward API error messages in `jquery.ui.unitsuggester`.

## 0.17.3 (2016-08-01)
* Fixed `jQuery.ui.suggester` font size.

## 0.17.2 (2016-07-28)
* Moved `valueviewchange` event after actual changing the value.
* `jQuery.ui.suggester` now also closes when tabbing out and reopens on click.
* `jQuery.ui.toggler` now uses the default MediaWiki link color.

## 0.17.1 (2016-04-13)
* Fixed `jQuery.focusAt` and `jquery.valueview.ExpertExtender.Listrotator` QUnit tests.

## 0.17.0 (2016-04-12)
* `GlobeCoordinateInput` and `TimeInput` do not use `jQuery.valueview.ExpertExtender.Toggler` any
  more.
* Simplified `jQuery.ui.listrotator` widget:
  * Removed `$prev` and `$next` elements as well as `prev`, `next` and `rotate` methods.
  * Replaced `$auto` element with "set manually" checkbox.
  * Removed all `animation` options.
  * Removed `isRTL` option.
* Improved `jQuery.ui.inputextender` styles.
* Made `jQuery.ui.inputextender` animations faster.

## 0.16.1 (2016-02-22)
* Fix quantities without unit in `QuantityInput`

## 0.16.0 (2016-02-10)

* Changed `ValueView` to take two `ValueFormatter` instances instead of a
  `ValueFormatterStore`
* Changed `Expert::valueCharacteristics` definition so that it does not have to be
  statically callable anymore

## 0.15.11 (2016-02-03)

* Correctly detect a changed language code when editing a `MonolingualTextValue`.

## 0.15.8 (2016-01-27)

* Added compatibility with DataValues JavaScript 0.8.0.
* Fixed `jQuery.valueview.experts.GlobeCoordinateInput` failing when precision is null.

## 0.15.7 (2016-01-15)

* Addded optional `visible` option to `jQuery.ui.toggler`.
* Fixed `jQuery.ui.languagesuggester` not propagating updates.
* Fixed `jQuery.ui.languagesuggester::getSelectedValue` to return `null` when the user changed the
  input's value and not yet selected a value.
* Fixed `jQuery.valueview.ExpertExtender.LanguageSelector` to fall back on the language code when
  there's no label available.

## 0.15.6 (2015-11-04)

* Introduced `toggle` and `isCollapsed` methods on `jQuery.ui.toggler`.

## 0.15.5 (2015-09-09)

* Fixed `jQuery.valueview.ExpertExtender.UnitSelector` test.

## 0.15.4 (2015-09-09)

* `jQuery.valueview.experts.QuantityInput` extracts an optional `.wb-unit` node from the formatted
  value and passes it to the UnitSelector.
* Both QuantityInput and UnitSelector use a different format in the `getUpstreamValue` callback.
* `jQuery.ui.unitsuggester` now supports a `defaultSelectedUrl` option.

## 0.15.3 (2015-08-27)

* Fixed `jQuery.valueview.experts.QuantityInput` test.

## 0.15.2 (2015-08-27)

### Enhancements

* `jQuery.valueview.expert.valueCharacteristics` gets the output format passed in.
* `jQuery.valueview.experts.QuantityInput` explicitely asks `QuantityFormatter` to not apply
  rounding and units in plain text format.
* `jQuery.valueview.valueview` passes a `vocabularyLookupApiUrl` option to all experts.
* `jQuery.valueview.experts.QuantityInput` and `jQuery.valueview.ExpertExtender.UnitSelector` now
  pass a `vocabularyLookupApiUrl` option to `jQuery.ui.unitsuggester`.
* `jQuery.ui.unitsuggester` uses the `concepturi` from `wbsearchentities` results, if available.

## 0.15.1 (2015-08-20)

### Enhancements

* `jQuery.valueview.experts.QuantityInput` also submits the `unit` option if it's null.

### Bugfixes

* `jQuery.ui.unitsuggester` now queries the `wbsearchentities` API for a specific language.
* Fixed `jQuery.valueview.ExpertExtender.UnitSelector.destroy`.

## 0.15.0 (2015-08-19)

### Breaking changes

* Removed deprecated constant `ValueView_VERSION`, use `VALUEVIEW_VERSION` instead.
* Removed `jQuery.valueview.disable`, `jQuery.valueview.enable` and `jQuery.valueview.isDisabled`.
  These function were used to mock native `jQuery.Widget` functionality while adding a full `draw`
  cycle on top. `jQuery.valueview.draw` does not consider the state anymore.

### Enhancements

* Added `jQuery.valueview.experts.QuantityInput` expert.
* Added `jQuery.valueview.ExpertExtender.UnitSelector`.
* Added `jQuery.ui.unitsuggester`.
* `jQuery.valueview.valueview` now passes a `language` option to all experts.
* Refined `jQuery.ui.listrotator` style to have a more obvious active state.
* Set `jQuery.ui.commonssuggester` to always use https.

## 0.14.5 (2015-06-11)

* Fixed `jQuery.valueview.ExpertExtender.CalendarHint` test broken due to DataValues JavaScript
  dependency update.

## 0.14.4 (2015-06-10)

* Added expert for `UnDeserializableValue`s.
* Updated DataValues JavaScript dependency to version 0.7.

## 0.14.3 (2015-04-02)

* Fix premature afterparse handling (e.g. save) of parsed values.

## 0.14.2 (2015-03-31)

### Bugfixes

* Remove qunit dependency to make QUnit tests work in Karma

## 0.14.1 (2015-03-16)

### Bugfixes

* Make QUnit tests pass in PhantomJS

## 0.14.0 (2015-03-12)

### Breaking changes

* Removed `jQuery.NativeEventHandler`.

### Bugfixes

* `jQuery.valueview.ExpertExtender.Listrotator` redraws on 0 value now.

## 0.13.0 (2015-02-05)

* Turned `util.MessageProvider` into an interface
* Introduced several implementations of `util.MessageProvider`
 * `util.HashMessageProvider`
 * `util.CombiningMessageProvider`
 * `util.PrefixingMessageProvider`
* Replaced the `mw` option to `valueview` with a `messageProvider` option

## 0.12.0 (2015-01-27)

* Removed internal dependency on Universal Language Selector (ULS)
* Introduced `utils.ContentLanguages`

### Breaking changes

* `jQuery.valueview.ExpertExtender.LanguageSelector` constructor requires `utils.ContentLanguages`
  now.

## 0.11.0 (2015-01-21)

* `jQuery.ui.toggler`: Added parameter to `animation` event determining whether the toggler's
  subject will be visible or hidden.
* `jQuery.ui.toggler`: Added `refresh` function to be able to reflect visibility changes to the
  toggler's subject that have been applied externally.
* `jQuery.ui.toggler`: Changed `_reflectVisibilityOnToggleIcon` to be private.
* Dropped `javascript:void(0)` placeholders from `$.ui.toggler`, `$.ui.listrotator` and
  `$.ui.CalendarHint`.

### Bugfixes

* `$.fn.inputautoexpand`: Fixed height expansion mechanism.
* Use `wgULSLanguages` instead of `jQuery.uls.data.languages` for MonolingualTextValue.
	This reduces the number of available languages, but makes it match the list
	used by the Wikibase backend validators.

## 0.10.0 (2015-01-06)

* `$.ui.suggester`: Removed `event` parameter from `search()`.

## 0.9.1 (2014-12-17)

### Enhancements

* `$.ui.suggester`: Added `isSearching()` function to determine whether searching is in progress.
* Added `force` parameter to `$.AutoInputExpand.prototype.expand()`.

## 0.9.0 (2014-12-05)

### Breaking changes

* `util.highlightSubstring`: Replaced `caseInsensitive` option with `caseSensitive` option
  defaulting to `false`.
* `$.ui.suggester`: Moved protected `_minTermLength` member to `options`.

### Enhancements

* `$.ui.suggester`: Fixed input element being refocused when selecting a suggestion via keyboard
  input.

## 0.8.1 (2014-11-07)

### Enhancements

* `$.ui.ooMenu.CustomItem`: Added `setVisibility`, `setAction` and `setCssClass` functions.
  Visibility may be set to a static (boolean) value.
* `$.valueview.draw` (`$.valueview.valueview.draw`), `$.valueview.drawContent`
  (`$.valueview.valueview.drawContent`) and `$.valueview.Expert.draw` return jQuery promises.
* `$.valueview.draw` (`$.valueview.valueview.draw`) triggers `afterdraw` event.
* `$.valueview.startEditing` (`$.valueview.valueview.startEditing`) triggers `afterstartediting`
  event.
* `$.valueview.stopEditing` (`$.valueview.valueview.stopEditing`) triggers `afterstopediting` event.
* Fixed precision auto-detection of `$.valueview.experts.GlobeCoordinateInput`.

## 0.8.0 (2014-11-03)

### Enhancements

* $.ui.suggester: Hitting the backspace or delete key if the input is empty already does not trigger
  search anymore.
* $.ui.suggester: Refocus input element after selecting a suggestion via mouse click.
* $.ui.suggester: Added "confineMinWidthTo" option for specifying an element, the suggestion list's
  minimum width shall be confined to.

### Breaking changes

* Replaced $.util.highlightMatchingCharacters with util.highlightSubstring.
* $.valueview(.valueview) requires new "language" option to be set.

## 0.7.0 (2014-09-10)

### Enhancements

* Implemented jQuery.ui.languagesuggester.
* Input extender extension will not be hidden on mousedown event.

### Breaking changes

* Updated DataValues JavaScript dependency to version 0.6.
* Renamed jQuery.ui.suggestCommons to jQuery.ui.commonssuggester.
* When pressing ESC on a suggester enhanced input element while the suggester menu is visible, the
  key event's propagation is stopped.

### Bugfixes

* Fixed eachchange event: Cancel event after it got removed.
* Fixed calendar switch to Julian (Bug 65847).
* Fixed bug that broke the ListRotator after edit and cancel (Bug 70294).

## 0.6.10 (2014-08-14)

* Remove ResourceLoader dependencies on jquery and mediawiki (bug 69468)

## 0.6.9 (2014-08-13)

* $.ui.suggester updates "lang" and "dir" attribute of its menu whenever repositioning the menu.
* $.ui.suggester issues "error" event in case of an error.
* LanguageSelector allows typing the language code instead of the name.

## 0.6.8 (2014-07-29)

* Only attempt to close a suggester if it's open

## 0.6.7 (2014-07-23)

* Fix bug 68386, black colored text on blue background in jquery.ui.suggester when hovered via
  keydown.

## 0.6.6 (2014-07-18)

* Mouse clicks other than simple left clicks don't trigger events any more in all ooMenus
* Suggester items default to black even if they are links
* Suggesters z-index is now dynamically calculated with it's position on screen

## 0.6.5 (2014-07-17)

* Fixed the QUnit tests
* Don't underline ooMenu/ suggester items
* Various small bug fixes

## 0.6.4 (2014-07-02)

* Changed MonolingualText option from "lang" to "valuelang".
* Added setLink() function to jQuery.ui.ooMenu.CustomItem prototype allowing dynamic updates of the
  link target.
* Removed default "javascript:void(0);" link target of jQuery.ui.ooMenu.CustomItem instances.
* Reordered GlobeCoordinate precisions.

## 0.6.3 (2014-06-25)

* Added expert for MonolingualText values.
* Support editing arbitrary precisions for GlobeCoordinates.
* Added support for options in the ui.toggler widget.
* Fixed wrong valueview-valueview-... class names after jQuery update.
* Fixed RTL related bug in ui.suggester.

## 0.6.2 (2014-06-16)

* Fixed a bug where the values of inputs with a suggester on were set to an older value sometimes.

## 0.6.1 (2014-06-09)

* Make the minimal term length of the suggester configurable.
* Add resource loader dependencies for jquery.ui.suggester, fixing bug 66268 and bug 66257.

## 0.6.0 (2014-06-04)

* Re-created jQuery.ui.suggester widget removing dependencies on jQuery.ui.autocomplete and
  jQuery.ui.menu.
* Implemented jQuery.util.highlightMatchingCharacters
* Implemented jQuery.ui.ooMenu
* Implemented jQuery.ui.suggestCommons
* Removed CommonsMediaType expert dependency on SuggestedStringValue expert.
* Prevent enter-key from adding newline character in String expert
* Fixed bug 64658 which caused the inputextender widget being invisible
* Refactored inputextender usage of experts
* Added addExtension method to jQuery.ValueView.Expert

## 0.5.1 (2014-04-01)

* Change TimeInput::valueCharacteristics() to not returning precision or calendarmodel if set to
  auto.
* Change TimeInput::draw() to update the rotators' values if they are in auto mode
* Change GlobeCoordinateInput::draw() to update the precision rotator value if it is in auto mode

## 0.5.0 (2014-03-28)

* Renamed jQuery.valueView.ExpertFactory to jQuery.valueView.ExpertStore.
* Renamed jQuery.valueView option "expertProvider" to "expertStore".
* Renamed jQuery.valueView.ExpertFactory to jQuery.valueView.ExpertStore.
* Renamed jQuery.valueView option "expertProvider" to "expertStore".
* Renamed jQuery.valueView option "valueFormatterProvider" to "formatterStore".
* Renamed jQuery valueView option "valueParserProvider" to "parserStore".
* Updated DataValues JavaScript dependency to version 0.5.
* Removed setting default formatter provider/store and parser provider/store of jQuery.valueView in
  mw.ext.valueView since no defaults are provided by DataValues JavaScript as of version 0.5.0.
* Removed mw.ext.valueView module.
* Fixed ValueView to again support setting value to null
* jQuery.valueview expects the rejected promise that may be returned by ValueParser's parse() and
  ValueFormatter's format() to feature a single parameter only.

## 0.4.2 (2014-03-27)

* Use DOM children of the ValueView as formatted value on initialization
* Don't parse and format a value if it did not change

## 0.4.1 (2014-03-26)

* Updated DataValues JavaScript dependency to version 0.4.

## 0.4.0 (2014-03-26)

* Remove trimming from StringValue expert
* Use ViewState::getFormattedValue for GlobeCoordinate formatting
* Make some of the animations user definable
* Use ViewState formatting and parsing in TimeValue
* Make ValueView responsible for static mode and remove BifidExpert
* Don't redraw ValueView in {en,dis}able if nothing changed

## 0.3.3 (2014-02-24)

* Fix inputextender for time values

## 0.3.2 (2014-02-24)

* REVERTED Use ViewState::getFormattedValue for GlobeCoordinate formatting

## 0.3.1 (2014-02-12)

### Enhancements

* Added "isRtl" option to jQuery.ui.listrotator.
* Use ViewState::getFormattedValue for Url formatting
* Use ViewState::getFormattedValue for GlobeCoordinate formatting

## 0.3.0 (2014-02-04)

### Enhancements

* Removed dependency on the DataTypes library.
* ExpertFactory may be initialized with a default expert now.

### Breaking changes

* Changed ExpertFactory mechanisms to comply with ValueFormatterFactory and ValueParserFactory:
 * Removed generic registerExpert() method. registerDataTypeExpert() and registerDataValueExpert()
   should be used to register experts.
 * Removed additional unused and obsolete functions:
  * getCoveredDataValueTypes()
  * getCoveredDataTypes()
  * hasExpertFor()
  * newExpert()
* Removed CommonsMediaType and UrlType expert registrations from mw.ext.valueView.js since these are
  supposed to be registered in Wikibase where the corresponding data types are instantiated.
* Replaced jQuery.valueview.valueview's "on" option with "dataTypeId" and "dataValueType" options.

## 0.2.1 (2014-01-30)

### Enhancements

* Updated DataValues JavaScript dependency to version 0.3.
* Renamed jQuery.valueview.preview to jQuery.ui.preview

## 0.2.0 (2014-01-29)

### Refactorings

* Renamed $.valueview.MessageProvider to util.MessageProvider
* Renamed $.inputAutoExpand to $.inputautoexpand
* Renamed $.nativeEventHandler to $.NativeEventHandler
* Moved $.valueview.MockViewState to $.valueview.tests.MockViewState
* Corrected several MediaWiki resource loader module names (and some file names):
 * $.fn.focusAt -> $.focusAt
 * $.valueview.experts.commonsmediatype -> $.valueview.experts.CommonsMediaType
 * $.valueview.experts.emptyvalue -> $.valueview.experts.EmptyValue
 * $.valueview.experts.globecoordinateinput -> $.valueview.experts.GlobeCoordinateInput
 * $.valueview.experts.globecoordinatevalue -> $.valueview.experts.GlobeCoordinateValue
 * $.valueview.experts.mock -> $.valueview.experts.Mock
 * $.valueview.experts.quantitytype -> $.valueview.experts.QuantityType
 * $.valueview.experts.staticdom -> $.valueview.experts.StaticDom
 * $.valueview.experts.stringvalue -> $.valueview.experts.StringValue
 * $.valueview.experts.timeinput -> $.valueview.experts.TimeInput
 * $.valueview.experts.timevalue -> $.valueview.experts.TimeValue
 * $.valueview.experts.unsupportedvalue -> $.valueview.experts.UnsupportedValue
 * $.valueview.experts.urltype -> $.valueview.experts.UrlType
* Added $.valueview.experts.SuggestedStringValue as a separate resource loader module
* $.valueview.experts.CommonsMediaType does not format on its own, but relies on value formatters.

### Enhancements

* #6 Added util.Notifier

## 0.1.0 (2013-12-23)

Initial release.
