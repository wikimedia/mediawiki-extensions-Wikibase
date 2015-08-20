# ValueView

ValueView introduces the <code>jQuery.valueview</code> widget which may be used to display and edit data values (`DataValue` objects defined in the [DataValues](https://github.com/DataValues/DataValues) library and supported via the [DataValues JavaScript](https://github.com/wmde/DataValuesJavascript) package). The `jQuery.valueview` widget and its resources may be extended to support custom `DataValue` implementations.

## Components

### jQuery.valueview

`jQuery.valueview` may be used to display and edit data values. While the widget's original constructor is located at `jQuery.valueview.valueview`, the widget should be instantiated via its bridge `jQuery.valueview`.

### jQuery.valueview.Expert

`jQuery.valueview.Expert`s are widgets that deal with editing `DataValue`s. An `Expert` provides the functionality to edit a specific `DataValue` (e.g. `StringValue`) or a `DataValue` suitable for a certain `DataType` (e.g. the `url` `DataType` which uses the `StringValue` for representation; see also [DataTypes](https://github.com/wmde/DataTypes) library). `jQuery.valueview.Expert` is the base constructor for such `Expert`s.

### jQuery.valueview.ExpertExtender

`jQuery.valueview.ExpertExtender` may be used to provide additional information and/or input elements while interacting with the `Expert`. The `ExpertExtender` may, for example, be used to provide a preview of how the parsed value will be displayed after saving (see `jQuery.ExpertExtender.Preview`). Options provided by the `ValueParser` corresponding to the `DataValue` being edited may be set using `jQuery.valueview.ExpertExtender.*` input elements added to the `ExpertExtender` instance.

### jQuery.valueview.ExpertStore

`Expert`s are managed by `jQuery.valueview.ExpertStore` instance which provides its `Expert`s to `jQuery.valueview`.

### jQuery.valueview.ViewState

`jQuery.valueview.ViewState` acts as a *Facade* linking `Expert`s and `jQuery.valueview`. `ViewState` allows `Expert`s to observe certain aspects of `jQuery.valueview` and enables `Expert`s to update the linked `jQuery.valueview` instance.

## Usage

For the usage examples, it is assumed the following packages are installed:
* [DataValues](https://github.com/DataValues/DataValues)
* [DataValues JavaScript](https://github.com/wmde/DataValuesJavascript)
* [DataTypes](https://github.com/wmde/DataTypes)

When using `jQuery.valueview` for handling a `DataValue`, a `jQuery.valueview.ExpertStore` with knowledge about an `Expert` dedicated to the `DataValue`'s type is required and can be set up as follows:

```javascript
var dv = dataValues,
	vv = jQuery.valueview,
	dt = dataTypes,
	experts = new vv.ExpertStore();

var stringValue = new dv.StringValue( 'foo' );

// Consider this a DataType using the StringValue DataValue internally:
var urlDataType = new dt.DataType( 'url', dv.StringValue.TYPE );

experts.registerDataValueExpert( vv.experts.StringValue, dv.StringValue.TYPE );

console.log(
	experts.getExpert( stringValue.getType() )
		=== experts.getExpert( urlDataType.getDataValueType(), urlDataType.getId() )
);
// true because "url" DataType's DataValue type is "string"; The "string" DataValue's Expert will be
// used as fall-back.

```

Now, the `jQuery.valueview.ExpertStore` can be injected into a new `jQuery.valueview` instance enabling it to edit "string" `DataValue`s.

```javascript
var $subject = $( '<div/>' ).appendTo( $( 'body' ).empty() );

// In addition to the Expert store, ValueParser and ValueFormatter stores need to be provided. These
// feature the same mechanisms as the Expert store. For this example, we just initialize them with
// the "string" Parser/Formatter as default Parser/Formatter.
var parsers = new valueParsers.ValueParserStore( valueParsers.StringParser ),
	formatters = new valueFormatters.ValueFormatterStore( valueFormatters.StringFormatter );

$subject.valueview( {
  expertStore: experts,
  parserStore: parsers,
  formatterStore: formatters,
  language: 'en', // language code transmitted to Parser and Formatter
  value: new dv.StringValue( 'text' )
} );

var valueView = $subject.data( 'valueview' );
```

Having created a `jQuery.valueview` displaying *text*, the widget's member functions may be used for interaction, for example:
* Emptying the view: `valueView.value( null );`
* Allowing the user to edit the value: `valueView.startEditing();`
* Stopping the user from editing the value: `valueView.stopEditing();`
* Returning the current value: `valueView.value();`

Setting a `jQuery.valueview` instance's value to a `DataValue` it cannot handle because no suitable `Expert` can be determined from the `ExpertStore` will result in an error notification being displayed. Calling `.value()` will still return the value but the user can neither see nor edit the value.

## Architecture

`jQuery.valueview` heavily depends on `ValueFormatter`s and `ValueParser`s defined via the [DataValues JavaScript](https://github.com/wmde/DataValuesJavascript) library. `ValueFormatter`s are used to convert `DataValue` instances to DOM elements, and `ValueParser`s are used to convert plain strings (which may be accompanied by some options) to `DataValue` instances.
Since `Expert`s only are used for editing values, they are constructed when starting edit mode and destroyed after leaving edit mode. `Expert`s have the following lifecycle:
* `_init()`: Load parsed, formatted and raw (text) values from the `jQuery.valueview` instance linked via `jQuery.valueview.ViewState` and initialize DOM.
* Edit loop
	* (User edits)
	* `Expert` calls `viewNotifier.notify( 'change' )` and triggers parsing and formatting.
	* `rawValue()`: Return the current raw (text) value.
	* (optional) `preview.showSpinner()`: Replace preview with a loading spinner.
	* `draw()`: (Re-)draw non-editable parts of the `Expert` using the (new) parsed and formatted value 		from the `jQuery.valueview` instance (via `jQuery.valueview.ViewState`)
* `destroy()`: Destroy DOM.

Other methods an `Expert` needs to provide:
* `valueCharacteristics()`
* `focus()`
* `blur()`

## Release notes

### 0.15.1 (2015-08-20)

#### Enhancements
* `jQuery.valueview.experts.QuantityInput` also submits the `unit` option if it's null.

#### Bugfixes
* `jQuery.ui.unitsuggester` now queries the `wbsearchentities` API for a specific language.
* Fixed `jQuery.valueview.ExpertExtender.UnitSelector.destroy`.

### 0.15.0 (2015-08-19)

#### Breaking changes
* Removed deprecated constant `ValueView_VERSION`, use `VALUEVIEW_VERSION` instead.
* Removed `jQuery.valueview.disable`, `jQuery.valueview.enable` and `jQuery.valueview.isDisabled`. These function were used to mock native `jQuery.Widget` functionality while adding a full `draw` cycle on top. `jQuery.valueview.draw` does not consider the state anymore.

#### Enhancements
* Added `jQuery.valueview.experts.QuantityInput` expert.
* Added `jQuery.valueview.ExpertExtender.UnitSelector`.
* Added `jQuery.ui.unitsuggester`.
* `jQuery.valueview.valueview` now passes a `language` option to all experts.
* Refined `jQuery.ui.listrotator` style to have a more obvious active state.
* Set `jQuery.ui.commonssuggester` to always use https.

### 0.14.5 (2015-06-11)
* Fixed `jQuery.valueview.ExpertExtender.CalendarHint` test broken due to DataValues JavaScript dependency update.

### 0.14.4 (2015-06-10)
* Added expert for `UnDeserializableValue`s.
* Updated DataValues JavaScript dependency to version 0.7.

### 0.14.3 (2015-04-02)
* Fix premature afterparse handling (e.g. save) of parsed values.

### 0.14.2 (2015-03-31)

#### Bugfixes
* Remove qunit dependency to make QUnit tests work in Karma

### 0.14.1 (2015-03-16)

#### Bugfixes
* Make QUnit tests pass in PhantomJS

### 0.14.0 (2015-03-12)

#### Breaking changes
* Removed `jQuery.NativeEventHandler`.

#### Bugfixes
* `jQuery.valueview.ExpertExtender.Listrotator` redraws on 0 value now.

### 0.13.0 (2015-02-05)
* Turned `util.MessageProvider` into an interface
* Introduced several implementations of `util.MessageProvider`
 * `util.HashMessageProvider`
 * `util.CombiningMessageProvider`
 * `util.PrefixingMessageProvider`
* Replaced the `mw` option to `valueview` with a `messageProvider` option

### 0.12.0 (2015-01-27)
* Removed internal dependency on Universal Language Selector (ULS)
* Introduced `utils.ContentLanguages`

#### Breaking changes
* `jQuery.valueview.ExpertExtender.LanguageSelector` constructor requires `utils.ContentLanguages` now.

### 0.11.0 (2015-01-21)
* `jQuery.ui.toggler`: Added parameter to `animation` event determining whether the toggler's subject will be visible or hidden.
* `jQuery.ui.toggler`: Added `refresh` function to be able to reflect visibility changes to the toggler's subject that have been applied externally.
* `jQuery.ui.toggler`: Changed `_reflectVisibilityOnToggleIcon` to be private.
* Dropped `javascript:void(0)` placeholders from `$.ui.toggler`, `$.ui.listrotator` and `$.ui.CalendarHint`.

#### Bugfixes
* `$.fn.inputautoexpand`: Fixed height expansion mechanism.
* Use `wgULSLanguages` instead of `jQuery.uls.data.languages` for MonolingualTextValue.
	This reduces the number of available languages, but makes it match the list
	used by the Wikibase backend validators.

### 0.10.0 (2015-01-06)
* `$.ui.suggester`: Removed `event` parameter from `search()`.

### 0.9.1 (2014-12-17)

#### Enhancements
* `$.ui.suggester`: Added `isSearching()` function to determine whether searching is in progress.
* Added `force` parameter to `$.AutoInputExpand.prototype.expand()`.

### 0.9.0 (2014-12-05)

#### Breaking changes
* `util.highlightSubstring`: Replaced `caseInsensitive` option with `caseSensitive` option defaulting to `false`.
* `$.ui.suggester`: Moved protected `_minTermLength` member to `options`.

#### Enhancements
* `$.ui.suggester`: Fixed input element being refocused when selecting a suggestion via keyboard input.

### 0.8.1 (2014-11-07)

#### Enhancements
* `$.ui.ooMenu.CustomItem`: Added `setVisibility`, `setAction` and `setCssClass` functions. Visibility may be set to a static (boolean) value.
* `$.valueview.draw` (`$.valueview.valueview.draw`), `$.valueview.drawContent` (`$.valueview.valueview.drawContent`) and `$.valueview.Expert.draw` return jQuery promises.
* `$.valueview.draw` (`$.valueview.valueview.draw`) triggers `afterdraw` event.
* `$.valueview.startEditing` (`$.valueview.valueview.startEditing`) triggers `afterstartediting` event.
* `$.valueview.stopEditing` (`$.valueview.valueview.stopEditing`) triggers `afterstopediting` event.
* Fixed precision auto-detection of `$.valueview.experts.GlobeCoordinateInput`.

### 0.8.0 (2014-11-03)

#### Enhancements
* $.ui.suggester: Hitting the backspace or delete key if the input is empty already does not trigger search anymore.
* $.ui.suggester: Refocus input element after selecting a suggestion via mouse click.
* $.ui.suggester: Added "confineMinWidthTo" option for specifying an element, the suggestion list's minimum width shall be confined to.

#### Breaking changes
* Replaced $.util.highlightMatchingCharacters with util.highlightSubstring.
* $.valueview(.valueview) requires new "language" option to be set.

### 0.7.0 (2014-09-10)

#### Enhancements
* Implemented jQuery.ui.languagesuggester.
* Input extender extension will not be hidden on mousedown event.

#### Breaking changes
* Updated DataValues JavaScript dependency to version 0.6.
* Renamed jQuery.ui.suggestCommons to jQuery.ui.commonssuggester.
* When pressing ESC on a suggester enhanced input element while the suggester menu is visible, the key event's propagation is stopped.

#### Bugfixes
* Fixed eachchange event: Cancel event after it got removed.
* Fixed calendar switch to Julian (Bug 65847).
* Fixed bug that broke the ListRotator after edit and cancel (Bug 70294).

### 0.6.10 (2014-08-14)
* Remove ResourceLoader dependencies on jquery and mediawiki (bug 69468)

### 0.6.9 (2014-08-13)
* $.ui.suggester updates "lang" and "dir" attribute of its menu whenever repositioning the menu.
* $.ui.suggester issues "error" event in case of an error.
* LanguageSelector allows typing the language code instead of the name.

### 0.6.8 (2014-07-29)
* Only attempt to close a suggester if it's open

### 0.6.7 (2014-07-23)
* Fix bug 68386, black colored text on blue background in jquery.ui.suggester when hovered via keydown.

### 0.6.6 (2014-07-18)
* Mouse clicks other than simple left clicks don't trigger events any more in all ooMenus
* Suggester items default to black even if they are links
* Suggesters z-index is now dynamically calculated with it's position on screen

### 0.6.5 (2014-07-17)
* Fixed the QUnit tests
* Don't underline ooMenu/ suggester items
* Various small bug fixes

### 0.6.4 (2014-07-02)
* Changed MonolingualText option from "lang" to "valuelang".
* Added setLink() function to jQuery.ui.ooMenu.CustomItem prototype allowing dynamic updates of the link target.
* Removed default "javascript:void(0);" link target of jQuery.ui.ooMenu.CustomItem instances.
* Reordered GlobeCoordinate precisions.

### 0.6.3 (2014-06-25)
* Added expert for MonolingualText values.
* Support editing arbitrary precisions for GlobeCoordinates.
* Added support for options in the ui.toggler widget.
* Fixed wrong valueview-valueview-... class names after jQuery update.
* Fixed RTL related bug in ui.suggester.

### 0.6.2 (2014-06-16)
* Fixed a bug where the values of inputs with a suggester on were set to an older value sometimes.

### 0.6.1 (2014-06-09)

* Make the minimal term length of the suggester configurable.
* Add resource loader dependencies for jquery.ui.suggester, fixing bug 66268 and bug 66257.

### 0.6 (2014-06-04)

* Re-created jQuery.ui.suggester widget removing dependencies on jQuery.ui.autocomplete and jQuery.ui.menu
* Implemented jQuery.util.highlightMatchingCharacters
* Implemented jQuery.ui.ooMenu
* Implemented jQuery.ui.suggestCommons
* Removed CommonsMediaType expert dependency on SuggestedStringValue expert.
* Prevent enter-key from adding newline character in String expert
* Fixed bug 64658 which caused the inputextender widget being invisible
* Refactored inputextender usage of experts
* Added addExtension method to jQuery.ValueView.Expert

### 0.5.1 (2014-04-01)

* Change TimeInput::valueCharacteristics() to not returning precision or calendarmodel if set to auto
* Change TimeInput::draw() to update the rotators' values if they are in auto mode
* Change GlobeCoordinateInput::draw() to update the precision rotator value if it is in auto mode

### 0.5 (2014-03-28)

* Renamed jQuery.valueView.ExpertFactory to jQuery.valueView.ExpertStore.
* Renamed jQuery.valueView option "expertProvider" to "expertStore".
* Renamed jQuery.valueView.ExpertFactory to jQuery.valueView.ExpertStore.
* Renamed jQuery.valueView option "expertProvider" to "expertStore".
* Renamed jQuery.valueView option "valueFormatterProvider" to "formatterStore".
* Renamed jQuery valueView option "valueParserProvider" to "parserStore".
* Updated DataValues JavaScript dependency to version 0.5.
* Removed setting default formatter provider/store and parser provider/store of jQuery.valueView in mw.ext.valueView since no defaults are provided by DataValues JavaScript as of version 0.5.0.
* Removed mw.ext.valueView module.
* Fixed ValueView to again support setting value to null
* jQuery.valueview expects the rejected promise that may be returned by ValueParser's parse() and ValueFormatter's format() to feature a single parameter only.

### 0.4.2 (2014-03-27)

* Use DOM children of the ValueView as formatted value on initialization
* Don't parse and format a value if it did not change

### 0.4.1 (2014-03-26)

* Updated DataValues JavaScript dependency to version 0.4.

### 0.4 (2014-03-26)

* Remove trimming from StringValue expert
* Use ViewState::getFormattedValue for GlobeCoordinate formatting
* Make some of the animations user definable
* Use ViewState formatting and parsing in TimeValue
* Make ValueView responsible for static mode and remove BifidExpert
* Don't redraw ValueView in {en,dis}able if nothing changed

### 0.3.3 (2014-02-24)

* Fix inputextender for time values

### 0.3.2 (2014-02-24)

* REVERTED Use ViewState::getFormattedValue for GlobeCoordinate formatting

### 0.3.1 (2014-02-12)

#### Enhancements

* Added "isRtl" option to jQuery.ui.listrotator.
* Use ViewState::getFormattedValue for Url formatting
* Use ViewState::getFormattedValue for GlobeCoordinate formatting

### 0.3 (2014-02-04)

#### Enhancements

* Removed dependency on the DataTypes library.
* ExpertFactory may be initialized with a default expert now.

#### Breaking changes

* Changed ExpertFactory mechanisms to comply with ValueFormatterFactory and ValueParserFactory:
 * Removed generic registerExpert() method. registerDataTypeExpert() and registerDataValueExpert() should be used to register experts.
 * Removed additional unused and obsolete functions:
  * getCoveredDataValueTypes()
  * getCoveredDataTypes()
  * hasExpertFor()
  * newExpert()
* Removed CommonsMediaType and UrlType expert registrations from mw.ext.valueView.js since these are supposed to be registered in Wikibase where the corresponding data types are instantiated.
* Replaced jQuery.valueview.valueview's "on" option with "dataTypeId" and "dataValueType" options.

### 0.2.1 (2014-01-30)

#### Enhancements

* Updated DataValues JavaScript dependency to version 0.3.
* Renamed jQuery.valueview.preview to jQuery.ui.preview

### 0.2 (2014-01-29)

#### Refactorings

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

#### Enhancements

* #6 Added util.Notifier

### 0.1 (2013-12-23)

Initial release.

# Bugs on Phabricator

https://phabricator.wikimedia.org/project/view/918/
