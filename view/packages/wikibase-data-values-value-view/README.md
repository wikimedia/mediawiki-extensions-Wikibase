# ValueView

ValueView introduces the <code>jQuery.valueview</code> widget which may be used to display and edit data values (DataValue objects defined in the DataValues library and supported via the DataValuesJavaScript package). The valueview widget and its resources may be extended to support custom custom data value implementations.

## Components

### jQuery.valueview (jQuery.valueview.valueview)

<code>jQuery.valueview.valueview</code> may be used to display and edit data values. It can be instantiated via the widget's bridge <code>jQuery.valueview</code>.

### jQuery.valueview.Expert

Experts are widgets that deal with editing data values. An expert provides the functionality to edit a specific data value type or data values suitable for a certain data type. <code>jQuery.valueview.Expert</code> is the base constructor for such experts.

### jQuery.valueview.ExpertStore

Experts are managed by <code>jQuery.valueview.ExpertStore</code> instance which provides its experts to <code>jQuery.valueview</code>.

### jQuery.valueview.ViewState

<code>jQuery.valueview.ViewState</code> links experts and <code>jQuery.valueview</code> in form of a facade that allows experts to observe certain aspects of <code>jQuery.valueview</code>.

## Usage

The following assumes you also have ```data-values/data-types```,  ```data-values/data-values``` and ```data-values/javascript``` installed.

When using <code>jQuery.valueview</code> for handling a data value of some sort, a <code>jQuery.valueview.ExpertStore</code> with knowledge about an expert dedicated to the used data value type is required and can be set up as follows:

```javascript
var dv = dataValues;
var vv = jQuery.valueview;
var dt = dataTypes;
var experts = new vv.ExpertStore();

var stringValue = new dv.StringValue( 'foo' );

// Consider this a data value using the "string" data value type internally:
var urlDataType = new dt.DataType( 'url', dv.StringValue.TYPE );

experts.registerDataValueExpert( vv.experts.StringValue, dv.StringValue.TYPE );

console.log(
  experts.getExpert( stringValue.getType() ) === experts.getExpert( urlDataType.getDataValueType(), urlDataType.getId() )
);
// true because "url" data type's data value type is "string"; The string expert will be used as fallback.
```

The <code>jQuery.valueview.ExpertStore</code> can now be injected into a new <code>jQuery.valueview</code> which will then be able to edit string data values.

```javascript
var $subject = $( '<div/>' ).appendTo( $( 'body' ).empty() );

// In addition to the expert factory, value parser and value formatter factories need to be provided. The feature the same mechanisms than the expert factory. For this example, we just initialize them with the string parser/formatter as default parser/formatter.
var parsers = new valueParsers.ValueParserStore( valueParsers.StringParser );
var formatters = new valueFormatters.ValueFormatterStore( valueFormatters.StringFormatter );

$subject.valueview( {
  expertStore: experts,
  parserStore: parsers,
  formatterStore: formatters,
  value: new dv.StringValue( 'text' )
} );

var valueView = $subject.data( 'valueview' );
```

Having created a <code>jQuery.valueview</code> displaying *text*, <code>valueView.\<memberFn\></code>
will now allow invoking member functions. For example:
* Emptying the view: <code>valueView.value( null );</code>
* Allowing the user to edit the value: <code>valueView.startEditing();</code>
* Stopping the user from editing the value: <code>valueView.stopEditing();</code>
* Returning the current value: <code>valueView.value();</code>

Setting the view to a data value it cannot handle because of lacking a suitable expert will result in a proper error notification being displayed. Calling <code>.value()</code> will still return the value but the user can neither see nor edit the value.

## Running as MediaWiki extension

<code>mediaWiki.ext.valueView</code> may be used to initialize ValueView as MediaWiki extension. Loading <code>mediaWiki.ext.valueView</code> will initialize and fill a <code>jQuery.valueview.ExpertStore</code> which is issued to <code>jQuery.valueview</code> as default expert provider. Consequently, no custom experts for basic data values and data types need to be registered and <code>jQuery.valueview</code> may be used without passing a custom <code>jQuery.ExpertStore</code>.

## Architecture

ValueView depends heavily on formatters and parsers. Formatters are used for converting
dataValues.DataValue instances to DOM elements, and parsers are used for converting
plain strings to dataValues.DataValue instances.

Experts are only used for editing values. They are constructed when starting edit mode,
and destroyed after leaving edit mode. Experts have the following lifecycle:

* `\_init()`: Load parsed, formatted and raw (text) values from the ValueView (via ViewState)
	and initialize DOM
* Edit loop
	* (User edits)
	* Expert calls `viewNotifier.notify( 'change' )` and triggers parsing and formatting
	* `rawValue()`: Return the current raw (text) value
	* (optional) `preview.showSpinner()`: Replace preview with a spinner
	* `draw()`: (Re-)draw non-editable parts of the expert using the (new) parsed and formatted value
		from the ValueView (via ViewState)
* `destroy()`: Destroy DOM

Other methods an Expert needs to provide:

* `valueCharacteristics()`
* `focus()`
* `blur()`

## Release notes

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
* Use version 0.6.0 of DataValuesJavascript
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
* Updated DataValuesJavaScript dependency to version 0.5.0.
* Removed setting default formatter provider/store and parser provider/store of jQuery.valueView in mw.ext.valueView since no defaults are provided by DataValuesJavaScript as of version 0.5.0.
* Removed mw.ext.valueView module.
* Fixed ValueView to again support setting value to null
* jQuery.valueview expects the rejected promise that may be returned by ValueParser's parse() and ValueFormatter's format() to feature a single parameter only.

### 0.4.2 (2014-03-27)

* Use DOM children of the ValueView as formatted value on initialization
* Don't parse and format a value if it did not change

### 0.4.1 (2014-03-26)

* Require data-values/javascript 0.4

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

* Adapted changes of data-values/javascript version 0.3.
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
