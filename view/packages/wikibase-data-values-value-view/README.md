# ValueView

ValueView introduces the <code>jQuery.valueview</code> widget which may be used to display and edit
data values (`DataValue` objects defined in the
[DataValues](https://github.com/DataValues/DataValues) library and supported via the
[DataValues JavaScript](https://github.com/wmde/DataValuesJavascript) package). The
`jQuery.valueview` widget and its resources may be extended to support custom `DataValue`
implementations.


Recent changes can be found in the [release notes](RELEASE-NOTES.md).

## Components

### jQuery.valueview

`jQuery.valueview` may be used to display and edit data values. While the widget's original
constructor is located at `jQuery.valueview.valueview`, the widget should be instantiated via its
bridge `jQuery.valueview`.

### jQuery.valueview.Expert

`jQuery.valueview.Expert`s are widgets that deal with editing `DataValue`s. An `Expert` provides the
functionality to edit a specific `DataValue` (e.g. `StringValue`) or a `DataValue` suitable for a
certain `DataType` (e.g. the `url` `DataType` which uses the `StringValue` for representation; see
also [DataTypes](https://github.com/wmde/DataTypes) library). `jQuery.valueview.Expert` is the base
constructor for such `Expert`s.

### jQuery.valueview.ExpertExtender

`jQuery.valueview.ExpertExtender` may be used to provide additional information and/or input
elements while interacting with the `Expert`. The `ExpertExtender` may, for example, be used to
provide a preview of how the parsed value will be displayed after saving (see
`jQuery.ExpertExtender.Preview`). Options provided by the `ValueParser` corresponding to the
`DataValue` being edited may be set using `jQuery.valueview.ExpertExtender.*` input elements added
to the `ExpertExtender` instance.

### jQuery.valueview.ExpertStore

`Expert`s are managed by `jQuery.valueview.ExpertStore` instance which provides its `Expert`s to
`jQuery.valueview`.

### ViewState

`ViewState` acts as a *Facade* linking `Expert`s and `jQuery.valueview`.
`ViewState` allows `Expert`s to observe certain aspects of `jQuery.valueview` and enables `Expert`s
to update the linked `jQuery.valueview` instance.

## Usage

For the usage examples, it is assumed the following packages are installed:
* [DataValues](https://github.com/DataValues/DataValues)
* [DataValues JavaScript](https://github.com/wmde/DataValuesJavascript)
* [DataTypes](https://github.com/wmde/DataTypes)

When using `jQuery.valueview` for handling a `DataValue`, a `jQuery.valueview.ExpertStore` with
knowledge about an `Expert` dedicated to the `DataValue`'s type is required and can be set up as
follows:

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

Now, the `jQuery.valueview.ExpertStore` can be injected into a new `jQuery.valueview` instance
enabling it to edit "string" `DataValue`s.

```javascript
var $subject = $( '<div/>' ).appendTo( $( 'body' ).empty() );

// In addition to the Expert store, a ValueParser store and two ValueFormatters need to be provided. The parser store
// features the same mechanisms as the Expert store. For this example, we just initialize the parser store with
// the "string" parser as default. The formatters will format a string as it is.
var parsers = new valueParsers.ValueParserStore( valueParsers.StringParser );

$subject.valueview( {
  expertStore: experts,
  parserStore: parsers,
  plaintextFormatter: new valueFormatters.StringFormatter(),
  htmlFormatter: new valueFormatters.StringFormatter(),
  language: 'en', // language code transmitted to Parser
  value: new dv.StringValue( 'text' )
} );

var valueView = $subject.data( 'valueview' );
```

Having created a `jQuery.valueview` displaying *text*, the widget's member functions may be used for
interaction, for example:
* Emptying the view: `valueView.value( null );`
* Allowing the user to edit the value: `valueView.startEditing();`
* Stopping the user from editing the value: `valueView.stopEditing();`
* Returning the current value: `valueView.value();`

Setting a `jQuery.valueview` instance's value to a `DataValue` it cannot handle because no suitable
`Expert` can be determined from the `ExpertStore` will result in an error notification being
displayed. Calling `.value()` will still return the value but the user can neither see nor edit the
value.

## Architecture

`jQuery.valueview` heavily depends on `ValueFormatter`s and `ValueParser`s defined via the
[DataValues JavaScript](https://github.com/wmde/DataValuesJavascript) library. `ValueFormatter`s are
used to convert `DataValue` instances to DOM elements, and `ValueParser`s are used to convert plain
strings (which may be accompanied by some options) to `DataValue` instances.
Since `Expert`s only are used for editing values, they are constructed when starting edit mode and
destroyed after leaving edit mode. `Expert`s have the following lifecycle:
* `_init()`: Load parsed, formatted and raw (text) values from the `jQuery.valueview` instance
  linked via `ViewState` and initialize DOM.
* Edit loop
	* (User edits)
	* `Expert` calls `viewNotifier.notify( 'change' )` and triggers parsing and formatting.
	* `rawValue()`: Return the current raw (text) value.
	* (optional) `preview.showSpinner()`: Replace preview with a loading spinner.
	* `draw()`: (Re-)draw non-editable parts of the `Expert` using the (new) parsed and formatted
	  value from the `jQuery.valueview` instance (via `ViewState`)
* `destroy()`: Destroy DOM.

Other methods an `Expert` needs to provide:
* `valueCharacteristics()`
* `focus()`
* `blur()`

# Bugs on Phabricator

https://phabricator.wikimedia.org/project/view/918/
