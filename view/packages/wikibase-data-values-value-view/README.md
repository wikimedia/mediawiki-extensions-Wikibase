# ValueView

ValueView introduces the jQuery.ui.widget based front-end component jquery.valueview which
allows to display and edit data values defined by the DataValues library.

## Components

When loading the jquery.valueview resource loader definition, the following components
introduced by this extension will be available:

### jquery.valueview

Widget for displaying and editing data values.

### mediaWiki.ext.valueView

Object representing the "ValueView" MediaWiki extension.
When loaded, this will hook ''jQuery.valueview'' up to some of its basic
formatters by overwriting jQuery.valueview.valueview.prototype.options.expertProvider


### jquery.valueview

@see resources/jquery.valueview/README

### Dependencies

See ValueView.resources.mw.php for dependencies of this library. These dependencies are
shipped as part of the MediaWiki extension while the core jQuery.valueview component
can be found under resources/jQuery.valueview.

## Release notes

### 0.1 (2013-12-23)

Initial release.
