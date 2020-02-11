module.exports =
/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "fae3");
/******/ })
/************************************************************************/
/******/ ({

/***/ "00ee":
/***/ (function(module, exports, __webpack_require__) {

var wellKnownSymbol = __webpack_require__("b622");

var TO_STRING_TAG = wellKnownSymbol('toStringTag');
var test = {};

test[TO_STRING_TAG] = 'z';

module.exports = String(test) === '[object z]';


/***/ }),

/***/ "019d":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "0366":
/***/ (function(module, exports, __webpack_require__) {

var aFunction = __webpack_require__("1c0b");

// optional / simple context binding
module.exports = function (fn, that, length) {
  aFunction(fn);
  if (that === undefined) return fn;
  switch (length) {
    case 0: return function () {
      return fn.call(that);
    };
    case 1: return function (a) {
      return fn.call(that, a);
    };
    case 2: return function (a, b) {
      return fn.call(that, a, b);
    };
    case 3: return function (a, b, c) {
      return fn.call(that, a, b, c);
    };
  }
  return function (/* ...args */) {
    return fn.apply(that, arguments);
  };
};


/***/ }),

/***/ "0538":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var aFunction = __webpack_require__("1c0b");
var isObject = __webpack_require__("861d");

var slice = [].slice;
var factories = {};

var construct = function (C, argsLength, args) {
  if (!(argsLength in factories)) {
    for (var list = [], i = 0; i < argsLength; i++) list[i] = 'a[' + i + ']';
    // eslint-disable-next-line no-new-func
    factories[argsLength] = Function('C,a', 'return new C(' + list.join(',') + ')');
  } return factories[argsLength](C, args);
};

// `Function.prototype.bind` method implementation
// https://tc39.github.io/ecma262/#sec-function.prototype.bind
module.exports = Function.bind || function bind(that /* , ...args */) {
  var fn = aFunction(this);
  var partArgs = slice.call(arguments, 1);
  var boundFunction = function bound(/* args... */) {
    var args = partArgs.concat(slice.call(arguments));
    return this instanceof boundFunction ? construct(fn, args.length, args) : fn.apply(that, args);
  };
  if (isObject(fn.prototype)) boundFunction.prototype = fn.prototype;
  return boundFunction;
};


/***/ }),

/***/ "057f":
/***/ (function(module, exports, __webpack_require__) {

var toIndexedObject = __webpack_require__("fc6a");
var nativeGetOwnPropertyNames = __webpack_require__("241c").f;

var toString = {}.toString;

var windowNames = typeof window == 'object' && window && Object.getOwnPropertyNames
  ? Object.getOwnPropertyNames(window) : [];

var getWindowNames = function (it) {
  try {
    return nativeGetOwnPropertyNames(it);
  } catch (error) {
    return windowNames.slice();
  }
};

// fallback for IE11 buggy Object.getOwnPropertyNames with iframe and window
module.exports.f = function getOwnPropertyNames(it) {
  return windowNames && toString.call(it) == '[object Window]'
    ? getWindowNames(it)
    : nativeGetOwnPropertyNames(toIndexedObject(it));
};


/***/ }),

/***/ "06cf":
/***/ (function(module, exports, __webpack_require__) {

var DESCRIPTORS = __webpack_require__("83ab");
var propertyIsEnumerableModule = __webpack_require__("d1e7");
var createPropertyDescriptor = __webpack_require__("5c6c");
var toIndexedObject = __webpack_require__("fc6a");
var toPrimitive = __webpack_require__("c04e");
var has = __webpack_require__("5135");
var IE8_DOM_DEFINE = __webpack_require__("0cfb");

var nativeGetOwnPropertyDescriptor = Object.getOwnPropertyDescriptor;

// `Object.getOwnPropertyDescriptor` method
// https://tc39.github.io/ecma262/#sec-object.getownpropertydescriptor
exports.f = DESCRIPTORS ? nativeGetOwnPropertyDescriptor : function getOwnPropertyDescriptor(O, P) {
  O = toIndexedObject(O);
  P = toPrimitive(P, true);
  if (IE8_DOM_DEFINE) try {
    return nativeGetOwnPropertyDescriptor(O, P);
  } catch (error) { /* empty */ }
  if (has(O, P)) return createPropertyDescriptor(!propertyIsEnumerableModule.f.call(O, P), O[P]);
};


/***/ }),

/***/ "071a":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "07ac":
/***/ (function(module, exports, __webpack_require__) {

var $ = __webpack_require__("23e7");
var $values = __webpack_require__("6f53").values;

// `Object.values` method
// https://tc39.github.io/ecma262/#sec-object.values
$({ target: 'Object', stat: true }, {
  values: function values(O) {
    return $values(O);
  }
});


/***/ }),

/***/ "0828":
/***/ (function(module, exports) {

/**
 * Constants enumerating the HTTP status codes.
 *
 * All status codes defined in RFC1945 (HTTP/1.0, RFC2616 (HTTP/1.1),
 * RFC2518 (WebDAV), RFC6585 (Additional HTTP Status Codes), and
 * RFC7538 (Permanent Redirect) are supported.
 *
 * Based on the org.apache.commons.httpclient.HttpStatus Java API.
 *
 * Ported by Bryce Neal.
 */

var statusCodes = {};

statusCodes[exports.ACCEPTED = 202] = "Accepted";
statusCodes[exports.BAD_GATEWAY = 502] = "Bad Gateway";
statusCodes[exports.BAD_REQUEST = 400] = "Bad Request";
statusCodes[exports.CONFLICT = 409] = "Conflict";
statusCodes[exports.CONTINUE = 100] = "Continue";
statusCodes[exports.CREATED = 201] = "Created";
statusCodes[exports.EXPECTATION_FAILED = 417] = "Expectation Failed";
statusCodes[exports.FAILED_DEPENDENCY  = 424] = "Failed Dependency";
statusCodes[exports.FORBIDDEN = 403] = "Forbidden";
statusCodes[exports.GATEWAY_TIMEOUT = 504] = "Gateway Timeout";
statusCodes[exports.GONE = 410] = "Gone";
statusCodes[exports.HTTP_VERSION_NOT_SUPPORTED = 505] = "HTTP Version Not Supported";
statusCodes[exports.IM_A_TEAPOT = 418] = "I'm a teapot";
statusCodes[exports.INSUFFICIENT_SPACE_ON_RESOURCE = 419] = "Insufficient Space on Resource";
statusCodes[exports.INSUFFICIENT_STORAGE = 507] = "Insufficient Storage";
statusCodes[exports.INTERNAL_SERVER_ERROR = 500] = "Server Error";
statusCodes[exports.LENGTH_REQUIRED = 411] = "Length Required";
statusCodes[exports.LOCKED = 423] = "Locked";
statusCodes[exports.METHOD_FAILURE = 420] = "Method Failure";
statusCodes[exports.METHOD_NOT_ALLOWED = 405] = "Method Not Allowed";
statusCodes[exports.MOVED_PERMANENTLY = 301] = "Moved Permanently";
statusCodes[exports.MOVED_TEMPORARILY = 302] = "Moved Temporarily";
statusCodes[exports.MULTI_STATUS = 207] = "Multi-Status";
statusCodes[exports.MULTIPLE_CHOICES = 300] = "Multiple Choices";
statusCodes[exports.NETWORK_AUTHENTICATION_REQUIRED = 511] = "Network Authentication Required";
statusCodes[exports.NO_CONTENT = 204] = "No Content";
statusCodes[exports.NON_AUTHORITATIVE_INFORMATION = 203] = "Non Authoritative Information";
statusCodes[exports.NOT_ACCEPTABLE = 406] = "Not Acceptable";
statusCodes[exports.NOT_FOUND = 404] = "Not Found";
statusCodes[exports.NOT_IMPLEMENTED = 501] = "Not Implemented";
statusCodes[exports.NOT_MODIFIED = 304] = "Not Modified";
statusCodes[exports.OK = 200] = "OK";
statusCodes[exports.PARTIAL_CONTENT = 206] = "Partial Content";
statusCodes[exports.PAYMENT_REQUIRED = 402] = "Payment Required";
statusCodes[exports.PERMANENT_REDIRECT = 308] = "Permanent Redirect";
statusCodes[exports.PRECONDITION_FAILED = 412] = "Precondition Failed";
statusCodes[exports.PRECONDITION_REQUIRED = 428] = "Precondition Required";
statusCodes[exports.PROCESSING = 102] = "Processing";
statusCodes[exports.PROXY_AUTHENTICATION_REQUIRED = 407] = "Proxy Authentication Required";
statusCodes[exports.REQUEST_HEADER_FIELDS_TOO_LARGE = 431] = "Request Header Fields Too Large";
statusCodes[exports.REQUEST_TIMEOUT = 408] = "Request Timeout";
statusCodes[exports.REQUEST_TOO_LONG = 413] = "Request Entity Too Large";
statusCodes[exports.REQUEST_URI_TOO_LONG = 414] = "Request-URI Too Long";
statusCodes[exports.REQUESTED_RANGE_NOT_SATISFIABLE = 416] = "Requested Range Not Satisfiable";
statusCodes[exports.RESET_CONTENT = 205] = "Reset Content";
statusCodes[exports.SEE_OTHER = 303] = "See Other";
statusCodes[exports.SERVICE_UNAVAILABLE = 503] = "Service Unavailable";
statusCodes[exports.SWITCHING_PROTOCOLS = 101] = "Switching Protocols";
statusCodes[exports.TEMPORARY_REDIRECT = 307] = "Temporary Redirect";
statusCodes[exports.TOO_MANY_REQUESTS = 429] = "Too Many Requests";
statusCodes[exports.UNAUTHORIZED = 401] = "Unauthorized";
statusCodes[exports.UNPROCESSABLE_ENTITY = 422] = "Unprocessable Entity";
statusCodes[exports.UNSUPPORTED_MEDIA_TYPE = 415] = "Unsupported Media Type";
statusCodes[exports.USE_PROXY = 305] = "Use Proxy";

exports.getStatusText = function(statusCode) {
  if (statusCodes.hasOwnProperty(statusCode)) {
    return statusCodes[statusCode];
  } else {
    throw new Error("Status code does not exist: " + statusCode);
  }
};

exports.getStatusCode = function(reasonPhrase) {
  for (key in statusCodes) {
    if (statusCodes[key] === reasonPhrase) {
      return parseInt(key, 10);
    }
  }
  throw new Error("Reason phrase does not exist: " + reasonPhrase);
};



/***/ }),

/***/ "0cfb":
/***/ (function(module, exports, __webpack_require__) {

var DESCRIPTORS = __webpack_require__("83ab");
var fails = __webpack_require__("d039");
var createElement = __webpack_require__("cc12");

// Thank's IE8 for his funny defineProperty
module.exports = !DESCRIPTORS && !fails(function () {
  return Object.defineProperty(createElement('div'), 'a', {
    get: function () { return 7; }
  }).a != 7;
});


/***/ }),

/***/ "0e65":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var getDay = Date.prototype.getDay;
var tryDateObject = function tryDateObject(value) {
	try {
		getDay.call(value);
		return true;
	} catch (e) {
		return false;
	}
};

var toStr = Object.prototype.toString;
var dateClass = '[object Date]';
var hasToStringTag = typeof Symbol === 'function' && typeof Symbol.toStringTag === 'symbol';

module.exports = function isDateObject(value) {
	if (typeof value !== 'object' || value === null) { return false; }
	return hasToStringTag ? tryDateObject(value) : toStr.call(value) === dateClass;
};


/***/ }),

/***/ "0ea2":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_TermLabel_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("6bb8");
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_TermLabel_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_TermLabel_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */
 /* unused harmony default export */ var _unused_webpack_default_export = (_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_TermLabel_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "0f7c":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var implementation = __webpack_require__("688e");

module.exports = Function.prototype.bind || implementation;


/***/ }),

/***/ "11c1":
/***/ (function(module, exports, __webpack_require__) {

var v1 = __webpack_require__("c437");
var v4 = __webpack_require__("c64e");

var uuid = v4;
uuid.v1 = v1;
uuid.v4 = v4;

module.exports = uuid;


/***/ }),

/***/ "1276":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var fixRegExpWellKnownSymbolLogic = __webpack_require__("d784");
var isRegExp = __webpack_require__("44e7");
var anObject = __webpack_require__("825a");
var requireObjectCoercible = __webpack_require__("1d80");
var speciesConstructor = __webpack_require__("4840");
var advanceStringIndex = __webpack_require__("8aa5");
var toLength = __webpack_require__("50c4");
var callRegExpExec = __webpack_require__("14c3");
var regexpExec = __webpack_require__("9263");
var fails = __webpack_require__("d039");

var arrayPush = [].push;
var min = Math.min;
var MAX_UINT32 = 0xFFFFFFFF;

// babel-minify transpiles RegExp('x', 'y') -> /x/y and it causes SyntaxError
var SUPPORTS_Y = !fails(function () { return !RegExp(MAX_UINT32, 'y'); });

// @@split logic
fixRegExpWellKnownSymbolLogic('split', 2, function (SPLIT, nativeSplit, maybeCallNative) {
  var internalSplit;
  if (
    'abbc'.split(/(b)*/)[1] == 'c' ||
    'test'.split(/(?:)/, -1).length != 4 ||
    'ab'.split(/(?:ab)*/).length != 2 ||
    '.'.split(/(.?)(.?)/).length != 4 ||
    '.'.split(/()()/).length > 1 ||
    ''.split(/.?/).length
  ) {
    // based on es5-shim implementation, need to rework it
    internalSplit = function (separator, limit) {
      var string = String(requireObjectCoercible(this));
      var lim = limit === undefined ? MAX_UINT32 : limit >>> 0;
      if (lim === 0) return [];
      if (separator === undefined) return [string];
      // If `separator` is not a regex, use native split
      if (!isRegExp(separator)) {
        return nativeSplit.call(string, separator, lim);
      }
      var output = [];
      var flags = (separator.ignoreCase ? 'i' : '') +
                  (separator.multiline ? 'm' : '') +
                  (separator.unicode ? 'u' : '') +
                  (separator.sticky ? 'y' : '');
      var lastLastIndex = 0;
      // Make `global` and avoid `lastIndex` issues by working with a copy
      var separatorCopy = new RegExp(separator.source, flags + 'g');
      var match, lastIndex, lastLength;
      while (match = regexpExec.call(separatorCopy, string)) {
        lastIndex = separatorCopy.lastIndex;
        if (lastIndex > lastLastIndex) {
          output.push(string.slice(lastLastIndex, match.index));
          if (match.length > 1 && match.index < string.length) arrayPush.apply(output, match.slice(1));
          lastLength = match[0].length;
          lastLastIndex = lastIndex;
          if (output.length >= lim) break;
        }
        if (separatorCopy.lastIndex === match.index) separatorCopy.lastIndex++; // Avoid an infinite loop
      }
      if (lastLastIndex === string.length) {
        if (lastLength || !separatorCopy.test('')) output.push('');
      } else output.push(string.slice(lastLastIndex));
      return output.length > lim ? output.slice(0, lim) : output;
    };
  // Chakra, V8
  } else if ('0'.split(undefined, 0).length) {
    internalSplit = function (separator, limit) {
      return separator === undefined && limit === 0 ? [] : nativeSplit.call(this, separator, limit);
    };
  } else internalSplit = nativeSplit;

  return [
    // `String.prototype.split` method
    // https://tc39.github.io/ecma262/#sec-string.prototype.split
    function split(separator, limit) {
      var O = requireObjectCoercible(this);
      var splitter = separator == undefined ? undefined : separator[SPLIT];
      return splitter !== undefined
        ? splitter.call(separator, O, limit)
        : internalSplit.call(String(O), separator, limit);
    },
    // `RegExp.prototype[@@split]` method
    // https://tc39.github.io/ecma262/#sec-regexp.prototype-@@split
    //
    // NOTE: This cannot be properly polyfilled in engines that don't support
    // the 'y' flag.
    function (regexp, limit) {
      var res = maybeCallNative(internalSplit, regexp, this, limit, internalSplit !== nativeSplit);
      if (res.done) return res.value;

      var rx = anObject(regexp);
      var S = String(this);
      var C = speciesConstructor(rx, RegExp);

      var unicodeMatching = rx.unicode;
      var flags = (rx.ignoreCase ? 'i' : '') +
                  (rx.multiline ? 'm' : '') +
                  (rx.unicode ? 'u' : '') +
                  (SUPPORTS_Y ? 'y' : 'g');

      // ^(? + rx + ) is needed, in combination with some S slicing, to
      // simulate the 'y' flag.
      var splitter = new C(SUPPORTS_Y ? rx : '^(?:' + rx.source + ')', flags);
      var lim = limit === undefined ? MAX_UINT32 : limit >>> 0;
      if (lim === 0) return [];
      if (S.length === 0) return callRegExpExec(splitter, S) === null ? [S] : [];
      var p = 0;
      var q = 0;
      var A = [];
      while (q < S.length) {
        splitter.lastIndex = SUPPORTS_Y ? q : 0;
        var z = callRegExpExec(splitter, SUPPORTS_Y ? S : S.slice(q));
        var e;
        if (
          z === null ||
          (e = min(toLength(splitter.lastIndex + (SUPPORTS_Y ? 0 : q)), S.length)) === p
        ) {
          q = advanceStringIndex(S, q, unicodeMatching);
        } else {
          A.push(S.slice(p, q));
          if (A.length === lim) return A;
          for (var i = 1; i <= z.length - 1; i++) {
            A.push(z[i]);
            if (A.length === lim) return A;
          }
          q = p = e;
        }
      }
      A.push(S.slice(p));
      return A;
    }
  ];
}, !SUPPORTS_Y);


/***/ }),

/***/ "131a":
/***/ (function(module, exports, __webpack_require__) {

var $ = __webpack_require__("23e7");
var setPrototypeOf = __webpack_require__("d2bb");

// `Object.setPrototypeOf` method
// https://tc39.github.io/ecma262/#sec-object.setprototypeof
$({ target: 'Object', stat: true }, {
  setPrototypeOf: setPrototypeOf
});


/***/ }),

/***/ "14c3":
/***/ (function(module, exports, __webpack_require__) {

var classof = __webpack_require__("c6b6");
var regexpExec = __webpack_require__("9263");

// `RegExpExec` abstract operation
// https://tc39.github.io/ecma262/#sec-regexpexec
module.exports = function (R, S) {
  var exec = R.exec;
  if (typeof exec === 'function') {
    var result = exec.call(R, S);
    if (typeof result !== 'object') {
      throw TypeError('RegExp exec method returned something other than an Object or null');
    }
    return result;
  }

  if (classof(R) !== 'RegExp') {
    throw TypeError('RegExp#exec called on incompatible receiver');
  }

  return regexpExec.call(R, S);
};



/***/ }),

/***/ "151e":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_EditDecision_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("39dd");
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_EditDecision_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_EditDecision_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */
 /* unused harmony default export */ var _unused_webpack_default_export = (_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_EditDecision_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "156b":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ErrorWrapper_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("3f08");
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ErrorWrapper_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ErrorWrapper_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */
 /* unused harmony default export */ var _unused_webpack_default_export = (_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ErrorWrapper_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "159b":
/***/ (function(module, exports, __webpack_require__) {

var global = __webpack_require__("da84");
var DOMIterables = __webpack_require__("fdbc");
var forEach = __webpack_require__("17c2");
var createNonEnumerableProperty = __webpack_require__("9112");

for (var COLLECTION_NAME in DOMIterables) {
  var Collection = global[COLLECTION_NAME];
  var CollectionPrototype = Collection && Collection.prototype;
  // some Chrome versions have non-configurable methods on DOMTokenList
  if (CollectionPrototype && CollectionPrototype.forEach !== forEach) try {
    createNonEnumerableProperty(CollectionPrototype, 'forEach', forEach);
  } catch (error) {
    CollectionPrototype.forEach = forEach;
  }
}


/***/ }),

/***/ "15ae":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_IconMessageBox_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("be79");
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_IconMessageBox_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_IconMessageBox_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */
 /* unused harmony default export */ var _unused_webpack_default_export = (_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_IconMessageBox_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "17c2":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var $forEach = __webpack_require__("b727").forEach;
var arrayMethodIsStrict = __webpack_require__("a640");
var arrayMethodUsesToLength = __webpack_require__("ae40");

var STRICT_METHOD = arrayMethodIsStrict('forEach');
var USES_TO_LENGTH = arrayMethodUsesToLength('forEach');

// `Array.prototype.forEach` method implementation
// https://tc39.github.io/ecma262/#sec-array.prototype.foreach
module.exports = (!STRICT_METHOD || !USES_TO_LENGTH) ? function forEach(callbackfn /* , thisArg */) {
  return $forEach(this, callbackfn, arguments.length > 1 ? arguments[1] : undefined);
} : [].forEach;


/***/ }),

/***/ "19aa":
/***/ (function(module, exports) {

module.exports = function (it, Constructor, name) {
  if (!(it instanceof Constructor)) {
    throw TypeError('Incorrect ' + (name ? name + ' ' : '') + 'invocation');
  } return it;
};


/***/ }),

/***/ "1be4":
/***/ (function(module, exports, __webpack_require__) {

var getBuiltIn = __webpack_require__("d066");

module.exports = getBuiltIn('document', 'documentElement');


/***/ }),

/***/ "1c0b":
/***/ (function(module, exports) {

module.exports = function (it) {
  if (typeof it != 'function') {
    throw TypeError(String(it) + ' is not a function');
  } return it;
};


/***/ }),

/***/ "1c7e":
/***/ (function(module, exports, __webpack_require__) {

var wellKnownSymbol = __webpack_require__("b622");

var ITERATOR = wellKnownSymbol('iterator');
var SAFE_CLOSING = false;

try {
  var called = 0;
  var iteratorWithReturn = {
    next: function () {
      return { done: !!called++ };
    },
    'return': function () {
      SAFE_CLOSING = true;
    }
  };
  iteratorWithReturn[ITERATOR] = function () {
    return this;
  };
  // eslint-disable-next-line no-throw-literal
  Array.from(iteratorWithReturn, function () { throw 2; });
} catch (error) { /* empty */ }

module.exports = function (exec, SKIP_CLOSING) {
  if (!SKIP_CLOSING && !SAFE_CLOSING) return false;
  var ITERATION_SUPPORT = false;
  try {
    var object = {};
    object[ITERATOR] = function () {
      return {
        next: function () {
          return { done: ITERATION_SUPPORT = true };
        }
      };
    };
    exec(object);
  } catch (error) { /* empty */ }
  return ITERATION_SUPPORT;
};


/***/ }),

/***/ "1c7e9":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var supportsDescriptors = __webpack_require__("f367").supportsDescriptors;
var getPolyfill = __webpack_require__("57ec");
var gOPD = Object.getOwnPropertyDescriptor;
var defineProperty = Object.defineProperty;
var TypeErr = TypeError;
var getProto = Object.getPrototypeOf;
var regex = /a/;

module.exports = function shimFlags() {
	if (!supportsDescriptors || !getProto) {
		throw new TypeErr('RegExp.prototype.flags requires a true ES5 environment that supports property descriptors');
	}
	var polyfill = getPolyfill();
	var proto = getProto(regex);
	var descriptor = gOPD(proto, 'flags');
	if (!descriptor || descriptor.get !== polyfill) {
		defineProperty(proto, 'flags', {
			configurable: true,
			enumerable: false,
			get: polyfill
		});
	}
	return polyfill;
};


/***/ }),

/***/ "1cbf":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "1cdc":
/***/ (function(module, exports, __webpack_require__) {

var userAgent = __webpack_require__("342f");

module.exports = /(iphone|ipod|ipad).*applewebkit/i.test(userAgent);


/***/ }),

/***/ "1d68":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ErrorDeprecatedStatement_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("2566");
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ErrorDeprecatedStatement_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ErrorDeprecatedStatement_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */
 /* unused harmony default export */ var _unused_webpack_default_export = (_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ErrorDeprecatedStatement_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "1d80":
/***/ (function(module, exports) {

// `RequireObjectCoercible` abstract operation
// https://tc39.github.io/ecma262/#sec-requireobjectcoercible
module.exports = function (it) {
  if (it == undefined) throw TypeError("Can't call method on " + it);
  return it;
};


/***/ }),

/***/ "1d9d":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ErrorUnsupportedSnakType_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("8055");
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ErrorUnsupportedSnakType_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ErrorUnsupportedSnakType_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */
 /* unused harmony default export */ var _unused_webpack_default_export = (_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ErrorUnsupportedSnakType_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "1dde":
/***/ (function(module, exports, __webpack_require__) {

var fails = __webpack_require__("d039");
var wellKnownSymbol = __webpack_require__("b622");
var V8_VERSION = __webpack_require__("2d00");

var SPECIES = wellKnownSymbol('species');

module.exports = function (METHOD_NAME) {
  // We can't use this feature detection in V8 since it causes
  // deoptimization and serious performance degradation
  // https://github.com/zloirock/core-js/issues/677
  return V8_VERSION >= 51 || !fails(function () {
    var array = [];
    var constructor = array.constructor = {};
    constructor[SPECIES] = function () {
      return { foo: 1 };
    };
    return array[METHOD_NAME](Boolean).foo !== 1;
  });
};


/***/ }),

/***/ "2266":
/***/ (function(module, exports, __webpack_require__) {

var anObject = __webpack_require__("825a");
var isArrayIteratorMethod = __webpack_require__("e95a");
var toLength = __webpack_require__("50c4");
var bind = __webpack_require__("0366");
var getIteratorMethod = __webpack_require__("35a1");
var callWithSafeIterationClosing = __webpack_require__("9bdd");

var Result = function (stopped, result) {
  this.stopped = stopped;
  this.result = result;
};

var iterate = module.exports = function (iterable, fn, that, AS_ENTRIES, IS_ITERATOR) {
  var boundFunction = bind(fn, that, AS_ENTRIES ? 2 : 1);
  var iterator, iterFn, index, length, result, next, step;

  if (IS_ITERATOR) {
    iterator = iterable;
  } else {
    iterFn = getIteratorMethod(iterable);
    if (typeof iterFn != 'function') throw TypeError('Target is not iterable');
    // optimisation for array iterators
    if (isArrayIteratorMethod(iterFn)) {
      for (index = 0, length = toLength(iterable.length); length > index; index++) {
        result = AS_ENTRIES
          ? boundFunction(anObject(step = iterable[index])[0], step[1])
          : boundFunction(iterable[index]);
        if (result && result instanceof Result) return result;
      } return new Result(false);
    }
    iterator = iterFn.call(iterable);
  }

  next = iterator.next;
  while (!(step = next.call(iterator)).done) {
    result = callWithSafeIterationClosing(iterator, boundFunction, step.value, AS_ENTRIES);
    if (typeof result == 'object' && result && result instanceof Result) return result;
  } return new Result(false);
};

iterate.stop = function (result) {
  return new Result(true, result);
};


/***/ }),

/***/ "2366":
/***/ (function(module, exports) {

/**
 * Convert array of 16 byte values to UUID string format of the form:
 * XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX
 */
var byteToHex = [];
for (var i = 0; i < 256; ++i) {
  byteToHex[i] = (i + 0x100).toString(16).substr(1);
}

function bytesToUuid(buf, offset) {
  var i = offset || 0;
  var bth = byteToHex;
  // join used to fix memory issue caused by concatenation: https://bugs.chromium.org/p/v8/issues/detail?id=3175#c4
  return ([
    bth[buf[i++]], bth[buf[i++]],
    bth[buf[i++]], bth[buf[i++]], '-',
    bth[buf[i++]], bth[buf[i++]], '-',
    bth[buf[i++]], bth[buf[i++]], '-',
    bth[buf[i++]], bth[buf[i++]], '-',
    bth[buf[i++]], bth[buf[i++]],
    bth[buf[i++]], bth[buf[i++]],
    bth[buf[i++]], bth[buf[i++]]
  ]).join('');
}

module.exports = bytesToUuid;


/***/ }),

/***/ "23cb":
/***/ (function(module, exports, __webpack_require__) {

var toInteger = __webpack_require__("a691");

var max = Math.max;
var min = Math.min;

// Helper for a popular repeating case of the spec:
// Let integer be ? ToInteger(index).
// If integer < 0, let result be max((length + integer), 0); else let result be min(integer, length).
module.exports = function (index, length) {
  var integer = toInteger(index);
  return integer < 0 ? max(integer + length, 0) : min(integer, length);
};


/***/ }),

/***/ "23e7":
/***/ (function(module, exports, __webpack_require__) {

var global = __webpack_require__("da84");
var getOwnPropertyDescriptor = __webpack_require__("06cf").f;
var createNonEnumerableProperty = __webpack_require__("9112");
var redefine = __webpack_require__("6eeb");
var setGlobal = __webpack_require__("ce4e");
var copyConstructorProperties = __webpack_require__("e893");
var isForced = __webpack_require__("94ca");

/*
  options.target      - name of the target object
  options.global      - target is the global object
  options.stat        - export as static methods of target
  options.proto       - export as prototype methods of target
  options.real        - real prototype method for the `pure` version
  options.forced      - export even if the native feature is available
  options.bind        - bind methods to the target, required for the `pure` version
  options.wrap        - wrap constructors to preventing global pollution, required for the `pure` version
  options.unsafe      - use the simple assignment of property instead of delete + defineProperty
  options.sham        - add a flag to not completely full polyfills
  options.enumerable  - export as enumerable property
  options.noTargetGet - prevent calling a getter on target
*/
module.exports = function (options, source) {
  var TARGET = options.target;
  var GLOBAL = options.global;
  var STATIC = options.stat;
  var FORCED, target, key, targetProperty, sourceProperty, descriptor;
  if (GLOBAL) {
    target = global;
  } else if (STATIC) {
    target = global[TARGET] || setGlobal(TARGET, {});
  } else {
    target = (global[TARGET] || {}).prototype;
  }
  if (target) for (key in source) {
    sourceProperty = source[key];
    if (options.noTargetGet) {
      descriptor = getOwnPropertyDescriptor(target, key);
      targetProperty = descriptor && descriptor.value;
    } else targetProperty = target[key];
    FORCED = isForced(GLOBAL ? key : TARGET + (STATIC ? '.' : '#') + key, options.forced);
    // contained in target
    if (!FORCED && targetProperty !== undefined) {
      if (typeof sourceProperty === typeof targetProperty) continue;
      copyConstructorProperties(sourceProperty, targetProperty);
    }
    // add a flag to not completely full polyfills
    if (options.sham || (targetProperty && targetProperty.sham)) {
      createNonEnumerableProperty(sourceProperty, 'sham', true);
    }
    // extend global
    redefine(target, key, sourceProperty, options);
  }
};


/***/ }),

/***/ "241c":
/***/ (function(module, exports, __webpack_require__) {

var internalObjectKeys = __webpack_require__("ca84");
var enumBugKeys = __webpack_require__("7839");

var hiddenKeys = enumBugKeys.concat('length', 'prototype');

// `Object.getOwnPropertyNames` method
// https://tc39.github.io/ecma262/#sec-object.getownpropertynames
exports.f = Object.getOwnPropertyNames || function getOwnPropertyNames(O) {
  return internalObjectKeys(O, hiddenKeys);
};


/***/ }),

/***/ "2532":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var $ = __webpack_require__("23e7");
var notARegExp = __webpack_require__("5a34");
var requireObjectCoercible = __webpack_require__("1d80");
var correctIsRegExpLogic = __webpack_require__("ab13");

// `String.prototype.includes` method
// https://tc39.github.io/ecma262/#sec-string.prototype.includes
$({ target: 'String', proto: true, forced: !correctIsRegExpLogic('includes') }, {
  includes: function includes(searchString /* , position = 0 */) {
    return !!~String(requireObjectCoercible(this))
      .indexOf(notARegExp(searchString), arguments.length > 1 ? arguments[1] : undefined);
  }
});


/***/ }),

/***/ "2566":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "25f0":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var redefine = __webpack_require__("6eeb");
var anObject = __webpack_require__("825a");
var fails = __webpack_require__("d039");
var flags = __webpack_require__("ad6d");

var TO_STRING = 'toString';
var RegExpPrototype = RegExp.prototype;
var nativeToString = RegExpPrototype[TO_STRING];

var NOT_GENERIC = fails(function () { return nativeToString.call({ source: 'a', flags: 'b' }) != '/a/b'; });
// FF44- RegExp#toString has a wrong name
var INCORRECT_NAME = nativeToString.name != TO_STRING;

// `RegExp.prototype.toString` method
// https://tc39.github.io/ecma262/#sec-regexp.prototype.tostring
if (NOT_GENERIC || INCORRECT_NAME) {
  redefine(RegExp.prototype, TO_STRING, function toString() {
    var R = anObject(this);
    var p = String(R.source);
    var rf = R.flags;
    var f = String(rf === undefined && R instanceof RegExp && !('flags' in RegExpPrototype) ? flags.call(R) : rf);
    return '/' + p + '/' + f;
  }, { unsafe: true });
}


/***/ }),

/***/ "2626":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var getBuiltIn = __webpack_require__("d066");
var definePropertyModule = __webpack_require__("9bf2");
var wellKnownSymbol = __webpack_require__("b622");
var DESCRIPTORS = __webpack_require__("83ab");

var SPECIES = wellKnownSymbol('species');

module.exports = function (CONSTRUCTOR_NAME) {
  var Constructor = getBuiltIn(CONSTRUCTOR_NAME);
  var defineProperty = definePropertyModule.f;

  if (DESCRIPTORS && Constructor && !Constructor[SPECIES]) {
    defineProperty(Constructor, SPECIES, {
      configurable: true,
      get: function () { return this; }
    });
  }
};


/***/ }),

/***/ "2ca0":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var $ = __webpack_require__("23e7");
var getOwnPropertyDescriptor = __webpack_require__("06cf").f;
var toLength = __webpack_require__("50c4");
var notARegExp = __webpack_require__("5a34");
var requireObjectCoercible = __webpack_require__("1d80");
var correctIsRegExpLogic = __webpack_require__("ab13");
var IS_PURE = __webpack_require__("c430");

var nativeStartsWith = ''.startsWith;
var min = Math.min;

var CORRECT_IS_REGEXP_LOGIC = correctIsRegExpLogic('startsWith');
// https://github.com/zloirock/core-js/pull/702
var MDN_POLYFILL_BUG = !IS_PURE && !CORRECT_IS_REGEXP_LOGIC && !!function () {
  var descriptor = getOwnPropertyDescriptor(String.prototype, 'startsWith');
  return descriptor && !descriptor.writable;
}();

// `String.prototype.startsWith` method
// https://tc39.github.io/ecma262/#sec-string.prototype.startswith
$({ target: 'String', proto: true, forced: !MDN_POLYFILL_BUG && !CORRECT_IS_REGEXP_LOGIC }, {
  startsWith: function startsWith(searchString /* , position = 0 */) {
    var that = String(requireObjectCoercible(this));
    notARegExp(searchString);
    var index = toLength(min(arguments.length > 1 ? arguments[1] : undefined, that.length));
    var search = String(searchString);
    return nativeStartsWith
      ? nativeStartsWith.call(that, search, index)
      : that.slice(index, index + search.length) === search;
  }
});


/***/ }),

/***/ "2cf4":
/***/ (function(module, exports, __webpack_require__) {

var global = __webpack_require__("da84");
var fails = __webpack_require__("d039");
var classof = __webpack_require__("c6b6");
var bind = __webpack_require__("0366");
var html = __webpack_require__("1be4");
var createElement = __webpack_require__("cc12");
var IS_IOS = __webpack_require__("1cdc");

var location = global.location;
var set = global.setImmediate;
var clear = global.clearImmediate;
var process = global.process;
var MessageChannel = global.MessageChannel;
var Dispatch = global.Dispatch;
var counter = 0;
var queue = {};
var ONREADYSTATECHANGE = 'onreadystatechange';
var defer, channel, port;

var run = function (id) {
  // eslint-disable-next-line no-prototype-builtins
  if (queue.hasOwnProperty(id)) {
    var fn = queue[id];
    delete queue[id];
    fn();
  }
};

var runner = function (id) {
  return function () {
    run(id);
  };
};

var listener = function (event) {
  run(event.data);
};

var post = function (id) {
  // old engines have not location.origin
  global.postMessage(id + '', location.protocol + '//' + location.host);
};

// Node.js 0.9+ & IE10+ has setImmediate, otherwise:
if (!set || !clear) {
  set = function setImmediate(fn) {
    var args = [];
    var i = 1;
    while (arguments.length > i) args.push(arguments[i++]);
    queue[++counter] = function () {
      // eslint-disable-next-line no-new-func
      (typeof fn == 'function' ? fn : Function(fn)).apply(undefined, args);
    };
    defer(counter);
    return counter;
  };
  clear = function clearImmediate(id) {
    delete queue[id];
  };
  // Node.js 0.8-
  if (classof(process) == 'process') {
    defer = function (id) {
      process.nextTick(runner(id));
    };
  // Sphere (JS game engine) Dispatch API
  } else if (Dispatch && Dispatch.now) {
    defer = function (id) {
      Dispatch.now(runner(id));
    };
  // Browsers with MessageChannel, includes WebWorkers
  // except iOS - https://github.com/zloirock/core-js/issues/624
  } else if (MessageChannel && !IS_IOS) {
    channel = new MessageChannel();
    port = channel.port2;
    channel.port1.onmessage = listener;
    defer = bind(port.postMessage, port, 1);
  // Browsers with postMessage, skip WebWorkers
  // IE8 has postMessage, but it's sync & typeof its postMessage is 'object'
  } else if (global.addEventListener && typeof postMessage == 'function' && !global.importScripts && !fails(post)) {
    defer = post;
    global.addEventListener('message', listener, false);
  // IE8-
  } else if (ONREADYSTATECHANGE in createElement('script')) {
    defer = function (id) {
      html.appendChild(createElement('script'))[ONREADYSTATECHANGE] = function () {
        html.removeChild(this);
        run(id);
      };
    };
  // Rest old browsers
  } else {
    defer = function (id) {
      setTimeout(runner(id), 0);
    };
  }
}

module.exports = {
  set: set,
  clear: clear
};


/***/ }),

/***/ "2d00":
/***/ (function(module, exports, __webpack_require__) {

var global = __webpack_require__("da84");
var userAgent = __webpack_require__("342f");

var process = global.process;
var versions = process && process.versions;
var v8 = versions && versions.v8;
var match, version;

if (v8) {
  match = v8.split('.');
  version = match[0] + match[1];
} else if (userAgent) {
  match = userAgent.match(/Edge\/(\d+)/);
  if (!match || match[1] >= 74) {
    match = userAgent.match(/Chrome\/(\d+)/);
    if (match) version = match[1];
  }
}

module.exports = version && +version;


/***/ }),

/***/ "2d78":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


/* globals
	Atomics,
	SharedArrayBuffer,
*/

var undefined;

var $TypeError = TypeError;

var $gOPD = Object.getOwnPropertyDescriptor;
if ($gOPD) {
	try {
		$gOPD({}, '');
	} catch (e) {
		$gOPD = null; // this is IE 8, which has a broken gOPD
	}
}

var throwTypeError = function () { throw new $TypeError(); };
var ThrowTypeError = $gOPD
	? (function () {
		try {
			// eslint-disable-next-line no-unused-expressions, no-caller, no-restricted-properties
			arguments.callee; // IE 8 does not throw here
			return throwTypeError;
		} catch (calleeThrows) {
			try {
				// IE 8 throws on Object.getOwnPropertyDescriptor(arguments, '')
				return $gOPD(arguments, 'callee').get;
			} catch (gOPDthrows) {
				return throwTypeError;
			}
		}
	}())
	: throwTypeError;

var hasSymbols = __webpack_require__("32b0")();

var getProto = Object.getPrototypeOf || function (x) { return x.__proto__; }; // eslint-disable-line no-proto

var generator; // = function * () {};
var generatorFunction = generator ? getProto(generator) : undefined;
var asyncFn; // async function() {};
var asyncFunction = asyncFn ? asyncFn.constructor : undefined;
var asyncGen; // async function * () {};
var asyncGenFunction = asyncGen ? getProto(asyncGen) : undefined;
var asyncGenIterator = asyncGen ? asyncGen() : undefined;

var TypedArray = typeof Uint8Array === 'undefined' ? undefined : getProto(Uint8Array);

var INTRINSICS = {
	'%Array%': Array,
	'%ArrayBuffer%': typeof ArrayBuffer === 'undefined' ? undefined : ArrayBuffer,
	'%ArrayBufferPrototype%': typeof ArrayBuffer === 'undefined' ? undefined : ArrayBuffer.prototype,
	'%ArrayIteratorPrototype%': hasSymbols ? getProto([][Symbol.iterator]()) : undefined,
	'%ArrayPrototype%': Array.prototype,
	'%ArrayProto_entries%': Array.prototype.entries,
	'%ArrayProto_forEach%': Array.prototype.forEach,
	'%ArrayProto_keys%': Array.prototype.keys,
	'%ArrayProto_values%': Array.prototype.values,
	'%AsyncFromSyncIteratorPrototype%': undefined,
	'%AsyncFunction%': asyncFunction,
	'%AsyncFunctionPrototype%': asyncFunction ? asyncFunction.prototype : undefined,
	'%AsyncGenerator%': asyncGen ? getProto(asyncGenIterator) : undefined,
	'%AsyncGeneratorFunction%': asyncGenFunction,
	'%AsyncGeneratorPrototype%': asyncGenFunction ? asyncGenFunction.prototype : undefined,
	'%AsyncIteratorPrototype%': asyncGenIterator && hasSymbols && Symbol.asyncIterator ? asyncGenIterator[Symbol.asyncIterator]() : undefined,
	'%Atomics%': typeof Atomics === 'undefined' ? undefined : Atomics,
	'%Boolean%': Boolean,
	'%BooleanPrototype%': Boolean.prototype,
	'%DataView%': typeof DataView === 'undefined' ? undefined : DataView,
	'%DataViewPrototype%': typeof DataView === 'undefined' ? undefined : DataView.prototype,
	'%Date%': Date,
	'%DatePrototype%': Date.prototype,
	'%decodeURI%': decodeURI,
	'%decodeURIComponent%': decodeURIComponent,
	'%encodeURI%': encodeURI,
	'%encodeURIComponent%': encodeURIComponent,
	'%Error%': Error,
	'%ErrorPrototype%': Error.prototype,
	'%eval%': eval, // eslint-disable-line no-eval
	'%EvalError%': EvalError,
	'%EvalErrorPrototype%': EvalError.prototype,
	'%Float32Array%': typeof Float32Array === 'undefined' ? undefined : Float32Array,
	'%Float32ArrayPrototype%': typeof Float32Array === 'undefined' ? undefined : Float32Array.prototype,
	'%Float64Array%': typeof Float64Array === 'undefined' ? undefined : Float64Array,
	'%Float64ArrayPrototype%': typeof Float64Array === 'undefined' ? undefined : Float64Array.prototype,
	'%Function%': Function,
	'%FunctionPrototype%': Function.prototype,
	'%Generator%': generator ? getProto(generator()) : undefined,
	'%GeneratorFunction%': generatorFunction,
	'%GeneratorPrototype%': generatorFunction ? generatorFunction.prototype : undefined,
	'%Int8Array%': typeof Int8Array === 'undefined' ? undefined : Int8Array,
	'%Int8ArrayPrototype%': typeof Int8Array === 'undefined' ? undefined : Int8Array.prototype,
	'%Int16Array%': typeof Int16Array === 'undefined' ? undefined : Int16Array,
	'%Int16ArrayPrototype%': typeof Int16Array === 'undefined' ? undefined : Int8Array.prototype,
	'%Int32Array%': typeof Int32Array === 'undefined' ? undefined : Int32Array,
	'%Int32ArrayPrototype%': typeof Int32Array === 'undefined' ? undefined : Int32Array.prototype,
	'%isFinite%': isFinite,
	'%isNaN%': isNaN,
	'%IteratorPrototype%': hasSymbols ? getProto(getProto([][Symbol.iterator]())) : undefined,
	'%JSON%': typeof JSON === 'object' ? JSON : undefined,
	'%JSONParse%': typeof JSON === 'object' ? JSON.parse : undefined,
	'%Map%': typeof Map === 'undefined' ? undefined : Map,
	'%MapIteratorPrototype%': typeof Map === 'undefined' || !hasSymbols ? undefined : getProto(new Map()[Symbol.iterator]()),
	'%MapPrototype%': typeof Map === 'undefined' ? undefined : Map.prototype,
	'%Math%': Math,
	'%Number%': Number,
	'%NumberPrototype%': Number.prototype,
	'%Object%': Object,
	'%ObjectPrototype%': Object.prototype,
	'%ObjProto_toString%': Object.prototype.toString,
	'%ObjProto_valueOf%': Object.prototype.valueOf,
	'%parseFloat%': parseFloat,
	'%parseInt%': parseInt,
	'%Promise%': typeof Promise === 'undefined' ? undefined : Promise,
	'%PromisePrototype%': typeof Promise === 'undefined' ? undefined : Promise.prototype,
	'%PromiseProto_then%': typeof Promise === 'undefined' ? undefined : Promise.prototype.then,
	'%Promise_all%': typeof Promise === 'undefined' ? undefined : Promise.all,
	'%Promise_reject%': typeof Promise === 'undefined' ? undefined : Promise.reject,
	'%Promise_resolve%': typeof Promise === 'undefined' ? undefined : Promise.resolve,
	'%Proxy%': typeof Proxy === 'undefined' ? undefined : Proxy,
	'%RangeError%': RangeError,
	'%RangeErrorPrototype%': RangeError.prototype,
	'%ReferenceError%': ReferenceError,
	'%ReferenceErrorPrototype%': ReferenceError.prototype,
	'%Reflect%': typeof Reflect === 'undefined' ? undefined : Reflect,
	'%RegExp%': RegExp,
	'%RegExpPrototype%': RegExp.prototype,
	'%Set%': typeof Set === 'undefined' ? undefined : Set,
	'%SetIteratorPrototype%': typeof Set === 'undefined' || !hasSymbols ? undefined : getProto(new Set()[Symbol.iterator]()),
	'%SetPrototype%': typeof Set === 'undefined' ? undefined : Set.prototype,
	'%SharedArrayBuffer%': typeof SharedArrayBuffer === 'undefined' ? undefined : SharedArrayBuffer,
	'%SharedArrayBufferPrototype%': typeof SharedArrayBuffer === 'undefined' ? undefined : SharedArrayBuffer.prototype,
	'%String%': String,
	'%StringIteratorPrototype%': hasSymbols ? getProto(''[Symbol.iterator]()) : undefined,
	'%StringPrototype%': String.prototype,
	'%Symbol%': hasSymbols ? Symbol : undefined,
	'%SymbolPrototype%': hasSymbols ? Symbol.prototype : undefined,
	'%SyntaxError%': SyntaxError,
	'%SyntaxErrorPrototype%': SyntaxError.prototype,
	'%ThrowTypeError%': ThrowTypeError,
	'%TypedArray%': TypedArray,
	'%TypedArrayPrototype%': TypedArray ? TypedArray.prototype : undefined,
	'%TypeError%': $TypeError,
	'%TypeErrorPrototype%': $TypeError.prototype,
	'%Uint8Array%': typeof Uint8Array === 'undefined' ? undefined : Uint8Array,
	'%Uint8ArrayPrototype%': typeof Uint8Array === 'undefined' ? undefined : Uint8Array.prototype,
	'%Uint8ClampedArray%': typeof Uint8ClampedArray === 'undefined' ? undefined : Uint8ClampedArray,
	'%Uint8ClampedArrayPrototype%': typeof Uint8ClampedArray === 'undefined' ? undefined : Uint8ClampedArray.prototype,
	'%Uint16Array%': typeof Uint16Array === 'undefined' ? undefined : Uint16Array,
	'%Uint16ArrayPrototype%': typeof Uint16Array === 'undefined' ? undefined : Uint16Array.prototype,
	'%Uint32Array%': typeof Uint32Array === 'undefined' ? undefined : Uint32Array,
	'%Uint32ArrayPrototype%': typeof Uint32Array === 'undefined' ? undefined : Uint32Array.prototype,
	'%URIError%': URIError,
	'%URIErrorPrototype%': URIError.prototype,
	'%WeakMap%': typeof WeakMap === 'undefined' ? undefined : WeakMap,
	'%WeakMapPrototype%': typeof WeakMap === 'undefined' ? undefined : WeakMap.prototype,
	'%WeakSet%': typeof WeakSet === 'undefined' ? undefined : WeakSet,
	'%WeakSetPrototype%': typeof WeakSet === 'undefined' ? undefined : WeakSet.prototype
};

var bind = __webpack_require__("0f7c");
var $replace = bind.call(Function.call, String.prototype.replace);

/* adapted from https://github.com/lodash/lodash/blob/4.17.15/dist/lodash.js#L6735-L6744 */
var rePropName = /[^%.[\]]+|\[(?:(-?\d+(?:\.\d+)?)|(["'])((?:(?!\2)[^\\]|\\.)*?)\2)\]|(?=(?:\.|\[\])(?:\.|\[\]|%$))/g;
var reEscapeChar = /\\(\\)?/g; /** Used to match backslashes in property paths. */
var stringToPath = function stringToPath(string) {
	var result = [];
	$replace(string, rePropName, function (match, number, quote, subString) {
		result[result.length] = quote ? $replace(subString, reEscapeChar, '$1') : (number || match);
	});
	return result;
};
/* end adaptation */

var getBaseIntrinsic = function getBaseIntrinsic(name, allowMissing) {
	if (!(name in INTRINSICS)) {
		throw new SyntaxError('intrinsic ' + name + ' does not exist!');
	}

	// istanbul ignore if // hopefully this is impossible to test :-)
	if (typeof INTRINSICS[name] === 'undefined' && !allowMissing) {
		throw new $TypeError('intrinsic ' + name + ' exists, but is not available. Please file an issue!');
	}

	return INTRINSICS[name];
};

module.exports = function GetIntrinsic(name, allowMissing) {
	if (typeof name !== 'string' || name.length === 0) {
		throw new TypeError('intrinsic name must be a non-empty string');
	}
	if (arguments.length > 1 && typeof allowMissing !== 'boolean') {
		throw new TypeError('"allowMissing" argument must be a boolean');
	}

	var parts = stringToPath(name);

	var value = getBaseIntrinsic('%' + (parts.length > 0 ? parts[0] : '') + '%', allowMissing);
	for (var i = 1; i < parts.length; i += 1) {
		if (value != null) {
			if ($gOPD && (i + 1) >= parts.length) {
				var desc = $gOPD(value, parts[i]);
				if (!allowMissing && !(parts[i] in value)) {
					throw new $TypeError('base intrinsic for ' + name + ' exists, but the property is not available.');
				}
				value = desc ? (desc.get || desc.value) : value[parts[i]];
			} else {
				value = value[parts[i]];
			}
		}
	}
	return value;
};


/***/ }),

/***/ "2e53":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_EventEmittingButton_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("1cbf");
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_EventEmittingButton_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_EventEmittingButton_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */
 /* unused harmony default export */ var _unused_webpack_default_export = (_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_EventEmittingButton_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "2ee0":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "2f62":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* WEBPACK VAR INJECTION */(function(global) {/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return Store; });
/* unused harmony export install */
/* unused harmony export mapState */
/* unused harmony export mapMutations */
/* unused harmony export mapGetters */
/* unused harmony export mapActions */
/* unused harmony export createNamespacedHelpers */
/**
 * vuex v3.1.2
 * (c) 2019 Evan You
 * @license MIT
 */
function applyMixin (Vue) {
  var version = Number(Vue.version.split('.')[0]);

  if (version >= 2) {
    Vue.mixin({ beforeCreate: vuexInit });
  } else {
    // override init and inject vuex init procedure
    // for 1.x backwards compatibility.
    var _init = Vue.prototype._init;
    Vue.prototype._init = function (options) {
      if ( options === void 0 ) options = {};

      options.init = options.init
        ? [vuexInit].concat(options.init)
        : vuexInit;
      _init.call(this, options);
    };
  }

  /**
   * Vuex init hook, injected into each instances init hooks list.
   */

  function vuexInit () {
    var options = this.$options;
    // store injection
    if (options.store) {
      this.$store = typeof options.store === 'function'
        ? options.store()
        : options.store;
    } else if (options.parent && options.parent.$store) {
      this.$store = options.parent.$store;
    }
  }
}

var target = typeof window !== 'undefined'
  ? window
  : typeof global !== 'undefined'
    ? global
    : {};
var devtoolHook = target.__VUE_DEVTOOLS_GLOBAL_HOOK__;

function devtoolPlugin (store) {
  if (!devtoolHook) { return }

  store._devtoolHook = devtoolHook;

  devtoolHook.emit('vuex:init', store);

  devtoolHook.on('vuex:travel-to-state', function (targetState) {
    store.replaceState(targetState);
  });

  store.subscribe(function (mutation, state) {
    devtoolHook.emit('vuex:mutation', mutation, state);
  });
}

/**
 * Get the first item that pass the test
 * by second argument function
 *
 * @param {Array} list
 * @param {Function} f
 * @return {*}
 */

/**
 * forEach for object
 */
function forEachValue (obj, fn) {
  Object.keys(obj).forEach(function (key) { return fn(obj[key], key); });
}

function isObject (obj) {
  return obj !== null && typeof obj === 'object'
}

function isPromise (val) {
  return val && typeof val.then === 'function'
}

function assert (condition, msg) {
  if (!condition) { throw new Error(("[vuex] " + msg)) }
}

function partial (fn, arg) {
  return function () {
    return fn(arg)
  }
}

// Base data struct for store's module, package with some attribute and method
var Module = function Module (rawModule, runtime) {
  this.runtime = runtime;
  // Store some children item
  this._children = Object.create(null);
  // Store the origin module object which passed by programmer
  this._rawModule = rawModule;
  var rawState = rawModule.state;

  // Store the origin module's state
  this.state = (typeof rawState === 'function' ? rawState() : rawState) || {};
};

var prototypeAccessors = { namespaced: { configurable: true } };

prototypeAccessors.namespaced.get = function () {
  return !!this._rawModule.namespaced
};

Module.prototype.addChild = function addChild (key, module) {
  this._children[key] = module;
};

Module.prototype.removeChild = function removeChild (key) {
  delete this._children[key];
};

Module.prototype.getChild = function getChild (key) {
  return this._children[key]
};

Module.prototype.update = function update (rawModule) {
  this._rawModule.namespaced = rawModule.namespaced;
  if (rawModule.actions) {
    this._rawModule.actions = rawModule.actions;
  }
  if (rawModule.mutations) {
    this._rawModule.mutations = rawModule.mutations;
  }
  if (rawModule.getters) {
    this._rawModule.getters = rawModule.getters;
  }
};

Module.prototype.forEachChild = function forEachChild (fn) {
  forEachValue(this._children, fn);
};

Module.prototype.forEachGetter = function forEachGetter (fn) {
  if (this._rawModule.getters) {
    forEachValue(this._rawModule.getters, fn);
  }
};

Module.prototype.forEachAction = function forEachAction (fn) {
  if (this._rawModule.actions) {
    forEachValue(this._rawModule.actions, fn);
  }
};

Module.prototype.forEachMutation = function forEachMutation (fn) {
  if (this._rawModule.mutations) {
    forEachValue(this._rawModule.mutations, fn);
  }
};

Object.defineProperties( Module.prototype, prototypeAccessors );

var ModuleCollection = function ModuleCollection (rawRootModule) {
  // register root module (Vuex.Store options)
  this.register([], rawRootModule, false);
};

ModuleCollection.prototype.get = function get (path) {
  return path.reduce(function (module, key) {
    return module.getChild(key)
  }, this.root)
};

ModuleCollection.prototype.getNamespace = function getNamespace (path) {
  var module = this.root;
  return path.reduce(function (namespace, key) {
    module = module.getChild(key);
    return namespace + (module.namespaced ? key + '/' : '')
  }, '')
};

ModuleCollection.prototype.update = function update$1 (rawRootModule) {
  update([], this.root, rawRootModule);
};

ModuleCollection.prototype.register = function register (path, rawModule, runtime) {
    var this$1 = this;
    if ( runtime === void 0 ) runtime = true;

  if (false) {}

  var newModule = new Module(rawModule, runtime);
  if (path.length === 0) {
    this.root = newModule;
  } else {
    var parent = this.get(path.slice(0, -1));
    parent.addChild(path[path.length - 1], newModule);
  }

  // register nested modules
  if (rawModule.modules) {
    forEachValue(rawModule.modules, function (rawChildModule, key) {
      this$1.register(path.concat(key), rawChildModule, runtime);
    });
  }
};

ModuleCollection.prototype.unregister = function unregister (path) {
  var parent = this.get(path.slice(0, -1));
  var key = path[path.length - 1];
  if (!parent.getChild(key).runtime) { return }

  parent.removeChild(key);
};

function update (path, targetModule, newModule) {
  if (false) {}

  // update target module
  targetModule.update(newModule);

  // update nested modules
  if (newModule.modules) {
    for (var key in newModule.modules) {
      if (!targetModule.getChild(key)) {
        if (false) {}
        return
      }
      update(
        path.concat(key),
        targetModule.getChild(key),
        newModule.modules[key]
      );
    }
  }
}

var functionAssert = {
  assert: function (value) { return typeof value === 'function'; },
  expected: 'function'
};

var objectAssert = {
  assert: function (value) { return typeof value === 'function' ||
    (typeof value === 'object' && typeof value.handler === 'function'); },
  expected: 'function or object with "handler" function'
};

var assertTypes = {
  getters: functionAssert,
  mutations: functionAssert,
  actions: objectAssert
};

function assertRawModule (path, rawModule) {
  Object.keys(assertTypes).forEach(function (key) {
    if (!rawModule[key]) { return }

    var assertOptions = assertTypes[key];

    forEachValue(rawModule[key], function (value, type) {
      assert(
        assertOptions.assert(value),
        makeAssertionMessage(path, key, type, value, assertOptions.expected)
      );
    });
  });
}

function makeAssertionMessage (path, key, type, value, expected) {
  var buf = key + " should be " + expected + " but \"" + key + "." + type + "\"";
  if (path.length > 0) {
    buf += " in module \"" + (path.join('.')) + "\"";
  }
  buf += " is " + (JSON.stringify(value)) + ".";
  return buf
}

var Vue; // bind on install

var Store = function Store (options) {
  var this$1 = this;
  if ( options === void 0 ) options = {};

  // Auto install if it is not done yet and `window` has `Vue`.
  // To allow users to avoid auto-installation in some cases,
  // this code should be placed here. See #731
  if (!Vue && typeof window !== 'undefined' && window.Vue) {
    install(window.Vue);
  }

  if (false) {}

  var plugins = options.plugins; if ( plugins === void 0 ) plugins = [];
  var strict = options.strict; if ( strict === void 0 ) strict = false;

  // store internal state
  this._committing = false;
  this._actions = Object.create(null);
  this._actionSubscribers = [];
  this._mutations = Object.create(null);
  this._wrappedGetters = Object.create(null);
  this._modules = new ModuleCollection(options);
  this._modulesNamespaceMap = Object.create(null);
  this._subscribers = [];
  this._watcherVM = new Vue();
  this._makeLocalGettersCache = Object.create(null);

  // bind commit and dispatch to self
  var store = this;
  var ref = this;
  var dispatch = ref.dispatch;
  var commit = ref.commit;
  this.dispatch = function boundDispatch (type, payload) {
    return dispatch.call(store, type, payload)
  };
  this.commit = function boundCommit (type, payload, options) {
    return commit.call(store, type, payload, options)
  };

  // strict mode
  this.strict = strict;

  var state = this._modules.root.state;

  // init root module.
  // this also recursively registers all sub-modules
  // and collects all module getters inside this._wrappedGetters
  installModule(this, state, [], this._modules.root);

  // initialize the store vm, which is responsible for the reactivity
  // (also registers _wrappedGetters as computed properties)
  resetStoreVM(this, state);

  // apply plugins
  plugins.forEach(function (plugin) { return plugin(this$1); });

  var useDevtools = options.devtools !== undefined ? options.devtools : Vue.config.devtools;
  if (useDevtools) {
    devtoolPlugin(this);
  }
};

var prototypeAccessors$1 = { state: { configurable: true } };

prototypeAccessors$1.state.get = function () {
  return this._vm._data.$$state
};

prototypeAccessors$1.state.set = function (v) {
  if (false) {}
};

Store.prototype.commit = function commit (_type, _payload, _options) {
    var this$1 = this;

  // check object-style commit
  var ref = unifyObjectStyle(_type, _payload, _options);
    var type = ref.type;
    var payload = ref.payload;
    var options = ref.options;

  var mutation = { type: type, payload: payload };
  var entry = this._mutations[type];
  if (!entry) {
    if (false) {}
    return
  }
  this._withCommit(function () {
    entry.forEach(function commitIterator (handler) {
      handler(payload);
    });
  });
  this._subscribers.forEach(function (sub) { return sub(mutation, this$1.state); });

  if (
    false
  ) {}
};

Store.prototype.dispatch = function dispatch (_type, _payload) {
    var this$1 = this;

  // check object-style dispatch
  var ref = unifyObjectStyle(_type, _payload);
    var type = ref.type;
    var payload = ref.payload;

  var action = { type: type, payload: payload };
  var entry = this._actions[type];
  if (!entry) {
    if (false) {}
    return
  }

  try {
    this._actionSubscribers
      .filter(function (sub) { return sub.before; })
      .forEach(function (sub) { return sub.before(action, this$1.state); });
  } catch (e) {
    if (false) {}
  }

  var result = entry.length > 1
    ? Promise.all(entry.map(function (handler) { return handler(payload); }))
    : entry[0](payload);

  return result.then(function (res) {
    try {
      this$1._actionSubscribers
        .filter(function (sub) { return sub.after; })
        .forEach(function (sub) { return sub.after(action, this$1.state); });
    } catch (e) {
      if (false) {}
    }
    return res
  })
};

Store.prototype.subscribe = function subscribe (fn) {
  return genericSubscribe(fn, this._subscribers)
};

Store.prototype.subscribeAction = function subscribeAction (fn) {
  var subs = typeof fn === 'function' ? { before: fn } : fn;
  return genericSubscribe(subs, this._actionSubscribers)
};

Store.prototype.watch = function watch (getter, cb, options) {
    var this$1 = this;

  if (false) {}
  return this._watcherVM.$watch(function () { return getter(this$1.state, this$1.getters); }, cb, options)
};

Store.prototype.replaceState = function replaceState (state) {
    var this$1 = this;

  this._withCommit(function () {
    this$1._vm._data.$$state = state;
  });
};

Store.prototype.registerModule = function registerModule (path, rawModule, options) {
    if ( options === void 0 ) options = {};

  if (typeof path === 'string') { path = [path]; }

  if (false) {}

  this._modules.register(path, rawModule);
  installModule(this, this.state, path, this._modules.get(path), options.preserveState);
  // reset store to update getters...
  resetStoreVM(this, this.state);
};

Store.prototype.unregisterModule = function unregisterModule (path) {
    var this$1 = this;

  if (typeof path === 'string') { path = [path]; }

  if (false) {}

  this._modules.unregister(path);
  this._withCommit(function () {
    var parentState = getNestedState(this$1.state, path.slice(0, -1));
    Vue.delete(parentState, path[path.length - 1]);
  });
  resetStore(this);
};

Store.prototype.hotUpdate = function hotUpdate (newOptions) {
  this._modules.update(newOptions);
  resetStore(this, true);
};

Store.prototype._withCommit = function _withCommit (fn) {
  var committing = this._committing;
  this._committing = true;
  fn();
  this._committing = committing;
};

Object.defineProperties( Store.prototype, prototypeAccessors$1 );

function genericSubscribe (fn, subs) {
  if (subs.indexOf(fn) < 0) {
    subs.push(fn);
  }
  return function () {
    var i = subs.indexOf(fn);
    if (i > -1) {
      subs.splice(i, 1);
    }
  }
}

function resetStore (store, hot) {
  store._actions = Object.create(null);
  store._mutations = Object.create(null);
  store._wrappedGetters = Object.create(null);
  store._modulesNamespaceMap = Object.create(null);
  var state = store.state;
  // init all modules
  installModule(store, state, [], store._modules.root, true);
  // reset vm
  resetStoreVM(store, state, hot);
}

function resetStoreVM (store, state, hot) {
  var oldVm = store._vm;

  // bind store public getters
  store.getters = {};
  // reset local getters cache
  store._makeLocalGettersCache = Object.create(null);
  var wrappedGetters = store._wrappedGetters;
  var computed = {};
  forEachValue(wrappedGetters, function (fn, key) {
    // use computed to leverage its lazy-caching mechanism
    // direct inline function use will lead to closure preserving oldVm.
    // using partial to return function with only arguments preserved in closure environment.
    computed[key] = partial(fn, store);
    Object.defineProperty(store.getters, key, {
      get: function () { return store._vm[key]; },
      enumerable: true // for local getters
    });
  });

  // use a Vue instance to store the state tree
  // suppress warnings just in case the user has added
  // some funky global mixins
  var silent = Vue.config.silent;
  Vue.config.silent = true;
  store._vm = new Vue({
    data: {
      $$state: state
    },
    computed: computed
  });
  Vue.config.silent = silent;

  // enable strict mode for new vm
  if (store.strict) {
    enableStrictMode(store);
  }

  if (oldVm) {
    if (hot) {
      // dispatch changes in all subscribed watchers
      // to force getter re-evaluation for hot reloading.
      store._withCommit(function () {
        oldVm._data.$$state = null;
      });
    }
    Vue.nextTick(function () { return oldVm.$destroy(); });
  }
}

function installModule (store, rootState, path, module, hot) {
  var isRoot = !path.length;
  var namespace = store._modules.getNamespace(path);

  // register in namespace map
  if (module.namespaced) {
    if (store._modulesNamespaceMap[namespace] && "production" !== 'production') {
      console.error(("[vuex] duplicate namespace " + namespace + " for the namespaced module " + (path.join('/'))));
    }
    store._modulesNamespaceMap[namespace] = module;
  }

  // set state
  if (!isRoot && !hot) {
    var parentState = getNestedState(rootState, path.slice(0, -1));
    var moduleName = path[path.length - 1];
    store._withCommit(function () {
      if (false) {}
      Vue.set(parentState, moduleName, module.state);
    });
  }

  var local = module.context = makeLocalContext(store, namespace, path);

  module.forEachMutation(function (mutation, key) {
    var namespacedType = namespace + key;
    registerMutation(store, namespacedType, mutation, local);
  });

  module.forEachAction(function (action, key) {
    var type = action.root ? key : namespace + key;
    var handler = action.handler || action;
    registerAction(store, type, handler, local);
  });

  module.forEachGetter(function (getter, key) {
    var namespacedType = namespace + key;
    registerGetter(store, namespacedType, getter, local);
  });

  module.forEachChild(function (child, key) {
    installModule(store, rootState, path.concat(key), child, hot);
  });
}

/**
 * make localized dispatch, commit, getters and state
 * if there is no namespace, just use root ones
 */
function makeLocalContext (store, namespace, path) {
  var noNamespace = namespace === '';

  var local = {
    dispatch: noNamespace ? store.dispatch : function (_type, _payload, _options) {
      var args = unifyObjectStyle(_type, _payload, _options);
      var payload = args.payload;
      var options = args.options;
      var type = args.type;

      if (!options || !options.root) {
        type = namespace + type;
        if (false) {}
      }

      return store.dispatch(type, payload)
    },

    commit: noNamespace ? store.commit : function (_type, _payload, _options) {
      var args = unifyObjectStyle(_type, _payload, _options);
      var payload = args.payload;
      var options = args.options;
      var type = args.type;

      if (!options || !options.root) {
        type = namespace + type;
        if (false) {}
      }

      store.commit(type, payload, options);
    }
  };

  // getters and state object must be gotten lazily
  // because they will be changed by vm update
  Object.defineProperties(local, {
    getters: {
      get: noNamespace
        ? function () { return store.getters; }
        : function () { return makeLocalGetters(store, namespace); }
    },
    state: {
      get: function () { return getNestedState(store.state, path); }
    }
  });

  return local
}

function makeLocalGetters (store, namespace) {
  if (!store._makeLocalGettersCache[namespace]) {
    var gettersProxy = {};
    var splitPos = namespace.length;
    Object.keys(store.getters).forEach(function (type) {
      // skip if the target getter is not match this namespace
      if (type.slice(0, splitPos) !== namespace) { return }

      // extract local getter type
      var localType = type.slice(splitPos);

      // Add a port to the getters proxy.
      // Define as getter property because
      // we do not want to evaluate the getters in this time.
      Object.defineProperty(gettersProxy, localType, {
        get: function () { return store.getters[type]; },
        enumerable: true
      });
    });
    store._makeLocalGettersCache[namespace] = gettersProxy;
  }

  return store._makeLocalGettersCache[namespace]
}

function registerMutation (store, type, handler, local) {
  var entry = store._mutations[type] || (store._mutations[type] = []);
  entry.push(function wrappedMutationHandler (payload) {
    handler.call(store, local.state, payload);
  });
}

function registerAction (store, type, handler, local) {
  var entry = store._actions[type] || (store._actions[type] = []);
  entry.push(function wrappedActionHandler (payload) {
    var res = handler.call(store, {
      dispatch: local.dispatch,
      commit: local.commit,
      getters: local.getters,
      state: local.state,
      rootGetters: store.getters,
      rootState: store.state
    }, payload);
    if (!isPromise(res)) {
      res = Promise.resolve(res);
    }
    if (store._devtoolHook) {
      return res.catch(function (err) {
        store._devtoolHook.emit('vuex:error', err);
        throw err
      })
    } else {
      return res
    }
  });
}

function registerGetter (store, type, rawGetter, local) {
  if (store._wrappedGetters[type]) {
    if (false) {}
    return
  }
  store._wrappedGetters[type] = function wrappedGetter (store) {
    return rawGetter(
      local.state, // local state
      local.getters, // local getters
      store.state, // root state
      store.getters // root getters
    )
  };
}

function enableStrictMode (store) {
  store._vm.$watch(function () { return this._data.$$state }, function () {
    if (false) {}
  }, { deep: true, sync: true });
}

function getNestedState (state, path) {
  return path.length
    ? path.reduce(function (state, key) { return state[key]; }, state)
    : state
}

function unifyObjectStyle (type, payload, options) {
  if (isObject(type) && type.type) {
    options = payload;
    payload = type;
    type = type.type;
  }

  if (false) {}

  return { type: type, payload: payload, options: options }
}

function install (_Vue) {
  if (Vue && _Vue === Vue) {
    if (false) {}
    return
  }
  Vue = _Vue;
  applyMixin(Vue);
}

/**
 * Reduce the code which written in Vue.js for getting the state.
 * @param {String} [namespace] - Module's namespace
 * @param {Object|Array} states # Object's item can be a function which accept state and getters for param, you can do something for state and getters in it.
 * @param {Object}
 */
var mapState = normalizeNamespace(function (namespace, states) {
  var res = {};
  if (false) {}
  normalizeMap(states).forEach(function (ref) {
    var key = ref.key;
    var val = ref.val;

    res[key] = function mappedState () {
      var state = this.$store.state;
      var getters = this.$store.getters;
      if (namespace) {
        var module = getModuleByNamespace(this.$store, 'mapState', namespace);
        if (!module) {
          return
        }
        state = module.context.state;
        getters = module.context.getters;
      }
      return typeof val === 'function'
        ? val.call(this, state, getters)
        : state[val]
    };
    // mark vuex getter for devtools
    res[key].vuex = true;
  });
  return res
});

/**
 * Reduce the code which written in Vue.js for committing the mutation
 * @param {String} [namespace] - Module's namespace
 * @param {Object|Array} mutations # Object's item can be a function which accept `commit` function as the first param, it can accept anthor params. You can commit mutation and do any other things in this function. specially, You need to pass anthor params from the mapped function.
 * @return {Object}
 */
var mapMutations = normalizeNamespace(function (namespace, mutations) {
  var res = {};
  if (false) {}
  normalizeMap(mutations).forEach(function (ref) {
    var key = ref.key;
    var val = ref.val;

    res[key] = function mappedMutation () {
      var args = [], len = arguments.length;
      while ( len-- ) args[ len ] = arguments[ len ];

      // Get the commit method from store
      var commit = this.$store.commit;
      if (namespace) {
        var module = getModuleByNamespace(this.$store, 'mapMutations', namespace);
        if (!module) {
          return
        }
        commit = module.context.commit;
      }
      return typeof val === 'function'
        ? val.apply(this, [commit].concat(args))
        : commit.apply(this.$store, [val].concat(args))
    };
  });
  return res
});

/**
 * Reduce the code which written in Vue.js for getting the getters
 * @param {String} [namespace] - Module's namespace
 * @param {Object|Array} getters
 * @return {Object}
 */
var mapGetters = normalizeNamespace(function (namespace, getters) {
  var res = {};
  if (false) {}
  normalizeMap(getters).forEach(function (ref) {
    var key = ref.key;
    var val = ref.val;

    // The namespace has been mutated by normalizeNamespace
    val = namespace + val;
    res[key] = function mappedGetter () {
      if (namespace && !getModuleByNamespace(this.$store, 'mapGetters', namespace)) {
        return
      }
      if (false) {}
      return this.$store.getters[val]
    };
    // mark vuex getter for devtools
    res[key].vuex = true;
  });
  return res
});

/**
 * Reduce the code which written in Vue.js for dispatch the action
 * @param {String} [namespace] - Module's namespace
 * @param {Object|Array} actions # Object's item can be a function which accept `dispatch` function as the first param, it can accept anthor params. You can dispatch action and do any other things in this function. specially, You need to pass anthor params from the mapped function.
 * @return {Object}
 */
var mapActions = normalizeNamespace(function (namespace, actions) {
  var res = {};
  if (false) {}
  normalizeMap(actions).forEach(function (ref) {
    var key = ref.key;
    var val = ref.val;

    res[key] = function mappedAction () {
      var args = [], len = arguments.length;
      while ( len-- ) args[ len ] = arguments[ len ];

      // get dispatch function from store
      var dispatch = this.$store.dispatch;
      if (namespace) {
        var module = getModuleByNamespace(this.$store, 'mapActions', namespace);
        if (!module) {
          return
        }
        dispatch = module.context.dispatch;
      }
      return typeof val === 'function'
        ? val.apply(this, [dispatch].concat(args))
        : dispatch.apply(this.$store, [val].concat(args))
    };
  });
  return res
});

/**
 * Rebinding namespace param for mapXXX function in special scoped, and return them by simple object
 * @param {String} namespace
 * @return {Object}
 */
var createNamespacedHelpers = function (namespace) { return ({
  mapState: mapState.bind(null, namespace),
  mapGetters: mapGetters.bind(null, namespace),
  mapMutations: mapMutations.bind(null, namespace),
  mapActions: mapActions.bind(null, namespace)
}); };

/**
 * Normalize the map
 * normalizeMap([1, 2, 3]) => [ { key: 1, val: 1 }, { key: 2, val: 2 }, { key: 3, val: 3 } ]
 * normalizeMap({a: 1, b: 2, c: 3}) => [ { key: 'a', val: 1 }, { key: 'b', val: 2 }, { key: 'c', val: 3 } ]
 * @param {Array|Object} map
 * @return {Object}
 */
function normalizeMap (map) {
  if (!isValidMap(map)) {
    return []
  }
  return Array.isArray(map)
    ? map.map(function (key) { return ({ key: key, val: key }); })
    : Object.keys(map).map(function (key) { return ({ key: key, val: map[key] }); })
}

/**
 * Validate whether given map is valid or not
 * @param {*} map
 * @return {Boolean}
 */
function isValidMap (map) {
  return Array.isArray(map) || isObject(map)
}

/**
 * Return a function expect two param contains namespace and map. it will normalize the namespace and then the param's function will handle the new namespace and the map.
 * @param {Function} fn
 * @return {Function}
 */
function normalizeNamespace (fn) {
  return function (namespace, map) {
    if (typeof namespace !== 'string') {
      map = namespace;
      namespace = '';
    } else if (namespace.charAt(namespace.length - 1) !== '/') {
      namespace += '/';
    }
    return fn(namespace, map)
  }
}

/**
 * Search a special module from store by namespace. if module not exist, print error message.
 * @param {Object} store
 * @param {String} helper
 * @param {String} namespace
 * @return {Object}
 */
function getModuleByNamespace (store, helper, namespace) {
  var module = store._modulesNamespaceMap[namespace];
  if (false) {}
  return module
}

var index_esm = {
  Store: Store,
  install: install,
  version: '3.1.2',
  mapState: mapState,
  mapMutations: mapMutations,
  mapGetters: mapGetters,
  mapActions: mapActions,
  createNamespacedHelpers: createNamespacedHelpers
};

/* harmony default export */ __webpack_exports__["b"] = (index_esm);


/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__("c8ba")))

/***/ }),

/***/ "2f88":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_StringDataValue_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("071a");
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_StringDataValue_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_StringDataValue_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */
 /* unused harmony default export */ var _unused_webpack_default_export = (_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_StringDataValue_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "32b0":
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/* WEBPACK VAR INJECTION */(function(global) {

var origSymbol = global.Symbol;
var hasSymbolSham = __webpack_require__("3342");

module.exports = function hasNativeSymbols() {
	if (typeof origSymbol !== 'function') { return false; }
	if (typeof Symbol !== 'function') { return false; }
	if (typeof origSymbol('foo') !== 'symbol') { return false; }
	if (typeof Symbol('bar') !== 'symbol') { return false; }

	return hasSymbolSham();
};

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__("c8ba")))

/***/ }),

/***/ "3342":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


/* eslint complexity: [2, 18], max-statements: [2, 33] */
module.exports = function hasSymbols() {
	if (typeof Symbol !== 'function' || typeof Object.getOwnPropertySymbols !== 'function') { return false; }
	if (typeof Symbol.iterator === 'symbol') { return true; }

	var obj = {};
	var sym = Symbol('test');
	var symObj = Object(sym);
	if (typeof sym === 'string') { return false; }

	if (Object.prototype.toString.call(sym) !== '[object Symbol]') { return false; }
	if (Object.prototype.toString.call(symObj) !== '[object Symbol]') { return false; }

	// temp disabled per https://github.com/ljharb/object.assign/issues/17
	// if (sym instanceof Symbol) { return false; }
	// temp disabled per https://github.com/WebReflection/get-own-property-symbols/issues/4
	// if (!(symObj instanceof Symbol)) { return false; }

	// if (typeof Symbol.prototype.toString !== 'function') { return false; }
	// if (String(sym) !== Symbol.prototype.toString.call(sym)) { return false; }

	var symVal = 42;
	obj[sym] = symVal;
	for (sym in obj) { return false; } // eslint-disable-line no-restricted-syntax
	if (typeof Object.keys === 'function' && Object.keys(obj).length !== 0) { return false; }

	if (typeof Object.getOwnPropertyNames === 'function' && Object.getOwnPropertyNames(obj).length !== 0) { return false; }

	var syms = Object.getOwnPropertySymbols(obj);
	if (syms.length !== 1 || syms[0] !== sym) { return false; }

	if (!Object.prototype.propertyIsEnumerable.call(obj, sym)) { return false; }

	if (typeof Object.getOwnPropertyDescriptor === 'function') {
		var descriptor = Object.getOwnPropertyDescriptor(obj, sym);
		if (descriptor.value !== symVal || descriptor.enumerable !== true) { return false; }
	}

	return true;
};


/***/ }),

/***/ "3410":
/***/ (function(module, exports, __webpack_require__) {

var $ = __webpack_require__("23e7");
var fails = __webpack_require__("d039");
var toObject = __webpack_require__("7b0b");
var nativeGetPrototypeOf = __webpack_require__("e163");
var CORRECT_PROTOTYPE_GETTER = __webpack_require__("e177");

var FAILS_ON_PRIMITIVES = fails(function () { nativeGetPrototypeOf(1); });

// `Object.getPrototypeOf` method
// https://tc39.github.io/ecma262/#sec-object.getprototypeof
$({ target: 'Object', stat: true, forced: FAILS_ON_PRIMITIVES, sham: !CORRECT_PROTOTYPE_GETTER }, {
  getPrototypeOf: function getPrototypeOf(it) {
    return nativeGetPrototypeOf(toObject(it));
  }
});



/***/ }),

/***/ "342f":
/***/ (function(module, exports, __webpack_require__) {

var getBuiltIn = __webpack_require__("d066");

module.exports = getBuiltIn('navigator', 'userAgent') || '';


/***/ }),

/***/ "3572":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "35a1":
/***/ (function(module, exports, __webpack_require__) {

var classof = __webpack_require__("f5df");
var Iterators = __webpack_require__("3f8c");
var wellKnownSymbol = __webpack_require__("b622");

var ITERATOR = wellKnownSymbol('iterator');

module.exports = function (it) {
  if (it != undefined) return it[ITERATOR]
    || it['@@iterator']
    || Iterators[classof(it)];
};


/***/ }),

/***/ "37e8":
/***/ (function(module, exports, __webpack_require__) {

var DESCRIPTORS = __webpack_require__("83ab");
var definePropertyModule = __webpack_require__("9bf2");
var anObject = __webpack_require__("825a");
var objectKeys = __webpack_require__("df75");

// `Object.defineProperties` method
// https://tc39.github.io/ecma262/#sec-object.defineproperties
module.exports = DESCRIPTORS ? Object.defineProperties : function defineProperties(O, Properties) {
  anObject(O);
  var keys = objectKeys(Properties);
  var length = keys.length;
  var index = 0;
  var key;
  while (length > index) definePropertyModule.f(O, key = keys[index++], Properties[key]);
  return O;
};


/***/ }),

/***/ "39dd":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "3bbe":
/***/ (function(module, exports, __webpack_require__) {

var isObject = __webpack_require__("861d");

module.exports = function (it) {
  if (!isObject(it) && it !== null) {
    throw TypeError("Can't set " + String(it) + ' as a prototype');
  } return it;
};


/***/ }),

/***/ "3ca3":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var charAt = __webpack_require__("6547").charAt;
var InternalStateModule = __webpack_require__("69f3");
var defineIterator = __webpack_require__("7dd0");

var STRING_ITERATOR = 'String Iterator';
var setInternalState = InternalStateModule.set;
var getInternalState = InternalStateModule.getterFor(STRING_ITERATOR);

// `String.prototype[@@iterator]` method
// https://tc39.github.io/ecma262/#sec-string.prototype-@@iterator
defineIterator(String, 'String', function (iterated) {
  setInternalState(this, {
    type: STRING_ITERATOR,
    string: String(iterated),
    index: 0
  });
// `%StringIteratorPrototype%.next` method
// https://tc39.github.io/ecma262/#sec-%stringiteratorprototype%.next
}, function next() {
  var state = getInternalState(this);
  var string = state.string;
  var index = state.index;
  var point;
  if (index >= string.length) return { value: undefined, done: true };
  point = charAt(string, index);
  state.index += point.length;
  return { value: point, done: false };
});


/***/ }),

/***/ "3f08":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "3f8c":
/***/ (function(module, exports) {

module.exports = {};


/***/ }),

/***/ "4160":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var $ = __webpack_require__("23e7");
var forEach = __webpack_require__("17c2");

// `Array.prototype.forEach` method
// https://tc39.github.io/ecma262/#sec-array.prototype.foreach
$({ target: 'Array', proto: true, forced: [].forEach != forEach }, {
  forEach: forEach
});


/***/ }),

/***/ "428f":
/***/ (function(module, exports, __webpack_require__) {

var global = __webpack_require__("da84");

module.exports = global;


/***/ }),

/***/ "436e":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_DataBridge_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("8c5b");
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_DataBridge_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_DataBridge_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */
 /* unused harmony default export */ var _unused_webpack_default_export = (_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_DataBridge_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "44ad":
/***/ (function(module, exports, __webpack_require__) {

var fails = __webpack_require__("d039");
var classof = __webpack_require__("c6b6");

var split = ''.split;

// fallback for non-array-like ES3 and non-enumerable old V8 strings
module.exports = fails(function () {
  // throws an error in rhino, see https://github.com/mozilla/rhino/issues/346
  // eslint-disable-next-line no-prototype-builtins
  return !Object('z').propertyIsEnumerable(0);
}) ? function (it) {
  return classof(it) == 'String' ? split.call(it, '') : Object(it);
} : Object;


/***/ }),

/***/ "44d2":
/***/ (function(module, exports, __webpack_require__) {

var wellKnownSymbol = __webpack_require__("b622");
var create = __webpack_require__("7c73");
var definePropertyModule = __webpack_require__("9bf2");

var UNSCOPABLES = wellKnownSymbol('unscopables');
var ArrayPrototype = Array.prototype;

// Array.prototype[@@unscopables]
// https://tc39.github.io/ecma262/#sec-array.prototype-@@unscopables
if (ArrayPrototype[UNSCOPABLES] == undefined) {
  definePropertyModule.f(ArrayPrototype, UNSCOPABLES, {
    configurable: true,
    value: create(null)
  });
}

// add a key to Array.prototype[@@unscopables]
module.exports = function (key) {
  ArrayPrototype[UNSCOPABLES][key] = true;
};


/***/ }),

/***/ "44de":
/***/ (function(module, exports, __webpack_require__) {

var global = __webpack_require__("da84");

module.exports = function (a, b) {
  var console = global.console;
  if (console && console.error) {
    arguments.length === 1 ? console.error(a) : console.error(a, b);
  }
};


/***/ }),

/***/ "44e7":
/***/ (function(module, exports, __webpack_require__) {

var isObject = __webpack_require__("861d");
var classof = __webpack_require__("c6b6");
var wellKnownSymbol = __webpack_require__("b622");

var MATCH = wellKnownSymbol('match');

// `IsRegExp` abstract operation
// https://tc39.github.io/ecma262/#sec-isregexp
module.exports = function (it) {
  var isRegExp;
  return isObject(it) && ((isRegExp = it[MATCH]) !== undefined ? !!isRegExp : classof(it) == 'RegExp');
};


/***/ }),

/***/ "45fc":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var $ = __webpack_require__("23e7");
var $some = __webpack_require__("b727").some;
var arrayMethodIsStrict = __webpack_require__("a640");
var arrayMethodUsesToLength = __webpack_require__("ae40");

var STRICT_METHOD = arrayMethodIsStrict('some');
var USES_TO_LENGTH = arrayMethodUsesToLength('some');

// `Array.prototype.some` method
// https://tc39.github.io/ecma262/#sec-array.prototype.some
$({ target: 'Array', proto: true, forced: !STRICT_METHOD || !USES_TO_LENGTH }, {
  some: function some(callbackfn /* , thisArg */) {
    return $some(this, callbackfn, arguments.length > 1 ? arguments[1] : undefined);
  }
});


/***/ }),

/***/ "4637":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ProcessDialogHeader_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("8985");
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ProcessDialogHeader_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ProcessDialogHeader_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */
 /* unused harmony default export */ var _unused_webpack_default_export = (_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ProcessDialogHeader_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "4706":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "4840":
/***/ (function(module, exports, __webpack_require__) {

var anObject = __webpack_require__("825a");
var aFunction = __webpack_require__("1c0b");
var wellKnownSymbol = __webpack_require__("b622");

var SPECIES = wellKnownSymbol('species');

// `SpeciesConstructor` abstract operation
// https://tc39.github.io/ecma262/#sec-speciesconstructor
module.exports = function (O, defaultConstructor) {
  var C = anObject(O).constructor;
  var S;
  return C === undefined || (S = anObject(C)[SPECIES]) == undefined ? defaultConstructor : aFunction(S);
};


/***/ }),

/***/ "4930":
/***/ (function(module, exports, __webpack_require__) {

var fails = __webpack_require__("d039");

module.exports = !!Object.getOwnPropertySymbols && !fails(function () {
  // Chrome 38 Symbol has incorrect toString conversion
  // eslint-disable-next-line no-undef
  return !String(Symbol());
});


/***/ }),

/***/ "494d":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ErrorUnsupportedDatatype_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("5c79");
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ErrorUnsupportedDatatype_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ErrorUnsupportedDatatype_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */
 /* unused harmony default export */ var _unused_webpack_default_export = (_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ErrorUnsupportedDatatype_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "4ae1":
/***/ (function(module, exports, __webpack_require__) {

var $ = __webpack_require__("23e7");
var getBuiltIn = __webpack_require__("d066");
var aFunction = __webpack_require__("1c0b");
var anObject = __webpack_require__("825a");
var isObject = __webpack_require__("861d");
var create = __webpack_require__("7c73");
var bind = __webpack_require__("0538");
var fails = __webpack_require__("d039");

var nativeConstruct = getBuiltIn('Reflect', 'construct');

// `Reflect.construct` method
// https://tc39.github.io/ecma262/#sec-reflect.construct
// MS Edge supports only 2 arguments and argumentsList argument is optional
// FF Nightly sets third argument as `new.target`, but does not create `this` from it
var NEW_TARGET_BUG = fails(function () {
  function F() { /* empty */ }
  return !(nativeConstruct(function () { /* empty */ }, [], F) instanceof F);
});
var ARGS_BUG = !fails(function () {
  nativeConstruct(function () { /* empty */ });
});
var FORCED = NEW_TARGET_BUG || ARGS_BUG;

$({ target: 'Reflect', stat: true, forced: FORCED, sham: FORCED }, {
  construct: function construct(Target, args /* , newTarget */) {
    aFunction(Target);
    anObject(args);
    var newTarget = arguments.length < 3 ? Target : aFunction(arguments[2]);
    if (ARGS_BUG && !NEW_TARGET_BUG) return nativeConstruct(Target, args, newTarget);
    if (Target == newTarget) {
      // w/o altered newTarget, optimization for 0-4 arguments
      switch (args.length) {
        case 0: return new Target();
        case 1: return new Target(args[0]);
        case 2: return new Target(args[0], args[1]);
        case 3: return new Target(args[0], args[1], args[2]);
        case 4: return new Target(args[0], args[1], args[2], args[3]);
      }
      // w/o altered newTarget, lot of arguments case
      var $args = [null];
      $args.push.apply($args, args);
      return new (bind.apply(Target, $args))();
    }
    // with altered newTarget, not support built-in constructors
    var proto = newTarget.prototype;
    var instance = create(isObject(proto) ? proto : Object.prototype);
    var result = Function.apply.call(Target, instance, args);
    return isObject(result) ? result : instance;
  }
});


/***/ }),

/***/ "4d64":
/***/ (function(module, exports, __webpack_require__) {

var toIndexedObject = __webpack_require__("fc6a");
var toLength = __webpack_require__("50c4");
var toAbsoluteIndex = __webpack_require__("23cb");

// `Array.prototype.{ indexOf, includes }` methods implementation
var createMethod = function (IS_INCLUDES) {
  return function ($this, el, fromIndex) {
    var O = toIndexedObject($this);
    var length = toLength(O.length);
    var index = toAbsoluteIndex(fromIndex, length);
    var value;
    // Array#includes uses SameValueZero equality algorithm
    // eslint-disable-next-line no-self-compare
    if (IS_INCLUDES && el != el) while (length > index) {
      value = O[index++];
      // eslint-disable-next-line no-self-compare
      if (value != value) return true;
    // Array#indexOf ignores holes, Array#includes - not
    } else for (;length > index; index++) {
      if ((IS_INCLUDES || index in O) && O[index] === el) return IS_INCLUDES || index || 0;
    } return !IS_INCLUDES && -1;
  };
};

module.exports = {
  // `Array.prototype.includes` method
  // https://tc39.github.io/ecma262/#sec-array.prototype.includes
  includes: createMethod(true),
  // `Array.prototype.indexOf` method
  // https://tc39.github.io/ecma262/#sec-array.prototype.indexof
  indexOf: createMethod(false)
};


/***/ }),

/***/ "4de4":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var $ = __webpack_require__("23e7");
var $filter = __webpack_require__("b727").filter;
var arrayMethodHasSpeciesSupport = __webpack_require__("1dde");
var arrayMethodUsesToLength = __webpack_require__("ae40");

var HAS_SPECIES_SUPPORT = arrayMethodHasSpeciesSupport('filter');
// Edge 14- issue
var USES_TO_LENGTH = arrayMethodUsesToLength('filter');

// `Array.prototype.filter` method
// https://tc39.github.io/ecma262/#sec-array.prototype.filter
// with adding support of @@species
$({ target: 'Array', proto: true, forced: !HAS_SPECIES_SUPPORT || !USES_TO_LENGTH }, {
  filter: function filter(callbackfn /* , thisArg */) {
    return $filter(this, callbackfn, arguments.length > 1 ? arguments[1] : undefined);
  }
});


/***/ }),

/***/ "4df4":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var bind = __webpack_require__("0366");
var toObject = __webpack_require__("7b0b");
var callWithSafeIterationClosing = __webpack_require__("9bdd");
var isArrayIteratorMethod = __webpack_require__("e95a");
var toLength = __webpack_require__("50c4");
var createProperty = __webpack_require__("8418");
var getIteratorMethod = __webpack_require__("35a1");

// `Array.from` method implementation
// https://tc39.github.io/ecma262/#sec-array.from
module.exports = function from(arrayLike /* , mapfn = undefined, thisArg = undefined */) {
  var O = toObject(arrayLike);
  var C = typeof this == 'function' ? this : Array;
  var argumentsLength = arguments.length;
  var mapfn = argumentsLength > 1 ? arguments[1] : undefined;
  var mapping = mapfn !== undefined;
  var iteratorMethod = getIteratorMethod(O);
  var index = 0;
  var length, result, step, iterator, next, value;
  if (mapping) mapfn = bind(mapfn, argumentsLength > 2 ? arguments[2] : undefined, 2);
  // if the target is not iterable or it's an array with the default iterator - use a simple case
  if (iteratorMethod != undefined && !(C == Array && isArrayIteratorMethod(iteratorMethod))) {
    iterator = iteratorMethod.call(O);
    next = iterator.next;
    result = new C();
    for (;!(step = next.call(iterator)).done; index++) {
      value = mapping ? callWithSafeIterationClosing(iterator, mapfn, [step.value, index], true) : step.value;
      createProperty(result, index, value);
    }
  } else {
    length = toLength(O.length);
    result = new C(length);
    for (;length > index; index++) {
      value = mapping ? mapfn(O[index], index) : O[index];
      createProperty(result, index, value);
    }
  }
  result.length = index;
  return result;
};


/***/ }),

/***/ "4e23":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_RadioGroup_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("7600");
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_RadioGroup_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_RadioGroup_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */
 /* unused harmony default export */ var _unused_webpack_default_export = (_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_RadioGroup_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "4ec9":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var collection = __webpack_require__("6d61");
var collectionStrong = __webpack_require__("6566");

// `Map` constructor
// https://tc39.github.io/ecma262/#sec-map-objects
module.exports = collection('Map', function (init) {
  return function Map() { return init(this, arguments.length ? arguments[0] : undefined); };
}, collectionStrong);


/***/ }),

/***/ "50c4":
/***/ (function(module, exports, __webpack_require__) {

var toInteger = __webpack_require__("a691");

var min = Math.min;

// `ToLength` abstract operation
// https://tc39.github.io/ecma262/#sec-tolength
module.exports = function (argument) {
  return argument > 0 ? min(toInteger(argument), 0x1FFFFFFFFFFFFF) : 0; // 2 ** 53 - 1 == 9007199254740991
};


/***/ }),

/***/ "5135":
/***/ (function(module, exports) {

var hasOwnProperty = {}.hasOwnProperty;

module.exports = function (it, key) {
  return hasOwnProperty.call(it, key);
};


/***/ }),

/***/ "5319":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var fixRegExpWellKnownSymbolLogic = __webpack_require__("d784");
var anObject = __webpack_require__("825a");
var toObject = __webpack_require__("7b0b");
var toLength = __webpack_require__("50c4");
var toInteger = __webpack_require__("a691");
var requireObjectCoercible = __webpack_require__("1d80");
var advanceStringIndex = __webpack_require__("8aa5");
var regExpExec = __webpack_require__("14c3");

var max = Math.max;
var min = Math.min;
var floor = Math.floor;
var SUBSTITUTION_SYMBOLS = /\$([$&'`]|\d\d?|<[^>]*>)/g;
var SUBSTITUTION_SYMBOLS_NO_NAMED = /\$([$&'`]|\d\d?)/g;

var maybeToString = function (it) {
  return it === undefined ? it : String(it);
};

// @@replace logic
fixRegExpWellKnownSymbolLogic('replace', 2, function (REPLACE, nativeReplace, maybeCallNative, reason) {
  var REGEXP_REPLACE_SUBSTITUTES_UNDEFINED_CAPTURE = reason.REGEXP_REPLACE_SUBSTITUTES_UNDEFINED_CAPTURE;
  var REPLACE_KEEPS_$0 = reason.REPLACE_KEEPS_$0;
  var UNSAFE_SUBSTITUTE = REGEXP_REPLACE_SUBSTITUTES_UNDEFINED_CAPTURE ? '$' : '$0';

  return [
    // `String.prototype.replace` method
    // https://tc39.github.io/ecma262/#sec-string.prototype.replace
    function replace(searchValue, replaceValue) {
      var O = requireObjectCoercible(this);
      var replacer = searchValue == undefined ? undefined : searchValue[REPLACE];
      return replacer !== undefined
        ? replacer.call(searchValue, O, replaceValue)
        : nativeReplace.call(String(O), searchValue, replaceValue);
    },
    // `RegExp.prototype[@@replace]` method
    // https://tc39.github.io/ecma262/#sec-regexp.prototype-@@replace
    function (regexp, replaceValue) {
      if (
        (!REGEXP_REPLACE_SUBSTITUTES_UNDEFINED_CAPTURE && REPLACE_KEEPS_$0) ||
        (typeof replaceValue === 'string' && replaceValue.indexOf(UNSAFE_SUBSTITUTE) === -1)
      ) {
        var res = maybeCallNative(nativeReplace, regexp, this, replaceValue);
        if (res.done) return res.value;
      }

      var rx = anObject(regexp);
      var S = String(this);

      var functionalReplace = typeof replaceValue === 'function';
      if (!functionalReplace) replaceValue = String(replaceValue);

      var global = rx.global;
      if (global) {
        var fullUnicode = rx.unicode;
        rx.lastIndex = 0;
      }
      var results = [];
      while (true) {
        var result = regExpExec(rx, S);
        if (result === null) break;

        results.push(result);
        if (!global) break;

        var matchStr = String(result[0]);
        if (matchStr === '') rx.lastIndex = advanceStringIndex(S, toLength(rx.lastIndex), fullUnicode);
      }

      var accumulatedResult = '';
      var nextSourcePosition = 0;
      for (var i = 0; i < results.length; i++) {
        result = results[i];

        var matched = String(result[0]);
        var position = max(min(toInteger(result.index), S.length), 0);
        var captures = [];
        // NOTE: This is equivalent to
        //   captures = result.slice(1).map(maybeToString)
        // but for some reason `nativeSlice.call(result, 1, result.length)` (called in
        // the slice polyfill when slicing native arrays) "doesn't work" in safari 9 and
        // causes a crash (https://pastebin.com/N21QzeQA) when trying to debug it.
        for (var j = 1; j < result.length; j++) captures.push(maybeToString(result[j]));
        var namedCaptures = result.groups;
        if (functionalReplace) {
          var replacerArgs = [matched].concat(captures, position, S);
          if (namedCaptures !== undefined) replacerArgs.push(namedCaptures);
          var replacement = String(replaceValue.apply(undefined, replacerArgs));
        } else {
          replacement = getSubstitution(matched, S, position, captures, namedCaptures, replaceValue);
        }
        if (position >= nextSourcePosition) {
          accumulatedResult += S.slice(nextSourcePosition, position) + replacement;
          nextSourcePosition = position + matched.length;
        }
      }
      return accumulatedResult + S.slice(nextSourcePosition);
    }
  ];

  // https://tc39.github.io/ecma262/#sec-getsubstitution
  function getSubstitution(matched, str, position, captures, namedCaptures, replacement) {
    var tailPos = position + matched.length;
    var m = captures.length;
    var symbols = SUBSTITUTION_SYMBOLS_NO_NAMED;
    if (namedCaptures !== undefined) {
      namedCaptures = toObject(namedCaptures);
      symbols = SUBSTITUTION_SYMBOLS;
    }
    return nativeReplace.call(replacement, symbols, function (match, ch) {
      var capture;
      switch (ch.charAt(0)) {
        case '$': return '$';
        case '&': return matched;
        case '`': return str.slice(0, position);
        case "'": return str.slice(tailPos);
        case '<':
          capture = namedCaptures[ch.slice(1, -1)];
          break;
        default: // \d\d?
          var n = +ch;
          if (n === 0) return match;
          if (n > m) {
            var f = floor(n / 10);
            if (f === 0) return match;
            if (f <= m) return captures[f - 1] === undefined ? ch.charAt(1) : captures[f - 1] + ch.charAt(1);
            return match;
          }
          capture = captures[n - 1];
      }
      return capture === undefined ? '' : capture;
    });
  }
});


/***/ }),

/***/ "5692":
/***/ (function(module, exports, __webpack_require__) {

var IS_PURE = __webpack_require__("c430");
var store = __webpack_require__("c6cd");

(module.exports = function (key, value) {
  return store[key] || (store[key] = value !== undefined ? value : {});
})('versions', []).push({
  version: '3.6.4',
  mode: IS_PURE ? 'pure' : 'global',
  copyright: '© 2020 Denis Pushkarev (zloirock.ru)'
});


/***/ }),

/***/ "56ef":
/***/ (function(module, exports, __webpack_require__) {

var getBuiltIn = __webpack_require__("d066");
var getOwnPropertyNamesModule = __webpack_require__("241c");
var getOwnPropertySymbolsModule = __webpack_require__("7418");
var anObject = __webpack_require__("825a");

// all object keys, includes non-enumerable and symbols
module.exports = getBuiltIn('Reflect', 'ownKeys') || function ownKeys(it) {
  var keys = getOwnPropertyNamesModule.f(anObject(it));
  var getOwnPropertySymbols = getOwnPropertySymbolsModule.f;
  return getOwnPropertySymbols ? keys.concat(getOwnPropertySymbols(it)) : keys;
};


/***/ }),

/***/ "5708":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var $Object = Object;
var $TypeError = TypeError;

module.exports = function flags() {
	if (this != null && this !== $Object(this)) {
		throw new $TypeError('RegExp.prototype.flags getter called on non-object');
	}
	var result = '';
	if (this.global) {
		result += 'g';
	}
	if (this.ignoreCase) {
		result += 'i';
	}
	if (this.multiline) {
		result += 'm';
	}
	if (this.dotAll) {
		result += 's';
	}
	if (this.unicode) {
		result += 'u';
	}
	if (this.sticky) {
		result += 'y';
	}
	return result;
};


/***/ }),

/***/ "57ec":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var implementation = __webpack_require__("5708");

var supportsDescriptors = __webpack_require__("f367").supportsDescriptors;
var $gOPD = Object.getOwnPropertyDescriptor;
var $TypeError = TypeError;

module.exports = function getPolyfill() {
	if (!supportsDescriptors) {
		throw new $TypeError('RegExp.prototype.flags requires a true ES5 environment that supports property descriptors');
	}
	if ((/a/mig).flags === 'gim') {
		var descriptor = $gOPD(RegExp.prototype, 'flags');
		if (descriptor && typeof descriptor.get === 'function' && typeof (/a/).dotAll === 'boolean') {
			return descriptor.get;
		}
	}
	return implementation;
};


/***/ }),

/***/ "5899":
/***/ (function(module, exports) {

// a string of all valid unicode whitespaces
// eslint-disable-next-line max-len
module.exports = '\u0009\u000A\u000B\u000C\u000D\u0020\u00A0\u1680\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200A\u202F\u205F\u3000\u2028\u2029\uFEFF';


/***/ }),

/***/ "58a8":
/***/ (function(module, exports, __webpack_require__) {

var requireObjectCoercible = __webpack_require__("1d80");
var whitespaces = __webpack_require__("5899");

var whitespace = '[' + whitespaces + ']';
var ltrim = RegExp('^' + whitespace + whitespace + '*');
var rtrim = RegExp(whitespace + whitespace + '*$');

// `String.prototype.{ trim, trimStart, trimEnd, trimLeft, trimRight }` methods implementation
var createMethod = function (TYPE) {
  return function ($this) {
    var string = String(requireObjectCoercible($this));
    if (TYPE & 1) string = string.replace(ltrim, '');
    if (TYPE & 2) string = string.replace(rtrim, '');
    return string;
  };
};

module.exports = {
  // `String.prototype.{ trimLeft, trimStart }` methods
  // https://tc39.github.io/ecma262/#sec-string.prototype.trimstart
  start: createMethod(1),
  // `String.prototype.{ trimRight, trimEnd }` methods
  // https://tc39.github.io/ecma262/#sec-string.prototype.trimend
  end: createMethod(2),
  // `String.prototype.trim` method
  // https://tc39.github.io/ecma262/#sec-string.prototype.trim
  trim: createMethod(3)
};


/***/ }),

/***/ "5a34":
/***/ (function(module, exports, __webpack_require__) {

var isRegExp = __webpack_require__("44e7");

module.exports = function (it) {
  if (isRegExp(it)) {
    throw TypeError("The method doesn't accept regular expressions");
  } return it;
};


/***/ }),

/***/ "5c6c":
/***/ (function(module, exports) {

module.exports = function (bitmap, value) {
  return {
    enumerable: !(bitmap & 1),
    configurable: !(bitmap & 2),
    writable: !(bitmap & 4),
    value: value
  };
};


/***/ }),

/***/ "5c79":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "6062":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var collection = __webpack_require__("6d61");
var collectionStrong = __webpack_require__("6566");

// `Set` constructor
// https://tc39.github.io/ecma262/#sec-set-objects
module.exports = collection('Set', function (init) {
  return function Set() { return init(this, arguments.length ? arguments[0] : undefined); };
}, collectionStrong);


/***/ }),

/***/ "60a3":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Inject", function() { return Inject; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Provide", function() { return Provide; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Model", function() { return Model; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Prop", function() { return Prop; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Watch", function() { return Watch; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Emit", function() { return Emit; });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("8bbf");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(vue__WEBPACK_IMPORTED_MODULE_0__);
/* harmony reexport (default from non-harmony) */ __webpack_require__.d(__webpack_exports__, "Vue", function() { return vue__WEBPACK_IMPORTED_MODULE_0___default.a; });
/* harmony import */ var vue_class_component__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("65d9");
/* harmony import */ var vue_class_component__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(vue_class_component__WEBPACK_IMPORTED_MODULE_1__);
/* harmony reexport (default from non-harmony) */ __webpack_require__.d(__webpack_exports__, "Component", function() { return vue_class_component__WEBPACK_IMPORTED_MODULE_1___default.a; });
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Mixins", function() { return vue_class_component__WEBPACK_IMPORTED_MODULE_1__["mixins"]; });

/** vue-property-decorator verson 7.3.0 MIT LICENSE copyright 2018 kaorun343 */




/**
 * decorator of an inject
 * @param from key
 * @return PropertyDecorator
 */
function Inject(options) {
    return Object(vue_class_component__WEBPACK_IMPORTED_MODULE_1__["createDecorator"])(function (componentOptions, key) {
        if (typeof componentOptions.inject === 'undefined') {
            componentOptions.inject = {};
        }
        if (!Array.isArray(componentOptions.inject)) {
            componentOptions.inject[key] = options || key;
        }
    });
}
/**
 * decorator of a provide
 * @param key key
 * @return PropertyDecorator | void
 */
function Provide(key) {
    return Object(vue_class_component__WEBPACK_IMPORTED_MODULE_1__["createDecorator"])(function (componentOptions, k) {
        var provide = componentOptions.provide;
        if (typeof provide !== 'function' || !provide.managed) {
            var original_1 = componentOptions.provide;
            provide = componentOptions.provide = function () {
                var rv = Object.create((typeof original_1 === 'function' ? original_1.call(this) : original_1) || null);
                for (var i in provide.managed)
                    rv[provide.managed[i]] = this[i];
                return rv;
            };
            provide.managed = {};
        }
        provide.managed[k] = key || k;
    });
}
/**
 * decorator of model
 * @param  event event name
 * @param options options
 * @return PropertyDecorator
 */
function Model(event, options) {
    if (options === void 0) { options = {}; }
    return Object(vue_class_component__WEBPACK_IMPORTED_MODULE_1__["createDecorator"])(function (componentOptions, k) {
        (componentOptions.props || (componentOptions.props = {}))[k] = options;
        componentOptions.model = { prop: k, event: event || k };
    });
}
/**
 * decorator of a prop
 * @param  options the options for the prop
 * @return PropertyDecorator | void
 */
function Prop(options) {
    if (options === void 0) { options = {}; }
    return Object(vue_class_component__WEBPACK_IMPORTED_MODULE_1__["createDecorator"])(function (componentOptions, k) {
        (componentOptions.props || (componentOptions.props = {}))[k] = options;
    });
}
/**
 * decorator of a watch function
 * @param  path the path or the expression to observe
 * @param  WatchOption
 * @return MethodDecorator
 */
function Watch(path, options) {
    if (options === void 0) { options = {}; }
    var _a = options.deep, deep = _a === void 0 ? false : _a, _b = options.immediate, immediate = _b === void 0 ? false : _b;
    return Object(vue_class_component__WEBPACK_IMPORTED_MODULE_1__["createDecorator"])(function (componentOptions, handler) {
        if (typeof componentOptions.watch !== 'object') {
            componentOptions.watch = Object.create(null);
        }
        var watch = componentOptions.watch;
        if (typeof watch[path] === 'object' && !Array.isArray(watch[path])) {
            watch[path] = [watch[path]];
        }
        else if (typeof watch[path] === 'undefined') {
            watch[path] = [];
        }
        watch[path].push({ handler: handler, deep: deep, immediate: immediate });
    });
}
// Code copied from Vue/src/shared/util.js
var hyphenateRE = /\B([A-Z])/g;
var hyphenate = function (str) { return str.replace(hyphenateRE, '-$1').toLowerCase(); };
/**
 * decorator of an event-emitter function
 * @param  event The name of the event
 * @return MethodDecorator
 */
function Emit(event) {
    return function (_target, key, descriptor) {
        key = hyphenate(key);
        var original = descriptor.value;
        descriptor.value = function emitter() {
            var _this = this;
            var args = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                args[_i] = arguments[_i];
            }
            var emit = function (returnValue) {
                if (returnValue !== undefined)
                    args.unshift(returnValue);
                _this.$emit.apply(_this, [event || key].concat(args));
            };
            var returnValue = original.apply(this, args);
            if (isPromise(returnValue)) {
                returnValue.then(function (returnValue) {
                    emit(returnValue);
                });
            }
            else {
                emit(returnValue);
            }
        };
    };
}
function isPromise(obj) {
    return obj instanceof Promise || (obj && typeof obj.then === 'function');
}


/***/ }),

/***/ "6547":
/***/ (function(module, exports, __webpack_require__) {

var toInteger = __webpack_require__("a691");
var requireObjectCoercible = __webpack_require__("1d80");

// `String.prototype.{ codePointAt, at }` methods implementation
var createMethod = function (CONVERT_TO_STRING) {
  return function ($this, pos) {
    var S = String(requireObjectCoercible($this));
    var position = toInteger(pos);
    var size = S.length;
    var first, second;
    if (position < 0 || position >= size) return CONVERT_TO_STRING ? '' : undefined;
    first = S.charCodeAt(position);
    return first < 0xD800 || first > 0xDBFF || position + 1 === size
      || (second = S.charCodeAt(position + 1)) < 0xDC00 || second > 0xDFFF
        ? CONVERT_TO_STRING ? S.charAt(position) : first
        : CONVERT_TO_STRING ? S.slice(position, position + 2) : (first - 0xD800 << 10) + (second - 0xDC00) + 0x10000;
  };
};

module.exports = {
  // `String.prototype.codePointAt` method
  // https://tc39.github.io/ecma262/#sec-string.prototype.codepointat
  codeAt: createMethod(false),
  // `String.prototype.at` method
  // https://github.com/mathiasbynens/String.prototype.at
  charAt: createMethod(true)
};


/***/ }),

/***/ "6566":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var defineProperty = __webpack_require__("9bf2").f;
var create = __webpack_require__("7c73");
var redefineAll = __webpack_require__("e2cc");
var bind = __webpack_require__("0366");
var anInstance = __webpack_require__("19aa");
var iterate = __webpack_require__("2266");
var defineIterator = __webpack_require__("7dd0");
var setSpecies = __webpack_require__("2626");
var DESCRIPTORS = __webpack_require__("83ab");
var fastKey = __webpack_require__("f183").fastKey;
var InternalStateModule = __webpack_require__("69f3");

var setInternalState = InternalStateModule.set;
var internalStateGetterFor = InternalStateModule.getterFor;

module.exports = {
  getConstructor: function (wrapper, CONSTRUCTOR_NAME, IS_MAP, ADDER) {
    var C = wrapper(function (that, iterable) {
      anInstance(that, C, CONSTRUCTOR_NAME);
      setInternalState(that, {
        type: CONSTRUCTOR_NAME,
        index: create(null),
        first: undefined,
        last: undefined,
        size: 0
      });
      if (!DESCRIPTORS) that.size = 0;
      if (iterable != undefined) iterate(iterable, that[ADDER], that, IS_MAP);
    });

    var getInternalState = internalStateGetterFor(CONSTRUCTOR_NAME);

    var define = function (that, key, value) {
      var state = getInternalState(that);
      var entry = getEntry(that, key);
      var previous, index;
      // change existing entry
      if (entry) {
        entry.value = value;
      // create new entry
      } else {
        state.last = entry = {
          index: index = fastKey(key, true),
          key: key,
          value: value,
          previous: previous = state.last,
          next: undefined,
          removed: false
        };
        if (!state.first) state.first = entry;
        if (previous) previous.next = entry;
        if (DESCRIPTORS) state.size++;
        else that.size++;
        // add to index
        if (index !== 'F') state.index[index] = entry;
      } return that;
    };

    var getEntry = function (that, key) {
      var state = getInternalState(that);
      // fast case
      var index = fastKey(key);
      var entry;
      if (index !== 'F') return state.index[index];
      // frozen object case
      for (entry = state.first; entry; entry = entry.next) {
        if (entry.key == key) return entry;
      }
    };

    redefineAll(C.prototype, {
      // 23.1.3.1 Map.prototype.clear()
      // 23.2.3.2 Set.prototype.clear()
      clear: function clear() {
        var that = this;
        var state = getInternalState(that);
        var data = state.index;
        var entry = state.first;
        while (entry) {
          entry.removed = true;
          if (entry.previous) entry.previous = entry.previous.next = undefined;
          delete data[entry.index];
          entry = entry.next;
        }
        state.first = state.last = undefined;
        if (DESCRIPTORS) state.size = 0;
        else that.size = 0;
      },
      // 23.1.3.3 Map.prototype.delete(key)
      // 23.2.3.4 Set.prototype.delete(value)
      'delete': function (key) {
        var that = this;
        var state = getInternalState(that);
        var entry = getEntry(that, key);
        if (entry) {
          var next = entry.next;
          var prev = entry.previous;
          delete state.index[entry.index];
          entry.removed = true;
          if (prev) prev.next = next;
          if (next) next.previous = prev;
          if (state.first == entry) state.first = next;
          if (state.last == entry) state.last = prev;
          if (DESCRIPTORS) state.size--;
          else that.size--;
        } return !!entry;
      },
      // 23.2.3.6 Set.prototype.forEach(callbackfn, thisArg = undefined)
      // 23.1.3.5 Map.prototype.forEach(callbackfn, thisArg = undefined)
      forEach: function forEach(callbackfn /* , that = undefined */) {
        var state = getInternalState(this);
        var boundFunction = bind(callbackfn, arguments.length > 1 ? arguments[1] : undefined, 3);
        var entry;
        while (entry = entry ? entry.next : state.first) {
          boundFunction(entry.value, entry.key, this);
          // revert to the last existing entry
          while (entry && entry.removed) entry = entry.previous;
        }
      },
      // 23.1.3.7 Map.prototype.has(key)
      // 23.2.3.7 Set.prototype.has(value)
      has: function has(key) {
        return !!getEntry(this, key);
      }
    });

    redefineAll(C.prototype, IS_MAP ? {
      // 23.1.3.6 Map.prototype.get(key)
      get: function get(key) {
        var entry = getEntry(this, key);
        return entry && entry.value;
      },
      // 23.1.3.9 Map.prototype.set(key, value)
      set: function set(key, value) {
        return define(this, key === 0 ? 0 : key, value);
      }
    } : {
      // 23.2.3.1 Set.prototype.add(value)
      add: function add(value) {
        return define(this, value = value === 0 ? 0 : value, value);
      }
    });
    if (DESCRIPTORS) defineProperty(C.prototype, 'size', {
      get: function () {
        return getInternalState(this).size;
      }
    });
    return C;
  },
  setStrong: function (C, CONSTRUCTOR_NAME, IS_MAP) {
    var ITERATOR_NAME = CONSTRUCTOR_NAME + ' Iterator';
    var getInternalCollectionState = internalStateGetterFor(CONSTRUCTOR_NAME);
    var getInternalIteratorState = internalStateGetterFor(ITERATOR_NAME);
    // add .keys, .values, .entries, [@@iterator]
    // 23.1.3.4, 23.1.3.8, 23.1.3.11, 23.1.3.12, 23.2.3.5, 23.2.3.8, 23.2.3.10, 23.2.3.11
    defineIterator(C, CONSTRUCTOR_NAME, function (iterated, kind) {
      setInternalState(this, {
        type: ITERATOR_NAME,
        target: iterated,
        state: getInternalCollectionState(iterated),
        kind: kind,
        last: undefined
      });
    }, function () {
      var state = getInternalIteratorState(this);
      var kind = state.kind;
      var entry = state.last;
      // revert to the last existing entry
      while (entry && entry.removed) entry = entry.previous;
      // get next entry
      if (!state.target || !(state.last = entry = entry ? entry.next : state.state.first)) {
        // or finish the iteration
        state.target = undefined;
        return { value: undefined, done: true };
      }
      // return step by kind
      if (kind == 'keys') return { value: entry.key, done: false };
      if (kind == 'values') return { value: entry.value, done: false };
      return { value: [entry.key, entry.value], done: false };
    }, IS_MAP ? 'entries' : 'values', !IS_MAP, true);

    // add [@@species], 23.1.2.2, 23.2.2.2
    setSpecies(CONSTRUCTOR_NAME);
  }
};


/***/ }),

/***/ "65d9":
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/**
  * vue-class-component v6.3.2
  * (c) 2015-present Evan You
  * @license MIT
  */


Object.defineProperty(exports, '__esModule', { value: true });

function _interopDefault (ex) { return (ex && (typeof ex === 'object') && 'default' in ex) ? ex['default'] : ex; }

var Vue = _interopDefault(__webpack_require__("8bbf"));

var reflectionIsSupported = typeof Reflect !== 'undefined' && Reflect.defineMetadata;
function copyReflectionMetadata(to, from) {
    forwardMetadata(to, from);
    Object.getOwnPropertyNames(from.prototype).forEach(function (key) {
        forwardMetadata(to.prototype, from.prototype, key);
    });
    Object.getOwnPropertyNames(from).forEach(function (key) {
        forwardMetadata(to, from, key);
    });
}
function forwardMetadata(to, from, propertyKey) {
    var metaKeys = propertyKey
        ? Reflect.getOwnMetadataKeys(from, propertyKey)
        : Reflect.getOwnMetadataKeys(from);
    metaKeys.forEach(function (metaKey) {
        var metadata = propertyKey
            ? Reflect.getOwnMetadata(metaKey, from, propertyKey)
            : Reflect.getOwnMetadata(metaKey, from);
        if (propertyKey) {
            Reflect.defineMetadata(metaKey, metadata, to, propertyKey);
        }
        else {
            Reflect.defineMetadata(metaKey, metadata, to);
        }
    });
}

var fakeArray = { __proto__: [] };
var hasProto = fakeArray instanceof Array;
function createDecorator(factory) {
    return function (target, key, index) {
        var Ctor = typeof target === 'function'
            ? target
            : target.constructor;
        if (!Ctor.__decorators__) {
            Ctor.__decorators__ = [];
        }
        if (typeof index !== 'number') {
            index = undefined;
        }
        Ctor.__decorators__.push(function (options) { return factory(options, key, index); });
    };
}
function mixins() {
    var Ctors = [];
    for (var _i = 0; _i < arguments.length; _i++) {
        Ctors[_i] = arguments[_i];
    }
    return Vue.extend({ mixins: Ctors });
}
function isPrimitive(value) {
    var type = typeof value;
    return value == null || (type !== 'object' && type !== 'function');
}
function warn(message) {
    if (typeof console !== 'undefined') {
        console.warn('[vue-class-component] ' + message);
    }
}

function collectDataFromConstructor(vm, Component) {
    // override _init to prevent to init as Vue instance
    var originalInit = Component.prototype._init;
    Component.prototype._init = function () {
        var _this = this;
        // proxy to actual vm
        var keys = Object.getOwnPropertyNames(vm);
        // 2.2.0 compat (props are no longer exposed as self properties)
        if (vm.$options.props) {
            for (var key in vm.$options.props) {
                if (!vm.hasOwnProperty(key)) {
                    keys.push(key);
                }
            }
        }
        keys.forEach(function (key) {
            if (key.charAt(0) !== '_') {
                Object.defineProperty(_this, key, {
                    get: function () { return vm[key]; },
                    set: function (value) { vm[key] = value; },
                    configurable: true
                });
            }
        });
    };
    // should be acquired class property values
    var data = new Component();
    // restore original _init to avoid memory leak (#209)
    Component.prototype._init = originalInit;
    // create plain data object
    var plainData = {};
    Object.keys(data).forEach(function (key) {
        if (data[key] !== undefined) {
            plainData[key] = data[key];
        }
    });
    if (false) {}
    return plainData;
}

var $internalHooks = [
    'data',
    'beforeCreate',
    'created',
    'beforeMount',
    'mounted',
    'beforeDestroy',
    'destroyed',
    'beforeUpdate',
    'updated',
    'activated',
    'deactivated',
    'render',
    'errorCaptured' // 2.5
];
function componentFactory(Component, options) {
    if (options === void 0) { options = {}; }
    options.name = options.name || Component._componentTag || Component.name;
    // prototype props.
    var proto = Component.prototype;
    Object.getOwnPropertyNames(proto).forEach(function (key) {
        if (key === 'constructor') {
            return;
        }
        // hooks
        if ($internalHooks.indexOf(key) > -1) {
            options[key] = proto[key];
            return;
        }
        var descriptor = Object.getOwnPropertyDescriptor(proto, key);
        if (descriptor.value !== void 0) {
            // methods
            if (typeof descriptor.value === 'function') {
                (options.methods || (options.methods = {}))[key] = descriptor.value;
            }
            else {
                // typescript decorated data
                (options.mixins || (options.mixins = [])).push({
                    data: function () {
                        var _a;
                        return _a = {}, _a[key] = descriptor.value, _a;
                    }
                });
            }
        }
        else if (descriptor.get || descriptor.set) {
            // computed properties
            (options.computed || (options.computed = {}))[key] = {
                get: descriptor.get,
                set: descriptor.set
            };
        }
    });
    (options.mixins || (options.mixins = [])).push({
        data: function () {
            return collectDataFromConstructor(this, Component);
        }
    });
    // decorate options
    var decorators = Component.__decorators__;
    if (decorators) {
        decorators.forEach(function (fn) { return fn(options); });
        delete Component.__decorators__;
    }
    // find super
    var superProto = Object.getPrototypeOf(Component.prototype);
    var Super = superProto instanceof Vue
        ? superProto.constructor
        : Vue;
    var Extended = Super.extend(options);
    forwardStaticMembers(Extended, Component, Super);
    if (reflectionIsSupported) {
        copyReflectionMetadata(Extended, Component);
    }
    return Extended;
}
var reservedPropertyNames = [
    // Unique id
    'cid',
    // Super Vue constructor
    'super',
    // Component options that will be used by the component
    'options',
    'superOptions',
    'extendOptions',
    'sealedOptions',
    // Private assets
    'component',
    'directive',
    'filter'
];
function forwardStaticMembers(Extended, Original, Super) {
    // We have to use getOwnPropertyNames since Babel registers methods as non-enumerable
    Object.getOwnPropertyNames(Original).forEach(function (key) {
        // `prototype` should not be overwritten
        if (key === 'prototype') {
            return;
        }
        // Some browsers does not allow reconfigure built-in properties
        var extendedDescriptor = Object.getOwnPropertyDescriptor(Extended, key);
        if (extendedDescriptor && !extendedDescriptor.configurable) {
            return;
        }
        var descriptor = Object.getOwnPropertyDescriptor(Original, key);
        // If the user agent does not support `__proto__` or its family (IE <= 10),
        // the sub class properties may be inherited properties from the super class in TypeScript.
        // We need to exclude such properties to prevent to overwrite
        // the component options object which stored on the extended constructor (See #192).
        // If the value is a referenced value (object or function),
        // we can check equality of them and exclude it if they have the same reference.
        // If it is a primitive value, it will be forwarded for safety.
        if (!hasProto) {
            // Only `cid` is explicitly exluded from property forwarding
            // because we cannot detect whether it is a inherited property or not
            // on the no `__proto__` environment even though the property is reserved.
            if (key === 'cid') {
                return;
            }
            var superDescriptor = Object.getOwnPropertyDescriptor(Super, key);
            if (!isPrimitive(descriptor.value) &&
                superDescriptor &&
                superDescriptor.value === descriptor.value) {
                return;
            }
        }
        // Warn if the users manually declare reserved properties
        if (false) {}
        Object.defineProperty(Extended, key, descriptor);
    });
}

function Component(options) {
    if (typeof options === 'function') {
        return componentFactory(options);
    }
    return function (Component) {
        return componentFactory(Component, options);
    };
}
Component.registerHooks = function registerHooks(keys) {
    $internalHooks.push.apply($internalHooks, keys);
};

exports.default = Component;
exports.createDecorator = createDecorator;
exports.mixins = mixins;


/***/ }),

/***/ "65f0":
/***/ (function(module, exports, __webpack_require__) {

var isObject = __webpack_require__("861d");
var isArray = __webpack_require__("e8b5");
var wellKnownSymbol = __webpack_require__("b622");

var SPECIES = wellKnownSymbol('species');

// `ArraySpeciesCreate` abstract operation
// https://tc39.github.io/ecma262/#sec-arrayspeciescreate
module.exports = function (originalArray, length) {
  var C;
  if (isArray(originalArray)) {
    C = originalArray.constructor;
    // cross-realm fallback
    if (typeof C == 'function' && (C === Array || isArray(C.prototype))) C = undefined;
    else if (isObject(C)) {
      C = C[SPECIES];
      if (C === null) C = undefined;
    }
  } return new (C === undefined ? Array : C)(length === 0 ? 0 : length);
};


/***/ }),

/***/ "688e":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


/* eslint no-invalid-this: 1 */

var ERROR_MESSAGE = 'Function.prototype.bind called on incompatible ';
var slice = Array.prototype.slice;
var toStr = Object.prototype.toString;
var funcType = '[object Function]';

module.exports = function bind(that) {
    var target = this;
    if (typeof target !== 'function' || toStr.call(target) !== funcType) {
        throw new TypeError(ERROR_MESSAGE + target);
    }
    var args = slice.call(arguments, 1);

    var bound;
    var binder = function () {
        if (this instanceof bound) {
            var result = target.apply(
                this,
                args.concat(slice.call(arguments))
            );
            if (Object(result) === result) {
                return result;
            }
            return this;
        } else {
            return target.apply(
                that,
                args.concat(slice.call(arguments))
            );
        }
    };

    var boundLength = Math.max(0, target.length - args.length);
    var boundArgs = [];
    for (var i = 0; i < boundLength; i++) {
        boundArgs.push('$' + i);
    }

    bound = Function('binder', 'return function (' + boundArgs.join(',') + '){ return binder.apply(this,arguments); }')(binder);

    if (target.prototype) {
        var Empty = function Empty() {};
        Empty.prototype = target.prototype;
        bound.prototype = new Empty();
        Empty.prototype = null;
    }

    return bound;
};


/***/ }),

/***/ "69f3":
/***/ (function(module, exports, __webpack_require__) {

var NATIVE_WEAK_MAP = __webpack_require__("7f9a");
var global = __webpack_require__("da84");
var isObject = __webpack_require__("861d");
var createNonEnumerableProperty = __webpack_require__("9112");
var objectHas = __webpack_require__("5135");
var sharedKey = __webpack_require__("f772");
var hiddenKeys = __webpack_require__("d012");

var WeakMap = global.WeakMap;
var set, get, has;

var enforce = function (it) {
  return has(it) ? get(it) : set(it, {});
};

var getterFor = function (TYPE) {
  return function (it) {
    var state;
    if (!isObject(it) || (state = get(it)).type !== TYPE) {
      throw TypeError('Incompatible receiver, ' + TYPE + ' required');
    } return state;
  };
};

if (NATIVE_WEAK_MAP) {
  var store = new WeakMap();
  var wmget = store.get;
  var wmhas = store.has;
  var wmset = store.set;
  set = function (it, metadata) {
    wmset.call(store, it, metadata);
    return metadata;
  };
  get = function (it) {
    return wmget.call(store, it) || {};
  };
  has = function (it) {
    return wmhas.call(store, it);
  };
} else {
  var STATE = sharedKey('state');
  hiddenKeys[STATE] = true;
  set = function (it, metadata) {
    createNonEnumerableProperty(it, STATE, metadata);
    return metadata;
  };
  get = function (it) {
    return objectHas(it, STATE) ? it[STATE] : {};
  };
  has = function (it) {
    return objectHas(it, STATE);
  };
}

module.exports = {
  set: set,
  get: get,
  has: has,
  enforce: enforce,
  getterFor: getterFor
};


/***/ }),

/***/ "6bb8":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "6d61":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var $ = __webpack_require__("23e7");
var global = __webpack_require__("da84");
var isForced = __webpack_require__("94ca");
var redefine = __webpack_require__("6eeb");
var InternalMetadataModule = __webpack_require__("f183");
var iterate = __webpack_require__("2266");
var anInstance = __webpack_require__("19aa");
var isObject = __webpack_require__("861d");
var fails = __webpack_require__("d039");
var checkCorrectnessOfIteration = __webpack_require__("1c7e");
var setToStringTag = __webpack_require__("d44e");
var inheritIfRequired = __webpack_require__("7156");

module.exports = function (CONSTRUCTOR_NAME, wrapper, common) {
  var IS_MAP = CONSTRUCTOR_NAME.indexOf('Map') !== -1;
  var IS_WEAK = CONSTRUCTOR_NAME.indexOf('Weak') !== -1;
  var ADDER = IS_MAP ? 'set' : 'add';
  var NativeConstructor = global[CONSTRUCTOR_NAME];
  var NativePrototype = NativeConstructor && NativeConstructor.prototype;
  var Constructor = NativeConstructor;
  var exported = {};

  var fixMethod = function (KEY) {
    var nativeMethod = NativePrototype[KEY];
    redefine(NativePrototype, KEY,
      KEY == 'add' ? function add(value) {
        nativeMethod.call(this, value === 0 ? 0 : value);
        return this;
      } : KEY == 'delete' ? function (key) {
        return IS_WEAK && !isObject(key) ? false : nativeMethod.call(this, key === 0 ? 0 : key);
      } : KEY == 'get' ? function get(key) {
        return IS_WEAK && !isObject(key) ? undefined : nativeMethod.call(this, key === 0 ? 0 : key);
      } : KEY == 'has' ? function has(key) {
        return IS_WEAK && !isObject(key) ? false : nativeMethod.call(this, key === 0 ? 0 : key);
      } : function set(key, value) {
        nativeMethod.call(this, key === 0 ? 0 : key, value);
        return this;
      }
    );
  };

  // eslint-disable-next-line max-len
  if (isForced(CONSTRUCTOR_NAME, typeof NativeConstructor != 'function' || !(IS_WEAK || NativePrototype.forEach && !fails(function () {
    new NativeConstructor().entries().next();
  })))) {
    // create collection constructor
    Constructor = common.getConstructor(wrapper, CONSTRUCTOR_NAME, IS_MAP, ADDER);
    InternalMetadataModule.REQUIRED = true;
  } else if (isForced(CONSTRUCTOR_NAME, true)) {
    var instance = new Constructor();
    // early implementations not supports chaining
    var HASNT_CHAINING = instance[ADDER](IS_WEAK ? {} : -0, 1) != instance;
    // V8 ~ Chromium 40- weak-collections throws on primitives, but should return false
    var THROWS_ON_PRIMITIVES = fails(function () { instance.has(1); });
    // most early implementations doesn't supports iterables, most modern - not close it correctly
    // eslint-disable-next-line no-new
    var ACCEPT_ITERABLES = checkCorrectnessOfIteration(function (iterable) { new NativeConstructor(iterable); });
    // for early implementations -0 and +0 not the same
    var BUGGY_ZERO = !IS_WEAK && fails(function () {
      // V8 ~ Chromium 42- fails only with 5+ elements
      var $instance = new NativeConstructor();
      var index = 5;
      while (index--) $instance[ADDER](index, index);
      return !$instance.has(-0);
    });

    if (!ACCEPT_ITERABLES) {
      Constructor = wrapper(function (dummy, iterable) {
        anInstance(dummy, Constructor, CONSTRUCTOR_NAME);
        var that = inheritIfRequired(new NativeConstructor(), dummy, Constructor);
        if (iterable != undefined) iterate(iterable, that[ADDER], that, IS_MAP);
        return that;
      });
      Constructor.prototype = NativePrototype;
      NativePrototype.constructor = Constructor;
    }

    if (THROWS_ON_PRIMITIVES || BUGGY_ZERO) {
      fixMethod('delete');
      fixMethod('has');
      IS_MAP && fixMethod('get');
    }

    if (BUGGY_ZERO || HASNT_CHAINING) fixMethod(ADDER);

    // weak collections should not contains .clear method
    if (IS_WEAK && NativePrototype.clear) delete NativePrototype.clear;
  }

  exported[CONSTRUCTOR_NAME] = Constructor;
  $({ global: true, forced: Constructor != NativeConstructor }, exported);

  setToStringTag(Constructor, CONSTRUCTOR_NAME);

  if (!IS_WEAK) common.setStrong(Constructor, CONSTRUCTOR_NAME, IS_MAP);

  return Constructor;
};


/***/ }),

/***/ "6db7":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


// http://www.ecma-international.org/ecma-262/6.0/#sec-object.is

var numberIsNaN = function (value) {
	return value !== value;
};

module.exports = function is(a, b) {
	if (a === 0 && b === 0) {
		return 1 / a === 1 / b;
	}
	if (a === b) {
		return true;
	}
	if (numberIsNaN(a) && numberIsNaN(b)) {
		return true;
	}
	return false;
};



/***/ }),

/***/ "6eeb":
/***/ (function(module, exports, __webpack_require__) {

var global = __webpack_require__("da84");
var createNonEnumerableProperty = __webpack_require__("9112");
var has = __webpack_require__("5135");
var setGlobal = __webpack_require__("ce4e");
var inspectSource = __webpack_require__("8925");
var InternalStateModule = __webpack_require__("69f3");

var getInternalState = InternalStateModule.get;
var enforceInternalState = InternalStateModule.enforce;
var TEMPLATE = String(String).split('String');

(module.exports = function (O, key, value, options) {
  var unsafe = options ? !!options.unsafe : false;
  var simple = options ? !!options.enumerable : false;
  var noTargetGet = options ? !!options.noTargetGet : false;
  if (typeof value == 'function') {
    if (typeof key == 'string' && !has(value, 'name')) createNonEnumerableProperty(value, 'name', key);
    enforceInternalState(value).source = TEMPLATE.join(typeof key == 'string' ? key : '');
  }
  if (O === global) {
    if (simple) O[key] = value;
    else setGlobal(key, value);
    return;
  } else if (!unsafe) {
    delete O[key];
  } else if (!noTargetGet && O[key]) {
    simple = true;
  }
  if (simple) O[key] = value;
  else createNonEnumerableProperty(O, key, value);
// add fake Function#toString for correct work wrapped methods / constructors with methods like LoDash isNative
})(Function.prototype, 'toString', function toString() {
  return typeof this == 'function' && getInternalState(this).source || inspectSource(this);
});


/***/ }),

/***/ "6f53":
/***/ (function(module, exports, __webpack_require__) {

var DESCRIPTORS = __webpack_require__("83ab");
var objectKeys = __webpack_require__("df75");
var toIndexedObject = __webpack_require__("fc6a");
var propertyIsEnumerable = __webpack_require__("d1e7").f;

// `Object.{ entries, values }` methods implementation
var createMethod = function (TO_ENTRIES) {
  return function (it) {
    var O = toIndexedObject(it);
    var keys = objectKeys(O);
    var length = keys.length;
    var i = 0;
    var result = [];
    var key;
    while (length > i) {
      key = keys[i++];
      if (!DESCRIPTORS || propertyIsEnumerable.call(O, key)) {
        result.push(TO_ENTRIES ? [key, O[key]] : O[key]);
      }
    }
    return result;
  };
};

module.exports = {
  // `Object.entries` method
  // https://tc39.github.io/ecma262/#sec-object.entries
  entries: createMethod(true),
  // `Object.values` method
  // https://tc39.github.io/ecma262/#sec-object.values
  values: createMethod(false)
};


/***/ }),

/***/ "7039":
/***/ (function(module, exports, __webpack_require__) {

var $ = __webpack_require__("23e7");
var fails = __webpack_require__("d039");
var nativeGetOwnPropertyNames = __webpack_require__("057f").f;

var FAILS_ON_PRIMITIVES = fails(function () { return !Object.getOwnPropertyNames(1); });

// `Object.getOwnPropertyNames` method
// https://tc39.github.io/ecma262/#sec-object.getownpropertynames
$({ target: 'Object', stat: true, forced: FAILS_ON_PRIMITIVES }, {
  getOwnPropertyNames: nativeGetOwnPropertyNames
});


/***/ }),

/***/ "7156":
/***/ (function(module, exports, __webpack_require__) {

var isObject = __webpack_require__("861d");
var setPrototypeOf = __webpack_require__("d2bb");

// makes subclassing work correct for wrapped built-ins
module.exports = function ($this, dummy, Wrapper) {
  var NewTarget, NewTargetPrototype;
  if (
    // it can work only with native `setPrototypeOf`
    setPrototypeOf &&
    // we haven't completely correct pre-ES6 way for getting `new.target`, so use this
    typeof (NewTarget = dummy.constructor) == 'function' &&
    NewTarget !== Wrapper &&
    isObject(NewTargetPrototype = NewTarget.prototype) &&
    NewTargetPrototype !== Wrapper.prototype
  ) setPrototypeOf($this, NewTargetPrototype);
  return $this;
};


/***/ }),

/***/ "7418":
/***/ (function(module, exports) {

exports.f = Object.getOwnPropertySymbols;


/***/ }),

/***/ "746f":
/***/ (function(module, exports, __webpack_require__) {

var path = __webpack_require__("428f");
var has = __webpack_require__("5135");
var wrappedWellKnownSymbolModule = __webpack_require__("e538");
var defineProperty = __webpack_require__("9bf2").f;

module.exports = function (NAME) {
  var Symbol = path.Symbol || (path.Symbol = {});
  if (!has(Symbol, NAME)) defineProperty(Symbol, NAME, {
    value: wrappedWellKnownSymbolModule.f(NAME)
  });
};


/***/ }),

/***/ "7600":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "7839":
/***/ (function(module, exports) {

// IE8- don't enum bug keys
module.exports = [
  'constructor',
  'hasOwnProperty',
  'isPrototypeOf',
  'propertyIsEnumerable',
  'toLocaleString',
  'toString',
  'valueOf'
];


/***/ }),

/***/ "7b0b":
/***/ (function(module, exports, __webpack_require__) {

var requireObjectCoercible = __webpack_require__("1d80");

// `ToObject` abstract operation
// https://tc39.github.io/ecma262/#sec-toobject
module.exports = function (argument) {
  return Object(requireObjectCoercible(argument));
};


/***/ }),

/***/ "7c73":
/***/ (function(module, exports, __webpack_require__) {

var anObject = __webpack_require__("825a");
var defineProperties = __webpack_require__("37e8");
var enumBugKeys = __webpack_require__("7839");
var hiddenKeys = __webpack_require__("d012");
var html = __webpack_require__("1be4");
var documentCreateElement = __webpack_require__("cc12");
var sharedKey = __webpack_require__("f772");

var GT = '>';
var LT = '<';
var PROTOTYPE = 'prototype';
var SCRIPT = 'script';
var IE_PROTO = sharedKey('IE_PROTO');

var EmptyConstructor = function () { /* empty */ };

var scriptTag = function (content) {
  return LT + SCRIPT + GT + content + LT + '/' + SCRIPT + GT;
};

// Create object with fake `null` prototype: use ActiveX Object with cleared prototype
var NullProtoObjectViaActiveX = function (activeXDocument) {
  activeXDocument.write(scriptTag(''));
  activeXDocument.close();
  var temp = activeXDocument.parentWindow.Object;
  activeXDocument = null; // avoid memory leak
  return temp;
};

// Create object with fake `null` prototype: use iframe Object with cleared prototype
var NullProtoObjectViaIFrame = function () {
  // Thrash, waste and sodomy: IE GC bug
  var iframe = documentCreateElement('iframe');
  var JS = 'java' + SCRIPT + ':';
  var iframeDocument;
  iframe.style.display = 'none';
  html.appendChild(iframe);
  // https://github.com/zloirock/core-js/issues/475
  iframe.src = String(JS);
  iframeDocument = iframe.contentWindow.document;
  iframeDocument.open();
  iframeDocument.write(scriptTag('document.F=Object'));
  iframeDocument.close();
  return iframeDocument.F;
};

// Check for document.domain and active x support
// No need to use active x approach when document.domain is not set
// see https://github.com/es-shims/es5-shim/issues/150
// variation of https://github.com/kitcambridge/es5-shim/commit/4f738ac066346
// avoid IE GC bug
var activeXDocument;
var NullProtoObject = function () {
  try {
    /* global ActiveXObject */
    activeXDocument = document.domain && new ActiveXObject('htmlfile');
  } catch (error) { /* ignore */ }
  NullProtoObject = activeXDocument ? NullProtoObjectViaActiveX(activeXDocument) : NullProtoObjectViaIFrame();
  var length = enumBugKeys.length;
  while (length--) delete NullProtoObject[PROTOTYPE][enumBugKeys[length]];
  return NullProtoObject();
};

hiddenKeys[IE_PROTO] = true;

// `Object.create` method
// https://tc39.github.io/ecma262/#sec-object.create
module.exports = Object.create || function create(O, Properties) {
  var result;
  if (O !== null) {
    EmptyConstructor[PROTOTYPE] = anObject(O);
    result = new EmptyConstructor();
    EmptyConstructor[PROTOTYPE] = null;
    // add "__proto__" for Object.getPrototypeOf polyfill
    result[IE_PROTO] = O;
  } else result = NullProtoObject();
  return Properties === undefined ? result : defineProperties(result, Properties);
};


/***/ }),

/***/ "7dd0":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var $ = __webpack_require__("23e7");
var createIteratorConstructor = __webpack_require__("9ed3");
var getPrototypeOf = __webpack_require__("e163");
var setPrototypeOf = __webpack_require__("d2bb");
var setToStringTag = __webpack_require__("d44e");
var createNonEnumerableProperty = __webpack_require__("9112");
var redefine = __webpack_require__("6eeb");
var wellKnownSymbol = __webpack_require__("b622");
var IS_PURE = __webpack_require__("c430");
var Iterators = __webpack_require__("3f8c");
var IteratorsCore = __webpack_require__("ae93");

var IteratorPrototype = IteratorsCore.IteratorPrototype;
var BUGGY_SAFARI_ITERATORS = IteratorsCore.BUGGY_SAFARI_ITERATORS;
var ITERATOR = wellKnownSymbol('iterator');
var KEYS = 'keys';
var VALUES = 'values';
var ENTRIES = 'entries';

var returnThis = function () { return this; };

module.exports = function (Iterable, NAME, IteratorConstructor, next, DEFAULT, IS_SET, FORCED) {
  createIteratorConstructor(IteratorConstructor, NAME, next);

  var getIterationMethod = function (KIND) {
    if (KIND === DEFAULT && defaultIterator) return defaultIterator;
    if (!BUGGY_SAFARI_ITERATORS && KIND in IterablePrototype) return IterablePrototype[KIND];
    switch (KIND) {
      case KEYS: return function keys() { return new IteratorConstructor(this, KIND); };
      case VALUES: return function values() { return new IteratorConstructor(this, KIND); };
      case ENTRIES: return function entries() { return new IteratorConstructor(this, KIND); };
    } return function () { return new IteratorConstructor(this); };
  };

  var TO_STRING_TAG = NAME + ' Iterator';
  var INCORRECT_VALUES_NAME = false;
  var IterablePrototype = Iterable.prototype;
  var nativeIterator = IterablePrototype[ITERATOR]
    || IterablePrototype['@@iterator']
    || DEFAULT && IterablePrototype[DEFAULT];
  var defaultIterator = !BUGGY_SAFARI_ITERATORS && nativeIterator || getIterationMethod(DEFAULT);
  var anyNativeIterator = NAME == 'Array' ? IterablePrototype.entries || nativeIterator : nativeIterator;
  var CurrentIteratorPrototype, methods, KEY;

  // fix native
  if (anyNativeIterator) {
    CurrentIteratorPrototype = getPrototypeOf(anyNativeIterator.call(new Iterable()));
    if (IteratorPrototype !== Object.prototype && CurrentIteratorPrototype.next) {
      if (!IS_PURE && getPrototypeOf(CurrentIteratorPrototype) !== IteratorPrototype) {
        if (setPrototypeOf) {
          setPrototypeOf(CurrentIteratorPrototype, IteratorPrototype);
        } else if (typeof CurrentIteratorPrototype[ITERATOR] != 'function') {
          createNonEnumerableProperty(CurrentIteratorPrototype, ITERATOR, returnThis);
        }
      }
      // Set @@toStringTag to native iterators
      setToStringTag(CurrentIteratorPrototype, TO_STRING_TAG, true, true);
      if (IS_PURE) Iterators[TO_STRING_TAG] = returnThis;
    }
  }

  // fix Array#{values, @@iterator}.name in V8 / FF
  if (DEFAULT == VALUES && nativeIterator && nativeIterator.name !== VALUES) {
    INCORRECT_VALUES_NAME = true;
    defaultIterator = function values() { return nativeIterator.call(this); };
  }

  // define iterator
  if ((!IS_PURE || FORCED) && IterablePrototype[ITERATOR] !== defaultIterator) {
    createNonEnumerableProperty(IterablePrototype, ITERATOR, defaultIterator);
  }
  Iterators[NAME] = defaultIterator;

  // export additional methods
  if (DEFAULT) {
    methods = {
      values: getIterationMethod(VALUES),
      keys: IS_SET ? defaultIterator : getIterationMethod(KEYS),
      entries: getIterationMethod(ENTRIES)
    };
    if (FORCED) for (KEY in methods) {
      if (BUGGY_SAFARI_ITERATORS || INCORRECT_VALUES_NAME || !(KEY in IterablePrototype)) {
        redefine(IterablePrototype, KEY, methods[KEY]);
      }
    } else $({ target: NAME, proto: true, forced: BUGGY_SAFARI_ITERATORS || INCORRECT_VALUES_NAME }, methods);
  }

  return methods;
};


/***/ }),

/***/ "7f9a":
/***/ (function(module, exports, __webpack_require__) {

var global = __webpack_require__("da84");
var inspectSource = __webpack_require__("8925");

var WeakMap = global.WeakMap;

module.exports = typeof WeakMap === 'function' && /native code/.test(inspectSource(WeakMap));


/***/ }),

/***/ "7fae":
/***/ (function(module, exports, __webpack_require__) {

var objectKeys = __webpack_require__("d6c7");
var isArguments = __webpack_require__("e39c");
var is = __webpack_require__("6db7");
var isRegex = __webpack_require__("d8d8");
var flags = __webpack_require__("e710");
var isDate = __webpack_require__("0e65");

var getTime = Date.prototype.getTime;

function deepEqual(actual, expected, options) {
  var opts = options || {};

  // 7.1. All identical values are equivalent, as determined by ===.
  if (opts.strict ? is(actual, expected) : actual === expected) {
    return true;
  }

  // 7.3. Other pairs that do not both pass typeof value == 'object', equivalence is determined by ==.
  if (!actual || !expected || (typeof actual !== 'object' && typeof expected !== 'object')) {
    return opts.strict ? is(actual, expected) : actual == expected;
  }

  /*
   * 7.4. For all other Object pairs, including Array objects, equivalence is
   * determined by having the same number of owned properties (as verified
   * with Object.prototype.hasOwnProperty.call), the same set of keys
   * (although not necessarily the same order), equivalent values for every
   * corresponding key, and an identical 'prototype' property. Note: this
   * accounts for both named and indexed properties on Arrays.
   */
  // eslint-disable-next-line no-use-before-define
  return objEquiv(actual, expected, opts);
}

function isUndefinedOrNull(value) {
  return value === null || value === undefined;
}

function isBuffer(x) {
  if (!x || typeof x !== 'object' || typeof x.length !== 'number') {
    return false;
  }
  if (typeof x.copy !== 'function' || typeof x.slice !== 'function') {
    return false;
  }
  if (x.length > 0 && typeof x[0] !== 'number') {
    return false;
  }
  return true;
}

function objEquiv(a, b, opts) {
  /* eslint max-statements: [2, 50] */
  var i, key;
  if (typeof a !== typeof b) { return false; }
  if (isUndefinedOrNull(a) || isUndefinedOrNull(b)) { return false; }

  // an identical 'prototype' property.
  if (a.prototype !== b.prototype) { return false; }

  if (isArguments(a) !== isArguments(b)) { return false; }

  var aIsRegex = isRegex(a);
  var bIsRegex = isRegex(b);
  if (aIsRegex !== bIsRegex) { return false; }
  if (aIsRegex || bIsRegex) {
    return a.source === b.source && flags(a) === flags(b);
  }

  if (isDate(a) && isDate(b)) {
    return getTime.call(a) === getTime.call(b);
  }

  var aIsBuffer = isBuffer(a);
  var bIsBuffer = isBuffer(b);
  if (aIsBuffer !== bIsBuffer) { return false; }
  if (aIsBuffer || bIsBuffer) { // && would work too, because both are true or both false here
    if (a.length !== b.length) { return false; }
    for (i = 0; i < a.length; i++) {
      if (a[i] !== b[i]) { return false; }
    }
    return true;
  }

  if (typeof a !== typeof b) { return false; }

  try {
    var ka = objectKeys(a);
    var kb = objectKeys(b);
  } catch (e) { // happens when one is a string literal and the other isn't
    return false;
  }
  // having the same number of owned properties (keys incorporates hasOwnProperty)
  if (ka.length !== kb.length) { return false; }

  // the same set of keys (although not necessarily the same order),
  ka.sort();
  kb.sort();
  // ~~~cheap key test
  for (i = ka.length - 1; i >= 0; i--) {
    if (ka[i] != kb[i]) { return false; }
  }
  // equivalent values for every corresponding key, and ~~~possibly expensive deep test
  for (i = ka.length - 1; i >= 0; i--) {
    key = ka[i];
    if (!deepEqual(a[key], b[key], opts)) { return false; }
  }

  return true;
}

module.exports = deepEqual;


/***/ }),

/***/ "8055":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "825a":
/***/ (function(module, exports, __webpack_require__) {

var isObject = __webpack_require__("861d");

module.exports = function (it) {
  if (!isObject(it)) {
    throw TypeError(String(it) + ' is not an object');
  } return it;
};


/***/ }),

/***/ "83ab":
/***/ (function(module, exports, __webpack_require__) {

var fails = __webpack_require__("d039");

// Thank's IE8 for his funny defineProperty
module.exports = !fails(function () {
  return Object.defineProperty({}, 1, { get: function () { return 7; } })[1] != 7;
});


/***/ }),

/***/ "8418":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var toPrimitive = __webpack_require__("c04e");
var definePropertyModule = __webpack_require__("9bf2");
var createPropertyDescriptor = __webpack_require__("5c6c");

module.exports = function (object, key, value) {
  var propertyKey = toPrimitive(key);
  if (propertyKey in object) definePropertyModule.f(object, propertyKey, createPropertyDescriptor(0, value));
  else object[propertyKey] = value;
};


/***/ }),

/***/ "861d":
/***/ (function(module, exports) {

module.exports = function (it) {
  return typeof it === 'object' ? it !== null : typeof it === 'function';
};


/***/ }),

/***/ "87a9":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_PropertyLabel_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("4706");
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_PropertyLabel_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_PropertyLabel_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */
 /* unused harmony default export */ var _unused_webpack_default_export = (_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_PropertyLabel_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "8813":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "8925":
/***/ (function(module, exports, __webpack_require__) {

var store = __webpack_require__("c6cd");

var functionToString = Function.toString;

// this helper broken in `3.4.1-3.4.4`, so we can't use `shared` helper
if (typeof store.inspectSource != 'function') {
  store.inspectSource = function (it) {
    return functionToString.call(it);
  };
}

module.exports = store.inspectSource;


/***/ }),

/***/ "8985":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "8a79":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var $ = __webpack_require__("23e7");
var getOwnPropertyDescriptor = __webpack_require__("06cf").f;
var toLength = __webpack_require__("50c4");
var notARegExp = __webpack_require__("5a34");
var requireObjectCoercible = __webpack_require__("1d80");
var correctIsRegExpLogic = __webpack_require__("ab13");
var IS_PURE = __webpack_require__("c430");

var nativeEndsWith = ''.endsWith;
var min = Math.min;

var CORRECT_IS_REGEXP_LOGIC = correctIsRegExpLogic('endsWith');
// https://github.com/zloirock/core-js/pull/702
var MDN_POLYFILL_BUG = !IS_PURE && !CORRECT_IS_REGEXP_LOGIC && !!function () {
  var descriptor = getOwnPropertyDescriptor(String.prototype, 'endsWith');
  return descriptor && !descriptor.writable;
}();

// `String.prototype.endsWith` method
// https://tc39.github.io/ecma262/#sec-string.prototype.endswith
$({ target: 'String', proto: true, forced: !MDN_POLYFILL_BUG && !CORRECT_IS_REGEXP_LOGIC }, {
  endsWith: function endsWith(searchString /* , endPosition = @length */) {
    var that = String(requireObjectCoercible(this));
    notARegExp(searchString);
    var endPosition = arguments.length > 1 ? arguments[1] : undefined;
    var len = toLength(that.length);
    var end = endPosition === undefined ? len : min(toLength(endPosition), len);
    var search = String(searchString);
    return nativeEndsWith
      ? nativeEndsWith.call(that, search, end)
      : that.slice(end - search.length, end) === search;
  }
});


/***/ }),

/***/ "8aa5":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var charAt = __webpack_require__("6547").charAt;

// `AdvanceStringIndex` abstract operation
// https://tc39.github.io/ecma262/#sec-advancestringindex
module.exports = function (S, index, unicode) {
  return index + (unicode ? charAt(S, index).length : 1);
};


/***/ }),

/***/ "8bbf":
/***/ (function(module, exports) {

module.exports = require("vue2");

/***/ }),

/***/ "8c5b":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "90e3":
/***/ (function(module, exports) {

var id = 0;
var postfix = Math.random();

module.exports = function (key) {
  return 'Symbol(' + String(key === undefined ? '' : key) + ')_' + (++id + postfix).toString(36);
};


/***/ }),

/***/ "9112":
/***/ (function(module, exports, __webpack_require__) {

var DESCRIPTORS = __webpack_require__("83ab");
var definePropertyModule = __webpack_require__("9bf2");
var createPropertyDescriptor = __webpack_require__("5c6c");

module.exports = DESCRIPTORS ? function (object, key, value) {
  return definePropertyModule.f(object, key, createPropertyDescriptor(1, value));
} : function (object, key, value) {
  object[key] = value;
  return object;
};


/***/ }),

/***/ "9263":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var regexpFlags = __webpack_require__("ad6d");
var stickyHelpers = __webpack_require__("9f7f");

var nativeExec = RegExp.prototype.exec;
// This always refers to the native implementation, because the
// String#replace polyfill uses ./fix-regexp-well-known-symbol-logic.js,
// which loads this file before patching the method.
var nativeReplace = String.prototype.replace;

var patchedExec = nativeExec;

var UPDATES_LAST_INDEX_WRONG = (function () {
  var re1 = /a/;
  var re2 = /b*/g;
  nativeExec.call(re1, 'a');
  nativeExec.call(re2, 'a');
  return re1.lastIndex !== 0 || re2.lastIndex !== 0;
})();

var UNSUPPORTED_Y = stickyHelpers.UNSUPPORTED_Y || stickyHelpers.BROKEN_CARET;

// nonparticipating capturing group, copied from es5-shim's String#split patch.
var NPCG_INCLUDED = /()??/.exec('')[1] !== undefined;

var PATCH = UPDATES_LAST_INDEX_WRONG || NPCG_INCLUDED || UNSUPPORTED_Y;

if (PATCH) {
  patchedExec = function exec(str) {
    var re = this;
    var lastIndex, reCopy, match, i;
    var sticky = UNSUPPORTED_Y && re.sticky;
    var flags = regexpFlags.call(re);
    var source = re.source;
    var charsAdded = 0;
    var strCopy = str;

    if (sticky) {
      flags = flags.replace('y', '');
      if (flags.indexOf('g') === -1) {
        flags += 'g';
      }

      strCopy = String(str).slice(re.lastIndex);
      // Support anchored sticky behavior.
      if (re.lastIndex > 0 && (!re.multiline || re.multiline && str[re.lastIndex - 1] !== '\n')) {
        source = '(?: ' + source + ')';
        strCopy = ' ' + strCopy;
        charsAdded++;
      }
      // ^(? + rx + ) is needed, in combination with some str slicing, to
      // simulate the 'y' flag.
      reCopy = new RegExp('^(?:' + source + ')', flags);
    }

    if (NPCG_INCLUDED) {
      reCopy = new RegExp('^' + source + '$(?!\\s)', flags);
    }
    if (UPDATES_LAST_INDEX_WRONG) lastIndex = re.lastIndex;

    match = nativeExec.call(sticky ? reCopy : re, strCopy);

    if (sticky) {
      if (match) {
        match.input = match.input.slice(charsAdded);
        match[0] = match[0].slice(charsAdded);
        match.index = re.lastIndex;
        re.lastIndex += match[0].length;
      } else re.lastIndex = 0;
    } else if (UPDATES_LAST_INDEX_WRONG && match) {
      re.lastIndex = re.global ? match.index + match[0].length : lastIndex;
    }
    if (NPCG_INCLUDED && match && match.length > 1) {
      // Fix browsers whose `exec` methods don't consistently return `undefined`
      // for NPCG, like IE8. NOTE: This doesn' work for /(.?)?/
      nativeReplace.call(match[0], reCopy, function () {
        for (i = 1; i < arguments.length - 2; i++) {
          if (arguments[i] === undefined) match[i] = undefined;
        }
      });
    }

    return match;
  };
}

module.exports = patchedExec;


/***/ }),

/***/ "94ca":
/***/ (function(module, exports, __webpack_require__) {

var fails = __webpack_require__("d039");

var replacement = /#|\.prototype\./;

var isForced = function (feature, detection) {
  var value = data[normalize(feature)];
  return value == POLYFILL ? true
    : value == NATIVE ? false
    : typeof detection == 'function' ? fails(detection)
    : !!detection;
};

var normalize = isForced.normalize = function (string) {
  return String(string).replace(replacement, '.').toLowerCase();
};

var data = isForced.data = {};
var NATIVE = isForced.NATIVE = 'N';
var POLYFILL = isForced.POLYFILL = 'P';

module.exports = isForced;


/***/ }),

/***/ "9634":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "96cf":
/***/ (function(module, exports, __webpack_require__) {

/**
 * Copyright (c) 2014-present, Facebook, Inc.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

var runtime = (function (exports) {
  "use strict";

  var Op = Object.prototype;
  var hasOwn = Op.hasOwnProperty;
  var undefined; // More compressible than void 0.
  var $Symbol = typeof Symbol === "function" ? Symbol : {};
  var iteratorSymbol = $Symbol.iterator || "@@iterator";
  var asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator";
  var toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag";

  function wrap(innerFn, outerFn, self, tryLocsList) {
    // If outerFn provided and outerFn.prototype is a Generator, then outerFn.prototype instanceof Generator.
    var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator;
    var generator = Object.create(protoGenerator.prototype);
    var context = new Context(tryLocsList || []);

    // The ._invoke method unifies the implementations of the .next,
    // .throw, and .return methods.
    generator._invoke = makeInvokeMethod(innerFn, self, context);

    return generator;
  }
  exports.wrap = wrap;

  // Try/catch helper to minimize deoptimizations. Returns a completion
  // record like context.tryEntries[i].completion. This interface could
  // have been (and was previously) designed to take a closure to be
  // invoked without arguments, but in all the cases we care about we
  // already have an existing method we want to call, so there's no need
  // to create a new function object. We can even get away with assuming
  // the method takes exactly one argument, since that happens to be true
  // in every case, so we don't have to touch the arguments object. The
  // only additional allocation required is the completion record, which
  // has a stable shape and so hopefully should be cheap to allocate.
  function tryCatch(fn, obj, arg) {
    try {
      return { type: "normal", arg: fn.call(obj, arg) };
    } catch (err) {
      return { type: "throw", arg: err };
    }
  }

  var GenStateSuspendedStart = "suspendedStart";
  var GenStateSuspendedYield = "suspendedYield";
  var GenStateExecuting = "executing";
  var GenStateCompleted = "completed";

  // Returning this object from the innerFn has the same effect as
  // breaking out of the dispatch switch statement.
  var ContinueSentinel = {};

  // Dummy constructor functions that we use as the .constructor and
  // .constructor.prototype properties for functions that return Generator
  // objects. For full spec compliance, you may wish to configure your
  // minifier not to mangle the names of these two functions.
  function Generator() {}
  function GeneratorFunction() {}
  function GeneratorFunctionPrototype() {}

  // This is a polyfill for %IteratorPrototype% for environments that
  // don't natively support it.
  var IteratorPrototype = {};
  IteratorPrototype[iteratorSymbol] = function () {
    return this;
  };

  var getProto = Object.getPrototypeOf;
  var NativeIteratorPrototype = getProto && getProto(getProto(values([])));
  if (NativeIteratorPrototype &&
      NativeIteratorPrototype !== Op &&
      hasOwn.call(NativeIteratorPrototype, iteratorSymbol)) {
    // This environment has a native %IteratorPrototype%; use it instead
    // of the polyfill.
    IteratorPrototype = NativeIteratorPrototype;
  }

  var Gp = GeneratorFunctionPrototype.prototype =
    Generator.prototype = Object.create(IteratorPrototype);
  GeneratorFunction.prototype = Gp.constructor = GeneratorFunctionPrototype;
  GeneratorFunctionPrototype.constructor = GeneratorFunction;
  GeneratorFunctionPrototype[toStringTagSymbol] =
    GeneratorFunction.displayName = "GeneratorFunction";

  // Helper for defining the .next, .throw, and .return methods of the
  // Iterator interface in terms of a single ._invoke method.
  function defineIteratorMethods(prototype) {
    ["next", "throw", "return"].forEach(function(method) {
      prototype[method] = function(arg) {
        return this._invoke(method, arg);
      };
    });
  }

  exports.isGeneratorFunction = function(genFun) {
    var ctor = typeof genFun === "function" && genFun.constructor;
    return ctor
      ? ctor === GeneratorFunction ||
        // For the native GeneratorFunction constructor, the best we can
        // do is to check its .name property.
        (ctor.displayName || ctor.name) === "GeneratorFunction"
      : false;
  };

  exports.mark = function(genFun) {
    if (Object.setPrototypeOf) {
      Object.setPrototypeOf(genFun, GeneratorFunctionPrototype);
    } else {
      genFun.__proto__ = GeneratorFunctionPrototype;
      if (!(toStringTagSymbol in genFun)) {
        genFun[toStringTagSymbol] = "GeneratorFunction";
      }
    }
    genFun.prototype = Object.create(Gp);
    return genFun;
  };

  // Within the body of any async function, `await x` is transformed to
  // `yield regeneratorRuntime.awrap(x)`, so that the runtime can test
  // `hasOwn.call(value, "__await")` to determine if the yielded value is
  // meant to be awaited.
  exports.awrap = function(arg) {
    return { __await: arg };
  };

  function AsyncIterator(generator) {
    function invoke(method, arg, resolve, reject) {
      var record = tryCatch(generator[method], generator, arg);
      if (record.type === "throw") {
        reject(record.arg);
      } else {
        var result = record.arg;
        var value = result.value;
        if (value &&
            typeof value === "object" &&
            hasOwn.call(value, "__await")) {
          return Promise.resolve(value.__await).then(function(value) {
            invoke("next", value, resolve, reject);
          }, function(err) {
            invoke("throw", err, resolve, reject);
          });
        }

        return Promise.resolve(value).then(function(unwrapped) {
          // When a yielded Promise is resolved, its final value becomes
          // the .value of the Promise<{value,done}> result for the
          // current iteration.
          result.value = unwrapped;
          resolve(result);
        }, function(error) {
          // If a rejected Promise was yielded, throw the rejection back
          // into the async generator function so it can be handled there.
          return invoke("throw", error, resolve, reject);
        });
      }
    }

    var previousPromise;

    function enqueue(method, arg) {
      function callInvokeWithMethodAndArg() {
        return new Promise(function(resolve, reject) {
          invoke(method, arg, resolve, reject);
        });
      }

      return previousPromise =
        // If enqueue has been called before, then we want to wait until
        // all previous Promises have been resolved before calling invoke,
        // so that results are always delivered in the correct order. If
        // enqueue has not been called before, then it is important to
        // call invoke immediately, without waiting on a callback to fire,
        // so that the async generator function has the opportunity to do
        // any necessary setup in a predictable way. This predictability
        // is why the Promise constructor synchronously invokes its
        // executor callback, and why async functions synchronously
        // execute code before the first await. Since we implement simple
        // async functions in terms of async generators, it is especially
        // important to get this right, even though it requires care.
        previousPromise ? previousPromise.then(
          callInvokeWithMethodAndArg,
          // Avoid propagating failures to Promises returned by later
          // invocations of the iterator.
          callInvokeWithMethodAndArg
        ) : callInvokeWithMethodAndArg();
    }

    // Define the unified helper method that is used to implement .next,
    // .throw, and .return (see defineIteratorMethods).
    this._invoke = enqueue;
  }

  defineIteratorMethods(AsyncIterator.prototype);
  AsyncIterator.prototype[asyncIteratorSymbol] = function () {
    return this;
  };
  exports.AsyncIterator = AsyncIterator;

  // Note that simple async functions are implemented on top of
  // AsyncIterator objects; they just return a Promise for the value of
  // the final result produced by the iterator.
  exports.async = function(innerFn, outerFn, self, tryLocsList) {
    var iter = new AsyncIterator(
      wrap(innerFn, outerFn, self, tryLocsList)
    );

    return exports.isGeneratorFunction(outerFn)
      ? iter // If outerFn is a generator, return the full iterator.
      : iter.next().then(function(result) {
          return result.done ? result.value : iter.next();
        });
  };

  function makeInvokeMethod(innerFn, self, context) {
    var state = GenStateSuspendedStart;

    return function invoke(method, arg) {
      if (state === GenStateExecuting) {
        throw new Error("Generator is already running");
      }

      if (state === GenStateCompleted) {
        if (method === "throw") {
          throw arg;
        }

        // Be forgiving, per 25.3.3.3.3 of the spec:
        // https://people.mozilla.org/~jorendorff/es6-draft.html#sec-generatorresume
        return doneResult();
      }

      context.method = method;
      context.arg = arg;

      while (true) {
        var delegate = context.delegate;
        if (delegate) {
          var delegateResult = maybeInvokeDelegate(delegate, context);
          if (delegateResult) {
            if (delegateResult === ContinueSentinel) continue;
            return delegateResult;
          }
        }

        if (context.method === "next") {
          // Setting context._sent for legacy support of Babel's
          // function.sent implementation.
          context.sent = context._sent = context.arg;

        } else if (context.method === "throw") {
          if (state === GenStateSuspendedStart) {
            state = GenStateCompleted;
            throw context.arg;
          }

          context.dispatchException(context.arg);

        } else if (context.method === "return") {
          context.abrupt("return", context.arg);
        }

        state = GenStateExecuting;

        var record = tryCatch(innerFn, self, context);
        if (record.type === "normal") {
          // If an exception is thrown from innerFn, we leave state ===
          // GenStateExecuting and loop back for another invocation.
          state = context.done
            ? GenStateCompleted
            : GenStateSuspendedYield;

          if (record.arg === ContinueSentinel) {
            continue;
          }

          return {
            value: record.arg,
            done: context.done
          };

        } else if (record.type === "throw") {
          state = GenStateCompleted;
          // Dispatch the exception by looping back around to the
          // context.dispatchException(context.arg) call above.
          context.method = "throw";
          context.arg = record.arg;
        }
      }
    };
  }

  // Call delegate.iterator[context.method](context.arg) and handle the
  // result, either by returning a { value, done } result from the
  // delegate iterator, or by modifying context.method and context.arg,
  // setting context.delegate to null, and returning the ContinueSentinel.
  function maybeInvokeDelegate(delegate, context) {
    var method = delegate.iterator[context.method];
    if (method === undefined) {
      // A .throw or .return when the delegate iterator has no .throw
      // method always terminates the yield* loop.
      context.delegate = null;

      if (context.method === "throw") {
        // Note: ["return"] must be used for ES3 parsing compatibility.
        if (delegate.iterator["return"]) {
          // If the delegate iterator has a return method, give it a
          // chance to clean up.
          context.method = "return";
          context.arg = undefined;
          maybeInvokeDelegate(delegate, context);

          if (context.method === "throw") {
            // If maybeInvokeDelegate(context) changed context.method from
            // "return" to "throw", let that override the TypeError below.
            return ContinueSentinel;
          }
        }

        context.method = "throw";
        context.arg = new TypeError(
          "The iterator does not provide a 'throw' method");
      }

      return ContinueSentinel;
    }

    var record = tryCatch(method, delegate.iterator, context.arg);

    if (record.type === "throw") {
      context.method = "throw";
      context.arg = record.arg;
      context.delegate = null;
      return ContinueSentinel;
    }

    var info = record.arg;

    if (! info) {
      context.method = "throw";
      context.arg = new TypeError("iterator result is not an object");
      context.delegate = null;
      return ContinueSentinel;
    }

    if (info.done) {
      // Assign the result of the finished delegate to the temporary
      // variable specified by delegate.resultName (see delegateYield).
      context[delegate.resultName] = info.value;

      // Resume execution at the desired location (see delegateYield).
      context.next = delegate.nextLoc;

      // If context.method was "throw" but the delegate handled the
      // exception, let the outer generator proceed normally. If
      // context.method was "next", forget context.arg since it has been
      // "consumed" by the delegate iterator. If context.method was
      // "return", allow the original .return call to continue in the
      // outer generator.
      if (context.method !== "return") {
        context.method = "next";
        context.arg = undefined;
      }

    } else {
      // Re-yield the result returned by the delegate method.
      return info;
    }

    // The delegate iterator is finished, so forget it and continue with
    // the outer generator.
    context.delegate = null;
    return ContinueSentinel;
  }

  // Define Generator.prototype.{next,throw,return} in terms of the
  // unified ._invoke helper method.
  defineIteratorMethods(Gp);

  Gp[toStringTagSymbol] = "Generator";

  // A Generator should always return itself as the iterator object when the
  // @@iterator function is called on it. Some browsers' implementations of the
  // iterator prototype chain incorrectly implement this, causing the Generator
  // object to not be returned from this call. This ensures that doesn't happen.
  // See https://github.com/facebook/regenerator/issues/274 for more details.
  Gp[iteratorSymbol] = function() {
    return this;
  };

  Gp.toString = function() {
    return "[object Generator]";
  };

  function pushTryEntry(locs) {
    var entry = { tryLoc: locs[0] };

    if (1 in locs) {
      entry.catchLoc = locs[1];
    }

    if (2 in locs) {
      entry.finallyLoc = locs[2];
      entry.afterLoc = locs[3];
    }

    this.tryEntries.push(entry);
  }

  function resetTryEntry(entry) {
    var record = entry.completion || {};
    record.type = "normal";
    delete record.arg;
    entry.completion = record;
  }

  function Context(tryLocsList) {
    // The root entry object (effectively a try statement without a catch
    // or a finally block) gives us a place to store values thrown from
    // locations where there is no enclosing try statement.
    this.tryEntries = [{ tryLoc: "root" }];
    tryLocsList.forEach(pushTryEntry, this);
    this.reset(true);
  }

  exports.keys = function(object) {
    var keys = [];
    for (var key in object) {
      keys.push(key);
    }
    keys.reverse();

    // Rather than returning an object with a next method, we keep
    // things simple and return the next function itself.
    return function next() {
      while (keys.length) {
        var key = keys.pop();
        if (key in object) {
          next.value = key;
          next.done = false;
          return next;
        }
      }

      // To avoid creating an additional object, we just hang the .value
      // and .done properties off the next function object itself. This
      // also ensures that the minifier will not anonymize the function.
      next.done = true;
      return next;
    };
  };

  function values(iterable) {
    if (iterable) {
      var iteratorMethod = iterable[iteratorSymbol];
      if (iteratorMethod) {
        return iteratorMethod.call(iterable);
      }

      if (typeof iterable.next === "function") {
        return iterable;
      }

      if (!isNaN(iterable.length)) {
        var i = -1, next = function next() {
          while (++i < iterable.length) {
            if (hasOwn.call(iterable, i)) {
              next.value = iterable[i];
              next.done = false;
              return next;
            }
          }

          next.value = undefined;
          next.done = true;

          return next;
        };

        return next.next = next;
      }
    }

    // Return an iterator with no values.
    return { next: doneResult };
  }
  exports.values = values;

  function doneResult() {
    return { value: undefined, done: true };
  }

  Context.prototype = {
    constructor: Context,

    reset: function(skipTempReset) {
      this.prev = 0;
      this.next = 0;
      // Resetting context._sent for legacy support of Babel's
      // function.sent implementation.
      this.sent = this._sent = undefined;
      this.done = false;
      this.delegate = null;

      this.method = "next";
      this.arg = undefined;

      this.tryEntries.forEach(resetTryEntry);

      if (!skipTempReset) {
        for (var name in this) {
          // Not sure about the optimal order of these conditions:
          if (name.charAt(0) === "t" &&
              hasOwn.call(this, name) &&
              !isNaN(+name.slice(1))) {
            this[name] = undefined;
          }
        }
      }
    },

    stop: function() {
      this.done = true;

      var rootEntry = this.tryEntries[0];
      var rootRecord = rootEntry.completion;
      if (rootRecord.type === "throw") {
        throw rootRecord.arg;
      }

      return this.rval;
    },

    dispatchException: function(exception) {
      if (this.done) {
        throw exception;
      }

      var context = this;
      function handle(loc, caught) {
        record.type = "throw";
        record.arg = exception;
        context.next = loc;

        if (caught) {
          // If the dispatched exception was caught by a catch block,
          // then let that catch block handle the exception normally.
          context.method = "next";
          context.arg = undefined;
        }

        return !! caught;
      }

      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i];
        var record = entry.completion;

        if (entry.tryLoc === "root") {
          // Exception thrown outside of any try block that could handle
          // it, so set the completion value of the entire function to
          // throw the exception.
          return handle("end");
        }

        if (entry.tryLoc <= this.prev) {
          var hasCatch = hasOwn.call(entry, "catchLoc");
          var hasFinally = hasOwn.call(entry, "finallyLoc");

          if (hasCatch && hasFinally) {
            if (this.prev < entry.catchLoc) {
              return handle(entry.catchLoc, true);
            } else if (this.prev < entry.finallyLoc) {
              return handle(entry.finallyLoc);
            }

          } else if (hasCatch) {
            if (this.prev < entry.catchLoc) {
              return handle(entry.catchLoc, true);
            }

          } else if (hasFinally) {
            if (this.prev < entry.finallyLoc) {
              return handle(entry.finallyLoc);
            }

          } else {
            throw new Error("try statement without catch or finally");
          }
        }
      }
    },

    abrupt: function(type, arg) {
      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i];
        if (entry.tryLoc <= this.prev &&
            hasOwn.call(entry, "finallyLoc") &&
            this.prev < entry.finallyLoc) {
          var finallyEntry = entry;
          break;
        }
      }

      if (finallyEntry &&
          (type === "break" ||
           type === "continue") &&
          finallyEntry.tryLoc <= arg &&
          arg <= finallyEntry.finallyLoc) {
        // Ignore the finally entry if control is not jumping to a
        // location outside the try/catch block.
        finallyEntry = null;
      }

      var record = finallyEntry ? finallyEntry.completion : {};
      record.type = type;
      record.arg = arg;

      if (finallyEntry) {
        this.method = "next";
        this.next = finallyEntry.finallyLoc;
        return ContinueSentinel;
      }

      return this.complete(record);
    },

    complete: function(record, afterLoc) {
      if (record.type === "throw") {
        throw record.arg;
      }

      if (record.type === "break" ||
          record.type === "continue") {
        this.next = record.arg;
      } else if (record.type === "return") {
        this.rval = this.arg = record.arg;
        this.method = "return";
        this.next = "end";
      } else if (record.type === "normal" && afterLoc) {
        this.next = afterLoc;
      }

      return ContinueSentinel;
    },

    finish: function(finallyLoc) {
      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i];
        if (entry.finallyLoc === finallyLoc) {
          this.complete(entry.completion, entry.afterLoc);
          resetTryEntry(entry);
          return ContinueSentinel;
        }
      }
    },

    "catch": function(tryLoc) {
      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i];
        if (entry.tryLoc === tryLoc) {
          var record = entry.completion;
          if (record.type === "throw") {
            var thrown = record.arg;
            resetTryEntry(entry);
          }
          return thrown;
        }
      }

      // The context.catch method must only be called with a location
      // argument that corresponds to a known catch block.
      throw new Error("illegal catch attempt");
    },

    delegateYield: function(iterable, resultName, nextLoc) {
      this.delegate = {
        iterator: values(iterable),
        resultName: resultName,
        nextLoc: nextLoc
      };

      if (this.method === "next") {
        // Deliberately forget the last sent value so that we don't
        // accidentally pass it on to the delegate.
        this.arg = undefined;
      }

      return ContinueSentinel;
    }
  };

  // Regardless of whether this script is executing as a CommonJS module
  // or not, return the runtime object so that we can declare the variable
  // regeneratorRuntime in the outer scope, which allows this module to be
  // injected easily by `bin/regenerator --include-runtime script.js`.
  return exports;

}(
  // If this script is executing as a CommonJS module, use module.exports
  // as the regeneratorRuntime namespace. Otherwise create a new empty
  // object. Either way, the resulting object will be used to initialize
  // the regeneratorRuntime variable at the top of this file.
   true ? module.exports : undefined
));

try {
  regeneratorRuntime = runtime;
} catch (accidentalStrictMode) {
  // This module should not be running in strict mode, so the above
  // assignment should always work unless something is misconfigured. Just
  // in case runtime.js accidentally runs in strict mode, we can escape
  // strict mode using a global Function call. This could conceivably fail
  // if a Content Security Policy forbids using Function, but in that case
  // the proper solution is to fix the accidental strict mode problem. If
  // you've misconfigured your bundler to force strict mode and applied a
  // CSP to forbid Function, and you're not willing to fix either of those
  // problems, please detail your unique predicament in a GitHub issue.
  Function("r", "regeneratorRuntime = r")(runtime);
}


/***/ }),

/***/ "99af":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var $ = __webpack_require__("23e7");
var fails = __webpack_require__("d039");
var isArray = __webpack_require__("e8b5");
var isObject = __webpack_require__("861d");
var toObject = __webpack_require__("7b0b");
var toLength = __webpack_require__("50c4");
var createProperty = __webpack_require__("8418");
var arraySpeciesCreate = __webpack_require__("65f0");
var arrayMethodHasSpeciesSupport = __webpack_require__("1dde");
var wellKnownSymbol = __webpack_require__("b622");
var V8_VERSION = __webpack_require__("2d00");

var IS_CONCAT_SPREADABLE = wellKnownSymbol('isConcatSpreadable');
var MAX_SAFE_INTEGER = 0x1FFFFFFFFFFFFF;
var MAXIMUM_ALLOWED_INDEX_EXCEEDED = 'Maximum allowed index exceeded';

// We can't use this feature detection in V8 since it causes
// deoptimization and serious performance degradation
// https://github.com/zloirock/core-js/issues/679
var IS_CONCAT_SPREADABLE_SUPPORT = V8_VERSION >= 51 || !fails(function () {
  var array = [];
  array[IS_CONCAT_SPREADABLE] = false;
  return array.concat()[0] !== array;
});

var SPECIES_SUPPORT = arrayMethodHasSpeciesSupport('concat');

var isConcatSpreadable = function (O) {
  if (!isObject(O)) return false;
  var spreadable = O[IS_CONCAT_SPREADABLE];
  return spreadable !== undefined ? !!spreadable : isArray(O);
};

var FORCED = !IS_CONCAT_SPREADABLE_SUPPORT || !SPECIES_SUPPORT;

// `Array.prototype.concat` method
// https://tc39.github.io/ecma262/#sec-array.prototype.concat
// with adding support of @@isConcatSpreadable and @@species
$({ target: 'Array', proto: true, forced: FORCED }, {
  concat: function concat(arg) { // eslint-disable-line no-unused-vars
    var O = toObject(this);
    var A = arraySpeciesCreate(O, 0);
    var n = 0;
    var i, k, length, len, E;
    for (i = -1, length = arguments.length; i < length; i++) {
      E = i === -1 ? O : arguments[i];
      if (isConcatSpreadable(E)) {
        len = toLength(E.length);
        if (n + len > MAX_SAFE_INTEGER) throw TypeError(MAXIMUM_ALLOWED_INDEX_EXCEEDED);
        for (k = 0; k < len; k++, n++) if (k in E) createProperty(A, n, E[k]);
      } else {
        if (n >= MAX_SAFE_INTEGER) throw TypeError(MAXIMUM_ALLOWED_INDEX_EXCEEDED);
        createProperty(A, n++, E);
      }
    }
    A.length = n;
    return A;
  }
});


/***/ }),

/***/ "9bdd":
/***/ (function(module, exports, __webpack_require__) {

var anObject = __webpack_require__("825a");

// call something on iterator step with safe closing on error
module.exports = function (iterator, fn, value, ENTRIES) {
  try {
    return ENTRIES ? fn(anObject(value)[0], value[1]) : fn(value);
  // 7.4.6 IteratorClose(iterator, completion)
  } catch (error) {
    var returnMethod = iterator['return'];
    if (returnMethod !== undefined) anObject(returnMethod.call(iterator));
    throw error;
  }
};


/***/ }),

/***/ "9bf2":
/***/ (function(module, exports, __webpack_require__) {

var DESCRIPTORS = __webpack_require__("83ab");
var IE8_DOM_DEFINE = __webpack_require__("0cfb");
var anObject = __webpack_require__("825a");
var toPrimitive = __webpack_require__("c04e");

var nativeDefineProperty = Object.defineProperty;

// `Object.defineProperty` method
// https://tc39.github.io/ecma262/#sec-object.defineproperty
exports.f = DESCRIPTORS ? nativeDefineProperty : function defineProperty(O, P, Attributes) {
  anObject(O);
  P = toPrimitive(P, true);
  anObject(Attributes);
  if (IE8_DOM_DEFINE) try {
    return nativeDefineProperty(O, P, Attributes);
  } catch (error) { /* empty */ }
  if ('get' in Attributes || 'set' in Attributes) throw TypeError('Accessors not supported');
  if ('value' in Attributes) O[P] = Attributes.value;
  return O;
};


/***/ }),

/***/ "9ed3":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var IteratorPrototype = __webpack_require__("ae93").IteratorPrototype;
var create = __webpack_require__("7c73");
var createPropertyDescriptor = __webpack_require__("5c6c");
var setToStringTag = __webpack_require__("d44e");
var Iterators = __webpack_require__("3f8c");

var returnThis = function () { return this; };

module.exports = function (IteratorConstructor, NAME, next) {
  var TO_STRING_TAG = NAME + ' Iterator';
  IteratorConstructor.prototype = create(IteratorPrototype, { next: createPropertyDescriptor(1, next) });
  setToStringTag(IteratorConstructor, TO_STRING_TAG, false, true);
  Iterators[TO_STRING_TAG] = returnThis;
  return IteratorConstructor;
};


/***/ }),

/***/ "9f7f":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var fails = __webpack_require__("d039");

// babel-minify transpiles RegExp('a', 'y') -> /a/y and it causes SyntaxError,
// so we use an intermediate function.
function RE(s, f) {
  return RegExp(s, f);
}

exports.UNSUPPORTED_Y = fails(function () {
  // babel-minify transpiles RegExp('a', 'y') -> /a/y and it causes SyntaxError
  var re = RE('a', 'y');
  re.lastIndex = 2;
  return re.exec('abcd') != null;
});

exports.BROKEN_CARET = fails(function () {
  // https://bugzilla.mozilla.org/show_bug.cgi?id=773687
  var re = RE('^r', 'gy');
  re.lastIndex = 2;
  return re.exec('str') != null;
});


/***/ }),

/***/ "a0d3":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var bind = __webpack_require__("0f7c");

module.exports = bind.call(Function.call, Object.prototype.hasOwnProperty);


/***/ }),

/***/ "a4d3":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var $ = __webpack_require__("23e7");
var global = __webpack_require__("da84");
var getBuiltIn = __webpack_require__("d066");
var IS_PURE = __webpack_require__("c430");
var DESCRIPTORS = __webpack_require__("83ab");
var NATIVE_SYMBOL = __webpack_require__("4930");
var USE_SYMBOL_AS_UID = __webpack_require__("fdbf");
var fails = __webpack_require__("d039");
var has = __webpack_require__("5135");
var isArray = __webpack_require__("e8b5");
var isObject = __webpack_require__("861d");
var anObject = __webpack_require__("825a");
var toObject = __webpack_require__("7b0b");
var toIndexedObject = __webpack_require__("fc6a");
var toPrimitive = __webpack_require__("c04e");
var createPropertyDescriptor = __webpack_require__("5c6c");
var nativeObjectCreate = __webpack_require__("7c73");
var objectKeys = __webpack_require__("df75");
var getOwnPropertyNamesModule = __webpack_require__("241c");
var getOwnPropertyNamesExternal = __webpack_require__("057f");
var getOwnPropertySymbolsModule = __webpack_require__("7418");
var getOwnPropertyDescriptorModule = __webpack_require__("06cf");
var definePropertyModule = __webpack_require__("9bf2");
var propertyIsEnumerableModule = __webpack_require__("d1e7");
var createNonEnumerableProperty = __webpack_require__("9112");
var redefine = __webpack_require__("6eeb");
var shared = __webpack_require__("5692");
var sharedKey = __webpack_require__("f772");
var hiddenKeys = __webpack_require__("d012");
var uid = __webpack_require__("90e3");
var wellKnownSymbol = __webpack_require__("b622");
var wrappedWellKnownSymbolModule = __webpack_require__("e538");
var defineWellKnownSymbol = __webpack_require__("746f");
var setToStringTag = __webpack_require__("d44e");
var InternalStateModule = __webpack_require__("69f3");
var $forEach = __webpack_require__("b727").forEach;

var HIDDEN = sharedKey('hidden');
var SYMBOL = 'Symbol';
var PROTOTYPE = 'prototype';
var TO_PRIMITIVE = wellKnownSymbol('toPrimitive');
var setInternalState = InternalStateModule.set;
var getInternalState = InternalStateModule.getterFor(SYMBOL);
var ObjectPrototype = Object[PROTOTYPE];
var $Symbol = global.Symbol;
var $stringify = getBuiltIn('JSON', 'stringify');
var nativeGetOwnPropertyDescriptor = getOwnPropertyDescriptorModule.f;
var nativeDefineProperty = definePropertyModule.f;
var nativeGetOwnPropertyNames = getOwnPropertyNamesExternal.f;
var nativePropertyIsEnumerable = propertyIsEnumerableModule.f;
var AllSymbols = shared('symbols');
var ObjectPrototypeSymbols = shared('op-symbols');
var StringToSymbolRegistry = shared('string-to-symbol-registry');
var SymbolToStringRegistry = shared('symbol-to-string-registry');
var WellKnownSymbolsStore = shared('wks');
var QObject = global.QObject;
// Don't use setters in Qt Script, https://github.com/zloirock/core-js/issues/173
var USE_SETTER = !QObject || !QObject[PROTOTYPE] || !QObject[PROTOTYPE].findChild;

// fallback for old Android, https://code.google.com/p/v8/issues/detail?id=687
var setSymbolDescriptor = DESCRIPTORS && fails(function () {
  return nativeObjectCreate(nativeDefineProperty({}, 'a', {
    get: function () { return nativeDefineProperty(this, 'a', { value: 7 }).a; }
  })).a != 7;
}) ? function (O, P, Attributes) {
  var ObjectPrototypeDescriptor = nativeGetOwnPropertyDescriptor(ObjectPrototype, P);
  if (ObjectPrototypeDescriptor) delete ObjectPrototype[P];
  nativeDefineProperty(O, P, Attributes);
  if (ObjectPrototypeDescriptor && O !== ObjectPrototype) {
    nativeDefineProperty(ObjectPrototype, P, ObjectPrototypeDescriptor);
  }
} : nativeDefineProperty;

var wrap = function (tag, description) {
  var symbol = AllSymbols[tag] = nativeObjectCreate($Symbol[PROTOTYPE]);
  setInternalState(symbol, {
    type: SYMBOL,
    tag: tag,
    description: description
  });
  if (!DESCRIPTORS) symbol.description = description;
  return symbol;
};

var isSymbol = USE_SYMBOL_AS_UID ? function (it) {
  return typeof it == 'symbol';
} : function (it) {
  return Object(it) instanceof $Symbol;
};

var $defineProperty = function defineProperty(O, P, Attributes) {
  if (O === ObjectPrototype) $defineProperty(ObjectPrototypeSymbols, P, Attributes);
  anObject(O);
  var key = toPrimitive(P, true);
  anObject(Attributes);
  if (has(AllSymbols, key)) {
    if (!Attributes.enumerable) {
      if (!has(O, HIDDEN)) nativeDefineProperty(O, HIDDEN, createPropertyDescriptor(1, {}));
      O[HIDDEN][key] = true;
    } else {
      if (has(O, HIDDEN) && O[HIDDEN][key]) O[HIDDEN][key] = false;
      Attributes = nativeObjectCreate(Attributes, { enumerable: createPropertyDescriptor(0, false) });
    } return setSymbolDescriptor(O, key, Attributes);
  } return nativeDefineProperty(O, key, Attributes);
};

var $defineProperties = function defineProperties(O, Properties) {
  anObject(O);
  var properties = toIndexedObject(Properties);
  var keys = objectKeys(properties).concat($getOwnPropertySymbols(properties));
  $forEach(keys, function (key) {
    if (!DESCRIPTORS || $propertyIsEnumerable.call(properties, key)) $defineProperty(O, key, properties[key]);
  });
  return O;
};

var $create = function create(O, Properties) {
  return Properties === undefined ? nativeObjectCreate(O) : $defineProperties(nativeObjectCreate(O), Properties);
};

var $propertyIsEnumerable = function propertyIsEnumerable(V) {
  var P = toPrimitive(V, true);
  var enumerable = nativePropertyIsEnumerable.call(this, P);
  if (this === ObjectPrototype && has(AllSymbols, P) && !has(ObjectPrototypeSymbols, P)) return false;
  return enumerable || !has(this, P) || !has(AllSymbols, P) || has(this, HIDDEN) && this[HIDDEN][P] ? enumerable : true;
};

var $getOwnPropertyDescriptor = function getOwnPropertyDescriptor(O, P) {
  var it = toIndexedObject(O);
  var key = toPrimitive(P, true);
  if (it === ObjectPrototype && has(AllSymbols, key) && !has(ObjectPrototypeSymbols, key)) return;
  var descriptor = nativeGetOwnPropertyDescriptor(it, key);
  if (descriptor && has(AllSymbols, key) && !(has(it, HIDDEN) && it[HIDDEN][key])) {
    descriptor.enumerable = true;
  }
  return descriptor;
};

var $getOwnPropertyNames = function getOwnPropertyNames(O) {
  var names = nativeGetOwnPropertyNames(toIndexedObject(O));
  var result = [];
  $forEach(names, function (key) {
    if (!has(AllSymbols, key) && !has(hiddenKeys, key)) result.push(key);
  });
  return result;
};

var $getOwnPropertySymbols = function getOwnPropertySymbols(O) {
  var IS_OBJECT_PROTOTYPE = O === ObjectPrototype;
  var names = nativeGetOwnPropertyNames(IS_OBJECT_PROTOTYPE ? ObjectPrototypeSymbols : toIndexedObject(O));
  var result = [];
  $forEach(names, function (key) {
    if (has(AllSymbols, key) && (!IS_OBJECT_PROTOTYPE || has(ObjectPrototype, key))) {
      result.push(AllSymbols[key]);
    }
  });
  return result;
};

// `Symbol` constructor
// https://tc39.github.io/ecma262/#sec-symbol-constructor
if (!NATIVE_SYMBOL) {
  $Symbol = function Symbol() {
    if (this instanceof $Symbol) throw TypeError('Symbol is not a constructor');
    var description = !arguments.length || arguments[0] === undefined ? undefined : String(arguments[0]);
    var tag = uid(description);
    var setter = function (value) {
      if (this === ObjectPrototype) setter.call(ObjectPrototypeSymbols, value);
      if (has(this, HIDDEN) && has(this[HIDDEN], tag)) this[HIDDEN][tag] = false;
      setSymbolDescriptor(this, tag, createPropertyDescriptor(1, value));
    };
    if (DESCRIPTORS && USE_SETTER) setSymbolDescriptor(ObjectPrototype, tag, { configurable: true, set: setter });
    return wrap(tag, description);
  };

  redefine($Symbol[PROTOTYPE], 'toString', function toString() {
    return getInternalState(this).tag;
  });

  redefine($Symbol, 'withoutSetter', function (description) {
    return wrap(uid(description), description);
  });

  propertyIsEnumerableModule.f = $propertyIsEnumerable;
  definePropertyModule.f = $defineProperty;
  getOwnPropertyDescriptorModule.f = $getOwnPropertyDescriptor;
  getOwnPropertyNamesModule.f = getOwnPropertyNamesExternal.f = $getOwnPropertyNames;
  getOwnPropertySymbolsModule.f = $getOwnPropertySymbols;

  wrappedWellKnownSymbolModule.f = function (name) {
    return wrap(wellKnownSymbol(name), name);
  };

  if (DESCRIPTORS) {
    // https://github.com/tc39/proposal-Symbol-description
    nativeDefineProperty($Symbol[PROTOTYPE], 'description', {
      configurable: true,
      get: function description() {
        return getInternalState(this).description;
      }
    });
    if (!IS_PURE) {
      redefine(ObjectPrototype, 'propertyIsEnumerable', $propertyIsEnumerable, { unsafe: true });
    }
  }
}

$({ global: true, wrap: true, forced: !NATIVE_SYMBOL, sham: !NATIVE_SYMBOL }, {
  Symbol: $Symbol
});

$forEach(objectKeys(WellKnownSymbolsStore), function (name) {
  defineWellKnownSymbol(name);
});

$({ target: SYMBOL, stat: true, forced: !NATIVE_SYMBOL }, {
  // `Symbol.for` method
  // https://tc39.github.io/ecma262/#sec-symbol.for
  'for': function (key) {
    var string = String(key);
    if (has(StringToSymbolRegistry, string)) return StringToSymbolRegistry[string];
    var symbol = $Symbol(string);
    StringToSymbolRegistry[string] = symbol;
    SymbolToStringRegistry[symbol] = string;
    return symbol;
  },
  // `Symbol.keyFor` method
  // https://tc39.github.io/ecma262/#sec-symbol.keyfor
  keyFor: function keyFor(sym) {
    if (!isSymbol(sym)) throw TypeError(sym + ' is not a symbol');
    if (has(SymbolToStringRegistry, sym)) return SymbolToStringRegistry[sym];
  },
  useSetter: function () { USE_SETTER = true; },
  useSimple: function () { USE_SETTER = false; }
});

$({ target: 'Object', stat: true, forced: !NATIVE_SYMBOL, sham: !DESCRIPTORS }, {
  // `Object.create` method
  // https://tc39.github.io/ecma262/#sec-object.create
  create: $create,
  // `Object.defineProperty` method
  // https://tc39.github.io/ecma262/#sec-object.defineproperty
  defineProperty: $defineProperty,
  // `Object.defineProperties` method
  // https://tc39.github.io/ecma262/#sec-object.defineproperties
  defineProperties: $defineProperties,
  // `Object.getOwnPropertyDescriptor` method
  // https://tc39.github.io/ecma262/#sec-object.getownpropertydescriptors
  getOwnPropertyDescriptor: $getOwnPropertyDescriptor
});

$({ target: 'Object', stat: true, forced: !NATIVE_SYMBOL }, {
  // `Object.getOwnPropertyNames` method
  // https://tc39.github.io/ecma262/#sec-object.getownpropertynames
  getOwnPropertyNames: $getOwnPropertyNames,
  // `Object.getOwnPropertySymbols` method
  // https://tc39.github.io/ecma262/#sec-object.getownpropertysymbols
  getOwnPropertySymbols: $getOwnPropertySymbols
});

// Chrome 38 and 39 `Object.getOwnPropertySymbols` fails on primitives
// https://bugs.chromium.org/p/v8/issues/detail?id=3443
$({ target: 'Object', stat: true, forced: fails(function () { getOwnPropertySymbolsModule.f(1); }) }, {
  getOwnPropertySymbols: function getOwnPropertySymbols(it) {
    return getOwnPropertySymbolsModule.f(toObject(it));
  }
});

// `JSON.stringify` method behavior with symbols
// https://tc39.github.io/ecma262/#sec-json.stringify
if ($stringify) {
  var FORCED_JSON_STRINGIFY = !NATIVE_SYMBOL || fails(function () {
    var symbol = $Symbol();
    // MS Edge converts symbol values to JSON as {}
    return $stringify([symbol]) != '[null]'
      // WebKit converts symbol values to JSON as null
      || $stringify({ a: symbol }) != '{}'
      // V8 throws on boxed symbols
      || $stringify(Object(symbol)) != '{}';
  });

  $({ target: 'JSON', stat: true, forced: FORCED_JSON_STRINGIFY }, {
    // eslint-disable-next-line no-unused-vars
    stringify: function stringify(it, replacer, space) {
      var args = [it];
      var index = 1;
      var $replacer;
      while (arguments.length > index) args.push(arguments[index++]);
      $replacer = replacer;
      if (!isObject(replacer) && it === undefined || isSymbol(it)) return; // IE8 returns string on undefined
      if (!isArray(replacer)) replacer = function (key, value) {
        if (typeof $replacer == 'function') value = $replacer.call(this, key, value);
        if (!isSymbol(value)) return value;
      };
      args[1] = replacer;
      return $stringify.apply(null, args);
    }
  });
}

// `Symbol.prototype[@@toPrimitive]` method
// https://tc39.github.io/ecma262/#sec-symbol.prototype-@@toprimitive
if (!$Symbol[PROTOTYPE][TO_PRIMITIVE]) {
  createNonEnumerableProperty($Symbol[PROTOTYPE], TO_PRIMITIVE, $Symbol[PROTOTYPE].valueOf);
}
// `Symbol.prototype[@@toStringTag]` property
// https://tc39.github.io/ecma262/#sec-symbol.prototype-@@tostringtag
setToStringTag($Symbol, SYMBOL);

hiddenKeys[HIDDEN] = true;


/***/ }),

/***/ "a630":
/***/ (function(module, exports, __webpack_require__) {

var $ = __webpack_require__("23e7");
var from = __webpack_require__("4df4");
var checkCorrectnessOfIteration = __webpack_require__("1c7e");

var INCORRECT_ITERATION = !checkCorrectnessOfIteration(function (iterable) {
  Array.from(iterable);
});

// `Array.from` method
// https://tc39.github.io/ecma262/#sec-array.from
$({ target: 'Array', stat: true, forced: INCORRECT_ITERATION }, {
  from: from
});


/***/ }),

/***/ "a640":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var fails = __webpack_require__("d039");

module.exports = function (METHOD_NAME, argument) {
  var method = [][METHOD_NAME];
  return !!method && fails(function () {
    // eslint-disable-next-line no-useless-call,no-throw-literal
    method.call(null, argument || function () { throw 1; }, 1);
  });
};


/***/ }),

/***/ "a691":
/***/ (function(module, exports) {

var ceil = Math.ceil;
var floor = Math.floor;

// `ToInteger` abstract operation
// https://tc39.github.io/ecma262/#sec-tointeger
module.exports = function (argument) {
  return isNaN(argument = +argument) ? 0 : (argument > 0 ? floor : ceil)(argument);
};


/***/ }),

/***/ "a9e3":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var DESCRIPTORS = __webpack_require__("83ab");
var global = __webpack_require__("da84");
var isForced = __webpack_require__("94ca");
var redefine = __webpack_require__("6eeb");
var has = __webpack_require__("5135");
var classof = __webpack_require__("c6b6");
var inheritIfRequired = __webpack_require__("7156");
var toPrimitive = __webpack_require__("c04e");
var fails = __webpack_require__("d039");
var create = __webpack_require__("7c73");
var getOwnPropertyNames = __webpack_require__("241c").f;
var getOwnPropertyDescriptor = __webpack_require__("06cf").f;
var defineProperty = __webpack_require__("9bf2").f;
var trim = __webpack_require__("58a8").trim;

var NUMBER = 'Number';
var NativeNumber = global[NUMBER];
var NumberPrototype = NativeNumber.prototype;

// Opera ~12 has broken Object#toString
var BROKEN_CLASSOF = classof(create(NumberPrototype)) == NUMBER;

// `ToNumber` abstract operation
// https://tc39.github.io/ecma262/#sec-tonumber
var toNumber = function (argument) {
  var it = toPrimitive(argument, false);
  var first, third, radix, maxCode, digits, length, index, code;
  if (typeof it == 'string' && it.length > 2) {
    it = trim(it);
    first = it.charCodeAt(0);
    if (first === 43 || first === 45) {
      third = it.charCodeAt(2);
      if (third === 88 || third === 120) return NaN; // Number('+0x1') should be NaN, old V8 fix
    } else if (first === 48) {
      switch (it.charCodeAt(1)) {
        case 66: case 98: radix = 2; maxCode = 49; break; // fast equal of /^0b[01]+$/i
        case 79: case 111: radix = 8; maxCode = 55; break; // fast equal of /^0o[0-7]+$/i
        default: return +it;
      }
      digits = it.slice(2);
      length = digits.length;
      for (index = 0; index < length; index++) {
        code = digits.charCodeAt(index);
        // parseInt parses a string to a first unavailable symbol
        // but ToNumber should return NaN if a string contains unavailable symbols
        if (code < 48 || code > maxCode) return NaN;
      } return parseInt(digits, radix);
    }
  } return +it;
};

// `Number` constructor
// https://tc39.github.io/ecma262/#sec-number-constructor
if (isForced(NUMBER, !NativeNumber(' 0o1') || !NativeNumber('0b1') || NativeNumber('+0x1'))) {
  var NumberWrapper = function Number(value) {
    var it = arguments.length < 1 ? 0 : value;
    var dummy = this;
    return dummy instanceof NumberWrapper
      // check on 1..constructor(foo) case
      && (BROKEN_CLASSOF ? fails(function () { NumberPrototype.valueOf.call(dummy); }) : classof(dummy) != NUMBER)
        ? inheritIfRequired(new NativeNumber(toNumber(it)), dummy, NumberWrapper) : toNumber(it);
  };
  for (var keys = DESCRIPTORS ? getOwnPropertyNames(NativeNumber) : (
    // ES3:
    'MAX_VALUE,MIN_VALUE,NaN,NEGATIVE_INFINITY,POSITIVE_INFINITY,' +
    // ES2015 (in case, if modules with ES2015 Number statics required before):
    'EPSILON,isFinite,isInteger,isNaN,isSafeInteger,MAX_SAFE_INTEGER,' +
    'MIN_SAFE_INTEGER,parseFloat,parseInt,isInteger'
  ).split(','), j = 0, key; keys.length > j; j++) {
    if (has(NativeNumber, key = keys[j]) && !has(NumberWrapper, key)) {
      defineProperty(NumberWrapper, key, getOwnPropertyDescriptor(NativeNumber, key));
    }
  }
  NumberWrapper.prototype = NumberPrototype;
  NumberPrototype.constructor = NumberWrapper;
  redefine(global, NUMBER, NumberWrapper);
}


/***/ }),

/***/ "ab13":
/***/ (function(module, exports, __webpack_require__) {

var wellKnownSymbol = __webpack_require__("b622");

var MATCH = wellKnownSymbol('match');

module.exports = function (METHOD_NAME) {
  var regexp = /./;
  try {
    '/./'[METHOD_NAME](regexp);
  } catch (e) {
    try {
      regexp[MATCH] = false;
      return '/./'[METHOD_NAME](regexp);
    } catch (f) { /* empty */ }
  } return false;
};


/***/ }),

/***/ "ac1f":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var $ = __webpack_require__("23e7");
var exec = __webpack_require__("9263");

$({ target: 'RegExp', proto: true, forced: /./.exec !== exec }, {
  exec: exec
});


/***/ }),

/***/ "ad4b":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_App_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("019d");
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_App_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_App_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */
 /* unused harmony default export */ var _unused_webpack_default_export = (_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_App_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "ad6d":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var anObject = __webpack_require__("825a");

// `RegExp.prototype.flags` getter implementation
// https://tc39.github.io/ecma262/#sec-get-regexp.prototype.flags
module.exports = function () {
  var that = anObject(this);
  var result = '';
  if (that.global) result += 'g';
  if (that.ignoreCase) result += 'i';
  if (that.multiline) result += 'm';
  if (that.dotAll) result += 's';
  if (that.unicode) result += 'u';
  if (that.sticky) result += 'y';
  return result;
};


/***/ }),

/***/ "ae40":
/***/ (function(module, exports, __webpack_require__) {

var DESCRIPTORS = __webpack_require__("83ab");
var fails = __webpack_require__("d039");
var has = __webpack_require__("5135");

var defineProperty = Object.defineProperty;
var cache = {};

var thrower = function (it) { throw it; };

module.exports = function (METHOD_NAME, options) {
  if (has(cache, METHOD_NAME)) return cache[METHOD_NAME];
  if (!options) options = {};
  var method = [][METHOD_NAME];
  var ACCESSORS = has(options, 'ACCESSORS') ? options.ACCESSORS : false;
  var argument0 = has(options, 0) ? options[0] : thrower;
  var argument1 = has(options, 1) ? options[1] : undefined;

  return cache[METHOD_NAME] = !!method && !fails(function () {
    if (ACCESSORS && !DESCRIPTORS) return true;
    var O = { length: -1 };

    if (ACCESSORS) defineProperty(O, 1, { enumerable: true, get: thrower });
    else O[1] = 1;

    method.call(O, argument0, argument1);
  });
};


/***/ }),

/***/ "ae93":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var getPrototypeOf = __webpack_require__("e163");
var createNonEnumerableProperty = __webpack_require__("9112");
var has = __webpack_require__("5135");
var wellKnownSymbol = __webpack_require__("b622");
var IS_PURE = __webpack_require__("c430");

var ITERATOR = wellKnownSymbol('iterator');
var BUGGY_SAFARI_ITERATORS = false;

var returnThis = function () { return this; };

// `%IteratorPrototype%` object
// https://tc39.github.io/ecma262/#sec-%iteratorprototype%-object
var IteratorPrototype, PrototypeOfArrayIteratorPrototype, arrayIterator;

if ([].keys) {
  arrayIterator = [].keys();
  // Safari 8 has buggy iterators w/o `next`
  if (!('next' in arrayIterator)) BUGGY_SAFARI_ITERATORS = true;
  else {
    PrototypeOfArrayIteratorPrototype = getPrototypeOf(getPrototypeOf(arrayIterator));
    if (PrototypeOfArrayIteratorPrototype !== Object.prototype) IteratorPrototype = PrototypeOfArrayIteratorPrototype;
  }
}

if (IteratorPrototype == undefined) IteratorPrototype = {};

// 25.1.2.1.1 %IteratorPrototype%[@@iterator]()
if (!IS_PURE && !has(IteratorPrototype, ITERATOR)) {
  createNonEnumerableProperty(IteratorPrototype, ITERATOR, returnThis);
}

module.exports = {
  IteratorPrototype: IteratorPrototype,
  BUGGY_SAFARI_ITERATORS: BUGGY_SAFARI_ITERATORS
};


/***/ }),

/***/ "b041":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var TO_STRING_TAG_SUPPORT = __webpack_require__("00ee");
var classof = __webpack_require__("f5df");

// `Object.prototype.toString` method implementation
// https://tc39.github.io/ecma262/#sec-object.prototype.tostring
module.exports = TO_STRING_TAG_SUPPORT ? {}.toString : function toString() {
  return '[object ' + classof(this) + ']';
};


/***/ }),

/***/ "b189":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var keysShim;
if (!Object.keys) {
	// modified from https://github.com/es-shims/es5-shim
	var has = Object.prototype.hasOwnProperty;
	var toStr = Object.prototype.toString;
	var isArgs = __webpack_require__("d4ab"); // eslint-disable-line global-require
	var isEnumerable = Object.prototype.propertyIsEnumerable;
	var hasDontEnumBug = !isEnumerable.call({ toString: null }, 'toString');
	var hasProtoEnumBug = isEnumerable.call(function () {}, 'prototype');
	var dontEnums = [
		'toString',
		'toLocaleString',
		'valueOf',
		'hasOwnProperty',
		'isPrototypeOf',
		'propertyIsEnumerable',
		'constructor'
	];
	var equalsConstructorPrototype = function (o) {
		var ctor = o.constructor;
		return ctor && ctor.prototype === o;
	};
	var excludedKeys = {
		$applicationCache: true,
		$console: true,
		$external: true,
		$frame: true,
		$frameElement: true,
		$frames: true,
		$innerHeight: true,
		$innerWidth: true,
		$onmozfullscreenchange: true,
		$onmozfullscreenerror: true,
		$outerHeight: true,
		$outerWidth: true,
		$pageXOffset: true,
		$pageYOffset: true,
		$parent: true,
		$scrollLeft: true,
		$scrollTop: true,
		$scrollX: true,
		$scrollY: true,
		$self: true,
		$webkitIndexedDB: true,
		$webkitStorageInfo: true,
		$window: true
	};
	var hasAutomationEqualityBug = (function () {
		/* global window */
		if (typeof window === 'undefined') { return false; }
		for (var k in window) {
			try {
				if (!excludedKeys['$' + k] && has.call(window, k) && window[k] !== null && typeof window[k] === 'object') {
					try {
						equalsConstructorPrototype(window[k]);
					} catch (e) {
						return true;
					}
				}
			} catch (e) {
				return true;
			}
		}
		return false;
	}());
	var equalsConstructorPrototypeIfNotBuggy = function (o) {
		/* global window */
		if (typeof window === 'undefined' || !hasAutomationEqualityBug) {
			return equalsConstructorPrototype(o);
		}
		try {
			return equalsConstructorPrototype(o);
		} catch (e) {
			return false;
		}
	};

	keysShim = function keys(object) {
		var isObject = object !== null && typeof object === 'object';
		var isFunction = toStr.call(object) === '[object Function]';
		var isArguments = isArgs(object);
		var isString = isObject && toStr.call(object) === '[object String]';
		var theKeys = [];

		if (!isObject && !isFunction && !isArguments) {
			throw new TypeError('Object.keys called on a non-object');
		}

		var skipProto = hasProtoEnumBug && isFunction;
		if (isString && object.length > 0 && !has.call(object, 0)) {
			for (var i = 0; i < object.length; ++i) {
				theKeys.push(String(i));
			}
		}

		if (isArguments && object.length > 0) {
			for (var j = 0; j < object.length; ++j) {
				theKeys.push(String(j));
			}
		} else {
			for (var name in object) {
				if (!(skipProto && name === 'prototype') && has.call(object, name)) {
					theKeys.push(String(name));
				}
			}
		}

		if (hasDontEnumBug) {
			var skipConstructor = equalsConstructorPrototypeIfNotBuggy(object);

			for (var k = 0; k < dontEnums.length; ++k) {
				if (!(skipConstructor && dontEnums[k] === 'constructor') && has.call(object, dontEnums[k])) {
					theKeys.push(dontEnums[k]);
				}
			}
		}
		return theKeys;
	};
}
module.exports = keysShim;


/***/ }),

/***/ "b575":
/***/ (function(module, exports, __webpack_require__) {

var global = __webpack_require__("da84");
var getOwnPropertyDescriptor = __webpack_require__("06cf").f;
var classof = __webpack_require__("c6b6");
var macrotask = __webpack_require__("2cf4").set;
var IS_IOS = __webpack_require__("1cdc");

var MutationObserver = global.MutationObserver || global.WebKitMutationObserver;
var process = global.process;
var Promise = global.Promise;
var IS_NODE = classof(process) == 'process';
// Node.js 11 shows ExperimentalWarning on getting `queueMicrotask`
var queueMicrotaskDescriptor = getOwnPropertyDescriptor(global, 'queueMicrotask');
var queueMicrotask = queueMicrotaskDescriptor && queueMicrotaskDescriptor.value;

var flush, head, last, notify, toggle, node, promise, then;

// modern engines have queueMicrotask method
if (!queueMicrotask) {
  flush = function () {
    var parent, fn;
    if (IS_NODE && (parent = process.domain)) parent.exit();
    while (head) {
      fn = head.fn;
      head = head.next;
      try {
        fn();
      } catch (error) {
        if (head) notify();
        else last = undefined;
        throw error;
      }
    } last = undefined;
    if (parent) parent.enter();
  };

  // Node.js
  if (IS_NODE) {
    notify = function () {
      process.nextTick(flush);
    };
  // browsers with MutationObserver, except iOS - https://github.com/zloirock/core-js/issues/339
  } else if (MutationObserver && !IS_IOS) {
    toggle = true;
    node = document.createTextNode('');
    new MutationObserver(flush).observe(node, { characterData: true });
    notify = function () {
      node.data = toggle = !toggle;
    };
  // environments with maybe non-completely correct, but existent Promise
  } else if (Promise && Promise.resolve) {
    // Promise.resolve without an argument throws an error in LG WebOS 2
    promise = Promise.resolve(undefined);
    then = promise.then;
    notify = function () {
      then.call(promise, flush);
    };
  // for other environments - macrotask based on:
  // - setImmediate
  // - MessageChannel
  // - window.postMessag
  // - onreadystatechange
  // - setTimeout
  } else {
    notify = function () {
      // strange IE + webpack dev server bug - use .call(global)
      macrotask.call(global, flush);
    };
  }
}

module.exports = queueMicrotask || function (fn) {
  var task = { fn: fn, next: undefined };
  if (last) last.next = task;
  if (!head) {
    head = task;
    notify();
  } last = task;
};


/***/ }),

/***/ "b622":
/***/ (function(module, exports, __webpack_require__) {

var global = __webpack_require__("da84");
var shared = __webpack_require__("5692");
var has = __webpack_require__("5135");
var uid = __webpack_require__("90e3");
var NATIVE_SYMBOL = __webpack_require__("4930");
var USE_SYMBOL_AS_UID = __webpack_require__("fdbf");

var WellKnownSymbolsStore = shared('wks');
var Symbol = global.Symbol;
var createWellKnownSymbol = USE_SYMBOL_AS_UID ? Symbol : Symbol && Symbol.withoutSetter || uid;

module.exports = function (name) {
  if (!has(WellKnownSymbolsStore, name)) {
    if (NATIVE_SYMBOL && has(Symbol, name)) WellKnownSymbolsStore[name] = Symbol[name];
    else WellKnownSymbolsStore[name] = createWellKnownSymbol('Symbol.' + name);
  } return WellKnownSymbolsStore[name];
};


/***/ }),

/***/ "b64b":
/***/ (function(module, exports, __webpack_require__) {

var $ = __webpack_require__("23e7");
var toObject = __webpack_require__("7b0b");
var nativeKeys = __webpack_require__("df75");
var fails = __webpack_require__("d039");

var FAILS_ON_PRIMITIVES = fails(function () { nativeKeys(1); });

// `Object.keys` method
// https://tc39.github.io/ecma262/#sec-object.keys
$({ target: 'Object', stat: true, forced: FAILS_ON_PRIMITIVES }, {
  keys: function keys(it) {
    return nativeKeys(toObject(it));
  }
});


/***/ }),

/***/ "b727":
/***/ (function(module, exports, __webpack_require__) {

var bind = __webpack_require__("0366");
var IndexedObject = __webpack_require__("44ad");
var toObject = __webpack_require__("7b0b");
var toLength = __webpack_require__("50c4");
var arraySpeciesCreate = __webpack_require__("65f0");

var push = [].push;

// `Array.prototype.{ forEach, map, filter, some, every, find, findIndex }` methods implementation
var createMethod = function (TYPE) {
  var IS_MAP = TYPE == 1;
  var IS_FILTER = TYPE == 2;
  var IS_SOME = TYPE == 3;
  var IS_EVERY = TYPE == 4;
  var IS_FIND_INDEX = TYPE == 6;
  var NO_HOLES = TYPE == 5 || IS_FIND_INDEX;
  return function ($this, callbackfn, that, specificCreate) {
    var O = toObject($this);
    var self = IndexedObject(O);
    var boundFunction = bind(callbackfn, that, 3);
    var length = toLength(self.length);
    var index = 0;
    var create = specificCreate || arraySpeciesCreate;
    var target = IS_MAP ? create($this, length) : IS_FILTER ? create($this, 0) : undefined;
    var value, result;
    for (;length > index; index++) if (NO_HOLES || index in self) {
      value = self[index];
      result = boundFunction(value, index, O);
      if (TYPE) {
        if (IS_MAP) target[index] = result; // map
        else if (result) switch (TYPE) {
          case 3: return true;              // some
          case 5: return value;             // find
          case 6: return index;             // findIndex
          case 2: push.call(target, value); // filter
        } else if (IS_EVERY) return false;  // every
      }
    }
    return IS_FIND_INDEX ? -1 : IS_SOME || IS_EVERY ? IS_EVERY : target;
  };
};

module.exports = {
  // `Array.prototype.forEach` method
  // https://tc39.github.io/ecma262/#sec-array.prototype.foreach
  forEach: createMethod(0),
  // `Array.prototype.map` method
  // https://tc39.github.io/ecma262/#sec-array.prototype.map
  map: createMethod(1),
  // `Array.prototype.filter` method
  // https://tc39.github.io/ecma262/#sec-array.prototype.filter
  filter: createMethod(2),
  // `Array.prototype.some` method
  // https://tc39.github.io/ecma262/#sec-array.prototype.some
  some: createMethod(3),
  // `Array.prototype.every` method
  // https://tc39.github.io/ecma262/#sec-array.prototype.every
  every: createMethod(4),
  // `Array.prototype.find` method
  // https://tc39.github.io/ecma262/#sec-array.prototype.find
  find: createMethod(5),
  // `Array.prototype.findIndex` method
  // https://tc39.github.io/ecma262/#sec-array.prototype.findIndex
  findIndex: createMethod(6)
};


/***/ }),

/***/ "bb2f":
/***/ (function(module, exports, __webpack_require__) {

var fails = __webpack_require__("d039");

module.exports = !fails(function () {
  return Object.isExtensible(Object.preventExtensions({}));
});


/***/ }),

/***/ "be79":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "c04e":
/***/ (function(module, exports, __webpack_require__) {

var isObject = __webpack_require__("861d");

// `ToPrimitive` abstract operation
// https://tc39.github.io/ecma262/#sec-toprimitive
// instead of the ES6 spec version, we didn't implement @@toPrimitive case
// and the second argument - flag - preferred type is a string
module.exports = function (input, PREFERRED_STRING) {
  if (!isObject(input)) return input;
  var fn, val;
  if (PREFERRED_STRING && typeof (fn = input.toString) == 'function' && !isObject(val = fn.call(input))) return val;
  if (typeof (fn = input.valueOf) == 'function' && !isObject(val = fn.call(input))) return val;
  if (!PREFERRED_STRING && typeof (fn = input.toString) == 'function' && !isObject(val = fn.call(input))) return val;
  throw TypeError("Can't convert object to primitive value");
};


/***/ }),

/***/ "c430":
/***/ (function(module, exports) {

module.exports = false;


/***/ }),

/***/ "c437":
/***/ (function(module, exports, __webpack_require__) {

var rng = __webpack_require__("e1f4");
var bytesToUuid = __webpack_require__("2366");

// **`v1()` - Generate time-based UUID**
//
// Inspired by https://github.com/LiosK/UUID.js
// and http://docs.python.org/library/uuid.html

var _nodeId;
var _clockseq;

// Previous uuid creation time
var _lastMSecs = 0;
var _lastNSecs = 0;

// See https://github.com/uuidjs/uuid for API details
function v1(options, buf, offset) {
  var i = buf && offset || 0;
  var b = buf || [];

  options = options || {};
  var node = options.node || _nodeId;
  var clockseq = options.clockseq !== undefined ? options.clockseq : _clockseq;

  // node and clockseq need to be initialized to random values if they're not
  // specified.  We do this lazily to minimize issues related to insufficient
  // system entropy.  See #189
  if (node == null || clockseq == null) {
    var seedBytes = rng();
    if (node == null) {
      // Per 4.5, create and 48-bit node id, (47 random bits + multicast bit = 1)
      node = _nodeId = [
        seedBytes[0] | 0x01,
        seedBytes[1], seedBytes[2], seedBytes[3], seedBytes[4], seedBytes[5]
      ];
    }
    if (clockseq == null) {
      // Per 4.2.2, randomize (14 bit) clockseq
      clockseq = _clockseq = (seedBytes[6] << 8 | seedBytes[7]) & 0x3fff;
    }
  }

  // UUID timestamps are 100 nano-second units since the Gregorian epoch,
  // (1582-10-15 00:00).  JSNumbers aren't precise enough for this, so
  // time is handled internally as 'msecs' (integer milliseconds) and 'nsecs'
  // (100-nanoseconds offset from msecs) since unix epoch, 1970-01-01 00:00.
  var msecs = options.msecs !== undefined ? options.msecs : new Date().getTime();

  // Per 4.2.1.2, use count of uuid's generated during the current clock
  // cycle to simulate higher resolution clock
  var nsecs = options.nsecs !== undefined ? options.nsecs : _lastNSecs + 1;

  // Time since last uuid creation (in msecs)
  var dt = (msecs - _lastMSecs) + (nsecs - _lastNSecs)/10000;

  // Per 4.2.1.2, Bump clockseq on clock regression
  if (dt < 0 && options.clockseq === undefined) {
    clockseq = clockseq + 1 & 0x3fff;
  }

  // Reset nsecs if clock regresses (new clockseq) or we've moved onto a new
  // time interval
  if ((dt < 0 || msecs > _lastMSecs) && options.nsecs === undefined) {
    nsecs = 0;
  }

  // Per 4.2.1.2 Throw error if too many uuids are requested
  if (nsecs >= 10000) {
    throw new Error('uuid.v1(): Can\'t create more than 10M uuids/sec');
  }

  _lastMSecs = msecs;
  _lastNSecs = nsecs;
  _clockseq = clockseq;

  // Per 4.1.4 - Convert from unix epoch to Gregorian epoch
  msecs += 12219292800000;

  // `time_low`
  var tl = ((msecs & 0xfffffff) * 10000 + nsecs) % 0x100000000;
  b[i++] = tl >>> 24 & 0xff;
  b[i++] = tl >>> 16 & 0xff;
  b[i++] = tl >>> 8 & 0xff;
  b[i++] = tl & 0xff;

  // `time_mid`
  var tmh = (msecs / 0x100000000 * 10000) & 0xfffffff;
  b[i++] = tmh >>> 8 & 0xff;
  b[i++] = tmh & 0xff;

  // `time_high_and_version`
  b[i++] = tmh >>> 24 & 0xf | 0x10; // include version
  b[i++] = tmh >>> 16 & 0xff;

  // `clock_seq_hi_and_reserved` (Per 4.2.2 - include variant)
  b[i++] = clockseq >>> 8 | 0x80;

  // `clock_seq_low`
  b[i++] = clockseq & 0xff;

  // `node`
  for (var n = 0; n < 6; ++n) {
    b[i + n] = node[n];
  }

  return buf ? buf : bytesToUuid(b);
}

module.exports = v1;


/***/ }),

/***/ "c64e":
/***/ (function(module, exports, __webpack_require__) {

var rng = __webpack_require__("e1f4");
var bytesToUuid = __webpack_require__("2366");

function v4(options, buf, offset) {
  var i = buf && offset || 0;

  if (typeof(options) == 'string') {
    buf = options === 'binary' ? new Array(16) : null;
    options = null;
  }
  options = options || {};

  var rnds = options.random || (options.rng || rng)();

  // Per 4.4, set bits for version and `clock_seq_hi_and_reserved`
  rnds[6] = (rnds[6] & 0x0f) | 0x40;
  rnds[8] = (rnds[8] & 0x3f) | 0x80;

  // Copy bytes to buffer, if provided
  if (buf) {
    for (var ii = 0; ii < 16; ++ii) {
      buf[i + ii] = rnds[ii];
    }
  }

  return buf || bytesToUuid(rnds);
}

module.exports = v4;


/***/ }),

/***/ "c6b6":
/***/ (function(module, exports) {

var toString = {}.toString;

module.exports = function (it) {
  return toString.call(it).slice(8, -1);
};


/***/ }),

/***/ "c6cd":
/***/ (function(module, exports, __webpack_require__) {

var global = __webpack_require__("da84");
var setGlobal = __webpack_require__("ce4e");

var SHARED = '__core-js_shared__';
var store = global[SHARED] || setGlobal(SHARED, {});

module.exports = store;


/***/ }),

/***/ "c73c":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ErrorAmbiguousStatement_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("2ee0");
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ErrorAmbiguousStatement_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ErrorAmbiguousStatement_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */
 /* unused harmony default export */ var _unused_webpack_default_export = (_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ErrorAmbiguousStatement_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "c8ba":
/***/ (function(module, exports) {

var g;

// This works in non-strict mode
g = (function() {
	return this;
})();

try {
	// This works if eval is allowed (see CSP)
	g = g || new Function("return this")();
} catch (e) {
	// This works if the window reference is available
	if (typeof window === "object") g = window;
}

// g can still be undefined, but nothing to do about it...
// We return undefined, instead of nothing here, so it's
// easier to handle this case. if(!global) { ...}

module.exports = g;


/***/ }),

/***/ "c975":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var $ = __webpack_require__("23e7");
var $indexOf = __webpack_require__("4d64").indexOf;
var arrayMethodIsStrict = __webpack_require__("a640");
var arrayMethodUsesToLength = __webpack_require__("ae40");

var nativeIndexOf = [].indexOf;

var NEGATIVE_ZERO = !!nativeIndexOf && 1 / [1].indexOf(1, -0) < 0;
var STRICT_METHOD = arrayMethodIsStrict('indexOf');
var USES_TO_LENGTH = arrayMethodUsesToLength('indexOf', { ACCESSORS: true, 1: 0 });

// `Array.prototype.indexOf` method
// https://tc39.github.io/ecma262/#sec-array.prototype.indexof
$({ target: 'Array', proto: true, forced: NEGATIVE_ZERO || !STRICT_METHOD || !USES_TO_LENGTH }, {
  indexOf: function indexOf(searchElement /* , fromIndex = 0 */) {
    return NEGATIVE_ZERO
      // convert -0 to +0
      ? nativeIndexOf.apply(this, arguments) || 0
      : $indexOf(this, searchElement, arguments.length > 1 ? arguments[1] : undefined);
  }
});


/***/ }),

/***/ "ca84":
/***/ (function(module, exports, __webpack_require__) {

var has = __webpack_require__("5135");
var toIndexedObject = __webpack_require__("fc6a");
var indexOf = __webpack_require__("4d64").indexOf;
var hiddenKeys = __webpack_require__("d012");

module.exports = function (object, names) {
  var O = toIndexedObject(object);
  var i = 0;
  var result = [];
  var key;
  for (key in O) !has(hiddenKeys, key) && has(O, key) && result.push(key);
  // Don't enum bug & hidden keys
  while (names.length > i) if (has(O, key = names[i++])) {
    ~indexOf(result, key) || result.push(key);
  }
  return result;
};


/***/ }),

/***/ "caad":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var $ = __webpack_require__("23e7");
var $includes = __webpack_require__("4d64").includes;
var addToUnscopables = __webpack_require__("44d2");
var arrayMethodUsesToLength = __webpack_require__("ae40");

var USES_TO_LENGTH = arrayMethodUsesToLength('indexOf', { ACCESSORS: true, 1: 0 });

// `Array.prototype.includes` method
// https://tc39.github.io/ecma262/#sec-array.prototype.includes
$({ target: 'Array', proto: true, forced: !USES_TO_LENGTH }, {
  includes: function includes(el /* , fromIndex = 0 */) {
    return $includes(this, el, arguments.length > 1 ? arguments[1] : undefined);
  }
});

// https://tc39.github.io/ecma262/#sec-array.prototype-@@unscopables
addToUnscopables('includes');


/***/ }),

/***/ "cc12":
/***/ (function(module, exports, __webpack_require__) {

var global = __webpack_require__("da84");
var isObject = __webpack_require__("861d");

var document = global.document;
// typeof document.createElement is 'object' in old IE
var EXISTS = isObject(document) && isObject(document.createElement);

module.exports = function (it) {
  return EXISTS ? document.createElement(it) : {};
};


/***/ }),

/***/ "cdf9":
/***/ (function(module, exports, __webpack_require__) {

var anObject = __webpack_require__("825a");
var isObject = __webpack_require__("861d");
var newPromiseCapability = __webpack_require__("f069");

module.exports = function (C, x) {
  anObject(C);
  if (isObject(x) && x.constructor === C) return x;
  var promiseCapability = newPromiseCapability.f(C);
  var resolve = promiseCapability.resolve;
  resolve(x);
  return promiseCapability.promise;
};


/***/ }),

/***/ "ce4e":
/***/ (function(module, exports, __webpack_require__) {

var global = __webpack_require__("da84");
var createNonEnumerableProperty = __webpack_require__("9112");

module.exports = function (key, value) {
  try {
    createNonEnumerableProperty(global, key, value);
  } catch (error) {
    global[key] = value;
  } return value;
};


/***/ }),

/***/ "ceb6":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_BailoutActions_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("8813");
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_BailoutActions_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_BailoutActions_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */
 /* unused harmony default export */ var _unused_webpack_default_export = (_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_BailoutActions_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "d012":
/***/ (function(module, exports) {

module.exports = {};


/***/ }),

/***/ "d039":
/***/ (function(module, exports) {

module.exports = function (exec) {
  try {
    return !!exec();
  } catch (error) {
    return true;
  }
};


/***/ }),

/***/ "d066":
/***/ (function(module, exports, __webpack_require__) {

var path = __webpack_require__("428f");
var global = __webpack_require__("da84");

var aFunction = function (variable) {
  return typeof variable == 'function' ? variable : undefined;
};

module.exports = function (namespace, method) {
  return arguments.length < 2 ? aFunction(path[namespace]) || aFunction(global[namespace])
    : path[namespace] && path[namespace][method] || global[namespace] && global[namespace][method];
};


/***/ }),

/***/ "d1ac":
/***/ (function(module, exports, __webpack_require__) {

module.exports =
/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "fae3");
/******/ })
/************************************************************************/
/******/ ({

/***/ "01a6":
/***/ (function(module, exports) {

module.exports = __webpack_require__("60a3");

/***/ }),

/***/ "1437":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "8bbf":
/***/ (function(module, exports) {

module.exports = __webpack_require__("8bbf");

/***/ }),

/***/ "b4c6":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_IndeterminateProgressBar_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("da77");
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_IndeterminateProgressBar_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_IndeterminateProgressBar_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */
 /* unused harmony default export */ var _unused_webpack_default_export = (_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_IndeterminateProgressBar_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "c93a":
/***/ (function(module, exports) {

module.exports = __webpack_require__("65d9");

/***/ }),

/***/ "da77":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "ec93":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_RadioInput_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("1437");
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_RadioInput_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_RadioInput_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */
 /* unused harmony default export */ var _unused_webpack_default_export = (_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_RadioInput_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "f6fd":
/***/ (function(module, exports) {

// document.currentScript polyfill by Adam Miller

// MIT license

(function(document){
  var currentScript = "currentScript",
      scripts = document.getElementsByTagName('script'); // Live NodeList collection

  // If browser needs currentScript polyfill, add get currentScript() to the document object
  if (!(currentScript in document)) {
    Object.defineProperty(document, currentScript, {
      get: function(){

        // IE 6-10 supports script readyState
        // IE 10+ support stack trace
        try { throw new Error(); }
        catch (err) {

          // Find the second match for the "at" string to get file src url from stack.
          // Specifically works with the format of stack traces in IE.
          var i, res = ((/.*at [^\(]*\((.*):.+:.+\)$/ig).exec(err.stack) || [false])[1];

          // For all scripts on the page, if src matches or if ready state is interactive, return the script tag
          for(i in scripts){
            if(scripts[i].src == res || scripts[i].readyState == "interactive"){
              return scripts[i];
            }
          }

          // If no match, return null
          return null;
        }
      }
    });
  }
})(document);


/***/ }),

/***/ "fae3":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);

// CONCATENATED MODULE: ./node_modules/@vue/cli-service/lib/commands/build/setPublicPath.js
// This file is imported into lib/wc client bundles.

if (typeof window !== 'undefined') {
  if (true) {
    __webpack_require__("f6fd")
  }

  var i
  if ((i = window.document.currentScript) && (i = i.src.match(/(.+\/)[^/]+\.js(\?.*)?$/))) {
    __webpack_require__.p = i[1] // eslint-disable-line
  }
}

// Indicate to webpack that this file can be concatenated
/* harmony default export */ var setPublicPath = (null);

// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js?{"cacheDirectory":"node_modules/.cache/vue-loader","cacheIdentifier":"57451d30-vue-loader-template"}!./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/components/ResizingTextField.vue?vue&type=template&id=035b57b6&
var render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('textarea',{attrs:{"maxlength":_vm.maxlength},domProps:{"value":_vm.value},on:{"input":_vm.setValue,"keydown":function($event){if(!$event.type.indexOf('key')&&_vm._k($event.keyCode,"enter",13,$event.key,"Enter")){ return null; }$event.preventDefault();}}})}
var staticRenderFns = []


// CONCATENATED MODULE: ./src/components/ResizingTextField.vue?vue&type=template&id=035b57b6&

// EXTERNAL MODULE: external {"commonjs":"vue-class-component","commonjs2":"vue-class-component","amd":"vue-class-component","root":"vue-class-component"}
var external_commonjs_vue_class_component_commonjs2_vue_class_component_amd_vue_class_component_root_vue_class_component_ = __webpack_require__("c93a");
var external_commonjs_vue_class_component_commonjs2_vue_class_component_amd_vue_class_component_root_vue_class_component_default = /*#__PURE__*/__webpack_require__.n(external_commonjs_vue_class_component_commonjs2_vue_class_component_amd_vue_class_component_root_vue_class_component_);

// EXTERNAL MODULE: external {"commonjs":"vue","commonjs2":"vue","amd":"vue","root":"vue"}
var external_commonjs_vue_commonjs2_vue_amd_vue_root_vue_ = __webpack_require__("8bbf");
var external_commonjs_vue_commonjs2_vue_amd_vue_root_vue_default = /*#__PURE__*/__webpack_require__.n(external_commonjs_vue_commonjs2_vue_amd_vue_root_vue_);

// EXTERNAL MODULE: external {"commonjs":"vue-property-decorator","commonjs2":"vue-property-decorator","amd":"vue-property-decorator","root":"vue-property-decorator"}
var external_commonjs_vue_property_decorator_commonjs2_vue_property_decorator_amd_vue_property_decorator_root_vue_property_decorator_ = __webpack_require__("01a6");

// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/ts-loader??ref--14-3!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/components/ResizingTextField.vue?vue&type=script&lang=ts&
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

var __decorate = undefined && undefined.__decorate || function (decorators, target, key, desc) {
  var c = arguments.length,
      r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc,
      d;
  if ((typeof Reflect === "undefined" ? "undefined" : _typeof(Reflect)) === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);else for (var i = decorators.length - 1; i >= 0; i--) {
    if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
  }
  return c > 3 && r && Object.defineProperty(target, key, r), r;
};





var ResizingTextField =
/*#__PURE__*/
function (_Vue) {
  _inherits(ResizingTextField, _Vue);

  function ResizingTextField() {
    _classCallCheck(this, ResizingTextField);

    return _possibleConstructorReturn(this, _getPrototypeOf(ResizingTextField).apply(this, arguments));
  }

  _createClass(ResizingTextField, [{
    key: "mounted",
    value: function mounted() {
      this.resizeTextField();
    }
  }, {
    key: "setValue",
    value: function setValue(event) {
      var _this = this;

      this.$emit('input', this.removeNewlines(event.target.value)); // make sure that even nodiff changes to the state will update our textarea
      // a nodiff could be caused by pasting newlines only

      this.$forceUpdate();
      this.$nextTick().then(function () {
        _this.resizeTextField();
      });
    }
  }, {
    key: "removeNewlines",
    value: function removeNewlines(value) {
      return value.replace(/\r?\n/g, '');
    }
  }, {
    key: "resizeTextField",
    value: function resizeTextField() {
      var textarea = this.$el;
      textarea.style.height = '0';
      var border = this.getPropertyValueInPx(textarea, 'border-top-width') + this.getPropertyValueInPx(textarea, 'border-bottom-width');
      textarea.style.height = "".concat(this.$el.scrollHeight + border, "px");
    }
  }, {
    key: "getPropertyValueInPx",
    value: function getPropertyValueInPx(element, property) {
      return parseInt(window.getComputedStyle(element).getPropertyValue(property));
    }
  }]);

  return ResizingTextField;
}(external_commonjs_vue_commonjs2_vue_amd_vue_root_vue_default.a);

__decorate([Object(external_commonjs_vue_property_decorator_commonjs2_vue_property_decorator_amd_vue_property_decorator_root_vue_property_decorator_["Prop"])()], ResizingTextField.prototype, "value", void 0);

__decorate([Object(external_commonjs_vue_property_decorator_commonjs2_vue_property_decorator_amd_vue_property_decorator_root_vue_property_decorator_["Prop"])({
  type: Number,
  default: null
})], ResizingTextField.prototype, "maxlength", void 0);

ResizingTextField = __decorate([external_commonjs_vue_class_component_commonjs2_vue_class_component_amd_vue_class_component_root_vue_class_component_default.a], ResizingTextField);
/* harmony default export */ var ResizingTextFieldvue_type_script_lang_ts_ = (ResizingTextField);
// CONCATENATED MODULE: ./src/components/ResizingTextField.vue?vue&type=script&lang=ts&
 /* harmony default export */ var components_ResizingTextFieldvue_type_script_lang_ts_ = (ResizingTextFieldvue_type_script_lang_ts_); 
// CONCATENATED MODULE: ./node_modules/vue-loader/lib/runtime/componentNormalizer.js
/* globals __VUE_SSR_CONTEXT__ */

// IMPORTANT: Do NOT use ES2015 features in this file (except for modules).
// This module is a runtime utility for cleaner component module output and will
// be included in the final webpack user bundle.

function normalizeComponent (
  scriptExports,
  render,
  staticRenderFns,
  functionalTemplate,
  injectStyles,
  scopeId,
  moduleIdentifier, /* server only */
  shadowMode /* vue-cli only */
) {
  // Vue.extend constructor export interop
  var options = typeof scriptExports === 'function'
    ? scriptExports.options
    : scriptExports

  // render functions
  if (render) {
    options.render = render
    options.staticRenderFns = staticRenderFns
    options._compiled = true
  }

  // functional template
  if (functionalTemplate) {
    options.functional = true
  }

  // scopedId
  if (scopeId) {
    options._scopeId = 'data-v-' + scopeId
  }

  var hook
  if (moduleIdentifier) { // server build
    hook = function (context) {
      // 2.3 injection
      context =
        context || // cached call
        (this.$vnode && this.$vnode.ssrContext) || // stateful
        (this.parent && this.parent.$vnode && this.parent.$vnode.ssrContext) // functional
      // 2.2 with runInNewContext: true
      if (!context && typeof __VUE_SSR_CONTEXT__ !== 'undefined') {
        context = __VUE_SSR_CONTEXT__
      }
      // inject component styles
      if (injectStyles) {
        injectStyles.call(this, context)
      }
      // register component module identifier for async chunk inferrence
      if (context && context._registeredComponents) {
        context._registeredComponents.add(moduleIdentifier)
      }
    }
    // used by ssr in case component is cached and beforeCreate
    // never gets called
    options._ssrRegister = hook
  } else if (injectStyles) {
    hook = shadowMode
      ? function () { injectStyles.call(this, this.$root.$options.shadowRoot) }
      : injectStyles
  }

  if (hook) {
    if (options.functional) {
      // for template-only hot-reload because in that case the render fn doesn't
      // go through the normalizer
      options._injectStyles = hook
      // register for functioal component in vue file
      var originalRender = options.render
      options.render = function renderWithStyleInjection (h, context) {
        hook.call(context)
        return originalRender(h, context)
      }
    } else {
      // inject component registration as beforeCreate hook
      var existing = options.beforeCreate
      options.beforeCreate = existing
        ? [].concat(existing, hook)
        : [hook]
    }
  }

  return {
    exports: scriptExports,
    options: options
  }
}

// CONCATENATED MODULE: ./src/components/ResizingTextField.vue





/* normalize component */

var component = normalizeComponent(
  components_ResizingTextFieldvue_type_script_lang_ts_,
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var components_ResizingTextField = (component.exports);
// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js?{"cacheDirectory":"node_modules/.cache/vue-loader","cacheIdentifier":"57451d30-vue-loader-template"}!./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/components/IndeterminateProgressBar.vue?vue&type=template&id=36444cd6&
var IndeterminateProgressBarvue_type_template_id_36444cd6_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _vm._m(0)}
var IndeterminateProgressBarvue_type_template_id_36444cd6_staticRenderFns = [function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"wb-ui-indeterminate-progress-bar__box"},[_c('div',{staticClass:"wb-ui-indeterminate-progress-bar__bar",attrs:{"aria-busy":"true","aria-live":"polite"}})])}]


// CONCATENATED MODULE: ./src/components/IndeterminateProgressBar.vue?vue&type=template&id=36444cd6&

// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/ts-loader??ref--14-3!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/components/IndeterminateProgressBar.vue?vue&type=script&lang=ts&
function IndeterminateProgressBarvue_type_script_lang_ts_classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function IndeterminateProgressBarvue_type_script_lang_ts_possibleConstructorReturn(self, call) { if (call && (IndeterminateProgressBarvue_type_script_lang_ts_typeof(call) === "object" || typeof call === "function")) { return call; } return IndeterminateProgressBarvue_type_script_lang_ts_assertThisInitialized(self); }

function IndeterminateProgressBarvue_type_script_lang_ts_assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function IndeterminateProgressBarvue_type_script_lang_ts_getPrototypeOf(o) { IndeterminateProgressBarvue_type_script_lang_ts_getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return IndeterminateProgressBarvue_type_script_lang_ts_getPrototypeOf(o); }

function IndeterminateProgressBarvue_type_script_lang_ts_inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) IndeterminateProgressBarvue_type_script_lang_ts_setPrototypeOf(subClass, superClass); }

function IndeterminateProgressBarvue_type_script_lang_ts_setPrototypeOf(o, p) { IndeterminateProgressBarvue_type_script_lang_ts_setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return IndeterminateProgressBarvue_type_script_lang_ts_setPrototypeOf(o, p); }

function IndeterminateProgressBarvue_type_script_lang_ts_typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { IndeterminateProgressBarvue_type_script_lang_ts_typeof = function _typeof(obj) { return typeof obj; }; } else { IndeterminateProgressBarvue_type_script_lang_ts_typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return IndeterminateProgressBarvue_type_script_lang_ts_typeof(obj); }

var IndeterminateProgressBarvue_type_script_lang_ts_decorate = undefined && undefined.__decorate || function (decorators, target, key, desc) {
  var c = arguments.length,
      r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc,
      d;
  if ((typeof Reflect === "undefined" ? "undefined" : IndeterminateProgressBarvue_type_script_lang_ts_typeof(Reflect)) === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);else for (var i = decorators.length - 1; i >= 0; i--) {
    if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
  }
  return c > 3 && r && Object.defineProperty(target, key, r), r;
};




var IndeterminateProgressBar =
/*#__PURE__*/
function (_Vue) {
  IndeterminateProgressBarvue_type_script_lang_ts_inherits(IndeterminateProgressBar, _Vue);

  function IndeterminateProgressBar() {
    IndeterminateProgressBarvue_type_script_lang_ts_classCallCheck(this, IndeterminateProgressBar);

    return IndeterminateProgressBarvue_type_script_lang_ts_possibleConstructorReturn(this, IndeterminateProgressBarvue_type_script_lang_ts_getPrototypeOf(IndeterminateProgressBar).apply(this, arguments));
  }

  return IndeterminateProgressBar;
}(external_commonjs_vue_commonjs2_vue_amd_vue_root_vue_default.a);

IndeterminateProgressBar = IndeterminateProgressBarvue_type_script_lang_ts_decorate([external_commonjs_vue_class_component_commonjs2_vue_class_component_amd_vue_class_component_root_vue_class_component_default.a], IndeterminateProgressBar);
/* harmony default export */ var IndeterminateProgressBarvue_type_script_lang_ts_ = (IndeterminateProgressBar);
// CONCATENATED MODULE: ./src/components/IndeterminateProgressBar.vue?vue&type=script&lang=ts&
 /* harmony default export */ var components_IndeterminateProgressBarvue_type_script_lang_ts_ = (IndeterminateProgressBarvue_type_script_lang_ts_); 
// EXTERNAL MODULE: ./src/components/IndeterminateProgressBar.vue?vue&type=style&index=0&lang=scss&
var IndeterminateProgressBarvue_type_style_index_0_lang_scss_ = __webpack_require__("b4c6");

// CONCATENATED MODULE: ./src/components/IndeterminateProgressBar.vue






/* normalize component */

var IndeterminateProgressBar_component = normalizeComponent(
  components_IndeterminateProgressBarvue_type_script_lang_ts_,
  IndeterminateProgressBarvue_type_template_id_36444cd6_render,
  IndeterminateProgressBarvue_type_template_id_36444cd6_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var components_IndeterminateProgressBar = (IndeterminateProgressBar_component.exports);
// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js?{"cacheDirectory":"node_modules/.cache/vue-loader","cacheIdentifier":"57451d30-vue-loader-template"}!./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/components/RadioInput.vue?vue&type=template&id=7fa05fa2&
var RadioInputvue_type_template_id_7fa05fa2_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"wb-ui-radio-input"},[_c('input',{attrs:{"name":_vm.name,"type":"radio","id":_vm.id,"disabled":_vm.disabled},domProps:{"value":_vm.htmlValue,"checked":_vm.initiallyChecked},on:{"input":function($event){return _vm.$emit('input', $event.target.value)}}}),_c('span'),_c('label',{attrs:{"for":_vm.id}},[_c('span',{staticClass:"wb-ui-radio-input__main-label"},[_vm._t("label")],2),_c('span',{staticClass:"wb-ui-radio-input__description"},[_vm._t("description")],2)])])}
var RadioInputvue_type_template_id_7fa05fa2_staticRenderFns = []


// CONCATENATED MODULE: ./src/components/RadioInput.vue?vue&type=template&id=7fa05fa2&

// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/ts-loader??ref--14-3!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/components/RadioInput.vue?vue&type=script&lang=ts&
function RadioInputvue_type_script_lang_ts_classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function RadioInputvue_type_script_lang_ts_defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function RadioInputvue_type_script_lang_ts_createClass(Constructor, protoProps, staticProps) { if (protoProps) RadioInputvue_type_script_lang_ts_defineProperties(Constructor.prototype, protoProps); if (staticProps) RadioInputvue_type_script_lang_ts_defineProperties(Constructor, staticProps); return Constructor; }

function RadioInputvue_type_script_lang_ts_possibleConstructorReturn(self, call) { if (call && (RadioInputvue_type_script_lang_ts_typeof(call) === "object" || typeof call === "function")) { return call; } return RadioInputvue_type_script_lang_ts_assertThisInitialized(self); }

function RadioInputvue_type_script_lang_ts_assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function RadioInputvue_type_script_lang_ts_getPrototypeOf(o) { RadioInputvue_type_script_lang_ts_getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return RadioInputvue_type_script_lang_ts_getPrototypeOf(o); }

function RadioInputvue_type_script_lang_ts_inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) RadioInputvue_type_script_lang_ts_setPrototypeOf(subClass, superClass); }

function RadioInputvue_type_script_lang_ts_setPrototypeOf(o, p) { RadioInputvue_type_script_lang_ts_setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return RadioInputvue_type_script_lang_ts_setPrototypeOf(o, p); }

function RadioInputvue_type_script_lang_ts_typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { RadioInputvue_type_script_lang_ts_typeof = function _typeof(obj) { return typeof obj; }; } else { RadioInputvue_type_script_lang_ts_typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return RadioInputvue_type_script_lang_ts_typeof(obj); }

var RadioInputvue_type_script_lang_ts_decorate = undefined && undefined.__decorate || function (decorators, target, key, desc) {
  var c = arguments.length,
      r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc,
      d;
  if ((typeof Reflect === "undefined" ? "undefined" : RadioInputvue_type_script_lang_ts_typeof(Reflect)) === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);else for (var i = decorators.length - 1; i >= 0; i--) {
    if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
  }
  return c > 3 && r && Object.defineProperty(target, key, r), r;
};





var RadioInput =
/*#__PURE__*/
function (_Vue) {
  RadioInputvue_type_script_lang_ts_inherits(RadioInput, _Vue);

  function RadioInput() {
    var _this;

    RadioInputvue_type_script_lang_ts_classCallCheck(this, RadioInput);

    _this = RadioInputvue_type_script_lang_ts_possibleConstructorReturn(this, RadioInputvue_type_script_lang_ts_getPrototypeOf(RadioInput).apply(this, arguments)); // see:https://github.com/vuejs/vue/issues/5886

    _this.id = "wb-ui-radio-input-".concat(Math.floor(Math.random() * 1000000));
    return _this;
  }

  RadioInputvue_type_script_lang_ts_createClass(RadioInput, [{
    key: "initiallyChecked",
    get: function get() {
      return this.value === this.htmlValue;
    }
  }]);

  return RadioInput;
}(external_commonjs_vue_commonjs2_vue_amd_vue_root_vue_default.a);

RadioInputvue_type_script_lang_ts_decorate([Object(external_commonjs_vue_property_decorator_commonjs2_vue_property_decorator_amd_vue_property_decorator_root_vue_property_decorator_["Prop"])({
  type: String,
  required: true
})], RadioInput.prototype, "name", void 0);

RadioInputvue_type_script_lang_ts_decorate([Object(external_commonjs_vue_property_decorator_commonjs2_vue_property_decorator_amd_vue_property_decorator_root_vue_property_decorator_["Prop"])({
  type: String,
  required: true
})], RadioInput.prototype, "htmlValue", void 0);

RadioInputvue_type_script_lang_ts_decorate([Object(external_commonjs_vue_property_decorator_commonjs2_vue_property_decorator_amd_vue_property_decorator_root_vue_property_decorator_["Prop"])({
  type: Boolean,
  default: false
})], RadioInput.prototype, "disabled", void 0);

RadioInputvue_type_script_lang_ts_decorate([Object(external_commonjs_vue_property_decorator_commonjs2_vue_property_decorator_amd_vue_property_decorator_root_vue_property_decorator_["Prop"])({
  type: String,
  default: ''
})], RadioInput.prototype, "value", void 0);

RadioInput = RadioInputvue_type_script_lang_ts_decorate([external_commonjs_vue_class_component_commonjs2_vue_class_component_amd_vue_class_component_root_vue_class_component_default.a], RadioInput);
/* harmony default export */ var RadioInputvue_type_script_lang_ts_ = (RadioInput);
// CONCATENATED MODULE: ./src/components/RadioInput.vue?vue&type=script&lang=ts&
 /* harmony default export */ var components_RadioInputvue_type_script_lang_ts_ = (RadioInputvue_type_script_lang_ts_); 
// EXTERNAL MODULE: ./src/components/RadioInput.vue?vue&type=style&index=0&lang=scss&
var RadioInputvue_type_style_index_0_lang_scss_ = __webpack_require__("ec93");

// CONCATENATED MODULE: ./src/components/RadioInput.vue






/* normalize component */

var RadioInput_component = normalizeComponent(
  components_RadioInputvue_type_script_lang_ts_,
  RadioInputvue_type_template_id_7fa05fa2_render,
  RadioInputvue_type_template_id_7fa05fa2_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var components_RadioInput = (RadioInput_component.exports);
// CONCATENATED MODULE: ./src/index.ts




// CONCATENATED MODULE: ./node_modules/@vue/cli-service/lib/commands/build/entry-lib-no-default.js
/* concated harmony reexport ResizingTextField */__webpack_require__.d(__webpack_exports__, "ResizingTextField", function() { return components_ResizingTextField; });
/* concated harmony reexport RadioInput */__webpack_require__.d(__webpack_exports__, "RadioInput", function() { return components_RadioInput; });
/* concated harmony reexport IndeterminateProgressBar */__webpack_require__.d(__webpack_exports__, "IndeterminateProgressBar", function() { return components_IndeterminateProgressBar; });




/***/ })

/******/ });
//# sourceMappingURL=wikibase-vuejs-components.common.js.map

/***/ }),

/***/ "d1e7":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var nativePropertyIsEnumerable = {}.propertyIsEnumerable;
var getOwnPropertyDescriptor = Object.getOwnPropertyDescriptor;

// Nashorn ~ JDK8 bug
var NASHORN_BUG = getOwnPropertyDescriptor && !nativePropertyIsEnumerable.call({ 1: 2 }, 1);

// `Object.prototype.propertyIsEnumerable` method implementation
// https://tc39.github.io/ecma262/#sec-object.prototype.propertyisenumerable
exports.f = NASHORN_BUG ? function propertyIsEnumerable(V) {
  var descriptor = getOwnPropertyDescriptor(this, V);
  return !!descriptor && descriptor.enumerable;
} : nativePropertyIsEnumerable;


/***/ }),

/***/ "d28b":
/***/ (function(module, exports, __webpack_require__) {

var defineWellKnownSymbol = __webpack_require__("746f");

// `Symbol.iterator` well-known symbol
// https://tc39.github.io/ecma262/#sec-symbol.iterator
defineWellKnownSymbol('iterator');


/***/ }),

/***/ "d2bb":
/***/ (function(module, exports, __webpack_require__) {

var anObject = __webpack_require__("825a");
var aPossiblePrototype = __webpack_require__("3bbe");

// `Object.setPrototypeOf` method
// https://tc39.github.io/ecma262/#sec-object.setprototypeof
// Works with __proto__ only. Old v8 can't work with null proto objects.
/* eslint-disable no-proto */
module.exports = Object.setPrototypeOf || ('__proto__' in {} ? function () {
  var CORRECT_SETTER = false;
  var test = {};
  var setter;
  try {
    setter = Object.getOwnPropertyDescriptor(Object.prototype, '__proto__').set;
    setter.call(test, []);
    CORRECT_SETTER = test instanceof Array;
  } catch (error) { /* empty */ }
  return function setPrototypeOf(O, proto) {
    anObject(O);
    aPossiblePrototype(proto);
    if (CORRECT_SETTER) setter.call(O, proto);
    else O.__proto__ = proto;
    return O;
  };
}() : undefined);


/***/ }),

/***/ "d2e9":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ErrorPermissionInfo_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("e6bd");
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ErrorPermissionInfo_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ErrorPermissionInfo_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */
 /* unused harmony default export */ var _unused_webpack_default_export = (_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ErrorPermissionInfo_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "d3b7":
/***/ (function(module, exports, __webpack_require__) {

var TO_STRING_TAG_SUPPORT = __webpack_require__("00ee");
var redefine = __webpack_require__("6eeb");
var toString = __webpack_require__("b041");

// `Object.prototype.toString` method
// https://tc39.github.io/ecma262/#sec-object.prototype.tostring
if (!TO_STRING_TAG_SUPPORT) {
  redefine(Object.prototype, 'toString', toString, { unsafe: true });
}


/***/ }),

/***/ "d44e":
/***/ (function(module, exports, __webpack_require__) {

var defineProperty = __webpack_require__("9bf2").f;
var has = __webpack_require__("5135");
var wellKnownSymbol = __webpack_require__("b622");

var TO_STRING_TAG = wellKnownSymbol('toStringTag');

module.exports = function (it, TAG, STATIC) {
  if (it && !has(it = STATIC ? it : it.prototype, TO_STRING_TAG)) {
    defineProperty(it, TO_STRING_TAG, { configurable: true, value: TAG });
  }
};


/***/ }),

/***/ "d4ab":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var toStr = Object.prototype.toString;

module.exports = function isArguments(value) {
	var str = toStr.call(value);
	var isArgs = str === '[object Arguments]';
	if (!isArgs) {
		isArgs = str !== '[object Array]' &&
			value !== null &&
			typeof value === 'object' &&
			typeof value.length === 'number' &&
			value.length >= 0 &&
			toStr.call(value.callee) === '[object Function]';
	}
	return isArgs;
};


/***/ }),

/***/ "d6c7":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var slice = Array.prototype.slice;
var isArgs = __webpack_require__("d4ab");

var origKeys = Object.keys;
var keysShim = origKeys ? function keys(o) { return origKeys(o); } : __webpack_require__("b189");

var originalKeys = Object.keys;

keysShim.shim = function shimObjectKeys() {
	if (Object.keys) {
		var keysWorksWithArguments = (function () {
			// Safari 5.0 bug
			var args = Object.keys(arguments);
			return args && args.length === arguments.length;
		}(1, 2));
		if (!keysWorksWithArguments) {
			Object.keys = function keys(object) { // eslint-disable-line func-name-matching
				if (isArgs(object)) {
					return originalKeys(slice.call(object));
				}
				return originalKeys(object);
			};
		}
	} else {
		Object.keys = keysShim;
	}
	return Object.keys || keysShim;
};

module.exports = keysShim;


/***/ }),

/***/ "d784":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

// TODO: Remove from `core-js@4` since it's moved to entry points
__webpack_require__("ac1f");
var redefine = __webpack_require__("6eeb");
var fails = __webpack_require__("d039");
var wellKnownSymbol = __webpack_require__("b622");
var regexpExec = __webpack_require__("9263");
var createNonEnumerableProperty = __webpack_require__("9112");

var SPECIES = wellKnownSymbol('species');

var REPLACE_SUPPORTS_NAMED_GROUPS = !fails(function () {
  // #replace needs built-in support for named groups.
  // #match works fine because it just return the exec results, even if it has
  // a "grops" property.
  var re = /./;
  re.exec = function () {
    var result = [];
    result.groups = { a: '7' };
    return result;
  };
  return ''.replace(re, '$<a>') !== '7';
});

// IE <= 11 replaces $0 with the whole match, as if it was $&
// https://stackoverflow.com/questions/6024666/getting-ie-to-replace-a-regex-with-the-literal-string-0
var REPLACE_KEEPS_$0 = (function () {
  return 'a'.replace(/./, '$0') === '$0';
})();

var REPLACE = wellKnownSymbol('replace');
// Safari <= 13.0.3(?) substitutes nth capture where n>m with an empty string
var REGEXP_REPLACE_SUBSTITUTES_UNDEFINED_CAPTURE = (function () {
  if (/./[REPLACE]) {
    return /./[REPLACE]('a', '$0') === '';
  }
  return false;
})();

// Chrome 51 has a buggy "split" implementation when RegExp#exec !== nativeExec
// Weex JS has frozen built-in prototypes, so use try / catch wrapper
var SPLIT_WORKS_WITH_OVERWRITTEN_EXEC = !fails(function () {
  var re = /(?:)/;
  var originalExec = re.exec;
  re.exec = function () { return originalExec.apply(this, arguments); };
  var result = 'ab'.split(re);
  return result.length !== 2 || result[0] !== 'a' || result[1] !== 'b';
});

module.exports = function (KEY, length, exec, sham) {
  var SYMBOL = wellKnownSymbol(KEY);

  var DELEGATES_TO_SYMBOL = !fails(function () {
    // String methods call symbol-named RegEp methods
    var O = {};
    O[SYMBOL] = function () { return 7; };
    return ''[KEY](O) != 7;
  });

  var DELEGATES_TO_EXEC = DELEGATES_TO_SYMBOL && !fails(function () {
    // Symbol-named RegExp methods call .exec
    var execCalled = false;
    var re = /a/;

    if (KEY === 'split') {
      // We can't use real regex here since it causes deoptimization
      // and serious performance degradation in V8
      // https://github.com/zloirock/core-js/issues/306
      re = {};
      // RegExp[@@split] doesn't call the regex's exec method, but first creates
      // a new one. We need to return the patched regex when creating the new one.
      re.constructor = {};
      re.constructor[SPECIES] = function () { return re; };
      re.flags = '';
      re[SYMBOL] = /./[SYMBOL];
    }

    re.exec = function () { execCalled = true; return null; };

    re[SYMBOL]('');
    return !execCalled;
  });

  if (
    !DELEGATES_TO_SYMBOL ||
    !DELEGATES_TO_EXEC ||
    (KEY === 'replace' && !(
      REPLACE_SUPPORTS_NAMED_GROUPS &&
      REPLACE_KEEPS_$0 &&
      !REGEXP_REPLACE_SUBSTITUTES_UNDEFINED_CAPTURE
    )) ||
    (KEY === 'split' && !SPLIT_WORKS_WITH_OVERWRITTEN_EXEC)
  ) {
    var nativeRegExpMethod = /./[SYMBOL];
    var methods = exec(SYMBOL, ''[KEY], function (nativeMethod, regexp, str, arg2, forceStringMethod) {
      if (regexp.exec === regexpExec) {
        if (DELEGATES_TO_SYMBOL && !forceStringMethod) {
          // The native String method already delegates to @@method (this
          // polyfilled function), leasing to infinite recursion.
          // We avoid it by directly calling the native @@method method.
          return { done: true, value: nativeRegExpMethod.call(regexp, str, arg2) };
        }
        return { done: true, value: nativeMethod.call(str, regexp, arg2) };
      }
      return { done: false };
    }, {
      REPLACE_KEEPS_$0: REPLACE_KEEPS_$0,
      REGEXP_REPLACE_SUBSTITUTES_UNDEFINED_CAPTURE: REGEXP_REPLACE_SUBSTITUTES_UNDEFINED_CAPTURE
    });
    var stringMethod = methods[0];
    var regexMethod = methods[1];

    redefine(String.prototype, KEY, stringMethod);
    redefine(RegExp.prototype, SYMBOL, length == 2
      // 21.2.5.8 RegExp.prototype[@@replace](string, replaceValue)
      // 21.2.5.11 RegExp.prototype[@@split](string, limit)
      ? function (string, arg) { return regexMethod.call(string, this, arg); }
      // 21.2.5.6 RegExp.prototype[@@match](string)
      // 21.2.5.9 RegExp.prototype[@@search](string)
      : function (string) { return regexMethod.call(string, this); }
    );
  }

  if (sham) createNonEnumerableProperty(RegExp.prototype[SYMBOL], 'sham', true);
};


/***/ }),

/***/ "d81d":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var $ = __webpack_require__("23e7");
var $map = __webpack_require__("b727").map;
var arrayMethodHasSpeciesSupport = __webpack_require__("1dde");
var arrayMethodUsesToLength = __webpack_require__("ae40");

var HAS_SPECIES_SUPPORT = arrayMethodHasSpeciesSupport('map');
// FF49- issue
var USES_TO_LENGTH = arrayMethodUsesToLength('map');

// `Array.prototype.map` method
// https://tc39.github.io/ecma262/#sec-array.prototype.map
// with adding support of @@species
$({ target: 'Array', proto: true, forced: !HAS_SPECIES_SUPPORT || !USES_TO_LENGTH }, {
  map: function map(callbackfn /* , thisArg */) {
    return $map(this, callbackfn, arguments.length > 1 ? arguments[1] : undefined);
  }
});


/***/ }),

/***/ "d8d8":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var has = __webpack_require__("a0d3");
var regexExec = RegExp.prototype.exec;
var gOPD = Object.getOwnPropertyDescriptor;

var tryRegexExecCall = function tryRegexExec(value) {
	try {
		var lastIndex = value.lastIndex;
		value.lastIndex = 0;

		regexExec.call(value);
		return true;
	} catch (e) {
		return false;
	} finally {
		value.lastIndex = lastIndex;
	}
};
var toStr = Object.prototype.toString;
var regexClass = '[object RegExp]';
var hasToStringTag = typeof Symbol === 'function' && typeof Symbol.toStringTag === 'symbol';

module.exports = function isRegex(value) {
	if (!value || typeof value !== 'object') {
		return false;
	}
	if (!hasToStringTag) {
		return toStr.call(value) === regexClass;
	}

	var descriptor = gOPD(value, 'lastIndex');
	var hasLastIndexDataProperty = descriptor && has(descriptor, 'value');
	if (!hasLastIndexDataProperty) {
		return false;
	}

	return tryRegexExecCall(value);
};


/***/ }),

/***/ "da84":
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(global) {var check = function (it) {
  return it && it.Math == Math && it;
};

// https://github.com/zloirock/core-js/issues/86#issuecomment-115759028
module.exports =
  // eslint-disable-next-line no-undef
  check(typeof globalThis == 'object' && globalThis) ||
  check(typeof window == 'object' && window) ||
  check(typeof self == 'object' && self) ||
  check(typeof global == 'object' && global) ||
  // eslint-disable-next-line no-new-func
  Function('return this')();

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__("c8ba")))

/***/ }),

/***/ "dbb4":
/***/ (function(module, exports, __webpack_require__) {

var $ = __webpack_require__("23e7");
var DESCRIPTORS = __webpack_require__("83ab");
var ownKeys = __webpack_require__("56ef");
var toIndexedObject = __webpack_require__("fc6a");
var getOwnPropertyDescriptorModule = __webpack_require__("06cf");
var createProperty = __webpack_require__("8418");

// `Object.getOwnPropertyDescriptors` method
// https://tc39.github.io/ecma262/#sec-object.getownpropertydescriptors
$({ target: 'Object', stat: true, sham: !DESCRIPTORS }, {
  getOwnPropertyDescriptors: function getOwnPropertyDescriptors(object) {
    var O = toIndexedObject(object);
    var getOwnPropertyDescriptor = getOwnPropertyDescriptorModule.f;
    var keys = ownKeys(O);
    var result = {};
    var index = 0;
    var key, descriptor;
    while (keys.length > index) {
      descriptor = getOwnPropertyDescriptor(O, key = keys[index++]);
      if (descriptor !== undefined) createProperty(result, key, descriptor);
    }
    return result;
  }
});


/***/ }),

/***/ "ddb0":
/***/ (function(module, exports, __webpack_require__) {

var global = __webpack_require__("da84");
var DOMIterables = __webpack_require__("fdbc");
var ArrayIteratorMethods = __webpack_require__("e260");
var createNonEnumerableProperty = __webpack_require__("9112");
var wellKnownSymbol = __webpack_require__("b622");

var ITERATOR = wellKnownSymbol('iterator');
var TO_STRING_TAG = wellKnownSymbol('toStringTag');
var ArrayValues = ArrayIteratorMethods.values;

for (var COLLECTION_NAME in DOMIterables) {
  var Collection = global[COLLECTION_NAME];
  var CollectionPrototype = Collection && Collection.prototype;
  if (CollectionPrototype) {
    // some Chrome versions have non-configurable methods on DOMTokenList
    if (CollectionPrototype[ITERATOR] !== ArrayValues) try {
      createNonEnumerableProperty(CollectionPrototype, ITERATOR, ArrayValues);
    } catch (error) {
      CollectionPrototype[ITERATOR] = ArrayValues;
    }
    if (!CollectionPrototype[TO_STRING_TAG]) {
      createNonEnumerableProperty(CollectionPrototype, TO_STRING_TAG, COLLECTION_NAME);
    }
    if (DOMIterables[COLLECTION_NAME]) for (var METHOD_NAME in ArrayIteratorMethods) {
      // some Chrome versions have non-configurable methods on DOMTokenList
      if (CollectionPrototype[METHOD_NAME] !== ArrayIteratorMethods[METHOD_NAME]) try {
        createNonEnumerableProperty(CollectionPrototype, METHOD_NAME, ArrayIteratorMethods[METHOD_NAME]);
      } catch (error) {
        CollectionPrototype[METHOD_NAME] = ArrayIteratorMethods[METHOD_NAME];
      }
    }
  }
}


/***/ }),

/***/ "df75":
/***/ (function(module, exports, __webpack_require__) {

var internalObjectKeys = __webpack_require__("ca84");
var enumBugKeys = __webpack_require__("7839");

// `Object.keys` method
// https://tc39.github.io/ecma262/#sec-object.keys
module.exports = Object.keys || function keys(O) {
  return internalObjectKeys(O, enumBugKeys);
};


/***/ }),

/***/ "e01a":
/***/ (function(module, exports, __webpack_require__) {

"use strict";
// `Symbol.prototype.description` getter
// https://tc39.github.io/ecma262/#sec-symbol.prototype.description

var $ = __webpack_require__("23e7");
var DESCRIPTORS = __webpack_require__("83ab");
var global = __webpack_require__("da84");
var has = __webpack_require__("5135");
var isObject = __webpack_require__("861d");
var defineProperty = __webpack_require__("9bf2").f;
var copyConstructorProperties = __webpack_require__("e893");

var NativeSymbol = global.Symbol;

if (DESCRIPTORS && typeof NativeSymbol == 'function' && (!('description' in NativeSymbol.prototype) ||
  // Safari 12 bug
  NativeSymbol().description !== undefined
)) {
  var EmptyStringDescriptionStore = {};
  // wrap Symbol constructor for correct work with undefined description
  var SymbolWrapper = function Symbol() {
    var description = arguments.length < 1 || arguments[0] === undefined ? undefined : String(arguments[0]);
    var result = this instanceof SymbolWrapper
      ? new NativeSymbol(description)
      // in Edge 13, String(Symbol(undefined)) === 'Symbol(undefined)'
      : description === undefined ? NativeSymbol() : NativeSymbol(description);
    if (description === '') EmptyStringDescriptionStore[result] = true;
    return result;
  };
  copyConstructorProperties(SymbolWrapper, NativeSymbol);
  var symbolPrototype = SymbolWrapper.prototype = NativeSymbol.prototype;
  symbolPrototype.constructor = SymbolWrapper;

  var symbolToString = symbolPrototype.toString;
  var native = String(NativeSymbol('test')) == 'Symbol(test)';
  var regexp = /^Symbol\((.*)\)[^)]+$/;
  defineProperty(symbolPrototype, 'description', {
    configurable: true,
    get: function description() {
      var symbol = isObject(this) ? this.valueOf() : this;
      var string = symbolToString.call(symbol);
      if (has(EmptyStringDescriptionStore, symbol)) return '';
      var desc = native ? string.slice(7, -1) : string.replace(regexp, '$1');
      return desc === '' ? undefined : desc;
    }
  });

  $({ global: true, forced: true }, {
    Symbol: SymbolWrapper
  });
}


/***/ }),

/***/ "e163":
/***/ (function(module, exports, __webpack_require__) {

var has = __webpack_require__("5135");
var toObject = __webpack_require__("7b0b");
var sharedKey = __webpack_require__("f772");
var CORRECT_PROTOTYPE_GETTER = __webpack_require__("e177");

var IE_PROTO = sharedKey('IE_PROTO');
var ObjectPrototype = Object.prototype;

// `Object.getPrototypeOf` method
// https://tc39.github.io/ecma262/#sec-object.getprototypeof
module.exports = CORRECT_PROTOTYPE_GETTER ? Object.getPrototypeOf : function (O) {
  O = toObject(O);
  if (has(O, IE_PROTO)) return O[IE_PROTO];
  if (typeof O.constructor == 'function' && O instanceof O.constructor) {
    return O.constructor.prototype;
  } return O instanceof Object ? ObjectPrototype : null;
};


/***/ }),

/***/ "e177":
/***/ (function(module, exports, __webpack_require__) {

var fails = __webpack_require__("d039");

module.exports = !fails(function () {
  function F() { /* empty */ }
  F.prototype.constructor = null;
  return Object.getPrototypeOf(new F()) !== F.prototype;
});


/***/ }),

/***/ "e1f4":
/***/ (function(module, exports) {

// Unique ID creation requires a high quality random # generator.  In the
// browser this is a little complicated due to unknown quality of Math.random()
// and inconsistent support for the `crypto` API.  We do the best we can via
// feature-detection

// getRandomValues needs to be invoked in a context where "this" is a Crypto
// implementation. Also, find the complete implementation of crypto on IE11.
var getRandomValues = (typeof(crypto) != 'undefined' && crypto.getRandomValues && crypto.getRandomValues.bind(crypto)) ||
                      (typeof(msCrypto) != 'undefined' && typeof window.msCrypto.getRandomValues == 'function' && msCrypto.getRandomValues.bind(msCrypto));

if (getRandomValues) {
  // WHATWG crypto RNG - http://wiki.whatwg.org/wiki/Crypto
  var rnds8 = new Uint8Array(16); // eslint-disable-line no-undef

  module.exports = function whatwgRNG() {
    getRandomValues(rnds8);
    return rnds8;
  };
} else {
  // Math.random()-based (RNG)
  //
  // If all else fails, use Math.random().  It's fast, but is of unspecified
  // quality.
  var rnds = new Array(16);

  module.exports = function mathRNG() {
    for (var i = 0, r; i < 16; i++) {
      if ((i & 0x03) === 0) r = Math.random() * 0x100000000;
      rnds[i] = r >>> ((i & 0x03) << 3) & 0xff;
    }

    return rnds;
  };
}


/***/ }),

/***/ "e24a":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ErrorPermission_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("9634");
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ErrorPermission_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ErrorPermission_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */
 /* unused harmony default export */ var _unused_webpack_default_export = (_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ErrorPermission_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "e260":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var toIndexedObject = __webpack_require__("fc6a");
var addToUnscopables = __webpack_require__("44d2");
var Iterators = __webpack_require__("3f8c");
var InternalStateModule = __webpack_require__("69f3");
var defineIterator = __webpack_require__("7dd0");

var ARRAY_ITERATOR = 'Array Iterator';
var setInternalState = InternalStateModule.set;
var getInternalState = InternalStateModule.getterFor(ARRAY_ITERATOR);

// `Array.prototype.entries` method
// https://tc39.github.io/ecma262/#sec-array.prototype.entries
// `Array.prototype.keys` method
// https://tc39.github.io/ecma262/#sec-array.prototype.keys
// `Array.prototype.values` method
// https://tc39.github.io/ecma262/#sec-array.prototype.values
// `Array.prototype[@@iterator]` method
// https://tc39.github.io/ecma262/#sec-array.prototype-@@iterator
// `CreateArrayIterator` internal method
// https://tc39.github.io/ecma262/#sec-createarrayiterator
module.exports = defineIterator(Array, 'Array', function (iterated, kind) {
  setInternalState(this, {
    type: ARRAY_ITERATOR,
    target: toIndexedObject(iterated), // target
    index: 0,                          // next index
    kind: kind                         // kind
  });
// `%ArrayIteratorPrototype%.next` method
// https://tc39.github.io/ecma262/#sec-%arrayiteratorprototype%.next
}, function () {
  var state = getInternalState(this);
  var target = state.target;
  var kind = state.kind;
  var index = state.index++;
  if (!target || index >= target.length) {
    state.target = undefined;
    return { value: undefined, done: true };
  }
  if (kind == 'keys') return { value: index, done: false };
  if (kind == 'values') return { value: target[index], done: false };
  return { value: [index, target[index]], done: false };
}, 'values');

// argumentsList[@@iterator] is %ArrayProto_values%
// https://tc39.github.io/ecma262/#sec-createunmappedargumentsobject
// https://tc39.github.io/ecma262/#sec-createmappedargumentsobject
Iterators.Arguments = Iterators.Array;

// https://tc39.github.io/ecma262/#sec-array.prototype-@@unscopables
addToUnscopables('keys');
addToUnscopables('values');
addToUnscopables('entries');


/***/ }),

/***/ "e2cc":
/***/ (function(module, exports, __webpack_require__) {

var redefine = __webpack_require__("6eeb");

module.exports = function (target, src, options) {
  for (var key in src) redefine(target, key, src[key], options);
  return target;
};


/***/ }),

/***/ "e39c":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var hasToStringTag = typeof Symbol === 'function' && typeof Symbol.toStringTag === 'symbol';
var toStr = Object.prototype.toString;

var isStandardArguments = function isArguments(value) {
	if (hasToStringTag && value && typeof value === 'object' && Symbol.toStringTag in value) {
		return false;
	}
	return toStr.call(value) === '[object Arguments]';
};

var isLegacyArguments = function isArguments(value) {
	if (isStandardArguments(value)) {
		return true;
	}
	return value !== null &&
		typeof value === 'object' &&
		typeof value.length === 'number' &&
		value.length >= 0 &&
		toStr.call(value) !== '[object Array]' &&
		toStr.call(value.callee) === '[object Function]';
};

var supportsStandardArguments = (function () {
	return isStandardArguments(arguments);
}());

isStandardArguments.isLegacyArguments = isLegacyArguments; // for tests

module.exports = supportsStandardArguments ? isStandardArguments : isLegacyArguments;


/***/ }),

/***/ "e439":
/***/ (function(module, exports, __webpack_require__) {

var $ = __webpack_require__("23e7");
var fails = __webpack_require__("d039");
var toIndexedObject = __webpack_require__("fc6a");
var nativeGetOwnPropertyDescriptor = __webpack_require__("06cf").f;
var DESCRIPTORS = __webpack_require__("83ab");

var FAILS_ON_PRIMITIVES = fails(function () { nativeGetOwnPropertyDescriptor(1); });
var FORCED = !DESCRIPTORS || FAILS_ON_PRIMITIVES;

// `Object.getOwnPropertyDescriptor` method
// https://tc39.github.io/ecma262/#sec-object.getownpropertydescriptor
$({ target: 'Object', stat: true, forced: FORCED, sham: !DESCRIPTORS }, {
  getOwnPropertyDescriptor: function getOwnPropertyDescriptor(it, key) {
    return nativeGetOwnPropertyDescriptor(toIndexedObject(it), key);
  }
});


/***/ }),

/***/ "e538":
/***/ (function(module, exports, __webpack_require__) {

var wellKnownSymbol = __webpack_require__("b622");

exports.f = wellKnownSymbol;


/***/ }),

/***/ "e667":
/***/ (function(module, exports) {

module.exports = function (exec) {
  try {
    return { error: false, value: exec() };
  } catch (error) {
    return { error: true, value: error };
  }
};


/***/ }),

/***/ "e6bd":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "e6cf":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var $ = __webpack_require__("23e7");
var IS_PURE = __webpack_require__("c430");
var global = __webpack_require__("da84");
var getBuiltIn = __webpack_require__("d066");
var NativePromise = __webpack_require__("fea9");
var redefine = __webpack_require__("6eeb");
var redefineAll = __webpack_require__("e2cc");
var setToStringTag = __webpack_require__("d44e");
var setSpecies = __webpack_require__("2626");
var isObject = __webpack_require__("861d");
var aFunction = __webpack_require__("1c0b");
var anInstance = __webpack_require__("19aa");
var classof = __webpack_require__("c6b6");
var inspectSource = __webpack_require__("8925");
var iterate = __webpack_require__("2266");
var checkCorrectnessOfIteration = __webpack_require__("1c7e");
var speciesConstructor = __webpack_require__("4840");
var task = __webpack_require__("2cf4").set;
var microtask = __webpack_require__("b575");
var promiseResolve = __webpack_require__("cdf9");
var hostReportErrors = __webpack_require__("44de");
var newPromiseCapabilityModule = __webpack_require__("f069");
var perform = __webpack_require__("e667");
var InternalStateModule = __webpack_require__("69f3");
var isForced = __webpack_require__("94ca");
var wellKnownSymbol = __webpack_require__("b622");
var V8_VERSION = __webpack_require__("2d00");

var SPECIES = wellKnownSymbol('species');
var PROMISE = 'Promise';
var getInternalState = InternalStateModule.get;
var setInternalState = InternalStateModule.set;
var getInternalPromiseState = InternalStateModule.getterFor(PROMISE);
var PromiseConstructor = NativePromise;
var TypeError = global.TypeError;
var document = global.document;
var process = global.process;
var $fetch = getBuiltIn('fetch');
var newPromiseCapability = newPromiseCapabilityModule.f;
var newGenericPromiseCapability = newPromiseCapability;
var IS_NODE = classof(process) == 'process';
var DISPATCH_EVENT = !!(document && document.createEvent && global.dispatchEvent);
var UNHANDLED_REJECTION = 'unhandledrejection';
var REJECTION_HANDLED = 'rejectionhandled';
var PENDING = 0;
var FULFILLED = 1;
var REJECTED = 2;
var HANDLED = 1;
var UNHANDLED = 2;
var Internal, OwnPromiseCapability, PromiseWrapper, nativeThen;

var FORCED = isForced(PROMISE, function () {
  var GLOBAL_CORE_JS_PROMISE = inspectSource(PromiseConstructor) !== String(PromiseConstructor);
  if (!GLOBAL_CORE_JS_PROMISE) {
    // V8 6.6 (Node 10 and Chrome 66) have a bug with resolving custom thenables
    // https://bugs.chromium.org/p/chromium/issues/detail?id=830565
    // We can't detect it synchronously, so just check versions
    if (V8_VERSION === 66) return true;
    // Unhandled rejections tracking support, NodeJS Promise without it fails @@species test
    if (!IS_NODE && typeof PromiseRejectionEvent != 'function') return true;
  }
  // We need Promise#finally in the pure version for preventing prototype pollution
  if (IS_PURE && !PromiseConstructor.prototype['finally']) return true;
  // We can't use @@species feature detection in V8 since it causes
  // deoptimization and performance degradation
  // https://github.com/zloirock/core-js/issues/679
  if (V8_VERSION >= 51 && /native code/.test(PromiseConstructor)) return false;
  // Detect correctness of subclassing with @@species support
  var promise = PromiseConstructor.resolve(1);
  var FakePromise = function (exec) {
    exec(function () { /* empty */ }, function () { /* empty */ });
  };
  var constructor = promise.constructor = {};
  constructor[SPECIES] = FakePromise;
  return !(promise.then(function () { /* empty */ }) instanceof FakePromise);
});

var INCORRECT_ITERATION = FORCED || !checkCorrectnessOfIteration(function (iterable) {
  PromiseConstructor.all(iterable)['catch'](function () { /* empty */ });
});

// helpers
var isThenable = function (it) {
  var then;
  return isObject(it) && typeof (then = it.then) == 'function' ? then : false;
};

var notify = function (promise, state, isReject) {
  if (state.notified) return;
  state.notified = true;
  var chain = state.reactions;
  microtask(function () {
    var value = state.value;
    var ok = state.state == FULFILLED;
    var index = 0;
    // variable length - can't use forEach
    while (chain.length > index) {
      var reaction = chain[index++];
      var handler = ok ? reaction.ok : reaction.fail;
      var resolve = reaction.resolve;
      var reject = reaction.reject;
      var domain = reaction.domain;
      var result, then, exited;
      try {
        if (handler) {
          if (!ok) {
            if (state.rejection === UNHANDLED) onHandleUnhandled(promise, state);
            state.rejection = HANDLED;
          }
          if (handler === true) result = value;
          else {
            if (domain) domain.enter();
            result = handler(value); // can throw
            if (domain) {
              domain.exit();
              exited = true;
            }
          }
          if (result === reaction.promise) {
            reject(TypeError('Promise-chain cycle'));
          } else if (then = isThenable(result)) {
            then.call(result, resolve, reject);
          } else resolve(result);
        } else reject(value);
      } catch (error) {
        if (domain && !exited) domain.exit();
        reject(error);
      }
    }
    state.reactions = [];
    state.notified = false;
    if (isReject && !state.rejection) onUnhandled(promise, state);
  });
};

var dispatchEvent = function (name, promise, reason) {
  var event, handler;
  if (DISPATCH_EVENT) {
    event = document.createEvent('Event');
    event.promise = promise;
    event.reason = reason;
    event.initEvent(name, false, true);
    global.dispatchEvent(event);
  } else event = { promise: promise, reason: reason };
  if (handler = global['on' + name]) handler(event);
  else if (name === UNHANDLED_REJECTION) hostReportErrors('Unhandled promise rejection', reason);
};

var onUnhandled = function (promise, state) {
  task.call(global, function () {
    var value = state.value;
    var IS_UNHANDLED = isUnhandled(state);
    var result;
    if (IS_UNHANDLED) {
      result = perform(function () {
        if (IS_NODE) {
          process.emit('unhandledRejection', value, promise);
        } else dispatchEvent(UNHANDLED_REJECTION, promise, value);
      });
      // Browsers should not trigger `rejectionHandled` event if it was handled here, NodeJS - should
      state.rejection = IS_NODE || isUnhandled(state) ? UNHANDLED : HANDLED;
      if (result.error) throw result.value;
    }
  });
};

var isUnhandled = function (state) {
  return state.rejection !== HANDLED && !state.parent;
};

var onHandleUnhandled = function (promise, state) {
  task.call(global, function () {
    if (IS_NODE) {
      process.emit('rejectionHandled', promise);
    } else dispatchEvent(REJECTION_HANDLED, promise, state.value);
  });
};

var bind = function (fn, promise, state, unwrap) {
  return function (value) {
    fn(promise, state, value, unwrap);
  };
};

var internalReject = function (promise, state, value, unwrap) {
  if (state.done) return;
  state.done = true;
  if (unwrap) state = unwrap;
  state.value = value;
  state.state = REJECTED;
  notify(promise, state, true);
};

var internalResolve = function (promise, state, value, unwrap) {
  if (state.done) return;
  state.done = true;
  if (unwrap) state = unwrap;
  try {
    if (promise === value) throw TypeError("Promise can't be resolved itself");
    var then = isThenable(value);
    if (then) {
      microtask(function () {
        var wrapper = { done: false };
        try {
          then.call(value,
            bind(internalResolve, promise, wrapper, state),
            bind(internalReject, promise, wrapper, state)
          );
        } catch (error) {
          internalReject(promise, wrapper, error, state);
        }
      });
    } else {
      state.value = value;
      state.state = FULFILLED;
      notify(promise, state, false);
    }
  } catch (error) {
    internalReject(promise, { done: false }, error, state);
  }
};

// constructor polyfill
if (FORCED) {
  // 25.4.3.1 Promise(executor)
  PromiseConstructor = function Promise(executor) {
    anInstance(this, PromiseConstructor, PROMISE);
    aFunction(executor);
    Internal.call(this);
    var state = getInternalState(this);
    try {
      executor(bind(internalResolve, this, state), bind(internalReject, this, state));
    } catch (error) {
      internalReject(this, state, error);
    }
  };
  // eslint-disable-next-line no-unused-vars
  Internal = function Promise(executor) {
    setInternalState(this, {
      type: PROMISE,
      done: false,
      notified: false,
      parent: false,
      reactions: [],
      rejection: false,
      state: PENDING,
      value: undefined
    });
  };
  Internal.prototype = redefineAll(PromiseConstructor.prototype, {
    // `Promise.prototype.then` method
    // https://tc39.github.io/ecma262/#sec-promise.prototype.then
    then: function then(onFulfilled, onRejected) {
      var state = getInternalPromiseState(this);
      var reaction = newPromiseCapability(speciesConstructor(this, PromiseConstructor));
      reaction.ok = typeof onFulfilled == 'function' ? onFulfilled : true;
      reaction.fail = typeof onRejected == 'function' && onRejected;
      reaction.domain = IS_NODE ? process.domain : undefined;
      state.parent = true;
      state.reactions.push(reaction);
      if (state.state != PENDING) notify(this, state, false);
      return reaction.promise;
    },
    // `Promise.prototype.catch` method
    // https://tc39.github.io/ecma262/#sec-promise.prototype.catch
    'catch': function (onRejected) {
      return this.then(undefined, onRejected);
    }
  });
  OwnPromiseCapability = function () {
    var promise = new Internal();
    var state = getInternalState(promise);
    this.promise = promise;
    this.resolve = bind(internalResolve, promise, state);
    this.reject = bind(internalReject, promise, state);
  };
  newPromiseCapabilityModule.f = newPromiseCapability = function (C) {
    return C === PromiseConstructor || C === PromiseWrapper
      ? new OwnPromiseCapability(C)
      : newGenericPromiseCapability(C);
  };

  if (!IS_PURE && typeof NativePromise == 'function') {
    nativeThen = NativePromise.prototype.then;

    // wrap native Promise#then for native async functions
    redefine(NativePromise.prototype, 'then', function then(onFulfilled, onRejected) {
      var that = this;
      return new PromiseConstructor(function (resolve, reject) {
        nativeThen.call(that, resolve, reject);
      }).then(onFulfilled, onRejected);
    // https://github.com/zloirock/core-js/issues/640
    }, { unsafe: true });

    // wrap fetch result
    if (typeof $fetch == 'function') $({ global: true, enumerable: true, forced: true }, {
      // eslint-disable-next-line no-unused-vars
      fetch: function fetch(input /* , init */) {
        return promiseResolve(PromiseConstructor, $fetch.apply(global, arguments));
      }
    });
  }
}

$({ global: true, wrap: true, forced: FORCED }, {
  Promise: PromiseConstructor
});

setToStringTag(PromiseConstructor, PROMISE, false, true);
setSpecies(PROMISE);

PromiseWrapper = getBuiltIn(PROMISE);

// statics
$({ target: PROMISE, stat: true, forced: FORCED }, {
  // `Promise.reject` method
  // https://tc39.github.io/ecma262/#sec-promise.reject
  reject: function reject(r) {
    var capability = newPromiseCapability(this);
    capability.reject.call(undefined, r);
    return capability.promise;
  }
});

$({ target: PROMISE, stat: true, forced: IS_PURE || FORCED }, {
  // `Promise.resolve` method
  // https://tc39.github.io/ecma262/#sec-promise.resolve
  resolve: function resolve(x) {
    return promiseResolve(IS_PURE && this === PromiseWrapper ? PromiseConstructor : this, x);
  }
});

$({ target: PROMISE, stat: true, forced: INCORRECT_ITERATION }, {
  // `Promise.all` method
  // https://tc39.github.io/ecma262/#sec-promise.all
  all: function all(iterable) {
    var C = this;
    var capability = newPromiseCapability(C);
    var resolve = capability.resolve;
    var reject = capability.reject;
    var result = perform(function () {
      var $promiseResolve = aFunction(C.resolve);
      var values = [];
      var counter = 0;
      var remaining = 1;
      iterate(iterable, function (promise) {
        var index = counter++;
        var alreadyCalled = false;
        values.push(undefined);
        remaining++;
        $promiseResolve.call(C, promise).then(function (value) {
          if (alreadyCalled) return;
          alreadyCalled = true;
          values[index] = value;
          --remaining || resolve(values);
        }, reject);
      });
      --remaining || resolve(values);
    });
    if (result.error) reject(result.value);
    return capability.promise;
  },
  // `Promise.race` method
  // https://tc39.github.io/ecma262/#sec-promise.race
  race: function race(iterable) {
    var C = this;
    var capability = newPromiseCapability(C);
    var reject = capability.reject;
    var result = perform(function () {
      var $promiseResolve = aFunction(C.resolve);
      iterate(iterable, function (promise) {
        $promiseResolve.call(C, promise).then(capability.resolve, reject);
      });
    });
    if (result.error) reject(result.value);
    return capability.promise;
  }
});


/***/ }),

/***/ "e710":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var define = __webpack_require__("f367");
var callBind = __webpack_require__("ff8e");

var implementation = __webpack_require__("5708");
var getPolyfill = __webpack_require__("57ec");
var shim = __webpack_require__("1c7e9");

var flagsBound = callBind(implementation);

define(flagsBound, {
	getPolyfill: getPolyfill,
	implementation: implementation,
	shim: shim
});

module.exports = flagsBound;


/***/ }),

/***/ "e839":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ReferenceSection_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("3572");
/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ReferenceSection_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ReferenceSection_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */
 /* unused harmony default export */ var _unused_webpack_default_export = (_node_modules_mini_css_extract_plugin_dist_loader_js_ref_8_oneOf_1_0_node_modules_css_loader_dist_cjs_js_ref_8_oneOf_1_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_8_oneOf_1_2_node_modules_sass_loader_dist_cjs_js_ref_8_oneOf_1_3_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ReferenceSection_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "e893":
/***/ (function(module, exports, __webpack_require__) {

var has = __webpack_require__("5135");
var ownKeys = __webpack_require__("56ef");
var getOwnPropertyDescriptorModule = __webpack_require__("06cf");
var definePropertyModule = __webpack_require__("9bf2");

module.exports = function (target, source) {
  var keys = ownKeys(source);
  var defineProperty = definePropertyModule.f;
  var getOwnPropertyDescriptor = getOwnPropertyDescriptorModule.f;
  for (var i = 0; i < keys.length; i++) {
    var key = keys[i];
    if (!has(target, key)) defineProperty(target, key, getOwnPropertyDescriptor(source, key));
  }
};


/***/ }),

/***/ "e8b5":
/***/ (function(module, exports, __webpack_require__) {

var classof = __webpack_require__("c6b6");

// `IsArray` abstract operation
// https://tc39.github.io/ecma262/#sec-isarray
module.exports = Array.isArray || function isArray(arg) {
  return classof(arg) == 'Array';
};


/***/ }),

/***/ "e95a":
/***/ (function(module, exports, __webpack_require__) {

var wellKnownSymbol = __webpack_require__("b622");
var Iterators = __webpack_require__("3f8c");

var ITERATOR = wellKnownSymbol('iterator');
var ArrayPrototype = Array.prototype;

// check on default Array iterator
module.exports = function (it) {
  return it !== undefined && (Iterators.Array === it || ArrayPrototype[ITERATOR] === it);
};


/***/ }),

/***/ "f069":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var aFunction = __webpack_require__("1c0b");

var PromiseCapability = function (C) {
  var resolve, reject;
  this.promise = new C(function ($$resolve, $$reject) {
    if (resolve !== undefined || reject !== undefined) throw TypeError('Bad Promise constructor');
    resolve = $$resolve;
    reject = $$reject;
  });
  this.resolve = aFunction(resolve);
  this.reject = aFunction(reject);
};

// 25.4.1.5 NewPromiseCapability(C)
module.exports.f = function (C) {
  return new PromiseCapability(C);
};


/***/ }),

/***/ "f183":
/***/ (function(module, exports, __webpack_require__) {

var hiddenKeys = __webpack_require__("d012");
var isObject = __webpack_require__("861d");
var has = __webpack_require__("5135");
var defineProperty = __webpack_require__("9bf2").f;
var uid = __webpack_require__("90e3");
var FREEZING = __webpack_require__("bb2f");

var METADATA = uid('meta');
var id = 0;

var isExtensible = Object.isExtensible || function () {
  return true;
};

var setMetadata = function (it) {
  defineProperty(it, METADATA, { value: {
    objectID: 'O' + ++id, // object ID
    weakData: {}          // weak collections IDs
  } });
};

var fastKey = function (it, create) {
  // return a primitive with prefix
  if (!isObject(it)) return typeof it == 'symbol' ? it : (typeof it == 'string' ? 'S' : 'P') + it;
  if (!has(it, METADATA)) {
    // can't set metadata to uncaught frozen object
    if (!isExtensible(it)) return 'F';
    // not necessary to add metadata
    if (!create) return 'E';
    // add missing metadata
    setMetadata(it);
  // return object ID
  } return it[METADATA].objectID;
};

var getWeakData = function (it, create) {
  if (!has(it, METADATA)) {
    // can't set metadata to uncaught frozen object
    if (!isExtensible(it)) return true;
    // not necessary to add metadata
    if (!create) return false;
    // add missing metadata
    setMetadata(it);
  // return the store of weak collections IDs
  } return it[METADATA].weakData;
};

// add metadata on freeze-family methods calling
var onFreeze = function (it) {
  if (FREEZING && meta.REQUIRED && isExtensible(it) && !has(it, METADATA)) setMetadata(it);
  return it;
};

var meta = module.exports = {
  REQUIRED: false,
  fastKey: fastKey,
  getWeakData: getWeakData,
  onFreeze: onFreeze
};

hiddenKeys[METADATA] = true;


/***/ }),

/***/ "f367":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var keys = __webpack_require__("d6c7");
var hasSymbols = typeof Symbol === 'function' && typeof Symbol('foo') === 'symbol';

var toStr = Object.prototype.toString;
var concat = Array.prototype.concat;
var origDefineProperty = Object.defineProperty;

var isFunction = function (fn) {
	return typeof fn === 'function' && toStr.call(fn) === '[object Function]';
};

var arePropertyDescriptorsSupported = function () {
	var obj = {};
	try {
		origDefineProperty(obj, 'x', { enumerable: false, value: obj });
		// eslint-disable-next-line no-unused-vars, no-restricted-syntax
		for (var _ in obj) { // jscs:ignore disallowUnusedVariables
			return false;
		}
		return obj.x === obj;
	} catch (e) { /* this is IE 8. */
		return false;
	}
};
var supportsDescriptors = origDefineProperty && arePropertyDescriptorsSupported();

var defineProperty = function (object, name, value, predicate) {
	if (name in object && (!isFunction(predicate) || !predicate())) {
		return;
	}
	if (supportsDescriptors) {
		origDefineProperty(object, name, {
			configurable: true,
			enumerable: false,
			value: value,
			writable: true
		});
	} else {
		object[name] = value;
	}
};

var defineProperties = function (object, map) {
	var predicates = arguments.length > 2 ? arguments[2] : {};
	var props = keys(map);
	if (hasSymbols) {
		props = concat.call(props, Object.getOwnPropertySymbols(map));
	}
	for (var i = 0; i < props.length; i += 1) {
		defineProperty(object, props[i], map[props[i]], predicates[props[i]]);
	}
};

defineProperties.supportsDescriptors = !!supportsDescriptors;

module.exports = defineProperties;


/***/ }),

/***/ "f5df":
/***/ (function(module, exports, __webpack_require__) {

var TO_STRING_TAG_SUPPORT = __webpack_require__("00ee");
var classofRaw = __webpack_require__("c6b6");
var wellKnownSymbol = __webpack_require__("b622");

var TO_STRING_TAG = wellKnownSymbol('toStringTag');
// ES3 wrong here
var CORRECT_ARGUMENTS = classofRaw(function () { return arguments; }()) == 'Arguments';

// fallback for IE11 Script Access Denied error
var tryGet = function (it, key) {
  try {
    return it[key];
  } catch (error) { /* empty */ }
};

// getting tag from ES6+ `Object.prototype.toString`
module.exports = TO_STRING_TAG_SUPPORT ? classofRaw : function (it) {
  var O, tag, result;
  return it === undefined ? 'Undefined' : it === null ? 'Null'
    // @@toStringTag case
    : typeof (tag = tryGet(O = Object(it), TO_STRING_TAG)) == 'string' ? tag
    // builtinTag case
    : CORRECT_ARGUMENTS ? classofRaw(O)
    // ES3 arguments fallback
    : (result = classofRaw(O)) == 'Object' && typeof O.callee == 'function' ? 'Arguments' : result;
};


/***/ }),

/***/ "f6fd":
/***/ (function(module, exports) {

// document.currentScript polyfill by Adam Miller

// MIT license

(function(document){
  var currentScript = "currentScript",
      scripts = document.getElementsByTagName('script'); // Live NodeList collection

  // If browser needs currentScript polyfill, add get currentScript() to the document object
  if (!(currentScript in document)) {
    Object.defineProperty(document, currentScript, {
      get: function(){

        // IE 6-10 supports script readyState
        // IE 10+ support stack trace
        try { throw new Error(); }
        catch (err) {

          // Find the second match for the "at" string to get file src url from stack.
          // Specifically works with the format of stack traces in IE.
          var i, res = ((/.*at [^\(]*\((.*):.+:.+\)$/ig).exec(err.stack) || [false])[1];

          // For all scripts on the page, if src matches or if ready state is interactive, return the script tag
          for(i in scripts){
            if(scripts[i].src == res || scripts[i].readyState == "interactive"){
              return scripts[i];
            }
          }

          // If no match, return null
          return null;
        }
      }
    });
  }
})(document);


/***/ }),

/***/ "f772":
/***/ (function(module, exports, __webpack_require__) {

var shared = __webpack_require__("5692");
var uid = __webpack_require__("90e3");

var keys = shared('keys');

module.exports = function (key) {
  return keys[key] || (keys[key] = uid(key));
};


/***/ }),

/***/ "faa1":
/***/ (function(module, exports, __webpack_require__) {

"use strict";
// Copyright Joyent, Inc. and other Node contributors.
//
// Permission is hereby granted, free of charge, to any person obtaining a
// copy of this software and associated documentation files (the
// "Software"), to deal in the Software without restriction, including
// without limitation the rights to use, copy, modify, merge, publish,
// distribute, sublicense, and/or sell copies of the Software, and to permit
// persons to whom the Software is furnished to do so, subject to the
// following conditions:
//
// The above copyright notice and this permission notice shall be included
// in all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
// OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
// MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN
// NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
// DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
// OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE
// USE OR OTHER DEALINGS IN THE SOFTWARE.



var R = typeof Reflect === 'object' ? Reflect : null
var ReflectApply = R && typeof R.apply === 'function'
  ? R.apply
  : function ReflectApply(target, receiver, args) {
    return Function.prototype.apply.call(target, receiver, args);
  }

var ReflectOwnKeys
if (R && typeof R.ownKeys === 'function') {
  ReflectOwnKeys = R.ownKeys
} else if (Object.getOwnPropertySymbols) {
  ReflectOwnKeys = function ReflectOwnKeys(target) {
    return Object.getOwnPropertyNames(target)
      .concat(Object.getOwnPropertySymbols(target));
  };
} else {
  ReflectOwnKeys = function ReflectOwnKeys(target) {
    return Object.getOwnPropertyNames(target);
  };
}

function ProcessEmitWarning(warning) {
  if (console && console.warn) console.warn(warning);
}

var NumberIsNaN = Number.isNaN || function NumberIsNaN(value) {
  return value !== value;
}

function EventEmitter() {
  EventEmitter.init.call(this);
}
module.exports = EventEmitter;

// Backwards-compat with node 0.10.x
EventEmitter.EventEmitter = EventEmitter;

EventEmitter.prototype._events = undefined;
EventEmitter.prototype._eventsCount = 0;
EventEmitter.prototype._maxListeners = undefined;

// By default EventEmitters will print a warning if more than 10 listeners are
// added to it. This is a useful default which helps finding memory leaks.
var defaultMaxListeners = 10;

function checkListener(listener) {
  if (typeof listener !== 'function') {
    throw new TypeError('The "listener" argument must be of type Function. Received type ' + typeof listener);
  }
}

Object.defineProperty(EventEmitter, 'defaultMaxListeners', {
  enumerable: true,
  get: function() {
    return defaultMaxListeners;
  },
  set: function(arg) {
    if (typeof arg !== 'number' || arg < 0 || NumberIsNaN(arg)) {
      throw new RangeError('The value of "defaultMaxListeners" is out of range. It must be a non-negative number. Received ' + arg + '.');
    }
    defaultMaxListeners = arg;
  }
});

EventEmitter.init = function() {

  if (this._events === undefined ||
      this._events === Object.getPrototypeOf(this)._events) {
    this._events = Object.create(null);
    this._eventsCount = 0;
  }

  this._maxListeners = this._maxListeners || undefined;
};

// Obviously not all Emitters should be limited to 10. This function allows
// that to be increased. Set to zero for unlimited.
EventEmitter.prototype.setMaxListeners = function setMaxListeners(n) {
  if (typeof n !== 'number' || n < 0 || NumberIsNaN(n)) {
    throw new RangeError('The value of "n" is out of range. It must be a non-negative number. Received ' + n + '.');
  }
  this._maxListeners = n;
  return this;
};

function _getMaxListeners(that) {
  if (that._maxListeners === undefined)
    return EventEmitter.defaultMaxListeners;
  return that._maxListeners;
}

EventEmitter.prototype.getMaxListeners = function getMaxListeners() {
  return _getMaxListeners(this);
};

EventEmitter.prototype.emit = function emit(type) {
  var args = [];
  for (var i = 1; i < arguments.length; i++) args.push(arguments[i]);
  var doError = (type === 'error');

  var events = this._events;
  if (events !== undefined)
    doError = (doError && events.error === undefined);
  else if (!doError)
    return false;

  // If there is no 'error' event listener then throw.
  if (doError) {
    var er;
    if (args.length > 0)
      er = args[0];
    if (er instanceof Error) {
      // Note: The comments on the `throw` lines are intentional, they show
      // up in Node's output if this results in an unhandled exception.
      throw er; // Unhandled 'error' event
    }
    // At least give some kind of context to the user
    var err = new Error('Unhandled error.' + (er ? ' (' + er.message + ')' : ''));
    err.context = er;
    throw err; // Unhandled 'error' event
  }

  var handler = events[type];

  if (handler === undefined)
    return false;

  if (typeof handler === 'function') {
    ReflectApply(handler, this, args);
  } else {
    var len = handler.length;
    var listeners = arrayClone(handler, len);
    for (var i = 0; i < len; ++i)
      ReflectApply(listeners[i], this, args);
  }

  return true;
};

function _addListener(target, type, listener, prepend) {
  var m;
  var events;
  var existing;

  checkListener(listener);

  events = target._events;
  if (events === undefined) {
    events = target._events = Object.create(null);
    target._eventsCount = 0;
  } else {
    // To avoid recursion in the case that type === "newListener"! Before
    // adding it to the listeners, first emit "newListener".
    if (events.newListener !== undefined) {
      target.emit('newListener', type,
                  listener.listener ? listener.listener : listener);

      // Re-assign `events` because a newListener handler could have caused the
      // this._events to be assigned to a new object
      events = target._events;
    }
    existing = events[type];
  }

  if (existing === undefined) {
    // Optimize the case of one listener. Don't need the extra array object.
    existing = events[type] = listener;
    ++target._eventsCount;
  } else {
    if (typeof existing === 'function') {
      // Adding the second element, need to change to array.
      existing = events[type] =
        prepend ? [listener, existing] : [existing, listener];
      // If we've already got an array, just append.
    } else if (prepend) {
      existing.unshift(listener);
    } else {
      existing.push(listener);
    }

    // Check for listener leak
    m = _getMaxListeners(target);
    if (m > 0 && existing.length > m && !existing.warned) {
      existing.warned = true;
      // No error code for this since it is a Warning
      // eslint-disable-next-line no-restricted-syntax
      var w = new Error('Possible EventEmitter memory leak detected. ' +
                          existing.length + ' ' + String(type) + ' listeners ' +
                          'added. Use emitter.setMaxListeners() to ' +
                          'increase limit');
      w.name = 'MaxListenersExceededWarning';
      w.emitter = target;
      w.type = type;
      w.count = existing.length;
      ProcessEmitWarning(w);
    }
  }

  return target;
}

EventEmitter.prototype.addListener = function addListener(type, listener) {
  return _addListener(this, type, listener, false);
};

EventEmitter.prototype.on = EventEmitter.prototype.addListener;

EventEmitter.prototype.prependListener =
    function prependListener(type, listener) {
      return _addListener(this, type, listener, true);
    };

function onceWrapper() {
  if (!this.fired) {
    this.target.removeListener(this.type, this.wrapFn);
    this.fired = true;
    if (arguments.length === 0)
      return this.listener.call(this.target);
    return this.listener.apply(this.target, arguments);
  }
}

function _onceWrap(target, type, listener) {
  var state = { fired: false, wrapFn: undefined, target: target, type: type, listener: listener };
  var wrapped = onceWrapper.bind(state);
  wrapped.listener = listener;
  state.wrapFn = wrapped;
  return wrapped;
}

EventEmitter.prototype.once = function once(type, listener) {
  checkListener(listener);
  this.on(type, _onceWrap(this, type, listener));
  return this;
};

EventEmitter.prototype.prependOnceListener =
    function prependOnceListener(type, listener) {
      checkListener(listener);
      this.prependListener(type, _onceWrap(this, type, listener));
      return this;
    };

// Emits a 'removeListener' event if and only if the listener was removed.
EventEmitter.prototype.removeListener =
    function removeListener(type, listener) {
      var list, events, position, i, originalListener;

      checkListener(listener);

      events = this._events;
      if (events === undefined)
        return this;

      list = events[type];
      if (list === undefined)
        return this;

      if (list === listener || list.listener === listener) {
        if (--this._eventsCount === 0)
          this._events = Object.create(null);
        else {
          delete events[type];
          if (events.removeListener)
            this.emit('removeListener', type, list.listener || listener);
        }
      } else if (typeof list !== 'function') {
        position = -1;

        for (i = list.length - 1; i >= 0; i--) {
          if (list[i] === listener || list[i].listener === listener) {
            originalListener = list[i].listener;
            position = i;
            break;
          }
        }

        if (position < 0)
          return this;

        if (position === 0)
          list.shift();
        else {
          spliceOne(list, position);
        }

        if (list.length === 1)
          events[type] = list[0];

        if (events.removeListener !== undefined)
          this.emit('removeListener', type, originalListener || listener);
      }

      return this;
    };

EventEmitter.prototype.off = EventEmitter.prototype.removeListener;

EventEmitter.prototype.removeAllListeners =
    function removeAllListeners(type) {
      var listeners, events, i;

      events = this._events;
      if (events === undefined)
        return this;

      // not listening for removeListener, no need to emit
      if (events.removeListener === undefined) {
        if (arguments.length === 0) {
          this._events = Object.create(null);
          this._eventsCount = 0;
        } else if (events[type] !== undefined) {
          if (--this._eventsCount === 0)
            this._events = Object.create(null);
          else
            delete events[type];
        }
        return this;
      }

      // emit removeListener for all listeners on all events
      if (arguments.length === 0) {
        var keys = Object.keys(events);
        var key;
        for (i = 0; i < keys.length; ++i) {
          key = keys[i];
          if (key === 'removeListener') continue;
          this.removeAllListeners(key);
        }
        this.removeAllListeners('removeListener');
        this._events = Object.create(null);
        this._eventsCount = 0;
        return this;
      }

      listeners = events[type];

      if (typeof listeners === 'function') {
        this.removeListener(type, listeners);
      } else if (listeners !== undefined) {
        // LIFO order
        for (i = listeners.length - 1; i >= 0; i--) {
          this.removeListener(type, listeners[i]);
        }
      }

      return this;
    };

function _listeners(target, type, unwrap) {
  var events = target._events;

  if (events === undefined)
    return [];

  var evlistener = events[type];
  if (evlistener === undefined)
    return [];

  if (typeof evlistener === 'function')
    return unwrap ? [evlistener.listener || evlistener] : [evlistener];

  return unwrap ?
    unwrapListeners(evlistener) : arrayClone(evlistener, evlistener.length);
}

EventEmitter.prototype.listeners = function listeners(type) {
  return _listeners(this, type, true);
};

EventEmitter.prototype.rawListeners = function rawListeners(type) {
  return _listeners(this, type, false);
};

EventEmitter.listenerCount = function(emitter, type) {
  if (typeof emitter.listenerCount === 'function') {
    return emitter.listenerCount(type);
  } else {
    return listenerCount.call(emitter, type);
  }
};

EventEmitter.prototype.listenerCount = listenerCount;
function listenerCount(type) {
  var events = this._events;

  if (events !== undefined) {
    var evlistener = events[type];

    if (typeof evlistener === 'function') {
      return 1;
    } else if (evlistener !== undefined) {
      return evlistener.length;
    }
  }

  return 0;
}

EventEmitter.prototype.eventNames = function eventNames() {
  return this._eventsCount > 0 ? ReflectOwnKeys(this._events) : [];
};

function arrayClone(arr, n) {
  var copy = new Array(n);
  for (var i = 0; i < n; ++i)
    copy[i] = arr[i];
  return copy;
}

function spliceOne(list, index) {
  for (; index + 1 < list.length; index++)
    list[index] = list[index + 1];
  list.pop();
}

function unwrapListeners(arr) {
  var ret = new Array(arr.length);
  for (var i = 0; i < ret.length; ++i) {
    ret[i] = arr[i].listener || arr[i];
  }
  return ret;
}


/***/ }),

/***/ "fae3":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);

// CONCATENATED MODULE: ./node_modules/@vue/cli-service/lib/commands/build/setPublicPath.js
// This file is imported into lib/wc client bundles.

if (typeof window !== 'undefined') {
  if (true) {
    __webpack_require__("f6fd")
  }

  var setPublicPath_i
  if ((setPublicPath_i = window.document.currentScript) && (setPublicPath_i = setPublicPath_i.src.match(/(.+\/)[^/]+\.js(\?.*)?$/))) {
    __webpack_require__.p = setPublicPath_i[1] // eslint-disable-line
  }
}

// Indicate to webpack that this file can be concatenated
/* harmony default export */ var setPublicPath = (null);

// EXTERNAL MODULE: ./node_modules/core-js/modules/es.object.values.js
var es_object_values = __webpack_require__("07ac");

// EXTERNAL MODULE: external {"commonjs":"vue2","commonjs2":"vue2","amd":"vue2","root":"vue2"}
var external_commonjs_vue2_commonjs2_vue2_amd_vue2_root_vue2_ = __webpack_require__("8bbf");
var external_commonjs_vue2_commonjs2_vue2_amd_vue2_root_vue2_default = /*#__PURE__*/__webpack_require__.n(external_commonjs_vue2_commonjs2_vue2_amd_vue2_root_vue2_);

// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js?{"cacheDirectory":"node_modules/.cache/vue-loader","cacheIdentifier":"42133e48-vue-loader-template"}!./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/App.vue?vue&type=template&id=41589f27&
var render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"wb-db-app",attrs:{"id":"data-bridge-app"}},[_c('ProcessDialogHeader',{staticClass:"wb-db-app__header",scopedSlots:_vm._u([{key:"title",fn:function(){return [_c('span',{domProps:{"innerHTML":_vm._s(_vm.title)}})]},proxy:true},{key:"primaryAction",fn:function(){return [(!_vm.hasError)?_c('EventEmittingButton',{attrs:{"message":_vm.$messages.get( _vm.publishOrSave ),"type":"primaryProgressive","squary":true,"disabled":!_vm.canSave},on:{"click":_vm.saveAndClose}}):_vm._e()]},proxy:true},{key:"safeAction",fn:function(){return [_c('EventEmittingButton',{attrs:{"message":_vm.$messages.get( _vm.$messages.KEYS.CANCEL ),"type":"cancel","squary":true},on:{"click":_vm.cancel}})]},proxy:true}])}),_c('div',{staticClass:"wb-db-app__body"},[(_vm.hasError)?_c('ErrorWrapper'):_c('Initializing',{attrs:{"is-initializing":_vm.isInitializing}},[_c('DataBridge')],1)],1)],1)}
var staticRenderFns = []


// CONCATENATED MODULE: ./src/presentation/App.vue?vue&type=template&id=41589f27&

// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/classCallCheck.js
function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}
// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/createClass.js
function _defineProperties(target, props) {
  for (var i = 0; i < props.length; i++) {
    var descriptor = props[i];
    descriptor.enumerable = descriptor.enumerable || false;
    descriptor.configurable = true;
    if ("value" in descriptor) descriptor.writable = true;
    Object.defineProperty(target, descriptor.key, descriptor);
  }
}

function _createClass(Constructor, protoProps, staticProps) {
  if (protoProps) _defineProperties(Constructor.prototype, protoProps);
  if (staticProps) _defineProperties(Constructor, staticProps);
  return Constructor;
}
// EXTERNAL MODULE: ./node_modules/core-js/modules/es.symbol.js
var es_symbol = __webpack_require__("a4d3");

// EXTERNAL MODULE: ./node_modules/core-js/modules/es.symbol.description.js
var es_symbol_description = __webpack_require__("e01a");

// EXTERNAL MODULE: ./node_modules/core-js/modules/es.symbol.iterator.js
var es_symbol_iterator = __webpack_require__("d28b");

// EXTERNAL MODULE: ./node_modules/core-js/modules/es.array.iterator.js
var es_array_iterator = __webpack_require__("e260");

// EXTERNAL MODULE: ./node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("d3b7");

// EXTERNAL MODULE: ./node_modules/core-js/modules/es.string.iterator.js
var es_string_iterator = __webpack_require__("3ca3");

// EXTERNAL MODULE: ./node_modules/core-js/modules/web.dom-collections.iterator.js
var web_dom_collections_iterator = __webpack_require__("ddb0");

// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/typeof.js







function _typeof(obj) {
  "@babel/helpers - typeof";

  if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") {
    _typeof = function _typeof(obj) {
      return typeof obj;
    };
  } else {
    _typeof = function _typeof(obj) {
      return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
    };
  }

  return _typeof(obj);
}
// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js
function _assertThisInitialized(self) {
  if (self === void 0) {
    throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
  }

  return self;
}
// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js


function _possibleConstructorReturn(self, call) {
  if (call && (_typeof(call) === "object" || typeof call === "function")) {
    return call;
  }

  return _assertThisInitialized(self);
}
// EXTERNAL MODULE: ./node_modules/core-js/modules/es.object.get-prototype-of.js
var es_object_get_prototype_of = __webpack_require__("3410");

// EXTERNAL MODULE: ./node_modules/core-js/modules/es.object.set-prototype-of.js
var es_object_set_prototype_of = __webpack_require__("131a");

// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js


function _getPrototypeOf(o) {
  _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) {
    return o.__proto__ || Object.getPrototypeOf(o);
  };
  return _getPrototypeOf(o);
}
// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/setPrototypeOf.js

function _setPrototypeOf(o, p) {
  _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) {
    o.__proto__ = p;
    return o;
  };

  return _setPrototypeOf(o, p);
}
// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/inherits.js

function _inherits(subClass, superClass) {
  if (typeof superClass !== "function" && superClass !== null) {
    throw new TypeError("Super expression must either be null or a function");
  }

  subClass.prototype = Object.create(superClass && superClass.prototype, {
    constructor: {
      value: subClass,
      writable: true,
      configurable: true
    }
  });
  if (superClass) _setPrototypeOf(subClass, superClass);
}
// CONCATENATED MODULE: ./node_modules/tslib/tslib.es6.js
/*! *****************************************************************************
Copyright (c) Microsoft Corporation. All rights reserved.
Licensed under the Apache License, Version 2.0 (the "License"); you may not use
this file except in compliance with the License. You may obtain a copy of the
License at http://www.apache.org/licenses/LICENSE-2.0

THIS CODE IS PROVIDED ON AN *AS IS* BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
KIND, EITHER EXPRESS OR IMPLIED, INCLUDING WITHOUT LIMITATION ANY IMPLIED
WARRANTIES OR CONDITIONS OF TITLE, FITNESS FOR A PARTICULAR PURPOSE,
MERCHANTABLITY OR NON-INFRINGEMENT.

See the Apache Version 2.0 License for specific language governing permissions
and limitations under the License.
***************************************************************************** */
/* global Reflect, Promise */

var extendStatics = function(d, b) {
    extendStatics = Object.setPrototypeOf ||
        ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
        function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
    return extendStatics(d, b);
};

function __extends(d, b) {
    extendStatics(d, b);
    function __() { this.constructor = d; }
    d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
}

var __assign = function() {
    __assign = Object.assign || function __assign(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p)) t[p] = s[p];
        }
        return t;
    }
    return __assign.apply(this, arguments);
}

function __rest(s, e) {
    var t = {};
    for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p) && e.indexOf(p) < 0)
        t[p] = s[p];
    if (s != null && typeof Object.getOwnPropertySymbols === "function")
        for (var i = 0, p = Object.getOwnPropertySymbols(s); i < p.length; i++) {
            if (e.indexOf(p[i]) < 0 && Object.prototype.propertyIsEnumerable.call(s, p[i]))
                t[p[i]] = s[p[i]];
        }
    return t;
}

function __decorate(decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
}

function __param(paramIndex, decorator) {
    return function (target, key) { decorator(target, key, paramIndex); }
}

function __metadata(metadataKey, metadataValue) {
    if (typeof Reflect === "object" && typeof Reflect.metadata === "function") return Reflect.metadata(metadataKey, metadataValue);
}

function __awaiter(thisArg, _arguments, P, generator) {
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : new P(function (resolve) { resolve(result.value); }).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
}

function __generator(thisArg, body) {
    var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g;
    return g = { next: verb(0), "throw": verb(1), "return": verb(2) }, typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
    function verb(n) { return function (v) { return step([n, v]); }; }
    function step(op) {
        if (f) throw new TypeError("Generator is already executing.");
        while (_) try {
            if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
            if (y = 0, t) op = [op[0] & 2, t.value];
            switch (op[0]) {
                case 0: case 1: t = op; break;
                case 4: _.label++; return { value: op[1], done: false };
                case 5: _.label++; y = op[1]; op = [0]; continue;
                case 7: op = _.ops.pop(); _.trys.pop(); continue;
                default:
                    if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                    if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                    if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                    if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                    if (t[2]) _.ops.pop();
                    _.trys.pop(); continue;
            }
            op = body.call(thisArg, _);
        } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
        if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
    }
}

function __exportStar(m, exports) {
    for (var p in m) if (!exports.hasOwnProperty(p)) exports[p] = m[p];
}

function __values(o) {
    var m = typeof Symbol === "function" && o[Symbol.iterator], i = 0;
    if (m) return m.call(o);
    return {
        next: function () {
            if (o && i >= o.length) o = void 0;
            return { value: o && o[i++], done: !o };
        }
    };
}

function __read(o, n) {
    var m = typeof Symbol === "function" && o[Symbol.iterator];
    if (!m) return o;
    var i = m.call(o), r, ar = [], e;
    try {
        while ((n === void 0 || n-- > 0) && !(r = i.next()).done) ar.push(r.value);
    }
    catch (error) { e = { error: error }; }
    finally {
        try {
            if (r && !r.done && (m = i["return"])) m.call(i);
        }
        finally { if (e) throw e.error; }
    }
    return ar;
}

function __spread() {
    for (var ar = [], i = 0; i < arguments.length; i++)
        ar = ar.concat(__read(arguments[i]));
    return ar;
}

function __spreadArrays() {
    for (var s = 0, i = 0, il = arguments.length; i < il; i++) s += arguments[i].length;
    for (var r = Array(s), k = 0, i = 0; i < il; i++)
        for (var a = arguments[i], j = 0, jl = a.length; j < jl; j++, k++)
            r[k] = a[j];
    return r;
};

function __await(v) {
    return this instanceof __await ? (this.v = v, this) : new __await(v);
}

function __asyncGenerator(thisArg, _arguments, generator) {
    if (!Symbol.asyncIterator) throw new TypeError("Symbol.asyncIterator is not defined.");
    var g = generator.apply(thisArg, _arguments || []), i, q = [];
    return i = {}, verb("next"), verb("throw"), verb("return"), i[Symbol.asyncIterator] = function () { return this; }, i;
    function verb(n) { if (g[n]) i[n] = function (v) { return new Promise(function (a, b) { q.push([n, v, a, b]) > 1 || resume(n, v); }); }; }
    function resume(n, v) { try { step(g[n](v)); } catch (e) { settle(q[0][3], e); } }
    function step(r) { r.value instanceof __await ? Promise.resolve(r.value.v).then(fulfill, reject) : settle(q[0][2], r); }
    function fulfill(value) { resume("next", value); }
    function reject(value) { resume("throw", value); }
    function settle(f, v) { if (f(v), q.shift(), q.length) resume(q[0][0], q[0][1]); }
}

function __asyncDelegator(o) {
    var i, p;
    return i = {}, verb("next"), verb("throw", function (e) { throw e; }), verb("return"), i[Symbol.iterator] = function () { return this; }, i;
    function verb(n, f) { i[n] = o[n] ? function (v) { return (p = !p) ? { value: __await(o[n](v)), done: n === "return" } : f ? f(v) : v; } : f; }
}

function __asyncValues(o) {
    if (!Symbol.asyncIterator) throw new TypeError("Symbol.asyncIterator is not defined.");
    var m = o[Symbol.asyncIterator], i;
    return m ? m.call(o) : (o = typeof __values === "function" ? __values(o) : o[Symbol.iterator](), i = {}, verb("next"), verb("throw"), verb("return"), i[Symbol.asyncIterator] = function () { return this; }, i);
    function verb(n) { i[n] = o[n] && function (v) { return new Promise(function (resolve, reject) { v = o[n](v), settle(resolve, reject, v.done, v.value); }); }; }
    function settle(resolve, reject, d, v) { Promise.resolve(v).then(function(v) { resolve({ value: v, done: d }); }, reject); }
}

function __makeTemplateObject(cooked, raw) {
    if (Object.defineProperty) { Object.defineProperty(cooked, "raw", { value: raw }); } else { cooked.raw = raw; }
    return cooked;
};

function __importStar(mod) {
    if (mod && mod.__esModule) return mod;
    var result = {};
    if (mod != null) for (var k in mod) if (Object.hasOwnProperty.call(mod, k)) result[k] = mod[k];
    result.default = mod;
    return result;
}

function __importDefault(mod) {
    return (mod && mod.__esModule) ? mod : { default: mod };
}

// EXTERNAL MODULE: ./node_modules/vue-class-component/dist/vue-class-component.common.js
var vue_class_component_common = __webpack_require__("65d9");
var vue_class_component_common_default = /*#__PURE__*/__webpack_require__.n(vue_class_component_common);

// EXTERNAL MODULE: ./node_modules/vue-property-decorator/lib/vue-property-decorator.js
var vue_property_decorator = __webpack_require__("60a3");

// CONCATENATED MODULE: ./src/events/index.ts
var events;

(function (events) {
  events["onSaved"] = "saved";
  events["onCancel"] = "cancel";
})(events || (events = {}));

/* harmony default export */ var src_events = (events);
// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/defineProperty.js
function _defineProperty(obj, key, value) {
  if (key in obj) {
    Object.defineProperty(obj, key, {
      value: value,
      enumerable: true,
      configurable: true,
      writable: true
    });
  } else {
    obj[key] = value;
  }

  return obj;
}
// EXTERNAL MODULE: ./node_modules/vuex/dist/vuex.esm.js
var vuex_esm = __webpack_require__("2f62");

// EXTERNAL MODULE: ./node_modules/core-js/modules/es.array.filter.js
var es_array_filter = __webpack_require__("4de4");

// EXTERNAL MODULE: ./node_modules/core-js/modules/es.array.for-each.js
var es_array_for_each = __webpack_require__("4160");

// EXTERNAL MODULE: ./node_modules/core-js/modules/es.object.get-own-property-descriptor.js
var es_object_get_own_property_descriptor = __webpack_require__("e439");

// EXTERNAL MODULE: ./node_modules/core-js/modules/es.object.get-own-property-descriptors.js
var es_object_get_own_property_descriptors = __webpack_require__("dbb4");

// EXTERNAL MODULE: ./node_modules/core-js/modules/es.object.keys.js
var es_object_keys = __webpack_require__("b64b");

// EXTERNAL MODULE: ./node_modules/core-js/modules/es.promise.js
var es_promise = __webpack_require__("e6cf");

// EXTERNAL MODULE: ./node_modules/core-js/modules/web.dom-collections.for-each.js
var web_dom_collections_for_each = __webpack_require__("159b");

// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/arrayWithHoles.js
function _arrayWithHoles(arr) {
  if (Array.isArray(arr)) return arr;
}
// EXTERNAL MODULE: ./node_modules/core-js/modules/es.regexp.to-string.js
var es_regexp_to_string = __webpack_require__("25f0");

// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/iterableToArrayLimit.js








function _iterableToArrayLimit(arr, i) {
  if (!(Symbol.iterator in Object(arr) || Object.prototype.toString.call(arr) === "[object Arguments]")) {
    return;
  }

  var _arr = [];
  var _n = true;
  var _d = false;
  var _e = undefined;

  try {
    for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) {
      _arr.push(_s.value);

      if (i && _arr.length === i) break;
    }
  } catch (err) {
    _d = true;
    _e = err;
  } finally {
    try {
      if (!_n && _i["return"] != null) _i["return"]();
    } finally {
      if (_d) throw _e;
    }
  }

  return _arr;
}
// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/nonIterableRest.js
function _nonIterableRest() {
  throw new TypeError("Invalid attempt to destructure non-iterable instance");
}
// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/slicedToArray.js



function _slicedToArray(arr, i) {
  return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _nonIterableRest();
}
// EXTERNAL MODULE: ./node_modules/regenerator-runtime/runtime.js
var runtime = __webpack_require__("96cf");

// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js



function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) {
  try {
    var info = gen[key](arg);
    var value = info.value;
  } catch (error) {
    reject(error);
    return;
  }

  if (info.done) {
    resolve(value);
  } else {
    Promise.resolve(value).then(_next, _throw);
  }
}

function _asyncToGenerator(fn) {
  return function () {
    var self = this,
        args = arguments;
    return new Promise(function (resolve, reject) {
      var gen = fn.apply(self, args);

      function _next(value) {
        asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value);
      }

      function _throw(err) {
        asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err);
      }

      _next(undefined);
    });
  };
}
// CONCATENATED MODULE: ./src/presentation/plugins/BridgeConfigPlugin/BridgeConfig.ts


var BridgeConfig_BridgeConfig = function BridgeConfig(config) {
  _classCallCheck(this, BridgeConfig);

  if (typeof config.usePublish !== 'boolean') {
    throw new Error('No valid usePublish option provided.');
  }

  this.usePublish = config.usePublish;

  if (config.dataTypeLimits) {
    if (typeof config.dataTypeLimits.string.maxLength !== 'number') {
      throw new Error('No valid stringMaxLength option provided.');
    }

    this.stringMaxLength = config.dataTypeLimits.string.maxLength;
  } else {
    this.stringMaxLength = null;
  }
};


// CONCATENATED MODULE: ./src/presentation/plugins/BridgeConfigPlugin/index.ts

function BridgeConfigPlugin(vue, options) {
  if (!options) {
    throw new Error('No BridgeConfigOptions provided.');
  }

  vue.prototype.$bridgeConfig = new BridgeConfig_BridgeConfig(options);
}
// CONCATENATED MODULE: ./src/definitions/ApplicationStatus.ts









function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

var ValidApplicationStatus;

(function (ValidApplicationStatus) {
  ValidApplicationStatus["INITIALIZING"] = "initializing";
  ValidApplicationStatus["READY"] = "ready";
})(ValidApplicationStatus || (ValidApplicationStatus = {}));

var ErrorStatus;

(function (ErrorStatus) {
  ErrorStatus["ERROR"] = "error";
})(ErrorStatus || (ErrorStatus = {}));

var ApplicationStatus = _objectSpread({}, ValidApplicationStatus, {}, ErrorStatus);

/* harmony default export */ var definitions_ApplicationStatus = (ApplicationStatus);
// CONCATENATED MODULE: ./src/store/namespaces.ts
var NS_ENTITY = 'entity';
var NS_STATEMENTS = 'statements';
// CONCATENATED MODULE: ./src/store/statements/MainSnakPath.ts


var MainSnakPath_MainSnakPath =
/*#__PURE__*/
function () {
  function MainSnakPath(entityId, propertyId, index) {
    _classCallCheck(this, MainSnakPath);

    this.entityId = entityId;
    this.propertyId = propertyId;
    this.index = index;
  }

  _createClass(MainSnakPath, [{
    key: "resolveStatement",
    value: function resolveStatement(state) {
      if (!state[this.entityId]) {
        return null;
      }

      if (!state[this.entityId][this.propertyId]) {
        return null;
      }

      if (!state[this.entityId][this.propertyId][this.index]) {
        return null;
      }

      return state[this.entityId][this.propertyId][this.index];
    }
  }, {
    key: "resolveSnakInStatement",
    value: function resolveSnakInStatement(state) {
      var statement = this.resolveStatement(state);

      if (statement === null) {
        return null;
      }

      return statement.mainsnak;
    }
  }]);

  return MainSnakPath;
}();
// CONCATENATED MODULE: ./src/definitions/ApplicationError.ts
var ErrorTypes;

(function (ErrorTypes) {
  ErrorTypes["APPLICATION_LOGIC_ERROR"] = "APPLICATION_LOGIC_ERROR";
  ErrorTypes["INVALID_ENTITY_STATE_ERROR"] = "INVALID_ENTITY_STATE_ERROR";
  ErrorTypes["UNSUPPORTED_AMBIGUOUS_STATEMENT"] = "UNSUPPORTED_AMBIGUOUS_STATEMENT";
  ErrorTypes["UNSUPPORTED_DEPRECATED_STATEMENT"] = "UNSUPPORTED_DEPRECATED_STATEMENT";
  ErrorTypes["UNSUPPORTED_SNAK_TYPE"] = "UNSUPPORTED_SNAK_TYPE";
  ErrorTypes["UNSUPPORTED_DATATYPE"] = "UNSUPPORTED_DATATYPE";
  ErrorTypes["UNSUPPORTED_DATAVALUE_TYPE"] = "UNSUPPORTED_DATAVALUE_TYPE";
  ErrorTypes["SAVING_FAILED"] = "SAVING_FAILED";
})(ErrorTypes || (ErrorTypes = {}));
// CONCATENATED MODULE: ./node_modules/vuex-smart-module/dist/vuex-smart-module.esm.js
/*!
 * vuex-smart-module v0.3.4
 * https://github.com/ktsn/vuex-smart-module
 *
 * @license
 * Copyright (c) 2018 katashin
 * Released under the MIT license
 * https://github.com/ktsn/vuex-smart-module/blob/master/LICENSE
 */


/*! *****************************************************************************
Copyright (c) Microsoft Corporation. All rights reserved.
Licensed under the Apache License, Version 2.0 (the "License"); you may not use
this file except in compliance with the License. You may obtain a copy of the
License at http://www.apache.org/licenses/LICENSE-2.0

THIS CODE IS PROVIDED ON AN *AS IS* BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
KIND, EITHER EXPRESS OR IMPLIED, INCLUDING WITHOUT LIMITATION ANY IMPLIED
WARRANTIES OR CONDITIONS OF TITLE, FITNESS FOR A PARTICULAR PURPOSE,
MERCHANTABLITY OR NON-INFRINGEMENT.

See the Apache Version 2.0 License for specific language governing permissions
and limitations under the License.
***************************************************************************** */

var vuex_smart_module_esm_assign = function() {
    vuex_smart_module_esm_assign = Object.assign || function __assign(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p)) t[p] = s[p];
        }
        return t;
    };
    return vuex_smart_module_esm_assign.apply(this, arguments);
};

function inject(F, injection) {
    var proto = F.prototype;
    var descs = {};
    Object.keys(injection).forEach(function (key) {
        descs[key] = {
            configurable: true,
            enumerable: true,
            writable: true,
            value: injection[key]
        };
    });
    return Object.create(proto, descs);
}
var Getters = /** @class */ (function () {
    function Getters() {
    }
    Getters.prototype.$init = function (_store) { };
    Object.defineProperty(Getters.prototype, "state", {
        get: function () {
            return this.__ctx__.state;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Getters.prototype, "getters", {
        get: function () {
            return this.__ctx__.getters;
        },
        enumerable: true,
        configurable: true
    });
    return Getters;
}());
var Mutations = /** @class */ (function () {
    function Mutations() {
    }
    Object.defineProperty(Mutations.prototype, "state", {
        get: function () {
            return this.__ctx__.state;
        },
        enumerable: true,
        configurable: true
    });
    return Mutations;
}());
var Actions = /** @class */ (function () {
    function Actions() {
    }
    Actions.prototype.$init = function (_store) { };
    Object.defineProperty(Actions.prototype, "state", {
        get: function () {
            return this.__ctx__.state;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Actions.prototype, "getters", {
        get: function () {
            return this.__ctx__.getters;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Actions.prototype, "commit", {
        get: function () {
            return this.__ctx__.commit;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Actions.prototype, "dispatch", {
        get: function () {
            return this.__ctx__.dispatch;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Actions.prototype, "actions", {
        /**
         * IMPORTANT: Each action type maybe incorrect - return type of all actions should be `Promise<any>`
         * but the ones under `actions` are same as what you declared in this actions class.
         * The reason why we declare the type in such way is to avoid recursive type error.
         * See: https://github.com/ktsn/vuex-smart-module/issues/30
         */
        get: function () {
            return this.__ctx__.actions;
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Actions.prototype, "mutations", {
        get: function () {
            return this.__ctx__.mutations;
        },
        enumerable: true,
        configurable: true
    });
    return Actions;
}());

var noop = function () { };
function combine() {
    var fs = [];
    for (var _i = 0; _i < arguments.length; _i++) {
        fs[_i] = arguments[_i];
    }
    return function (x) {
        fs.forEach(function (f) { return f(x); });
    };
}
function vuex_smart_module_esm_get(path, value) {
    return path.reduce(function (acc, key) {
        return acc[key];
    }, value);
}
function mapValues(record, fn) {
    var res = {};
    Object.keys(record).forEach(function (key) {
        res[key] = fn(record[key], key);
    });
    return res;
}
function vuex_smart_module_esm_error(message) {
    console.error("[vuex-smart-module] " + message);
}
function assert(condition, message) {
    if (!condition) {
        throw new Error("[vuex-smart-module] " + message);
    }
}
function deprecated(message) {
    console.warn("[vuex-smart-module] DEPRECATED: " + message);
}
function traverseDescriptors(proto, Base, fn, exclude) {
    if (exclude === void 0) { exclude = { constructor: true }; }
    if (proto.constructor === Base) {
        return;
    }
    Object.getOwnPropertyNames(proto).forEach(function (key) {
        // Ensure to only choose most extended properties
        if (exclude[key])
            return;
        exclude[key] = true;
        var desc = Object.getOwnPropertyDescriptor(proto, key);
        fn(desc, key);
    });
    traverseDescriptors(Object.getPrototypeOf(proto), Base, fn, exclude);
}
function gatherHandlerNames(proto, Base) {
    var ret = [];
    traverseDescriptors(proto, Base, function (desc, name) {
        if (typeof desc.value !== 'function') {
            return;
        }
        ret.push(name);
    });
    return ret;
}

function createLazyContextPosition(module) {
    var message = 'The module need to be registered a store before using `Module#context` or `createMapper`';
    return {
        get path() {
            assert(module.path !== undefined, message);
            return module.path;
        },
        get namespace() {
            assert(module.namespace !== undefined, message);
            return module.namespace;
        }
    };
}
function normalizedDispatch(dispatch, namespace, type, payload, options) {
    if (typeof type === 'string') {
        return dispatch(namespace + type, payload, options);
    }
    else {
        return dispatch(vuex_smart_module_esm_assign(vuex_smart_module_esm_assign({}, type), { type: namespace + type.type }), payload);
    }
}
function commit(store, namespace, type, payload, options) {
    normalizedDispatch(store.commit, namespace, type, payload, options);
}
function dispatch(store, namespace, type, payload, options) {
    return normalizedDispatch(store.dispatch, namespace, type, payload, options);
}
function getters(store, namespace) {
    var sliceIndex = namespace.length;
    var getters = {};
    Object.keys(store.getters).forEach(function (key) {
        var sameNamespace = namespace === key.slice(0, sliceIndex);
        var name = key.slice(sliceIndex);
        if (!sameNamespace || !name) {
            return;
        }
        Object.defineProperty(getters, name, {
            get: function () { return store.getters[key]; },
            enumerable: true
        });
    });
    return getters;
}
var Context = /** @class */ (function () {
    /** @internal */
    function Context(pos, store, mutationsClass, actionsClass) {
        var _this = this;
        this.pos = pos;
        this.store = store;
        this.mutationsClass = mutationsClass;
        this.actionsClass = actionsClass;
        this.commit = function (type, payload, options) {
            return commit(_this.store, _this.pos.namespace, type, payload, options);
        };
        this.dispatch = function (type, payload, options) {
            return dispatch(_this.store, _this.pos.namespace, type, payload, options);
        };
    }
    Object.defineProperty(Context.prototype, "mutations", {
        get: function () {
            var _this = this;
            if (this.__mutations__) {
                return this.__mutations__;
            }
            var mutations = {};
            if (this.mutationsClass) {
                var mutationNames = gatherHandlerNames(this.mutationsClass.prototype, Mutations);
                mutationNames.forEach(function (name) {
                    Object.defineProperty(mutations, name, {
                        value: function (payload) { return _this.commit(name, payload); },
                        enumerable: true
                    });
                });
            }
            return (this.__mutations__ = mutations);
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Context.prototype, "actions", {
        get: function () {
            var _this = this;
            if (this.__actions__) {
                return this.__actions__;
            }
            var actions = {};
            if (this.actionsClass) {
                var actionNames = gatherHandlerNames(this.actionsClass.prototype, Actions);
                actionNames.forEach(function (name) {
                    Object.defineProperty(actions, name, {
                        value: function (payload) { return _this.dispatch(name, payload); },
                        enumerable: true
                    });
                });
            }
            return (this.__actions__ = actions);
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Context.prototype, "state", {
        get: function () {
            return vuex_smart_module_esm_get(this.pos.path, this.store.state);
        },
        enumerable: true,
        configurable: true
    });
    Object.defineProperty(Context.prototype, "getters", {
        get: function () {
            return getters(this.store, this.pos.namespace);
        },
        enumerable: true,
        configurable: true
    });
    return Context;
}());

function createMapper(module) {
    return new ComponentMapper(createLazyContextPosition(module));
}
var ComponentMapper = /** @class */ (function () {
    function ComponentMapper(pos) {
        this.pos = pos;
    }
    ComponentMapper.prototype.mapState = function (map) {
        var pos = this.pos;
        return createMappedObject(map, function (value) {
            return function mappedStateComputed() {
                var state = vuex_smart_module_esm_get(pos.path, this.$store.state);
                if (typeof value === 'function') {
                    var getters$1 = getters(this.$store, pos.namespace);
                    return value.call(this, state, getters$1);
                }
                else {
                    return state[value];
                }
            };
        });
    };
    ComponentMapper.prototype.mapGetters = function (map) {
        var pos = this.pos;
        return createMappedObject(map, function (value) {
            function mappedGetterComputed() {
                return this.$store.getters[pos.namespace + value];
            }
            // mark vuex getter for devtools
            mappedGetterComputed.vuex = true;
            return mappedGetterComputed;
        });
    };
    ComponentMapper.prototype.mapMutations = function (map) {
        var pos = this.pos;
        return createMappedObject(map, function (value) {
            return function mappedMutationMethod() {
                var _this = this;
                var args = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    args[_i] = arguments[_i];
                }
                var commit$1 = function (type, payload) {
                    return commit(_this.$store, pos.namespace, type, payload);
                };
                return typeof value === 'function'
                    ? value.apply(this, [commit$1].concat(args))
                    : commit$1(value, args[0]);
            };
        });
    };
    ComponentMapper.prototype.mapActions = function (map) {
        var pos = this.pos;
        return createMappedObject(map, function (value) {
            return function mappedActionMethod() {
                var _this = this;
                var args = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    args[_i] = arguments[_i];
                }
                var dispatch$1 = function (type, payload) {
                    return dispatch(_this.$store, pos.namespace, type, payload);
                };
                return typeof value === 'function'
                    ? value.apply(this, [dispatch$1].concat(args))
                    : dispatch$1(value, args[0]);
            };
        });
    };
    return ComponentMapper;
}());
function createMappedObject(map, fn) {
    var normalized = !Array.isArray(map)
        ? map
        : map.reduce(function (acc, key) {
            acc[key] = key;
            return acc;
        }, {});
    return mapValues(normalized, fn);
}

var Module = /** @class */ (function () {
    function Module(options) {
        if (options === void 0) { options = {}; }
        this.options = options;
        this.mapper = new ComponentMapper(createLazyContextPosition(this));
    }
    Module.prototype.clone = function () {
        var options = vuex_smart_module_esm_assign({}, this.options);
        if (options.modules) {
            options.modules = mapValues(options.modules, function (m) { return m.clone(); });
        }
        return new Module(options);
    };
    Module.prototype.context = function (store) {
        return new Context(createLazyContextPosition(this), store, this.options.mutations, this.options.actions);
    };
    Module.prototype.mapState = function (map) {
        deprecated('`Module#mapState` is deprecated. Use `createMapper` instead.');
        return this.mapper.mapState(map);
    };
    Module.prototype.mapGetters = function (map) {
        deprecated('`Module#mapGetters` is deprecated. Use `createMapper` instead.');
        return this.mapper.mapGetters(map);
    };
    Module.prototype.mapMutations = function (map) {
        deprecated('`Module#mapMutations` is deprecated. Use `createMapper` instead.');
        return this.mapper.mapMutations(map);
    };
    Module.prototype.mapActions = function (map) {
        deprecated('`Module#mapActions` is deprecated. Use `createMapper` instead.');
        return this.mapper.mapActions(map);
    };
    /* @internal */
    Module.prototype.create = function (path, namespace) {
        assert(!this.path || this.path.join('.') === path.join('.'), 'You are reusing one module on multiple places in the same store.\n' +
            'Clone it by `module.clone()` method to make sure every module in the store is unique.');
        this.path = path;
        this.namespace = namespace;
        var _a = this.options, namespaced = _a.namespaced, state = _a.state, getters = _a.getters, mutations = _a.mutations, actions = _a.actions, modules = _a.modules;
        var children = !modules
            ? undefined
            : Object.keys(modules).reduce(function (acc, key) {
                var m = modules[key];
                var nextNamespaced = m.options.namespaced === undefined ? true : m.options.namespaced;
                var nextNamespaceKey = nextNamespaced ? key + '/' : '';
                var res = m.create(path.concat(key), namespaced ? namespace + nextNamespaceKey : nextNamespaceKey);
                acc.options[key] = res.options;
                acc.injectStore = combine(acc.injectStore, res.injectStore);
                return acc;
            }, {
                options: {},
                injectStore: noop
            });
        var gettersInstance = getters && initGetters(getters, this);
        var mutationsInstance = mutations && initMutations(mutations, this);
        var actionsInstance = actions && initActions(actions, this);
        return {
            options: {
                namespaced: namespaced === undefined ? true : namespaced,
                state: state ? new state() : {},
                getters: gettersInstance && gettersInstance.getters,
                mutations: mutationsInstance && mutationsInstance.mutations,
                actions: actionsInstance && actionsInstance.actions,
                modules: children && children.options
            },
            injectStore: combine(children ? children.injectStore : noop, gettersInstance ? gettersInstance.injectStore : noop, mutationsInstance ? mutationsInstance.injectStore : noop, actionsInstance ? actionsInstance.injectStore : noop)
        };
    };
    return Module;
}());
function initGetters(Getters$1, module) {
    var getters = new Getters$1();
    var options = {};
    // Proxy all getters to print useful warning on development
    function proxyGetters(getters, origin) {
        var proxy = Object.create(getters);
        Object.keys(options).forEach(function (key) {
            Object.defineProperty(proxy, key, {
                get: function () {
                    vuex_smart_module_esm_error("You are accessing " + Getters$1.name + "#" + key + " from " + Getters$1.name + "#" + origin +
                        ' but direct access to another getter is prohibitted.' +
                        (" Access it via this.getters." + key + " instead."));
                    return getters[key];
                },
                configurable: true
            });
        });
        return proxy;
    }
    traverseDescriptors(Getters$1.prototype, Getters, function (desc, key) {
        if (typeof desc.value !== 'function' && !desc.get) {
            return;
        }
        var methodFn = desc.value;
        var getterFn = desc.get;
        options[key] = function () {
            var proxy =  true
                ? getters
                : undefined;
            if (getterFn) {
                return getterFn.call(proxy);
            }
            if (methodFn) {
                return methodFn.bind(proxy);
            }
        };
    });
    return {
        getters: options,
        injectStore: function (store) {
            var context = module.context(store);
            Object.defineProperty(getters, '__ctx__', {
                get: function () { return context; }
            });
            getters.$init(store);
        }
    };
}
function initMutations(Mutations$1, module) {
    var mutations = new Mutations$1();
    var options = {};
    // Proxy all mutations to print useful warning on development
    function proxyMutations(mutations, origin) {
        var proxy = Object.create(mutations);
        Object.keys(options).forEach(function (key) {
            proxy[key] = function () {
                var args = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    args[_i] = arguments[_i];
                }
                vuex_smart_module_esm_error("You are accessing " + Mutations$1.name + "#" + key + " from " + Mutations$1.name + "#" + origin +
                    ' but accessing another mutation is prohibitted.' +
                    ' Use an action to consolidate the mutation chain.');
                mutations[key].apply(mutations, args);
            };
        });
        return proxy;
    }
    traverseDescriptors(Mutations$1.prototype, Mutations, function (desc, key) {
        if (typeof desc.value !== 'function') {
            return;
        }
        options[key] = function (_, payload) {
            var proxy =  true
                ? mutations
                : undefined;
            return mutations[key].call(proxy, payload);
        };
    });
    return {
        mutations: options,
        injectStore: function (store) {
            var context = module.context(store);
            Object.defineProperty(mutations, '__ctx__', {
                get: function () { return context; }
            });
        }
    };
}
function initActions(Actions$1, module) {
    var actions = new Actions$1();
    var options = {};
    // Proxy all actions to print useful warning on development
    function proxyActions(actions, origin) {
        var proxy = Object.create(actions);
        Object.keys(options).forEach(function (key) {
            proxy[key] = function () {
                var args = [];
                for (var _i = 0; _i < arguments.length; _i++) {
                    args[_i] = arguments[_i];
                }
                vuex_smart_module_esm_error("You are accessing " + Actions$1.name + "#" + key + " from " + Actions$1.name + "#" + origin +
                    ' but direct access to another action is prohibitted.' +
                    (" Access it via this.dispatch('" + key + "') instead."));
                actions[key].apply(actions, args);
            };
        });
        return proxy;
    }
    traverseDescriptors(Actions$1.prototype, Actions, function (desc, key) {
        if (typeof desc.value !== 'function') {
            return;
        }
        options[key] = function (_, payload) {
            var proxy =  true
                ? actions
                : undefined;
            return actions[key].call(proxy, payload);
        };
    });
    return {
        actions: options,
        injectStore: function (store) {
            var context = module.context(store);
            Object.defineProperty(actions, '__ctx__', {
                get: function () { return context; }
            });
            actions.$init(store);
        }
    };
}

function registerModule(store, path, namespace, module, options) {
    var normalizedPath = typeof path === 'string' ? [path] : path;
    var _a = module.create(normalizedPath, normalizeNamespace(namespace)), moduleOptions = _a.options, injectStore = _a.injectStore;
    store.registerModule(normalizedPath, moduleOptions, options);
    injectStore(store);
}
function unregisterModule(store, module) {
    assert(module.path, 'The module seems not registered in the store');
    store.unregisterModule(module.path);
}
function normalizeNamespace(namespace) {
    if (namespace === '' || namespace === null) {
        return '';
    }
    return namespace[namespace.length - 1] === '/' ? namespace : namespace + '/';
}

function createStore(rootModule, options) {
    if (options === void 0) { options = {}; }
    var _a = rootModule.create([], ''), rootModuleOptions = _a.options, injectStore = _a.injectStore;
    var store = new vuex_esm["a" /* Store */](vuex_smart_module_esm_assign(vuex_smart_module_esm_assign(vuex_smart_module_esm_assign({}, rootModuleOptions), options), { plugins: [injectStore].concat(options.plugins || []) }));
    return store;
}



// CONCATENATED MODULE: ./src/store/entity/mutations.ts






var mutations_EntityMutations =
/*#__PURE__*/
function (_Mutations) {
  _inherits(EntityMutations, _Mutations);

  function EntityMutations() {
    _classCallCheck(this, EntityMutations);

    return _possibleConstructorReturn(this, _getPrototypeOf(EntityMutations).apply(this, arguments));
  }

  _createClass(EntityMutations, [{
    key: "updateEntity",
    value: function updateEntity(entity) {
      this.state.id = entity.id;
    }
  }, {
    key: "updateRevision",
    value: function updateRevision(revision) {
      this.state.baseRevision = revision;
    }
  }]);

  return EntityMutations;
}(Mutations);
// CONCATENATED MODULE: ./src/datamodel/EntityRevision.ts


var EntityRevision_EntityRevision = function EntityRevision(entity, revisionId) {
  _classCallCheck(this, EntityRevision);

  this.entity = entity;
  this.revisionId = revisionId;
};


// CONCATENATED MODULE: ./src/store/statements/mutations.ts







var mutations_StatementMutations =
/*#__PURE__*/
function (_Mutations) {
  _inherits(StatementMutations, _Mutations);

  function StatementMutations() {
    _classCallCheck(this, StatementMutations);

    return _possibleConstructorReturn(this, _getPrototypeOf(StatementMutations).apply(this, arguments));
  }

  _createClass(StatementMutations, [{
    key: "setStatements",
    value: function setStatements(payload) {
      external_commonjs_vue2_commonjs2_vue2_amd_vue2_root_vue2_default.a.set(this.state, payload.entityId, payload.statements);
    }
  }, {
    key: "setDataValue",
    value: function setDataValue(payload) {
      var snak = payload.path.resolveSnakInStatement(this.state);
      external_commonjs_vue2_commonjs2_vue2_amd_vue2_root_vue2_default.a.set(snak, 'datavalue', payload.value);
    }
  }, {
    key: "setSnakType",
    value: function setSnakType(payload) {
      var snak = payload.path.resolveSnakInStatement(this.state);
      snak.snaktype = payload.value;
    }
  }]);

  return StatementMutations;
}(Mutations);
// CONCATENATED MODULE: ./src/definitions/storeActionErrors/SnakActionErrors.ts
var SnakActionErrors;

(function (SnakActionErrors) {
  SnakActionErrors["NO_SNAK_FOUND"] = "property not found";
  SnakActionErrors["WRONG_PAYLOAD_TYPE"] = "payload type does not match";
  SnakActionErrors["WRONG_PAYLOAD_VALUE_TYPE"] = "payload value is not a string";
})(SnakActionErrors || (SnakActionErrors = {}));

/* harmony default export */ var storeActionErrors_SnakActionErrors = (SnakActionErrors);
// CONCATENATED MODULE: ./src/store/statements/actions.ts









var actions_StatementActions =
/*#__PURE__*/
function (_Actions) {
  _inherits(StatementActions, _Actions);

  function StatementActions() {
    _classCallCheck(this, StatementActions);

    return _possibleConstructorReturn(this, _getPrototypeOf(StatementActions).apply(this, arguments));
  }

  _createClass(StatementActions, [{
    key: "initStatements",
    value: function initStatements(payload) {
      this.commit('setStatements', {
        entityId: payload.entityId,
        statements: payload.statements
      });
      return Promise.resolve();
    }
  }, {
    key: "setStringDataValue",
    value: function setStringDataValue(payloadDataValue) {
      var _this = this;

      return new Promise(function (resolve) {
        var snak = payloadDataValue.path.resolveSnakInStatement(_this.state);

        if (snak === null) {
          throw new Error(storeActionErrors_SnakActionErrors.NO_SNAK_FOUND);
        }

        if (payloadDataValue.value.type !== 'string') {
          throw new Error(storeActionErrors_SnakActionErrors.WRONG_PAYLOAD_TYPE);
        }

        if (typeof payloadDataValue.value.value !== 'string') {
          throw new Error(storeActionErrors_SnakActionErrors.WRONG_PAYLOAD_VALUE_TYPE);
        } // TODO put more validation here


        var payloadSnakType = {
          path: payloadDataValue.path,
          value: 'value'
        };

        _this.commit('setSnakType', payloadSnakType);

        _this.commit('setDataValue', payloadDataValue);

        resolve();
      });
    }
  }]);

  return StatementActions;
}(Actions);
// CONCATENATED MODULE: ./src/store/statements/getters.ts






var getters_StatementGetters =
/*#__PURE__*/
function (_Getters) {
  _inherits(StatementGetters, _Getters);

  function StatementGetters() {
    _classCallCheck(this, StatementGetters);

    return _possibleConstructorReturn(this, _getPrototypeOf(StatementGetters).apply(this, arguments));
  }

  _createClass(StatementGetters, [{
    key: "containsEntity",
    get: function get() {
      var _this = this;

      return function (entityId) {
        return _this.state[entityId] !== undefined;
      };
    }
  }, {
    key: "propertyExists",
    get: function get() {
      var _this2 = this;

      return function (entityId, propertyId) {
        return _this2.state[entityId] !== undefined && _this2.state[entityId][propertyId] !== undefined;
      };
    }
  }, {
    key: "isAmbiguous",
    get: function get() {
      var _this3 = this;

      return function (entityId, propertyId) {
        return _this3.state[entityId] !== undefined && _this3.state[entityId][propertyId] !== undefined && _this3.state[entityId][propertyId].length > 1;
      };
    }
  }, {
    key: "rank",
    get: function get() {
      var _this4 = this;

      return function (pathToStatement) {
        var statement = pathToStatement.resolveStatement(_this4.state);

        if (!statement) {
          return null;
        }

        return statement.rank;
      };
    }
  }, {
    key: "dataValue",
    get: function get() {
      var _this5 = this;

      return function (pathToSnak) {
        var snak = pathToSnak.resolveSnakInStatement(_this5.state);

        if (!snak || !snak.datavalue) {
          return null;
        }

        return snak.datavalue;
      };
    }
  }, {
    key: "snakType",
    get: function get() {
      var _this6 = this;

      return function (pathToSnak) {
        var snak = pathToSnak.resolveSnakInStatement(_this6.state);

        if (!snak) {
          return null;
        }

        return snak.snaktype;
      };
    }
  }, {
    key: "dataType",
    get: function get() {
      var _this7 = this;

      return function (pathToSnak) {
        var snak = pathToSnak.resolveSnakInStatement(_this7.state);

        if (!snak) {
          return null;
        }

        return snak.datatype;
      };
    }
  }, {
    key: "dataValueType",
    get: function get() {
      var _this8 = this;

      return function (pathToSnak) {
        var snak = pathToSnak.resolveSnakInStatement(_this8.state);

        if (!snak || !snak.datavalue) {
          return null;
        }

        return snak.datavalue.type;
      };
    }
  }]);

  return StatementGetters;
}(Getters);
// CONCATENATED MODULE: ./src/store/statements/index.ts





var statements_StatementState = function StatementState() {
  _classCallCheck(this, StatementState);
};
var statementModule = new Module({
  state: statements_StatementState,
  mutations: mutations_StatementMutations,
  actions: actions_StatementActions,
  getters: getters_StatementGetters
});
// CONCATENATED MODULE: ./src/store/entity/actions.ts








var actions_EntityActions =
/*#__PURE__*/
function (_Actions) {
  _inherits(EntityActions, _Actions);

  function EntityActions() {
    _classCallCheck(this, EntityActions);

    return _possibleConstructorReturn(this, _getPrototypeOf(EntityActions).apply(this, arguments));
  }

  _createClass(EntityActions, [{
    key: "$init",
    value: function $init(store) {
      this.store = store;
      this.statementsModule = statementModule.context(store);
    }
  }, {
    key: "entityInit",
    value: function entityInit(payload) {
      var _this = this;

      return this.store.$services.get('readingEntityRepository').getEntity(payload.entity, payload.revision).then(function (entityRevision) {
        return _this.dispatch('entityWrite', entityRevision);
      });
    }
  }, {
    key: "entitySave",
    value: function entitySave() {
      var _this2 = this;

      var entityRevision = new EntityRevision_EntityRevision({
        id: this.state.id,
        statements: this.statementsModule.state[this.state.id]
      }, this.state.baseRevision);
      return this.store.$services.get('writingEntityRepository').saveEntity(entityRevision).then(function (entityRevision) {
        return _this2.dispatch('entityWrite', entityRevision);
      });
    }
  }, {
    key: "entityWrite",
    value: function entityWrite(entityRevision) {
      this.commit('updateRevision', entityRevision.revisionId);
      this.commit('updateEntity', entityRevision.entity);
      return this.statementsModule.dispatch('initStatements', {
        entityId: entityRevision.entity.id,
        statements: entityRevision.entity.statements
      });
    }
  }]);

  return EntityActions;
}(Actions);
// CONCATENATED MODULE: ./src/store/entity/index.ts




var entity_EntityState = function EntityState() {
  _classCallCheck(this, EntityState);

  this.id = '';
  this.baseRevision = 0;
};
var entityModule = new Module({
  state: entity_EntityState,
  mutations: mutations_EntityMutations,
  actions: actions_EntityActions
});
// CONCATENATED MODULE: ./src/store/actions.ts






















function actions_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function actions_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { actions_ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { actions_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }










var actions_RootActions =
/*#__PURE__*/
function (_Actions) {
  _inherits(RootActions, _Actions);

  function RootActions() {
    _classCallCheck(this, RootActions);

    return _possibleConstructorReturn(this, _getPrototypeOf(RootActions).apply(this, arguments));
  }

  _createClass(RootActions, [{
    key: "$init",
    value: function $init(store) {
      this.store = store;
      this.entityModule = entityModule.context(store);
      this.statementModule = statementModule.context(store);
    }
  }, {
    key: "initBridge",
    value: function initBridge(information) {
      var _this = this;

      this.commit('setEditFlow', information.editFlow);
      this.commit('setPropertyPointer', information.propertyId);
      this.commit('setEntityTitle', information.entityTitle);
      this.commit('setOriginalHref', information.originalHref);
      this.commit('setPageTitle', information.pageTitle);
      this.dispatch('requestAndSetTargetLabel', information.propertyId);
      return Promise.all([this.store.$services.get('wikibaseRepoConfigRepository').getRepoConfiguration(), this.store.$services.get('editAuthorizationChecker').canUseBridgeForItemAndPage(information.entityTitle, information.pageTitle), this.store.$services.get('propertyDatatypeRepository').getDataType(information.propertyId), this.entityModule.dispatch('entityInit', {
        entity: information.entityId
      })]).then(function (results) {
        return _this.dispatch('initBridgeWithRemoteData', {
          information: information,
          results: results
        });
      }, function (error) {
        _this.commit('addApplicationErrors', [{
          type: ErrorTypes.APPLICATION_LOGIC_ERROR,
          info: error
        }]);

        throw error;
      });
    }
  }, {
    key: "initBridgeWithRemoteData",
    value: function () {
      var _initBridgeWithRemoteData = _asyncToGenerator(
      /*#__PURE__*/
      regeneratorRuntime.mark(function _callee(_ref) {
        var information, _ref$results, wikibaseRepoConfiguration, permissionErrors, dataType, _entityInit, state, path;

        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                information = _ref.information, _ref$results = _slicedToArray(_ref.results, 4), wikibaseRepoConfiguration = _ref$results[0], permissionErrors = _ref$results[1], dataType = _ref$results[2], _entityInit = _ref$results[3];

                if (!permissionErrors.length) {
                  _context.next = 4;
                  break;
                }

                this.commit('addApplicationErrors', permissionErrors);
                return _context.abrupt("return");

              case 4:
                this.store.$services.get('tracker').trackPropertyDatatype(dataType);
                BridgeConfigPlugin(external_commonjs_vue2_commonjs2_vue2_amd_vue2_root_vue2_default.a, actions_objectSpread({}, wikibaseRepoConfiguration, {}, information.client));
                state = this.state;
                path = new MainSnakPath_MainSnakPath(state[NS_ENTITY].id, state.targetProperty, 0);
                _context.next = 10;
                return this.dispatch('validateEntityState', path);

              case 10:
                if (this.getters.applicationStatus !== definitions_ApplicationStatus.ERROR) {
                  this.commit('setOriginalStatement', state[NS_STATEMENTS][path.entityId][path.propertyId][path.index]);
                  this.commit('setApplicationStatus', definitions_ApplicationStatus.READY);
                }

              case 11:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this);
      }));

      function initBridgeWithRemoteData(_x) {
        return _initBridgeWithRemoteData.apply(this, arguments);
      }

      return initBridgeWithRemoteData;
    }()
  }, {
    key: "requestAndSetTargetLabel",
    value: function requestAndSetTargetLabel(propertyId) {
      var _this2 = this;

      return this.store.$services.get('entityLabelRepository').getLabel(propertyId).then(function (label) {
        _this2.commit('setTargetLabel', label);
      }, function (_error) {// TODO: handling on failed label loading, which is not a bocking error for now
      });
    }
  }, {
    key: "validateEntityState",
    value: function validateEntityState(path) {
      if (!this.statementModule.getters.propertyExists(path.entityId, path.propertyId)) {
        this.commit('addApplicationErrors', [{
          type: ErrorTypes.INVALID_ENTITY_STATE_ERROR
        }]);
        return Promise.resolve();
      }

      return this.dispatch('validateBridgeApplicability', path);
    }
  }, {
    key: "validateBridgeApplicability",
    value: function validateBridgeApplicability(path) {
      if (this.statementModule.getters.isAmbiguous(path.entityId, path.propertyId)) {
        return this.dispatch('addError', [{
          type: ErrorTypes.UNSUPPORTED_AMBIGUOUS_STATEMENT
        }]);
      }

      if (this.statementModule.getters.rank(path) === 'deprecated') {
        return this.dispatch('addError', [{
          type: ErrorTypes.UNSUPPORTED_DEPRECATED_STATEMENT
        }]);
      }

      var snakType = this.statementModule.getters.snakType(path);

      if (snakType === null) {
        throw new Error('If snak type is missing, there should have been an error earlier');
      }

      if (snakType !== 'value') {
        var error = {
          type: ErrorTypes.UNSUPPORTED_SNAK_TYPE,
          info: {
            snakType: snakType
          }
        };
        return this.dispatch('addError', [error]);
      }

      var datatype = this.statementModule.getters.dataType(path);

      if (datatype === null) {
        throw new Error('If snak is missing, there should have been an error earlier');
      }

      if (datatype !== 'string') {
        var _error2 = {
          type: ErrorTypes.UNSUPPORTED_DATATYPE,
          info: {
            unsupportedDatatype: datatype
          }
        };
        return this.dispatch('addError', [_error2]);
      }

      if (this.statementModule.getters.dataValueType(path) !== 'string') {
        return this.dispatch('addError', [{
          type: ErrorTypes.UNSUPPORTED_DATAVALUE_TYPE
        }]);
      }

      return Promise.resolve();
    }
  }, {
    key: "setTargetValue",
    value: function setTargetValue(dataValue) {
      var _this3 = this;

      if (this.state.applicationStatus !== definitions_ApplicationStatus.READY) {
        this.commit('addApplicationErrors', [{
          type: ErrorTypes.APPLICATION_LOGIC_ERROR,
          info: {
            stack: new Error().stack
          }
        }]);
        return Promise.reject(null);
      }

      var state = this.state;
      var path = new MainSnakPath_MainSnakPath(state[NS_ENTITY].id, state.targetProperty, 0);
      return this.statementModule.dispatch('setStringDataValue', {
        path: path,
        value: dataValue
      }).catch(function (error) {
        _this3.commit('addApplicationErrors', [{
          type: ErrorTypes.APPLICATION_LOGIC_ERROR,
          info: error
        }]);

        throw error;
      });
    }
  }, {
    key: "saveBridge",
    value: function saveBridge() {
      var _this4 = this;

      if (this.state.applicationStatus !== definitions_ApplicationStatus.READY) {
        this.commit('addApplicationErrors', [{
          type: ErrorTypes.APPLICATION_LOGIC_ERROR,
          info: {
            stack: new Error().stack
          }
        }]);
        return Promise.reject(null);
      }

      return this.entityModule.dispatch('entitySave').catch(function (error) {
        _this4.commit('addApplicationErrors', [{
          type: ErrorTypes.SAVING_FAILED,
          info: error
        }]);

        throw error;
      });
    }
  }, {
    key: "addError",
    value: function addError(errors) {
      this.commit('addApplicationErrors', errors);
      return Promise.resolve();
    }
  }, {
    key: "setEditDecision",
    value: function setEditDecision(editDecision) {
      this.commit('setEditDecision', editDecision);
      return Promise.resolve();
    }
  }]);

  return RootActions;
}(Actions);
// EXTERNAL MODULE: ./node_modules/deep-equal/index.js
var deep_equal = __webpack_require__("7fae");
var deep_equal_default = /*#__PURE__*/__webpack_require__.n(deep_equal);

// CONCATENATED MODULE: ./src/store/getters.ts












var getters_RootGetters =
/*#__PURE__*/
function (_Getters) {
  _inherits(RootGetters, _Getters);

  function RootGetters() {
    _classCallCheck(this, RootGetters);

    return _possibleConstructorReturn(this, _getPrototypeOf(RootGetters).apply(this, arguments));
  }

  _createClass(RootGetters, [{
    key: "$init",
    value: function $init(store) {
      this.statementModule = statementModule.context(store);
    }
  }, {
    key: "targetValue",
    get: function get() {
      if (this.state.applicationStatus !== definitions_ApplicationStatus.READY) {
        return null;
      }

      var entityId = this.state[NS_ENTITY].id;
      var pathToMainSnak = new MainSnakPath_MainSnakPath(entityId, this.state.targetProperty, 0);
      return this.statementModule.getters.dataValue(pathToMainSnak);
    }
  }, {
    key: "targetLabel",
    get: function get() {
      if (this.state.targetLabel === null) {
        return {
          language: 'zxx',
          value: this.state.targetProperty
        };
      }

      return this.state.targetLabel;
    }
  }, {
    key: "targetReferences",
    get: function get() {
      if (this.state.applicationStatus !== definitions_ApplicationStatus.READY) {
        return [];
      }

      var activeState = this.state;
      var entityId = activeState[NS_ENTITY].id;
      var statements = activeState[NS_STATEMENTS][entityId][this.state.targetProperty][0];
      return statements.references ? statements.references : [];
    }
  }, {
    key: "isTargetStatementModified",
    get: function get() {
      if (this.state.applicationStatus !== definitions_ApplicationStatus.READY) {
        return false;
      }

      var initState = this.state;
      var entityId = initState[NS_ENTITY].id;
      return !deep_equal_default()(this.state.originalStatement, initState[NS_STATEMENTS][entityId][this.state.targetProperty][0], {
        strict: true
      });
    }
  }, {
    key: "canSave",
    get: function get() {
      return this.state.editDecision !== null && this.getters.isTargetStatementModified;
    }
  }, {
    key: "applicationStatus",
    get: function get() {
      if (this.state.applicationErrors.length > 0) {
        return definitions_ApplicationStatus.ERROR;
      }

      return this.state.applicationStatus;
    }
  }]);

  return RootGetters;
}(Getters);
// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/arrayWithoutHoles.js
function _arrayWithoutHoles(arr) {
  if (Array.isArray(arr)) {
    for (var i = 0, arr2 = new Array(arr.length); i < arr.length; i++) {
      arr2[i] = arr[i];
    }

    return arr2;
  }
}
// EXTERNAL MODULE: ./node_modules/core-js/modules/es.array.from.js
var es_array_from = __webpack_require__("a630");

// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/iterableToArray.js









function _iterableToArray(iter) {
  if (Symbol.iterator in Object(iter) || Object.prototype.toString.call(iter) === "[object Arguments]") return Array.from(iter);
}
// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/nonIterableSpread.js
function _nonIterableSpread() {
  throw new TypeError("Invalid attempt to spread non-iterable instance");
}
// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/toConsumableArray.js



function _toConsumableArray(arr) {
  return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _nonIterableSpread();
}
// CONCATENATED MODULE: ./src/store/clone.ts
function clone(source) {
  return JSON.parse(JSON.stringify(source));
}
// CONCATENATED MODULE: ./src/store/mutations.ts








var mutations_RootMutations =
/*#__PURE__*/
function (_Mutations) {
  _inherits(RootMutations, _Mutations);

  function RootMutations() {
    _classCallCheck(this, RootMutations);

    return _possibleConstructorReturn(this, _getPrototypeOf(RootMutations).apply(this, arguments));
  }

  _createClass(RootMutations, [{
    key: "setPropertyPointer",
    value: function setPropertyPointer(targetProperty) {
      this.state.targetProperty = targetProperty;
    }
  }, {
    key: "setEditFlow",
    value: function setEditFlow(editFlow) {
      this.state.editFlow = editFlow;
    }
  }, {
    key: "setApplicationStatus",
    value: function setApplicationStatus(status) {
      this.state.applicationStatus = status;
    }
  }, {
    key: "setTargetLabel",
    value: function setTargetLabel(label) {
      this.state.targetLabel = label;
    }
  }, {
    key: "setOriginalStatement",
    value: function setOriginalStatement(revision) {
      this.state.originalStatement = clone(revision);
    }
  }, {
    key: "addApplicationErrors",
    value: function addApplicationErrors(errors) {
      var _this$state$applicati;

      (_this$state$applicati = this.state.applicationErrors).push.apply(_this$state$applicati, _toConsumableArray(errors));
    }
  }, {
    key: "setEditDecision",
    value: function setEditDecision(editDecision) {
      this.state.editDecision = editDecision;
    }
  }, {
    key: "setEntityTitle",
    value: function setEntityTitle(entityTitle) {
      this.state.entityTitle = entityTitle;
    }
  }, {
    key: "setPageTitle",
    value: function setPageTitle(pageTitle) {
      this.state.pageTitle = pageTitle;
    }
  }, {
    key: "setOriginalHref",
    value: function setOriginalHref(orginalHref) {
      this.state.originalHref = orginalHref;
    }
  }]);

  return RootMutations;
}(Mutations);
// CONCATENATED MODULE: ./src/store/state.ts


var state_BaseState = function BaseState() {
  _classCallCheck(this, BaseState);

  this.applicationErrors = [];
  this.applicationStatus = ValidApplicationStatus.INITIALIZING;
  this.editDecision = null;
  this.editFlow = '';
  this.entityTitle = '';
  this.originalHref = '';
  this.originalStatement = null;
  this.pageTitle = '';
  this.targetLabel = null;
  this.targetProperty = '';
  this.wikibaseRepoConfiguration = null;
};
// CONCATENATED MODULE: ./src/store/index.ts


var _modules;











external_commonjs_vue2_commonjs2_vue2_amd_vue2_root_vue2_default.a.use(vuex_esm["b" /* default */]);
var store_rootModule = new Module({
  state: state_BaseState,
  getters: getters_RootGetters,
  mutations: mutations_RootMutations,
  actions: actions_RootActions,
  modules: (_modules = {}, _defineProperty(_modules, NS_ENTITY, entityModule), _defineProperty(_modules, NS_STATEMENTS, statementModule), _modules)
});
function store_createStore(services) {
  var store = createStore(store_rootModule, {
    strict: "production" !== 'production'
  });
  store.$services = services;
  return store;
}
// CONCATENATED MODULE: ./src/presentation/StateMixin.ts






/* eslint-disable @typescript-eslint/camelcase */




/**
 * Mixin for components that access the state.
 *
 * Basic usage:
 *
 *     class MyComponent extends mixins( StateMixin ) {
 *         public setValue( value ) {
 *             this.rootModule.dispatch( SET_VALUE, value );
 *         }
 *     }
 */

var StateMixin_StateMixin =
/*#__PURE__*/
function (_Vue) {
  _inherits(StateMixin, _Vue);

  function StateMixin() {
    _classCallCheck(this, StateMixin);

    return _possibleConstructorReturn(this, _getPrototypeOf(StateMixin).apply(this, arguments));
  }

  _createClass(StateMixin, [{
    key: "rootModule",
    get: function get() {
      if (this.$_StateMixin_rootModule === undefined) {
        this.$_StateMixin_rootModule = store_rootModule.context(this.$store);
      }

      return this.$_StateMixin_rootModule;
    }
  }]);

  return StateMixin;
}(external_commonjs_vue2_commonjs2_vue2_amd_vue2_root_vue2_default.a);

StateMixin_StateMixin = __decorate([vue_class_component_common_default.a], StateMixin_StateMixin);
/* harmony default export */ var presentation_StateMixin = (StateMixin_StateMixin);
// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js?{"cacheDirectory":"node_modules/.cache/vue-loader","cacheIdentifier":"42133e48-vue-loader-template"}!./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/DataBridge.vue?vue&type=template&id=14c7fe98&
var DataBridgevue_type_template_id_14c7fe98_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('section',{staticClass:"wb-db-bridge"},[_c('StringDataValue',{attrs:{"label":_vm.targetLabel,"data-value":_vm.targetValue,"set-data-value":_vm.setDataValue,"maxlength":this.$bridgeConfig.stringMaxLength}}),_c('ReferenceSection'),_c('EditDecision')],1)}
var DataBridgevue_type_template_id_14c7fe98_staticRenderFns = []


// CONCATENATED MODULE: ./src/presentation/components/DataBridge.vue?vue&type=template&id=14c7fe98&

// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js?{"cacheDirectory":"node_modules/.cache/vue-loader","cacheIdentifier":"42133e48-vue-loader-template"}!./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/EditDecision.vue?vue&type=template&id=c6bd5486&
var EditDecisionvue_type_template_id_c6bd5486_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"wb-db-edit-decision"},[_c('h2',{staticClass:"wb-db-edit-decision__heading"},[_vm._v(" "+_vm._s(_vm.$messages.get( _vm.$messages.KEYS.EDIT_DECISION_HEADING ))+" ")]),_c('RadioGroup',[_c('RadioInput',{attrs:{"name":"editDecision","html-value":"replace"},model:{value:(_vm.editDecision),callback:function ($$v) {_vm.editDecision=$$v},expression:"editDecision"}},[_c('template',{slot:"label"},[_c('span',{domProps:{"innerHTML":_vm._s(_vm.$messages.get( _vm.$messages.KEYS.EDIT_DECISION_REPLACE_LABEL ))}})]),_c('template',{slot:"description"},[_vm._v(" "+_vm._s(_vm.$messages.get( _vm.$messages.KEYS.EDIT_DECISION_REPLACE_DESCRIPTION ))+" ")])],2),_c('RadioInput',{attrs:{"name":"editDecision","html-value":"update"},model:{value:(_vm.editDecision),callback:function ($$v) {_vm.editDecision=$$v},expression:"editDecision"}},[_c('template',{slot:"label"},[_c('span',{domProps:{"innerHTML":_vm._s(_vm.$messages.get( _vm.$messages.KEYS.EDIT_DECISION_UPDATE_LABEL ))}})]),_c('template',{slot:"description"},[_vm._v(" "+_vm._s(_vm.$messages.get( _vm.$messages.KEYS.EDIT_DECISION_UPDATE_DESCRIPTION ))+" ")])],2)],1)],1)}
var EditDecisionvue_type_template_id_c6bd5486_staticRenderFns = []


// CONCATENATED MODULE: ./src/presentation/components/EditDecision.vue?vue&type=template&id=c6bd5486&

// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js?{"cacheDirectory":"node_modules/.cache/vue-loader","cacheIdentifier":"42133e48-vue-loader-template"}!./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/RadioGroup.vue?vue&type=template&id=09f3b87c&
var RadioGroupvue_type_template_id_09f3b87c_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"wb-db-radio-group"},[_vm._t("default")],2)}
var RadioGroupvue_type_template_id_09f3b87c_staticRenderFns = []


// CONCATENATED MODULE: ./src/presentation/components/RadioGroup.vue?vue&type=template&id=09f3b87c&

// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/ts-loader??ref--14-3!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/RadioGroup.vue?vue&type=script&lang=ts&








var RadioGroupvue_type_script_lang_ts_RadioGroup =
/*#__PURE__*/
function (_Vue) {
  _inherits(RadioGroup, _Vue);

  function RadioGroup() {
    _classCallCheck(this, RadioGroup);

    return _possibleConstructorReturn(this, _getPrototypeOf(RadioGroup).apply(this, arguments));
  }

  return RadioGroup;
}(external_commonjs_vue2_commonjs2_vue2_amd_vue2_root_vue2_default.a);

RadioGroupvue_type_script_lang_ts_RadioGroup = __decorate([vue_class_component_common_default.a], RadioGroupvue_type_script_lang_ts_RadioGroup);
/* harmony default export */ var RadioGroupvue_type_script_lang_ts_ = (RadioGroupvue_type_script_lang_ts_RadioGroup);
// CONCATENATED MODULE: ./src/presentation/components/RadioGroup.vue?vue&type=script&lang=ts&
 /* harmony default export */ var components_RadioGroupvue_type_script_lang_ts_ = (RadioGroupvue_type_script_lang_ts_); 
// EXTERNAL MODULE: ./src/presentation/components/RadioGroup.vue?vue&type=style&index=0&lang=scss&
var RadioGroupvue_type_style_index_0_lang_scss_ = __webpack_require__("4e23");

// CONCATENATED MODULE: ./node_modules/vue-loader/lib/runtime/componentNormalizer.js
/* globals __VUE_SSR_CONTEXT__ */

// IMPORTANT: Do NOT use ES2015 features in this file (except for modules).
// This module is a runtime utility for cleaner component module output and will
// be included in the final webpack user bundle.

function normalizeComponent (
  scriptExports,
  render,
  staticRenderFns,
  functionalTemplate,
  injectStyles,
  scopeId,
  moduleIdentifier, /* server only */
  shadowMode /* vue-cli only */
) {
  // Vue.extend constructor export interop
  var options = typeof scriptExports === 'function'
    ? scriptExports.options
    : scriptExports

  // render functions
  if (render) {
    options.render = render
    options.staticRenderFns = staticRenderFns
    options._compiled = true
  }

  // functional template
  if (functionalTemplate) {
    options.functional = true
  }

  // scopedId
  if (scopeId) {
    options._scopeId = 'data-v-' + scopeId
  }

  var hook
  if (moduleIdentifier) { // server build
    hook = function (context) {
      // 2.3 injection
      context =
        context || // cached call
        (this.$vnode && this.$vnode.ssrContext) || // stateful
        (this.parent && this.parent.$vnode && this.parent.$vnode.ssrContext) // functional
      // 2.2 with runInNewContext: true
      if (!context && typeof __VUE_SSR_CONTEXT__ !== 'undefined') {
        context = __VUE_SSR_CONTEXT__
      }
      // inject component styles
      if (injectStyles) {
        injectStyles.call(this, context)
      }
      // register component module identifier for async chunk inferrence
      if (context && context._registeredComponents) {
        context._registeredComponents.add(moduleIdentifier)
      }
    }
    // used by ssr in case component is cached and beforeCreate
    // never gets called
    options._ssrRegister = hook
  } else if (injectStyles) {
    hook = shadowMode
      ? function () { injectStyles.call(this, this.$root.$options.shadowRoot) }
      : injectStyles
  }

  if (hook) {
    if (options.functional) {
      // for template-only hot-reload because in that case the render fn doesn't
      // go through the normalizer
      options._injectStyles = hook
      // register for functioal component in vue file
      var originalRender = options.render
      options.render = function renderWithStyleInjection (h, context) {
        hook.call(context)
        return originalRender(h, context)
      }
    } else {
      // inject component registration as beforeCreate hook
      var existing = options.beforeCreate
      options.beforeCreate = existing
        ? [].concat(existing, hook)
        : [hook]
    }
  }

  return {
    exports: scriptExports,
    options: options
  }
}

// CONCATENATED MODULE: ./src/presentation/components/RadioGroup.vue






/* normalize component */

var component = normalizeComponent(
  components_RadioGroupvue_type_script_lang_ts_,
  RadioGroupvue_type_template_id_09f3b87c_render,
  RadioGroupvue_type_template_id_09f3b87c_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var components_RadioGroup = (component.exports);
// EXTERNAL MODULE: ./node_modules/@wmde/wikibase-vuejs-components/dist/wikibase-vuejs-components.common.js
var wikibase_vuejs_components_common = __webpack_require__("d1ac");

// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/ts-loader??ref--14-3!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/EditDecision.vue?vue&type=script&lang=ts&











var EditDecisionvue_type_script_lang_ts_EditDecision =
/*#__PURE__*/
function (_mixins) {
  _inherits(EditDecision, _mixins);

  function EditDecision() {
    _classCallCheck(this, EditDecision);

    return _possibleConstructorReturn(this, _getPrototypeOf(EditDecision).apply(this, arguments));
  }

  _createClass(EditDecision, [{
    key: "editDecision",
    get: function get() {
      return this.rootModule.state.editDecision;
    },
    set: function set(value) {
      if (value === null) {
        throw new Error('Cannot set editDecision back to null!');
      }

      this.rootModule.dispatch('setEditDecision', value);
    }
  }]);

  return EditDecision;
}(Object(vue_class_component_common["mixins"])(presentation_StateMixin));

EditDecisionvue_type_script_lang_ts_EditDecision = __decorate([vue_class_component_common_default()({
  components: {
    RadioGroup: components_RadioGroup,
    RadioInput: wikibase_vuejs_components_common["RadioInput"]
  }
})], EditDecisionvue_type_script_lang_ts_EditDecision);
/* harmony default export */ var EditDecisionvue_type_script_lang_ts_ = (EditDecisionvue_type_script_lang_ts_EditDecision);
// CONCATENATED MODULE: ./src/presentation/components/EditDecision.vue?vue&type=script&lang=ts&
 /* harmony default export */ var components_EditDecisionvue_type_script_lang_ts_ = (EditDecisionvue_type_script_lang_ts_); 
// EXTERNAL MODULE: ./src/presentation/components/EditDecision.vue?vue&type=style&index=0&lang=scss&
var EditDecisionvue_type_style_index_0_lang_scss_ = __webpack_require__("151e");

// CONCATENATED MODULE: ./src/presentation/components/EditDecision.vue






/* normalize component */

var EditDecision_component = normalizeComponent(
  components_EditDecisionvue_type_script_lang_ts_,
  EditDecisionvue_type_template_id_c6bd5486_render,
  EditDecisionvue_type_template_id_c6bd5486_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var components_EditDecision = (EditDecision_component.exports);
// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js?{"cacheDirectory":"node_modules/.cache/vue-loader","cacheIdentifier":"42133e48-vue-loader-template"}!./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/StringDataValue.vue?vue&type=template&id=a0d9f84c&
var StringDataValuevue_type_template_id_a0d9f84c_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"wb-db-string-value"},[_c('PropertyLabel',{attrs:{"term":_vm.label,"html-for":_vm.id}}),_c('ResizingTextField',{staticClass:"wb-db-string-value__input",attrs:{"id":_vm.id,"placeholder":_vm.placeholder,"maxlength":_vm.maxlength},model:{value:(_vm.value),callback:function ($$v) {_vm.value=$$v},expression:"value"}})],1)}
var StringDataValuevue_type_template_id_a0d9f84c_staticRenderFns = []


// CONCATENATED MODULE: ./src/presentation/components/StringDataValue.vue?vue&type=template&id=a0d9f84c&

// EXTERNAL MODULE: ./node_modules/core-js/modules/es.number.constructor.js
var es_number_constructor = __webpack_require__("a9e3");

// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js?{"cacheDirectory":"node_modules/.cache/vue-loader","cacheIdentifier":"42133e48-vue-loader-template"}!./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/PropertyLabel.vue?vue&type=template&id=aca55bce&
var PropertyLabelvue_type_template_id_aca55bce_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('label',{staticClass:"wb-db-property-label",attrs:{"for":_vm.htmlFor}},[_c('TermLabel',{attrs:{"term":_vm.term}})],1)}
var PropertyLabelvue_type_template_id_aca55bce_staticRenderFns = []


// CONCATENATED MODULE: ./src/presentation/components/PropertyLabel.vue?vue&type=template&id=aca55bce&

// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js?{"cacheDirectory":"node_modules/.cache/vue-loader","cacheIdentifier":"42133e48-vue-loader-template"}!./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/TermLabel.vue?vue&type=template&id=5939bc91&
var TermLabelvue_type_template_id_5939bc91_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('span',{directives:[{name:"inlanguage",rawName:"v-inlanguage",value:(_vm.term.language),expression:"term.language"}],staticClass:"wb-db-term-label"},[_vm._v(_vm._s(_vm.term.value))])}
var TermLabelvue_type_template_id_5939bc91_staticRenderFns = []


// CONCATENATED MODULE: ./src/presentation/components/TermLabel.vue?vue&type=template&id=5939bc91&

// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/ts-loader??ref--14-3!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/TermLabel.vue?vue&type=script&lang=ts&








/**
 * A label for a Term in a certain language.
 */

var TermLabelvue_type_script_lang_ts_TermLabel =
/*#__PURE__*/
function (_Vue) {
  _inherits(TermLabel, _Vue);

  function TermLabel() {
    _classCallCheck(this, TermLabel);

    return _possibleConstructorReturn(this, _getPrototypeOf(TermLabel).apply(this, arguments));
  }

  return TermLabel;
}(external_commonjs_vue2_commonjs2_vue2_amd_vue2_root_vue2_default.a);

__decorate([Object(vue_property_decorator["Prop"])({
  required: true
})], TermLabelvue_type_script_lang_ts_TermLabel.prototype, "term", void 0);

TermLabelvue_type_script_lang_ts_TermLabel = __decorate([vue_class_component_common_default.a], TermLabelvue_type_script_lang_ts_TermLabel);
/* harmony default export */ var TermLabelvue_type_script_lang_ts_ = (TermLabelvue_type_script_lang_ts_TermLabel);
// CONCATENATED MODULE: ./src/presentation/components/TermLabel.vue?vue&type=script&lang=ts&
 /* harmony default export */ var components_TermLabelvue_type_script_lang_ts_ = (TermLabelvue_type_script_lang_ts_); 
// EXTERNAL MODULE: ./src/presentation/components/TermLabel.vue?vue&type=style&index=0&lang=scss&
var TermLabelvue_type_style_index_0_lang_scss_ = __webpack_require__("0ea2");

// CONCATENATED MODULE: ./src/presentation/components/TermLabel.vue






/* normalize component */

var TermLabel_component = normalizeComponent(
  components_TermLabelvue_type_script_lang_ts_,
  TermLabelvue_type_template_id_5939bc91_render,
  TermLabelvue_type_template_id_5939bc91_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var components_TermLabel = (TermLabel_component.exports);
// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/ts-loader??ref--14-3!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/PropertyLabel.vue?vue&type=script&lang=ts&








var PropertyLabelvue_type_script_lang_ts_PropertyLabel =
/*#__PURE__*/
function (_Vue) {
  _inherits(PropertyLabel, _Vue);

  function PropertyLabel() {
    _classCallCheck(this, PropertyLabel);

    return _possibleConstructorReturn(this, _getPrototypeOf(PropertyLabel).apply(this, arguments));
  }

  return PropertyLabel;
}(vue_property_decorator["Vue"]);

__decorate([Object(vue_property_decorator["Prop"])({
  required: true
})], PropertyLabelvue_type_script_lang_ts_PropertyLabel.prototype, "term", void 0);

__decorate([Object(vue_property_decorator["Prop"])({
  required: true
})], PropertyLabelvue_type_script_lang_ts_PropertyLabel.prototype, "htmlFor", void 0);

PropertyLabelvue_type_script_lang_ts_PropertyLabel = __decorate([Object(vue_property_decorator["Component"])({
  components: {
    TermLabel: components_TermLabel
  }
})], PropertyLabelvue_type_script_lang_ts_PropertyLabel);
/* harmony default export */ var PropertyLabelvue_type_script_lang_ts_ = (PropertyLabelvue_type_script_lang_ts_PropertyLabel);
// CONCATENATED MODULE: ./src/presentation/components/PropertyLabel.vue?vue&type=script&lang=ts&
 /* harmony default export */ var components_PropertyLabelvue_type_script_lang_ts_ = (PropertyLabelvue_type_script_lang_ts_); 
// EXTERNAL MODULE: ./src/presentation/components/PropertyLabel.vue?vue&type=style&index=0&lang=scss&
var PropertyLabelvue_type_style_index_0_lang_scss_ = __webpack_require__("87a9");

// CONCATENATED MODULE: ./src/presentation/components/PropertyLabel.vue






/* normalize component */

var PropertyLabel_component = normalizeComponent(
  components_PropertyLabelvue_type_script_lang_ts_,
  PropertyLabelvue_type_template_id_aca55bce_render,
  PropertyLabelvue_type_template_id_aca55bce_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var components_PropertyLabel = (PropertyLabel_component.exports);
// EXTERNAL MODULE: ./node_modules/uuid/index.js
var uuid = __webpack_require__("11c1");

// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/ts-loader??ref--14-3!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/StringDataValue.vue?vue&type=script&lang=ts&












var StringDataValuevue_type_script_lang_ts_StringDataValue =
/*#__PURE__*/
function (_Vue) {
  _inherits(StringDataValue, _Vue);

  function StringDataValue() {
    var _this;

    _classCallCheck(this, StringDataValue);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(StringDataValue).apply(this, arguments));
    _this.id = Object(uuid["v4"])();
    return _this;
  }

  _createClass(StringDataValue, [{
    key: "value",
    get: function get() {
      if (!this.dataValue) {
        return '';
      } else {
        return this.dataValue.value;
      }
    },
    set: function set(value) {
      this.setDataValue({
        type: 'string',
        value: value
      });
    }
  }]);

  return StringDataValue;
}(vue_property_decorator["Vue"]);

__decorate([Object(vue_property_decorator["Prop"])({
  required: true
})], StringDataValuevue_type_script_lang_ts_StringDataValue.prototype, "dataValue", void 0);

__decorate([Object(vue_property_decorator["Prop"])({
  required: true
})], StringDataValuevue_type_script_lang_ts_StringDataValue.prototype, "label", void 0);

__decorate([Object(vue_property_decorator["Prop"])({
  required: false
})], StringDataValuevue_type_script_lang_ts_StringDataValue.prototype, "placeholder", void 0);

__decorate([Object(vue_property_decorator["Prop"])({
  type: Number,
  required: false
})], StringDataValuevue_type_script_lang_ts_StringDataValue.prototype, "maxlength", void 0);

__decorate([Object(vue_property_decorator["Prop"])({
  required: true,
  type: Function
})], StringDataValuevue_type_script_lang_ts_StringDataValue.prototype, "setDataValue", void 0);

StringDataValuevue_type_script_lang_ts_StringDataValue = __decorate([Object(vue_property_decorator["Component"])({
  components: {
    PropertyLabel: components_PropertyLabel,
    ResizingTextField: wikibase_vuejs_components_common["ResizingTextField"]
  }
})], StringDataValuevue_type_script_lang_ts_StringDataValue);
/* harmony default export */ var StringDataValuevue_type_script_lang_ts_ = (StringDataValuevue_type_script_lang_ts_StringDataValue);
// CONCATENATED MODULE: ./src/presentation/components/StringDataValue.vue?vue&type=script&lang=ts&
 /* harmony default export */ var components_StringDataValuevue_type_script_lang_ts_ = (StringDataValuevue_type_script_lang_ts_); 
// EXTERNAL MODULE: ./src/presentation/components/StringDataValue.vue?vue&type=style&index=0&lang=scss&
var StringDataValuevue_type_style_index_0_lang_scss_ = __webpack_require__("2f88");

// CONCATENATED MODULE: ./src/presentation/components/StringDataValue.vue






/* normalize component */

var StringDataValue_component = normalizeComponent(
  components_StringDataValuevue_type_script_lang_ts_,
  StringDataValuevue_type_template_id_a0d9f84c_render,
  StringDataValuevue_type_template_id_a0d9f84c_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var components_StringDataValue = (StringDataValue_component.exports);
// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js?{"cacheDirectory":"node_modules/.cache/vue-loader","cacheIdentifier":"42133e48-vue-loader-template"}!./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/ReferenceSection.vue?vue&type=template&id=400ef5a4&
var ReferenceSectionvue_type_template_id_400ef5a4_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"wb-db-references"},[_c('h2',{staticClass:"wb-db-references__heading"},[_vm._v(" "+_vm._s(_vm.$messages.get( _vm.$messages.KEYS.REFERENCES_HEADING ))+" ")]),_c('ul',_vm._l((_vm.targetReferences),function(reference,index){return _c('li',{key:index,staticClass:"wb-db-references__listItem"},[_c('div',[_c('SingleReferenceDisplay',{attrs:{"reference":reference,"separator":_vm.$messages.get( _vm.$messages.KEYS.REFERENCE_SNAK_SEPARATOR )}})],1)])}),0)])}
var ReferenceSectionvue_type_template_id_400ef5a4_staticRenderFns = []


// CONCATENATED MODULE: ./src/presentation/components/ReferenceSection.vue?vue&type=template&id=400ef5a4&

// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js?{"cacheDirectory":"node_modules/.cache/vue-loader","cacheIdentifier":"42133e48-vue-loader-template"}!./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/SingleReferenceDisplay.vue?vue&type=template&id=48caea56&scoped=true&
var SingleReferenceDisplayvue_type_template_id_48caea56_scoped_true_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('span',{staticClass:"wb-db-reference"},_vm._l((_vm.snaks()),function(value,index){return _c('span',{key:index},[(index > 0)?_c('span',[_vm._v(_vm._s(_vm.separator))]):_vm._e(),_c('span',[_vm._v(_vm._s(value))])])}),0)}
var SingleReferenceDisplayvue_type_template_id_48caea56_scoped_true_staticRenderFns = []


// CONCATENATED MODULE: ./src/presentation/components/SingleReferenceDisplay.vue?vue&type=template&id=48caea56&scoped=true&

// EXTERNAL MODULE: ./node_modules/core-js/modules/es.array.map.js
var es_array_map = __webpack_require__("d81d");

// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/ts-loader??ref--14-3!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/SingleReferenceDisplay.vue?vue&type=script&lang=ts&



















var SingleReferenceDisplayvue_type_script_lang_ts_SingleReferenceDisplay =
/*#__PURE__*/
function (_Vue) {
  _inherits(SingleReferenceDisplay, _Vue);

  function SingleReferenceDisplay() {
    _classCallCheck(this, SingleReferenceDisplay);

    return _possibleConstructorReturn(this, _getPrototypeOf(SingleReferenceDisplay).apply(this, arguments));
  }

  _createClass(SingleReferenceDisplay, [{
    key: "snaks",
    value: function snaks() {
      var _this = this;

      function flatten(array) {
        var flatArray = [];
        var _iteratorNormalCompletion = true;
        var _didIteratorError = false;
        var _iteratorError = undefined;

        try {
          for (var _iterator = array[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
            var elements = _step.value;
            var _iteratorNormalCompletion2 = true;
            var _didIteratorError2 = false;
            var _iteratorError2 = undefined;

            try {
              for (var _iterator2 = elements[Symbol.iterator](), _step2; !(_iteratorNormalCompletion2 = (_step2 = _iterator2.next()).done); _iteratorNormalCompletion2 = true) {
                var element = _step2.value;

                if (element !== null) {
                  flatArray.push(element);
                }
              }
            } catch (err) {
              _didIteratorError2 = true;
              _iteratorError2 = err;
            } finally {
              try {
                if (!_iteratorNormalCompletion2 && _iterator2.return != null) {
                  _iterator2.return();
                }
              } finally {
                if (_didIteratorError2) {
                  throw _iteratorError2;
                }
              }
            }
          }
        } catch (err) {
          _didIteratorError = true;
          _iteratorError = err;
        } finally {
          try {
            if (!_iteratorNormalCompletion && _iterator.return != null) {
              _iterator.return();
            }
          } finally {
            if (_didIteratorError) {
              throw _iteratorError;
            }
          }
        }

        return flatArray;
      }

      function isValueSnak(snak) {
        return snak.snaktype === 'value';
      }

      return flatten(this.reference['snaks-order'].map(function (propertyId) {
        return _this.reference.snaks[propertyId].map(function (snak) {
          if (!isValueSnak(snak)) {
            // TODO: handle novalue and somevalue
            return null;
          }

          var datavalueValue = snak.datavalue.value;

          if (_typeof(datavalueValue) === 'object') {
            return JSON.stringify(datavalueValue);
          }

          return datavalueValue;
        });
      }));
    }
  }]);

  return SingleReferenceDisplay;
}(external_commonjs_vue2_commonjs2_vue2_amd_vue2_root_vue2_default.a);

__decorate([Object(vue_property_decorator["Prop"])({
  required: true
})], SingleReferenceDisplayvue_type_script_lang_ts_SingleReferenceDisplay.prototype, "reference", void 0);

__decorate([Object(vue_property_decorator["Prop"])({
  required: false,
  type: String,
  default: '. '
})], SingleReferenceDisplayvue_type_script_lang_ts_SingleReferenceDisplay.prototype, "separator", void 0);

SingleReferenceDisplayvue_type_script_lang_ts_SingleReferenceDisplay = __decorate([vue_class_component_common_default.a], SingleReferenceDisplayvue_type_script_lang_ts_SingleReferenceDisplay);
/* harmony default export */ var SingleReferenceDisplayvue_type_script_lang_ts_ = (SingleReferenceDisplayvue_type_script_lang_ts_SingleReferenceDisplay);
// CONCATENATED MODULE: ./src/presentation/components/SingleReferenceDisplay.vue?vue&type=script&lang=ts&
 /* harmony default export */ var components_SingleReferenceDisplayvue_type_script_lang_ts_ = (SingleReferenceDisplayvue_type_script_lang_ts_); 
// CONCATENATED MODULE: ./src/presentation/components/SingleReferenceDisplay.vue





/* normalize component */

var SingleReferenceDisplay_component = normalizeComponent(
  components_SingleReferenceDisplayvue_type_script_lang_ts_,
  SingleReferenceDisplayvue_type_template_id_48caea56_scoped_true_render,
  SingleReferenceDisplayvue_type_template_id_48caea56_scoped_true_staticRenderFns,
  false,
  null,
  "48caea56",
  null
  
)

/* harmony default export */ var components_SingleReferenceDisplay = (SingleReferenceDisplay_component.exports);
// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/ts-loader??ref--14-3!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/ReferenceSection.vue?vue&type=script&lang=ts&










var ReferenceSectionvue_type_script_lang_ts_ReferenceSection =
/*#__PURE__*/
function (_mixins) {
  _inherits(ReferenceSection, _mixins);

  function ReferenceSection() {
    _classCallCheck(this, ReferenceSection);

    return _possibleConstructorReturn(this, _getPrototypeOf(ReferenceSection).apply(this, arguments));
  }

  _createClass(ReferenceSection, [{
    key: "targetReferences",
    get: function get() {
      return this.rootModule.getters.targetReferences;
    }
  }]);

  return ReferenceSection;
}(Object(vue_class_component_common["mixins"])(presentation_StateMixin));

ReferenceSectionvue_type_script_lang_ts_ReferenceSection = __decorate([vue_class_component_common_default()({
  components: {
    SingleReferenceDisplay: components_SingleReferenceDisplay
  }
})], ReferenceSectionvue_type_script_lang_ts_ReferenceSection);
/* harmony default export */ var ReferenceSectionvue_type_script_lang_ts_ = (ReferenceSectionvue_type_script_lang_ts_ReferenceSection);
// CONCATENATED MODULE: ./src/presentation/components/ReferenceSection.vue?vue&type=script&lang=ts&
 /* harmony default export */ var components_ReferenceSectionvue_type_script_lang_ts_ = (ReferenceSectionvue_type_script_lang_ts_); 
// EXTERNAL MODULE: ./src/presentation/components/ReferenceSection.vue?vue&type=style&index=0&lang=scss&
var ReferenceSectionvue_type_style_index_0_lang_scss_ = __webpack_require__("e839");

// CONCATENATED MODULE: ./src/presentation/components/ReferenceSection.vue






/* normalize component */

var ReferenceSection_component = normalizeComponent(
  components_ReferenceSectionvue_type_script_lang_ts_,
  ReferenceSectionvue_type_template_id_400ef5a4_render,
  ReferenceSectionvue_type_template_id_400ef5a4_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var components_ReferenceSection = (ReferenceSection_component.exports);
// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/ts-loader??ref--14-3!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/DataBridge.vue?vue&type=script&lang=ts&












var DataBridgevue_type_script_lang_ts_DataBridge =
/*#__PURE__*/
function (_mixins) {
  _inherits(DataBridge, _mixins);

  function DataBridge() {
    _classCallCheck(this, DataBridge);

    return _possibleConstructorReturn(this, _getPrototypeOf(DataBridge).apply(this, arguments));
  }

  _createClass(DataBridge, [{
    key: "setDataValue",
    value: function setDataValue(dataValue) {
      this.rootModule.dispatch('setTargetValue', dataValue);
    }
  }, {
    key: "targetValue",
    get: function get() {
      var targetValue = this.rootModule.getters.targetValue;

      if (targetValue === null) {
        throw new Error('not yet ready!');
      }

      return targetValue;
    }
  }, {
    key: "targetProperty",
    get: function get() {
      return this.rootModule.state.targetProperty;
    }
  }, {
    key: "targetLabel",
    get: function get() {
      return this.rootModule.getters.targetLabel;
    }
  }]);

  return DataBridge;
}(Object(vue_class_component_common["mixins"])(presentation_StateMixin));

DataBridgevue_type_script_lang_ts_DataBridge = __decorate([vue_class_component_common_default()({
  components: {
    EditDecision: components_EditDecision,
    StringDataValue: components_StringDataValue,
    ReferenceSection: components_ReferenceSection
  }
})], DataBridgevue_type_script_lang_ts_DataBridge);
/* harmony default export */ var DataBridgevue_type_script_lang_ts_ = (DataBridgevue_type_script_lang_ts_DataBridge);
// CONCATENATED MODULE: ./src/presentation/components/DataBridge.vue?vue&type=script&lang=ts&
 /* harmony default export */ var components_DataBridgevue_type_script_lang_ts_ = (DataBridgevue_type_script_lang_ts_); 
// EXTERNAL MODULE: ./src/presentation/components/DataBridge.vue?vue&type=style&index=0&lang=scss&
var DataBridgevue_type_style_index_0_lang_scss_ = __webpack_require__("436e");

// CONCATENATED MODULE: ./src/presentation/components/DataBridge.vue






/* normalize component */

var DataBridge_component = normalizeComponent(
  components_DataBridgevue_type_script_lang_ts_,
  DataBridgevue_type_template_id_14c7fe98_render,
  DataBridgevue_type_template_id_14c7fe98_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var components_DataBridge = (DataBridge_component.exports);
// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js?{"cacheDirectory":"node_modules/.cache/vue-loader","cacheIdentifier":"42133e48-vue-loader-template"}!./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/Initializing.vue?vue&type=template&id=5903a292&
var Initializingvue_type_template_id_5903a292_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"wb-db-init"},[(_vm.ready)?_vm._t("default"):[(_vm.loadingIsSlow)?_c('IndeterminateProgressBar'):_vm._e()]],2)}
var Initializingvue_type_template_id_5903a292_staticRenderFns = []


// CONCATENATED MODULE: ./src/presentation/components/Initializing.vue?vue&type=template&id=5903a292&

// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/ts-loader??ref--14-3!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/Initializing.vue?vue&type=script&lang=ts&









/**
 * A component which gets shown to illustrate that an operation is
 * ongoing which temporarily does not allow user interaction.
 *
 * Depending on the run time of the operation an animation is shown.
 *
 * This
 *
 * * shows the default slot if `isInitializing` is false (= ready)
 * * hides the default slot while `isInitializing` is true; during that time it
 *   * shows blank until `TIME_UNTIL_CONSIDERED_SLOW`
 *   * shows the `IndeterminateProgressBar` from there on until `isInitializing` is false
 *   * shows the `IndeterminateProgressBar` for at least `MINIMUM_TIME_OF_PROGRESS_ANIMATION`[1]
 *
 * [1] This condition is only applied while `isInitializing` is true
 *
 * Effectively there are three scenarios:
 *
 * ```
 * Timeline     0s                        1s            1.5s            2s
 * Scenario 1
 *   Loading    |------------------|
 *   Animation      (no animation)  <- ready
 * Scenario 2
 *   Loading    |----------------------------|
 *   Animation                            |--------------|<- ready
 * Scenario 3
 *   Loading    |---------------------------------------------|
 *   Animation                            |-------------------|<- ready
 * ```
 */

var Initializingvue_type_script_lang_ts_Initializing =
/*#__PURE__*/
function (_Vue) {
  _inherits(Initializing, _Vue);

  function Initializing() {
    var _this;

    _classCallCheck(this, Initializing);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(Initializing).apply(this, arguments));
    _this.ready = false;
    _this.loadingIsSlow = false;
    _this.animatedEnough = false;
    return _this;
  }

  _createClass(Initializing, [{
    key: "onStatusChange",
    value: function onStatusChange(isInitializing, _oldStatus) {
      if (isInitializing) {
        this.showLoading();
      } else {
        this.tendTowardsReady();
      }
    }
  }, {
    key: "showLoading",
    value: function showLoading() {
      var _this2 = this;

      this.ready = false;
      this.trackSlowness = setTimeout(function () {
        _this2.loadingIsSlow = true;
        _this2.trackAnimation = setTimeout(function () {
          _this2.animatedEnough = true;

          _this2.tendTowardsReady();
        }, _this2.MINIMUM_TIME_OF_PROGRESS_ANIMATION);
      }, this.TIME_UNTIL_CONSIDERED_SLOW);
    }
  }, {
    key: "tendTowardsReady",
    value: function tendTowardsReady() {
      if (this.isInitializing || this.loadingIsSlow && !this.animatedEnough) {
        return;
      }

      this.ready = true;
      this.resetSlownessTracking();
    }
  }, {
    key: "resetSlownessTracking",
    value: function resetSlownessTracking() {
      this.loadingIsSlow = false;
      this.animatedEnough = false;

      if (this.trackSlowness) {
        clearTimeout(this.trackSlowness);
        this.trackSlowness = undefined;
      }

      if (this.trackAnimation) {
        clearTimeout(this.trackAnimation);
        this.trackAnimation = undefined;
      }
    }
  }]);

  return Initializing;
}(vue_property_decorator["Vue"]);

__decorate([Object(vue_property_decorator["Prop"])({
  required: true
})], Initializingvue_type_script_lang_ts_Initializing.prototype, "isInitializing", void 0);

__decorate([Object(vue_property_decorator["Prop"])({
  default: 1000
})], Initializingvue_type_script_lang_ts_Initializing.prototype, "TIME_UNTIL_CONSIDERED_SLOW", void 0);

__decorate([Object(vue_property_decorator["Prop"])({
  default: 500
})], Initializingvue_type_script_lang_ts_Initializing.prototype, "MINIMUM_TIME_OF_PROGRESS_ANIMATION", void 0);

__decorate([Object(vue_property_decorator["Watch"])('isInitializing', {
  immediate: true
})], Initializingvue_type_script_lang_ts_Initializing.prototype, "onStatusChange", null);

Initializingvue_type_script_lang_ts_Initializing = __decorate([vue_class_component_common_default()({
  components: {
    IndeterminateProgressBar: wikibase_vuejs_components_common["IndeterminateProgressBar"]
  }
})], Initializingvue_type_script_lang_ts_Initializing);
/* harmony default export */ var Initializingvue_type_script_lang_ts_ = (Initializingvue_type_script_lang_ts_Initializing);
// CONCATENATED MODULE: ./src/presentation/components/Initializing.vue?vue&type=script&lang=ts&
 /* harmony default export */ var components_Initializingvue_type_script_lang_ts_ = (Initializingvue_type_script_lang_ts_); 
// CONCATENATED MODULE: ./src/presentation/components/Initializing.vue





/* normalize component */

var Initializing_component = normalizeComponent(
  components_Initializingvue_type_script_lang_ts_,
  Initializingvue_type_template_id_5903a292_render,
  Initializingvue_type_template_id_5903a292_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var components_Initializing = (Initializing_component.exports);
// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js?{"cacheDirectory":"node_modules/.cache/vue-loader","cacheIdentifier":"42133e48-vue-loader-template"}!./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/ErrorWrapper.vue?vue&type=template&id=5426d03f&
var ErrorWrappervue_type_template_id_5426d03f_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('section',{staticClass:"wb-db-error"},[(_vm.permissionErrors.length)?_c('ErrorPermission',{attrs:{"permission-errors":_vm.permissionErrors}}):(_vm.unsupportedDatatypeError !== null)?_c('ErrorUnsupportedDatatype',{attrs:{"data-type":_vm.unsupportedDatatypeError.info.unsupportedDatatype}}):(_vm.statementValueIsDeprecated)?_c('ErrorDeprecatedStatement'):(_vm.statementIsAmbiguous)?_c('ErrorAmbiguousStatement'):(_vm.unsupportedSnakTypeError !== null)?_c('ErrorUnsupportedSnakType',{attrs:{"snak-type":_vm.unsupportedSnakTypeError.info.snakType}}):_c('ErrorUnknown')],1)}
var ErrorWrappervue_type_template_id_5426d03f_staticRenderFns = []


// CONCATENATED MODULE: ./src/presentation/components/ErrorWrapper.vue?vue&type=template&id=5426d03f&

// EXTERNAL MODULE: ./node_modules/core-js/modules/es.array.includes.js
var es_array_includes = __webpack_require__("caad");

// EXTERNAL MODULE: ./node_modules/core-js/modules/es.array.some.js
var es_array_some = __webpack_require__("45fc");

// EXTERNAL MODULE: ./node_modules/core-js/modules/es.string.includes.js
var es_string_includes = __webpack_require__("2532");

// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js?{"cacheDirectory":"node_modules/.cache/vue-loader","cacheIdentifier":"42133e48-vue-loader-template"}!./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/ErrorUnsupportedSnakType.vue?vue&type=template&id=9cb372d2&
var ErrorUnsupportedSnakTypevue_type_template_id_9cb372d2_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"wb-db-unsupported-snaktype"},[_c('IconMessageBox',{staticClass:"wb-db-unsupported-snaktype__message",attrs:{"type":"notice","inline":true}},[_c('p',{staticClass:"wb-db-unsupported-snaktype__head",domProps:{"innerHTML":_vm._s(_vm.$messages.get( _vm.messageHeaderKey, _vm.propertyLabel ))}}),_c('p',{staticClass:"wb-db-unsupported-snaktype__body",domProps:{"innerHTML":_vm._s(_vm.$messages.get( _vm.messageBodyKey, _vm.propertyLabel ))}})]),_c('BailoutActions',{staticClass:"wb-db-unsupported-snaktype__bailout",attrs:{"original-href":_vm.originalHref,"page-title":_vm.pageTitle}})],1)}
var ErrorUnsupportedSnakTypevue_type_template_id_9cb372d2_staticRenderFns = []


// CONCATENATED MODULE: ./src/presentation/components/ErrorUnsupportedSnakType.vue?vue&type=template&id=9cb372d2&

// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js?{"cacheDirectory":"node_modules/.cache/vue-loader","cacheIdentifier":"42133e48-vue-loader-template"}!./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/IconMessageBox.vue?vue&type=template&id=16f56435&
var IconMessageBoxvue_type_template_id_16f56435_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"wb-ui-icon-message-box",class:[
		("wb-ui-icon-message-box--" + _vm.type),
		!_vm.inline ? 'wb-ui-icon-message-box--block' : '' ]},[_c('div',{staticClass:"wb-ui-icon-message-box__content"},[_vm._t("default")],2)])}
var IconMessageBoxvue_type_template_id_16f56435_staticRenderFns = []


// CONCATENATED MODULE: ./src/presentation/components/IconMessageBox.vue?vue&type=template&id=16f56435&

// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/ts-loader??ref--14-3!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/IconMessageBox.vue?vue&type=script&lang=ts&









var validTypes = ['notice'];

var IconMessageBoxvue_type_script_lang_ts_IconMessageBox =
/*#__PURE__*/
function (_Vue) {
  _inherits(IconMessageBox, _Vue);

  function IconMessageBox() {
    _classCallCheck(this, IconMessageBox);

    return _possibleConstructorReturn(this, _getPrototypeOf(IconMessageBox).apply(this, arguments));
  }

  return IconMessageBox;
}(external_commonjs_vue2_commonjs2_vue2_amd_vue2_root_vue2_default.a);

__decorate([Object(vue_property_decorator["Prop"])({
  type: String,
  required: true,
  validator: function validator(type) {
    return validTypes.includes(type);
  }
})], IconMessageBoxvue_type_script_lang_ts_IconMessageBox.prototype, "type", void 0);

__decorate([Object(vue_property_decorator["Prop"])({
  type: Boolean,
  required: false,
  default: false
})], IconMessageBoxvue_type_script_lang_ts_IconMessageBox.prototype, "inline", void 0);

IconMessageBoxvue_type_script_lang_ts_IconMessageBox = __decorate([vue_class_component_common_default.a], IconMessageBoxvue_type_script_lang_ts_IconMessageBox);
/* harmony default export */ var IconMessageBoxvue_type_script_lang_ts_ = (IconMessageBoxvue_type_script_lang_ts_IconMessageBox);
// CONCATENATED MODULE: ./src/presentation/components/IconMessageBox.vue?vue&type=script&lang=ts&
 /* harmony default export */ var components_IconMessageBoxvue_type_script_lang_ts_ = (IconMessageBoxvue_type_script_lang_ts_); 
// EXTERNAL MODULE: ./src/presentation/components/IconMessageBox.vue?vue&type=style&index=0&lang=scss&
var IconMessageBoxvue_type_style_index_0_lang_scss_ = __webpack_require__("15ae");

// CONCATENATED MODULE: ./src/presentation/components/IconMessageBox.vue






/* normalize component */

var IconMessageBox_component = normalizeComponent(
  components_IconMessageBoxvue_type_script_lang_ts_,
  IconMessageBoxvue_type_template_id_16f56435_render,
  IconMessageBoxvue_type_template_id_16f56435_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var components_IconMessageBox = (IconMessageBox_component.exports);
// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js?{"cacheDirectory":"node_modules/.cache/vue-loader","cacheIdentifier":"42133e48-vue-loader-template"}!./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/BailoutActions.vue?vue&type=template&id=0378d24a&
var BailoutActionsvue_type_template_id_0378d24a_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"wb-db-bailout-actions"},[_c('h2',{staticClass:"wb-db-bailout-actions__heading"},[_vm._v(" "+_vm._s(_vm.$messages.get( _vm.$messages.KEYS.BAILOUT_HEADING ))+" ")]),_c('ul',[_c('li',{staticClass:"wb-db-bailout-actions__suggestion"},[_vm._v(" "+_vm._s(_vm.$messages.get( _vm.$messages.KEYS.BAILOUT_SUGGESTION_GO_TO_REPO ))),_c('br'),_c('EventEmittingButton',{staticClass:"wb-db-bailout-actions__button",attrs:{"type":"primaryProgressive","message":_vm.$messages.get( _vm.$messages.KEYS.BAILOUT_SUGGESTION_GO_TO_REPO_BUTTON ),"href":_vm.originalHref,"new-tab":true,"prevent-default":false}})],1),_c('li',{staticClass:"wb-db-bailout-actions__suggestion",domProps:{"innerHTML":_vm._s(_vm.$messages.get( _vm.$messages.KEYS.BAILOUT_SUGGESTION_EDIT_ARTICLE, _vm.editArticleUrl ))}})])])}
var BailoutActionsvue_type_template_id_0378d24a_staticRenderFns = []


// CONCATENATED MODULE: ./src/presentation/components/BailoutActions.vue?vue&type=template&id=0378d24a&

// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js?{"cacheDirectory":"node_modules/.cache/vue-loader","cacheIdentifier":"42133e48-vue-loader-template"}!./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/EventEmittingButton.vue?vue&type=template&id=3a88cf2c&
var EventEmittingButtonvue_type_template_id_3a88cf2c_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('a',{staticClass:"wb-ui-event-emitting-button",class:[
		("wb-ui-event-emitting-button--" + (this.type)),
		{ 'wb-ui-event-emitting-button--squary': _vm.squary },
		{ 'wb-ui-event-emitting-button--pressed': _vm.isPressed },
		{ 'wb-ui-event-emitting-button--iconOnly': _vm.isIconOnly },
		{ 'wb-ui-event-emitting-button--frameless': _vm.isFrameless },
		{ 'wb-ui-event-emitting-button--disabled': _vm.disabled } ],attrs:{"href":_vm.href,"tabindex":_vm.tabindex,"role":_vm.href ? 'link' : 'button',"aria-disabled":_vm.disabled ? 'true' : null,"title":_vm.message,"target":_vm.opensInNewTab ? '_blank' : null,"rel":_vm.opensInNewTab ? 'noreferrer noopener' : null},on:{"click":_vm.click,"keydown":[function($event){if(!$event.type.indexOf('key')&&_vm._k($event.keyCode,"enter",13,$event.key,"Enter")){ return null; }return _vm.handleEnterPress($event)},function($event){if(!$event.type.indexOf('key')&&_vm._k($event.keyCode,"space",32,$event.key,[" ","Spacebar"])){ return null; }return _vm.handleSpacePress($event)}],"keyup":[function($event){if(!$event.type.indexOf('key')&&_vm._k($event.keyCode,"enter",13,$event.key,"Enter")){ return null; }return _vm.unpress($event)},function($event){if(!$event.type.indexOf('key')&&_vm._k($event.keyCode,"space",32,$event.key,[" ","Spacebar"])){ return null; }return _vm.unpress($event)}]}},[_c('span',{staticClass:"wb-ui-event-emitting-button__text"},[_vm._v(_vm._s(_vm.message))])])}
var EventEmittingButtonvue_type_template_id_3a88cf2c_staticRenderFns = []


// CONCATENATED MODULE: ./src/presentation/components/EventEmittingButton.vue?vue&type=template&id=3a88cf2c&

// EXTERNAL MODULE: ./node_modules/core-js/modules/es.array.index-of.js
var es_array_index_of = __webpack_require__("c975");

// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/ts-loader??ref--14-3!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/EventEmittingButton.vue?vue&type=script&lang=ts&











var EventEmittingButtonvue_type_script_lang_ts_validTypes = ['primaryProgressive', 'cancel'];
var framelessTypes = ['cancel'];
var imageOnlyTypes = ['cancel'];

var EventEmittingButtonvue_type_script_lang_ts_EventEmittingButton =
/*#__PURE__*/
function (_Vue) {
  _inherits(EventEmittingButton, _Vue);

  function EventEmittingButton() {
    var _this;

    _classCallCheck(this, EventEmittingButton);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(EventEmittingButton).apply(this, arguments));
    _this.isPressed = false;
    return _this;
  }

  _createClass(EventEmittingButton, [{
    key: "handleSpacePress",
    value: function handleSpacePress(event) {
      if (!this.simulateSpaceOnButton()) {
        return;
      }

      this.preventScrollingDown(event);
      this.isPressed = true;
      this.click(event);
    }
  }, {
    key: "handleEnterPress",
    value: function handleEnterPress(event) {
      this.isPressed = true;

      if (this.thereIsNoSeparateClickEvent()) {
        this.click(event);
      }
    }
  }, {
    key: "unpress",
    value: function unpress() {
      this.isPressed = false;
    }
  }, {
    key: "click",
    value: function click(event) {
      if (this.preventDefault) {
        this.preventOpeningLink(event);
      }

      if (this.disabled) {
        return;
      }

      this.$emit('click', event);
    }
  }, {
    key: "preventOpeningLink",
    value: function preventOpeningLink(event) {
      event.preventDefault();
    }
  }, {
    key: "preventScrollingDown",
    value: function preventScrollingDown(event) {
      event.preventDefault();
    }
  }, {
    key: "thereIsNoSeparateClickEvent",
    value: function thereIsNoSeparateClickEvent() {
      return this.href === null;
    }
  }, {
    key: "simulateSpaceOnButton",
    value: function simulateSpaceOnButton() {
      return this.href === null;
    }
  }, {
    key: "isIconOnly",
    get: function get() {
      return imageOnlyTypes.includes(this.type);
    }
  }, {
    key: "isFrameless",
    get: function get() {
      return framelessTypes.includes(this.type);
    }
  }, {
    key: "opensInNewTab",
    get: function get() {
      return this.href !== null && this.newTab;
    }
  }, {
    key: "tabindex",
    get: function get() {
      if (this.disabled) {
        return -1;
      }

      if (this.href) {
        return null;
      }

      return 0;
    }
  }]);

  return EventEmittingButton;
}(external_commonjs_vue2_commonjs2_vue2_amd_vue2_root_vue2_default.a);

__decorate([Object(vue_property_decorator["Prop"])({
  required: true,
  validator: function validator(type) {
    return EventEmittingButtonvue_type_script_lang_ts_validTypes.indexOf(type) !== -1;
  }
})], EventEmittingButtonvue_type_script_lang_ts_EventEmittingButton.prototype, "type", void 0);

__decorate([Object(vue_property_decorator["Prop"])({
  required: true,
  type: String
})], EventEmittingButtonvue_type_script_lang_ts_EventEmittingButton.prototype, "message", void 0);

__decorate([Object(vue_property_decorator["Prop"])({
  required: false,
  default: null,
  type: String
})], EventEmittingButtonvue_type_script_lang_ts_EventEmittingButton.prototype, "href", void 0);

__decorate([Object(vue_property_decorator["Prop"])({
  required: false,
  default: true,
  type: Boolean
})], EventEmittingButtonvue_type_script_lang_ts_EventEmittingButton.prototype, "preventDefault", void 0);

__decorate([Object(vue_property_decorator["Prop"])({
  required: false,
  default: false,
  type: Boolean
})], EventEmittingButtonvue_type_script_lang_ts_EventEmittingButton.prototype, "disabled", void 0);

__decorate([Object(vue_property_decorator["Prop"])({
  required: false,
  default: false,
  type: Boolean
})], EventEmittingButtonvue_type_script_lang_ts_EventEmittingButton.prototype, "squary", void 0);

__decorate([Object(vue_property_decorator["Prop"])({
  required: false,
  default: false,
  type: Boolean
})], EventEmittingButtonvue_type_script_lang_ts_EventEmittingButton.prototype, "newTab", void 0);

EventEmittingButtonvue_type_script_lang_ts_EventEmittingButton = __decorate([vue_class_component_common_default.a], EventEmittingButtonvue_type_script_lang_ts_EventEmittingButton);
/* harmony default export */ var EventEmittingButtonvue_type_script_lang_ts_ = (EventEmittingButtonvue_type_script_lang_ts_EventEmittingButton);
// CONCATENATED MODULE: ./src/presentation/components/EventEmittingButton.vue?vue&type=script&lang=ts&
 /* harmony default export */ var components_EventEmittingButtonvue_type_script_lang_ts_ = (EventEmittingButtonvue_type_script_lang_ts_); 
// EXTERNAL MODULE: ./src/presentation/components/EventEmittingButton.vue?vue&type=style&index=0&lang=scss&
var EventEmittingButtonvue_type_style_index_0_lang_scss_ = __webpack_require__("2e53");

// CONCATENATED MODULE: ./src/presentation/components/EventEmittingButton.vue






/* normalize component */

var EventEmittingButton_component = normalizeComponent(
  components_EventEmittingButtonvue_type_script_lang_ts_,
  EventEmittingButtonvue_type_template_id_3a88cf2c_render,
  EventEmittingButtonvue_type_template_id_3a88cf2c_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var components_EventEmittingButton = (EventEmittingButton_component.exports);
// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/ts-loader??ref--14-3!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/BailoutActions.vue?vue&type=script&lang=ts&










/**
 * A component to present the user with alternative suggestions
 * if they cannot use the Data Bridge to edit a value for some reason.
 * (That reason is typically displayed above this component in an IconMessageBox.)
 */

var BailoutActionsvue_type_script_lang_ts_BailoutActions =
/*#__PURE__*/
function (_Vue) {
  _inherits(BailoutActions, _Vue);

  function BailoutActions() {
    _classCallCheck(this, BailoutActions);

    return _possibleConstructorReturn(this, _getPrototypeOf(BailoutActions).apply(this, arguments));
  }

  _createClass(BailoutActions, [{
    key: "editArticleUrl",

    /**
     * The full URL of the “article editor” link.
     */
    get: function get() {
      return this.$clientRouter.getPageUrl(this.pageTitle, {
        action: 'edit'
      });
    }
  }]);

  return BailoutActions;
}(external_commonjs_vue2_commonjs2_vue2_amd_vue2_root_vue2_default.a);

__decorate([Object(vue_property_decorator["Prop"])({
  required: true,
  type: String
})], BailoutActionsvue_type_script_lang_ts_BailoutActions.prototype, "originalHref", void 0);

__decorate([Object(vue_property_decorator["Prop"])({
  required: true,
  type: String
})], BailoutActionsvue_type_script_lang_ts_BailoutActions.prototype, "pageTitle", void 0);

BailoutActionsvue_type_script_lang_ts_BailoutActions = __decorate([vue_class_component_common_default()({
  components: {
    EventEmittingButton: components_EventEmittingButton
  }
})], BailoutActionsvue_type_script_lang_ts_BailoutActions);
/* harmony default export */ var BailoutActionsvue_type_script_lang_ts_ = (BailoutActionsvue_type_script_lang_ts_BailoutActions);
// CONCATENATED MODULE: ./src/presentation/components/BailoutActions.vue?vue&type=script&lang=ts&
 /* harmony default export */ var components_BailoutActionsvue_type_script_lang_ts_ = (BailoutActionsvue_type_script_lang_ts_); 
// EXTERNAL MODULE: ./src/presentation/components/BailoutActions.vue?vue&type=style&index=0&lang=scss&
var BailoutActionsvue_type_style_index_0_lang_scss_ = __webpack_require__("ceb6");

// CONCATENATED MODULE: ./src/presentation/components/BailoutActions.vue






/* normalize component */

var BailoutActions_component = normalizeComponent(
  components_BailoutActionsvue_type_script_lang_ts_,
  BailoutActionsvue_type_template_id_0378d24a_render,
  BailoutActionsvue_type_template_id_0378d24a_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var components_BailoutActions = (BailoutActions_component.exports);
// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/ts-loader??ref--14-3!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/ErrorUnsupportedSnakType.vue?vue&type=script&lang=ts&












/**
 * A component used to illustrate an error which happened when the user tried
 * to edit a statement with a snak type not supported by Bridge yet.
 */

var ErrorUnsupportedSnakTypevue_type_script_lang_ts_ErrorUnsupportedSnakType =
/*#__PURE__*/
function (_mixins) {
  _inherits(ErrorUnsupportedSnakType, _mixins);

  function ErrorUnsupportedSnakType() {
    _classCallCheck(this, ErrorUnsupportedSnakType);

    return _possibleConstructorReturn(this, _getPrototypeOf(ErrorUnsupportedSnakType).apply(this, arguments));
  }

  _createClass(ErrorUnsupportedSnakType, [{
    key: "propertyLabel",
    get: function get() {
      return new components_TermLabel({
        propsData: {
          term: this.rootModule.getters.targetLabel
        }
      }).$mount().$el;
    }
  }, {
    key: "pageTitle",
    get: function get() {
      return this.rootModule.state.pageTitle;
    }
  }, {
    key: "originalHref",
    get: function get() {
      return this.rootModule.state.originalHref;
    }
  }, {
    key: "messageHeaderKey",
    get: function get() {
      switch (this.snakType) {
        case 'somevalue':
          return this.$messages.KEYS.SOMEVALUE_ERROR_HEAD;

        case 'novalue':
          return this.$messages.KEYS.NOVALUE_ERROR_HEAD;

        default:
          throw new Error("No message for unsupported snak type ".concat(this.snakType));
      }
    }
  }, {
    key: "messageBodyKey",
    get: function get() {
      switch (this.snakType) {
        case 'somevalue':
          return this.$messages.KEYS.SOMEVALUE_ERROR_BODY;

        case 'novalue':
          return this.$messages.KEYS.NOVALUE_ERROR_BODY;

        default:
          throw new Error("No message for unsupported snak type ".concat(this.snakType));
      }
    }
  }]);

  return ErrorUnsupportedSnakType;
}(Object(vue_class_component_common["mixins"])(presentation_StateMixin));

__decorate([Object(vue_property_decorator["Prop"])({
  required: true
})], ErrorUnsupportedSnakTypevue_type_script_lang_ts_ErrorUnsupportedSnakType.prototype, "snakType", void 0);

ErrorUnsupportedSnakTypevue_type_script_lang_ts_ErrorUnsupportedSnakType = __decorate([vue_class_component_common_default()({
  components: {
    IconMessageBox: components_IconMessageBox,
    BailoutActions: components_BailoutActions
  }
})], ErrorUnsupportedSnakTypevue_type_script_lang_ts_ErrorUnsupportedSnakType);
/* harmony default export */ var ErrorUnsupportedSnakTypevue_type_script_lang_ts_ = (ErrorUnsupportedSnakTypevue_type_script_lang_ts_ErrorUnsupportedSnakType);
// CONCATENATED MODULE: ./src/presentation/components/ErrorUnsupportedSnakType.vue?vue&type=script&lang=ts&
 /* harmony default export */ var components_ErrorUnsupportedSnakTypevue_type_script_lang_ts_ = (ErrorUnsupportedSnakTypevue_type_script_lang_ts_); 
// EXTERNAL MODULE: ./src/presentation/components/ErrorUnsupportedSnakType.vue?vue&type=style&index=0&lang=scss&
var ErrorUnsupportedSnakTypevue_type_style_index_0_lang_scss_ = __webpack_require__("1d9d");

// CONCATENATED MODULE: ./src/presentation/components/ErrorUnsupportedSnakType.vue






/* normalize component */

var ErrorUnsupportedSnakType_component = normalizeComponent(
  components_ErrorUnsupportedSnakTypevue_type_script_lang_ts_,
  ErrorUnsupportedSnakTypevue_type_template_id_9cb372d2_render,
  ErrorUnsupportedSnakTypevue_type_template_id_9cb372d2_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var components_ErrorUnsupportedSnakType = (ErrorUnsupportedSnakType_component.exports);
// CONCATENATED MODULE: ./src/definitions/data-access/BridgePermissionsRepository.ts
var PageNotEditable;

(function (PageNotEditable) {
  PageNotEditable["BLOCKED_ON_CLIENT_PAGE"] = "blocked_on_client_page";
  PageNotEditable["BLOCKED_ON_REPO_ITEM"] = "blocked_on_repo_item";
  PageNotEditable["PAGE_CASCADE_PROTECTED"] = "cascadeprotected_on_client_page";
  PageNotEditable["ITEM_FULLY_PROTECTED"] = "protectedpage";
  PageNotEditable["ITEM_SEMI_PROTECTED"] = "semiprotectedpage";
  PageNotEditable["ITEM_CASCADE_PROTECTED"] = "cascadeprotected";
  PageNotEditable["UNKNOWN"] = "unknown";
})(PageNotEditable || (PageNotEditable = {}));
// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js?{"cacheDirectory":"node_modules/.cache/vue-loader","cacheIdentifier":"42133e48-vue-loader-template"}!./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/ErrorPermission.vue?vue&type=template&id=98e281fe&
var ErrorPermissionvue_type_template_id_98e281fe_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('section',[_c('p',{staticClass:"wb-db-error-permission__heading"},[_vm._v(" "+_vm._s(_vm.$messages.get( _vm.$messages.KEYS.PERMISSIONS_HEADING ))+" ")]),_vm._l((_vm.permissionErrors),function(permissionError,index){return _c('ErrorPermissionInfo',{key:index,staticClass:"wb-db-error-permission__info",attrs:{"message-header":_vm.getMessageHeader( permissionError ),"message-body":_vm.getMessageBody( permissionError ),"expanded-by-default":_vm.permissionErrors.length === 1}})})],2)}
var ErrorPermissionvue_type_template_id_98e281fe_staticRenderFns = []


// CONCATENATED MODULE: ./src/presentation/components/ErrorPermission.vue?vue&type=template&id=98e281fe&

// EXTERNAL MODULE: ./node_modules/core-js/modules/es.array.concat.js
var es_array_concat = __webpack_require__("99af");

// EXTERNAL MODULE: ./node_modules/core-js/modules/es.regexp.exec.js
var es_regexp_exec = __webpack_require__("ac1f");

// EXTERNAL MODULE: ./node_modules/core-js/modules/es.string.split.js
var es_string_split = __webpack_require__("1276");

// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js?{"cacheDirectory":"node_modules/.cache/vue-loader","cacheIdentifier":"42133e48-vue-loader-template"}!./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/ErrorPermissionInfo.vue?vue&type=template&id=b74a69ee&
var ErrorPermissionInfovue_type_template_id_b74a69ee_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"wb-db-error-permission-info"},[_c('div',{staticClass:"wb-db-error-permission-info__header",domProps:{"innerHTML":_vm._s(_vm.messageHeader)}}),_c('a',{staticClass:"wb-db-error-permission-info__toggle",class:[ ("wb-db-error-permission-info__toggle--" + (_vm.infoIsExpanded ? 'open' : 'closed')) ],on:{"click":_vm.toggleInfo}},[_vm._v(" "+_vm._s(this.$messages.get( this.$messages.KEYS.PERMISSIONS_MORE_INFO ))+" ")]),(_vm.infoIsExpanded)?_c('div',{staticClass:"wb-db-error-permission-info__body",domProps:{"innerHTML":_vm._s(_vm.messageBody)}}):_vm._e()])}
var ErrorPermissionInfovue_type_template_id_b74a69ee_staticRenderFns = []


// CONCATENATED MODULE: ./src/presentation/components/ErrorPermissionInfo.vue?vue&type=template&id=b74a69ee&

// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/ts-loader??ref--14-3!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/ErrorPermissionInfo.vue?vue&type=script&lang=ts&








/**
 * A component used to illustrate permission errors which happened when
 * checking the user's authorization to perform an action.
 */

var ErrorPermissionInfovue_type_script_lang_ts_ErrorPermissionInfo =
/*#__PURE__*/
function (_Vue) {
  _inherits(ErrorPermissionInfo, _Vue);

  function ErrorPermissionInfo() {
    var _this;

    _classCallCheck(this, ErrorPermissionInfo);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(ErrorPermissionInfo).apply(this, arguments));
    _this.infoIsExpanded = false;
    return _this;
  }

  _createClass(ErrorPermissionInfo, [{
    key: "created",
    value: function created() {
      this.infoIsExpanded = this.expandedByDefault;
    }
  }, {
    key: "toggleInfo",
    value: function toggleInfo() {
      this.infoIsExpanded = !this.infoIsExpanded;
    }
  }]);

  return ErrorPermissionInfo;
}(vue_property_decorator["Vue"]);

__decorate([Object(vue_property_decorator["Prop"])({
  required: false,
  default: false,
  type: Boolean
})], ErrorPermissionInfovue_type_script_lang_ts_ErrorPermissionInfo.prototype, "expandedByDefault", void 0);

__decorate([Object(vue_property_decorator["Prop"])({
  required: true
})], ErrorPermissionInfovue_type_script_lang_ts_ErrorPermissionInfo.prototype, "messageHeader", void 0);

__decorate([Object(vue_property_decorator["Prop"])({
  required: true
})], ErrorPermissionInfovue_type_script_lang_ts_ErrorPermissionInfo.prototype, "messageBody", void 0);

ErrorPermissionInfovue_type_script_lang_ts_ErrorPermissionInfo = __decorate([vue_class_component_common_default.a], ErrorPermissionInfovue_type_script_lang_ts_ErrorPermissionInfo);
/* harmony default export */ var ErrorPermissionInfovue_type_script_lang_ts_ = (ErrorPermissionInfovue_type_script_lang_ts_ErrorPermissionInfo);
// CONCATENATED MODULE: ./src/presentation/components/ErrorPermissionInfo.vue?vue&type=script&lang=ts&
 /* harmony default export */ var components_ErrorPermissionInfovue_type_script_lang_ts_ = (ErrorPermissionInfovue_type_script_lang_ts_); 
// EXTERNAL MODULE: ./src/presentation/components/ErrorPermissionInfo.vue?vue&type=style&index=0&lang=scss&
var ErrorPermissionInfovue_type_style_index_0_lang_scss_ = __webpack_require__("d2e9");

// CONCATENATED MODULE: ./src/presentation/components/ErrorPermissionInfo.vue






/* normalize component */

var ErrorPermissionInfo_component = normalizeComponent(
  components_ErrorPermissionInfovue_type_script_lang_ts_,
  ErrorPermissionInfovue_type_template_id_b74a69ee_render,
  ErrorPermissionInfovue_type_template_id_b74a69ee_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var components_ErrorPermissionInfo = (ErrorPermissionInfo_component.exports);
// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js?{"cacheDirectory":"node_modules/.cache/vue-loader","cacheIdentifier":"42133e48-vue-loader-template"}!./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/PageList.vue?vue&type=template&id=19fe2d2f&
var PageListvue_type_template_id_19fe2d2f_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('ul',_vm._l((_vm.pages),function(page){return _c('li',{key:page},[_c('a',{attrs:{"href":_vm.router.getPageUrl( page )}},[_vm._v(_vm._s(page))])])}),0)}
var PageListvue_type_template_id_19fe2d2f_staticRenderFns = []


// CONCATENATED MODULE: ./src/presentation/components/PageList.vue?vue&type=template&id=19fe2d2f&

// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/ts-loader??ref--14-3!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/PageList.vue?vue&type=script&lang=ts&







/**
 * A component which renders page names into a list, incl. links to them.
 *
 * This is an internal component used when communicating permission
 * violations to the user. This happens for client and repo errors
 * alike; consequently the router is injectable instead of directly
 * accessing features from ClientRouterPlugin or RepoRouterPlugin
 * from here.
 */

var PageListvue_type_script_lang_ts_PageList =
/*#__PURE__*/
function (_Vue) {
  _inherits(PageList, _Vue);

  function PageList() {
    _classCallCheck(this, PageList);

    return _possibleConstructorReturn(this, _getPrototypeOf(PageList).apply(this, arguments));
  }

  return PageList;
}(vue_property_decorator["Vue"]);

__decorate([Object(vue_property_decorator["Prop"])({
  required: true
})], PageListvue_type_script_lang_ts_PageList.prototype, "pages", void 0);

__decorate([Object(vue_property_decorator["Prop"])({
  required: true
})], PageListvue_type_script_lang_ts_PageList.prototype, "router", void 0);

PageListvue_type_script_lang_ts_PageList = __decorate([vue_class_component_common_default.a], PageListvue_type_script_lang_ts_PageList);
/* harmony default export */ var PageListvue_type_script_lang_ts_ = (PageListvue_type_script_lang_ts_PageList);
// CONCATENATED MODULE: ./src/presentation/components/PageList.vue?vue&type=script&lang=ts&
 /* harmony default export */ var components_PageListvue_type_script_lang_ts_ = (PageListvue_type_script_lang_ts_); 
// CONCATENATED MODULE: ./src/presentation/components/PageList.vue





/* normalize component */

var PageList_component = normalizeComponent(
  components_PageListvue_type_script_lang_ts_,
  PageListvue_type_template_id_19fe2d2f_render,
  PageListvue_type_template_id_19fe2d2f_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var components_PageList = (PageList_component.exports);
// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js?{"cacheDirectory":"node_modules/.cache/vue-loader","cacheIdentifier":"42133e48-vue-loader-template"}!./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/UserLink.vue?vue&type=template&id=fbe573ec&
var UserLinkvue_type_template_id_fbe573ec_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('span',[(_vm.userId !== 0)?_c('a',{attrs:{"href":_vm.router.getPageUrl( ("Special:Redirect/user/" + _vm.userId) )}},[_c('bdi',[_vm._v(_vm._s(_vm.userName))])]):_c('bdi',[_vm._v(_vm._s(_vm.userName))])])}
var UserLinkvue_type_template_id_fbe573ec_staticRenderFns = []


// CONCATENATED MODULE: ./src/presentation/components/UserLink.vue?vue&type=template&id=fbe573ec&

// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/ts-loader??ref--14-3!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/UserLink.vue?vue&type=script&lang=ts&







/**
 * A component which renders a link to a user page on a certain wiki.
 *
 * This is an internal component used when communicating permission
 * violations to the user. This happens for client and repo errors
 * alike; consequently the router is injectable instead of directly
 * accessing features from ClientRouterPlugin or RepoRouterPlugin
 * from here.
 */

var UserLinkvue_type_script_lang_ts_UserLink =
/*#__PURE__*/
function (_Vue) {
  _inherits(UserLink, _Vue);

  function UserLink() {
    _classCallCheck(this, UserLink);

    return _possibleConstructorReturn(this, _getPrototypeOf(UserLink).apply(this, arguments));
  }

  return UserLink;
}(vue_property_decorator["Vue"]);

__decorate([Object(vue_property_decorator["Prop"])({
  required: true
})], UserLinkvue_type_script_lang_ts_UserLink.prototype, "userId", void 0);

__decorate([Object(vue_property_decorator["Prop"])({
  required: true
})], UserLinkvue_type_script_lang_ts_UserLink.prototype, "userName", void 0);

__decorate([Object(vue_property_decorator["Prop"])({
  required: true
})], UserLinkvue_type_script_lang_ts_UserLink.prototype, "router", void 0);

UserLinkvue_type_script_lang_ts_UserLink = __decorate([vue_class_component_common_default.a], UserLinkvue_type_script_lang_ts_UserLink);
/* harmony default export */ var UserLinkvue_type_script_lang_ts_ = (UserLinkvue_type_script_lang_ts_UserLink);
// CONCATENATED MODULE: ./src/presentation/components/UserLink.vue?vue&type=script&lang=ts&
 /* harmony default export */ var components_UserLinkvue_type_script_lang_ts_ = (UserLinkvue_type_script_lang_ts_); 
// CONCATENATED MODULE: ./src/presentation/components/UserLink.vue





/* normalize component */

var UserLink_component = normalizeComponent(
  components_UserLinkvue_type_script_lang_ts_,
  UserLinkvue_type_template_id_fbe573ec_render,
  UserLinkvue_type_template_id_fbe573ec_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var components_UserLink = (UserLink_component.exports);
// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/ts-loader??ref--14-3!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/ErrorPermission.vue?vue&type=script&lang=ts&















var _permissionTypeRender;









var permissionTypeRenderers = (_permissionTypeRender = {}, _defineProperty(_permissionTypeRender, PageNotEditable.ITEM_FULLY_PROTECTED, {
  header: 'PERMISSIONS_PROTECTED_HEADING',
  body: 'PERMISSIONS_PROTECTED_BODY'
}), _defineProperty(_permissionTypeRender, PageNotEditable.ITEM_SEMI_PROTECTED, {
  header: 'PERMISSIONS_SEMI_PROTECTED_HEADING',
  body: 'PERMISSIONS_SEMI_PROTECTED_BODY'
}), _defineProperty(_permissionTypeRender, PageNotEditable.ITEM_CASCADE_PROTECTED, {
  header: 'PERMISSIONS_CASCADE_PROTECTED_HEADING',
  body: 'PERMISSIONS_CASCADE_PROTECTED_BODY'
}), _defineProperty(_permissionTypeRender, PageNotEditable.BLOCKED_ON_CLIENT_PAGE, {
  header: 'PERMISSIONS_BLOCKED_ON_CLIENT_HEADING',
  body: 'PERMISSIONS_BLOCKED_ON_CLIENT_BODY'
}), _defineProperty(_permissionTypeRender, PageNotEditable.BLOCKED_ON_REPO_ITEM, {
  header: 'PERMISSIONS_BLOCKED_ON_REPO_HEADING',
  body: 'PERMISSIONS_BLOCKED_ON_REPO_BODY'
}), _defineProperty(_permissionTypeRender, PageNotEditable.PAGE_CASCADE_PROTECTED, {
  header: 'PERMISSIONS_PAGE_CASCADE_PROTECTED_HEADING',
  body: 'PERMISSIONS_PAGE_CASCADE_PROTECTED_BODY'
}), _defineProperty(_permissionTypeRender, PageNotEditable.UNKNOWN, {
  header: 'PERMISSIONS_ERROR_UNKNOWN_HEADING',
  body: 'PERMISSIONS_ERROR_UNKNOWN_BODY'
}), _permissionTypeRender);

var ErrorPermissionvue_type_script_lang_ts_ErrorPermission =
/*#__PURE__*/
function (_mixins) {
  _inherits(ErrorPermission, _mixins);

  function ErrorPermission() {
    _classCallCheck(this, ErrorPermission);

    return _possibleConstructorReturn(this, _getPrototypeOf(ErrorPermission).apply(this, arguments));
  }

  _createClass(ErrorPermission, [{
    key: "getMessageHeader",
    value: function getMessageHeader(permissionError) {
      var _this$$messages;

      return (_this$$messages = this.$messages).get.apply(_this$$messages, [this.$messages.KEYS[this.messageHeaderKey(permissionError)]].concat(_toConsumableArray(this.messageHeaderParameters(permissionError))));
    }
  }, {
    key: "getMessageBody",
    value: function getMessageBody(permissionError) {
      var _this$$messages2;

      return (_this$$messages2 = this.$messages).get.apply(_this$$messages2, [this.$messages.KEYS[this.messageBodyKey(permissionError)]].concat(_toConsumableArray(this.messageBodyParameters(permissionError))));
    }
    /** A poor (wo)man's implementation of constructing a correct
        talk page title due to lack of a redirect functionality.
        This can be removed once T242346 is resolved.
    */

  }, {
    key: "buildTalkPageNamespace",
    value: function buildTalkPageNamespace() {
      if (this.entityTitle.includes(':')) {
        var entityTitleParts = this.entityTitle.split(':', 2);
        return "".concat(entityTitleParts[0], "_talk:").concat(entityTitleParts[1]);
      }

      return "Talk:".concat(this.entityTitle);
    }
  }, {
    key: "messageHeaderKey",
    value: function messageHeaderKey(permissionError) {
      return permissionTypeRenderers[permissionError.type].header;
    }
  }, {
    key: "messageBodyKey",
    value: function messageBodyKey(permissionError) {
      return permissionTypeRenderers[permissionError.type].body;
    }
  }, {
    key: "messageHeaderParameters",
    value: function messageHeaderParameters(permissionError) {
      var params = [];

      switch (permissionError.type) {
        case PageNotEditable.ITEM_FULLY_PROTECTED:
          params.push(this.$repoRouter.getPageUrl('Project:Page_protection_policy'), this.$repoRouter.getPageUrl('Project:Administrators'));
          break;

        case PageNotEditable.ITEM_SEMI_PROTECTED:
          params.push(this.$repoRouter.getPageUrl('Project:Page_protection_policy'), this.$repoRouter.getPageUrl('Project:Autoconfirmed_users'));
          break;

        case PageNotEditable.ITEM_CASCADE_PROTECTED:
          params.push('', // unused (not reserved for anything in particular)
          this.$repoRouter.getPageUrl('Project:Administrators'));
          break;

        case PageNotEditable.PAGE_CASCADE_PROTECTED:
          // temporary; TODO remove once translations no longer use $2
          params.push('', // unused (not reserved for anything in particular)
          this.$clientRouter.getPageUrl('Project:Administrators'));
          break;
      }

      return params;
    }
  }, {
    key: "messageBodyParameters",
    value: function messageBodyParameters(permissionError) {
      var params = [];

      switch (permissionError.type) {
        case PageNotEditable.BLOCKED_ON_CLIENT_PAGE:
          {
            var _permissionError$info = permissionError.info,
                blockedBy = _permissionError$info.blockedBy,
                blockedById = _permissionError$info.blockedById,
                blockReason = _permissionError$info.blockReason,
                blockId = _permissionError$info.blockId,
                blockExpiry = _permissionError$info.blockExpiry,
                blockedTimestamp = _permissionError$info.blockedTimestamp;
            var blockedByText = this.bdi(blockedBy);
            var blockedByLink = new components_UserLink({
              propsData: {
                userId: blockedById,
                userName: blockedBy,
                router: this.$clientRouter
              }
            }).$mount().$el;
            params.push(blockedByLink, blockReason, '', // reserved for currentIP
            blockedByText, blockId.toString(), blockExpiry, '', // reserved for intended blockee
            blockedTimestamp);
            break;
          }

        case PageNotEditable.BLOCKED_ON_REPO_ITEM:
          {
            var _permissionError$info2 = permissionError.info,
                _blockedBy = _permissionError$info2.blockedBy,
                _blockedById = _permissionError$info2.blockedById,
                _blockReason = _permissionError$info2.blockReason,
                _blockId = _permissionError$info2.blockId,
                _blockExpiry = _permissionError$info2.blockExpiry,
                _blockedTimestamp = _permissionError$info2.blockedTimestamp;

            var _blockedByText = this.bdi(_blockedBy);

            var _blockedByLink = new components_UserLink({
              propsData: {
                userId: _blockedById,
                userName: _blockedBy,
                router: this.$repoRouter
              }
            }).$mount().$el;
            params.push(_blockedByLink, _blockReason, '', // reserved for currentIP
            _blockedByText, _blockId.toString(), _blockExpiry, '', // reserved for intended blockee
            _blockedTimestamp, this.$repoRouter.getPageUrl('Project:Administrators'));
            break;
          }

        case PageNotEditable.ITEM_FULLY_PROTECTED:
          params.push(this.$repoRouter.getPageUrl('Project:Page_protection_policy'), this.$repoRouter.getPageUrl('Project:Project:Edit_warring'), this.$repoRouter.getPageUrl('Special:Log/protect', {
            page: this.entityTitle
          }), this.$repoRouter.getPageUrl(this.buildTalkPageNamespace()));
          break;

        case PageNotEditable.ITEM_SEMI_PROTECTED:
          params.push(this.$repoRouter.getPageUrl('Special:Log/protect', {
            page: this.entityTitle
          }), this.$repoRouter.getPageUrl(this.buildTalkPageNamespace()));
          break;

        case PageNotEditable.ITEM_CASCADE_PROTECTED:
          params.push(permissionError.info.pages.length.toString(), this.convertToHtmlList(permissionError.info.pages, this.$repoRouter));
          break;

        case PageNotEditable.PAGE_CASCADE_PROTECTED:
          params.push(permissionError.info.pages.length.toString(), this.convertToHtmlList(permissionError.info.pages, this.$clientRouter));
          break;
      }

      return params;
    }
  }, {
    key: "bdi",
    value: function bdi(text) {
      return new vue_property_decorator["Vue"]({
        render: function render(createElement) {
          return createElement('bdi', text);
        }
      }).$mount().$el;
    }
  }, {
    key: "convertToHtmlList",
    value: function convertToHtmlList(arr, mwRouter) {
      var pageListInstance = new components_PageList({
        propsData: {
          pages: arr,
          router: mwRouter
        }
      });
      pageListInstance.$mount();
      return pageListInstance.$el;
    }
  }, {
    key: "entityTitle",
    get: function get() {
      return this.rootModule.state.entityTitle;
    }
  }]);

  return ErrorPermission;
}(Object(vue_class_component_common["mixins"])(presentation_StateMixin));

__decorate([Object(vue_property_decorator["Prop"])({
  required: true
})], ErrorPermissionvue_type_script_lang_ts_ErrorPermission.prototype, "permissionErrors", void 0);

ErrorPermissionvue_type_script_lang_ts_ErrorPermission = __decorate([vue_class_component_common_default()({
  components: {
    ErrorPermissionInfo: components_ErrorPermissionInfo
  }
})], ErrorPermissionvue_type_script_lang_ts_ErrorPermission);
/* harmony default export */ var ErrorPermissionvue_type_script_lang_ts_ = (ErrorPermissionvue_type_script_lang_ts_ErrorPermission);
// CONCATENATED MODULE: ./src/presentation/components/ErrorPermission.vue?vue&type=script&lang=ts&
 /* harmony default export */ var components_ErrorPermissionvue_type_script_lang_ts_ = (ErrorPermissionvue_type_script_lang_ts_); 
// EXTERNAL MODULE: ./src/presentation/components/ErrorPermission.vue?vue&type=style&index=0&lang=scss&
var ErrorPermissionvue_type_style_index_0_lang_scss_ = __webpack_require__("e24a");

// CONCATENATED MODULE: ./src/presentation/components/ErrorPermission.vue






/* normalize component */

var ErrorPermission_component = normalizeComponent(
  components_ErrorPermissionvue_type_script_lang_ts_,
  ErrorPermissionvue_type_template_id_98e281fe_render,
  ErrorPermissionvue_type_template_id_98e281fe_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var components_ErrorPermission = (ErrorPermission_component.exports);
// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js?{"cacheDirectory":"node_modules/.cache/vue-loader","cacheIdentifier":"42133e48-vue-loader-template"}!./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/ErrorUnknown.vue?vue&type=template&id=13ac6221&
var ErrorUnknownvue_type_template_id_13ac6221_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',[_c('h1',[_vm._v(" An error occurred ")]),_c('pre',[_vm._v(_vm._s(_vm.applicationErrorsString))])])}
var ErrorUnknownvue_type_template_id_13ac6221_staticRenderFns = []


// CONCATENATED MODULE: ./src/presentation/components/ErrorUnknown.vue?vue&type=template&id=13ac6221&

// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/ts-loader??ref--14-3!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/ErrorUnknown.vue?vue&type=script&lang=ts&








/**
 * A component which gets shown if no dedicated handling for the type of
 * error which happened is configured.
 */

var ErrorUnknownvue_type_script_lang_ts_ErrorUnknown =
/*#__PURE__*/
function (_mixins) {
  _inherits(ErrorUnknown, _mixins);

  function ErrorUnknown() {
    _classCallCheck(this, ErrorUnknown);

    return _possibleConstructorReturn(this, _getPrototypeOf(ErrorUnknown).apply(this, arguments));
  }

  _createClass(ErrorUnknown, [{
    key: "applicationErrorsString",
    get: function get() {
      return JSON.stringify(this.rootModule.state.applicationErrors, null, 4);
    }
  }]);

  return ErrorUnknown;
}(Object(vue_class_component_common["mixins"])(presentation_StateMixin));

ErrorUnknownvue_type_script_lang_ts_ErrorUnknown = __decorate([vue_class_component_common_default()({})], ErrorUnknownvue_type_script_lang_ts_ErrorUnknown);
/* harmony default export */ var ErrorUnknownvue_type_script_lang_ts_ = (ErrorUnknownvue_type_script_lang_ts_ErrorUnknown);
// CONCATENATED MODULE: ./src/presentation/components/ErrorUnknown.vue?vue&type=script&lang=ts&
 /* harmony default export */ var components_ErrorUnknownvue_type_script_lang_ts_ = (ErrorUnknownvue_type_script_lang_ts_); 
// CONCATENATED MODULE: ./src/presentation/components/ErrorUnknown.vue





/* normalize component */

var ErrorUnknown_component = normalizeComponent(
  components_ErrorUnknownvue_type_script_lang_ts_,
  ErrorUnknownvue_type_template_id_13ac6221_render,
  ErrorUnknownvue_type_template_id_13ac6221_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var components_ErrorUnknown = (ErrorUnknown_component.exports);
// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js?{"cacheDirectory":"node_modules/.cache/vue-loader","cacheIdentifier":"42133e48-vue-loader-template"}!./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/ErrorUnsupportedDatatype.vue?vue&type=template&id=5dc5073c&
var ErrorUnsupportedDatatypevue_type_template_id_5dc5073c_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"wb-db-unsupported-datatype"},[_c('IconMessageBox',{staticClass:"wb-db-unsupported-datatype__message",attrs:{"type":"notice","inline":true}},[_c('p',{staticClass:"wb-db-unsupported-datatype__head",domProps:{"innerHTML":_vm._s(_vm.$messages.get( _vm.$messages.KEYS.UNSUPPORTED_DATATYPE_ERROR_HEAD, _vm.propertyLabel ))}}),_c('p',{staticClass:"wb-db-unsupported-datatype__body",domProps:{"innerHTML":_vm._s(_vm.$messages.get( _vm.$messages.KEYS.UNSUPPORTED_DATATYPE_ERROR_BODY, _vm.propertyLabel, _vm.dataType ))}})]),_c('BailoutActions',{staticClass:"wb-db-unsupported-datatype__bailout",attrs:{"original-href":_vm.originalHref,"page-title":_vm.pageTitle}})],1)}
var ErrorUnsupportedDatatypevue_type_template_id_5dc5073c_staticRenderFns = []


// CONCATENATED MODULE: ./src/presentation/components/ErrorUnsupportedDatatype.vue?vue&type=template&id=5dc5073c&

// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/ts-loader??ref--14-3!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/ErrorUnsupportedDatatype.vue?vue&type=script&lang=ts&












/**
 * A component used to illustrate a datatype error which happened when
 * the user tried to edit a value which bridge does not support yet.
 */

var ErrorUnsupportedDatatypevue_type_script_lang_ts_ErrorUnsupportedDatatype =
/*#__PURE__*/
function (_mixins) {
  _inherits(ErrorUnsupportedDatatype, _mixins);

  function ErrorUnsupportedDatatype() {
    _classCallCheck(this, ErrorUnsupportedDatatype);

    return _possibleConstructorReturn(this, _getPrototypeOf(ErrorUnsupportedDatatype).apply(this, arguments));
  }

  _createClass(ErrorUnsupportedDatatype, [{
    key: "propertyLabel",
    get: function get() {
      return new components_TermLabel({
        propsData: {
          term: this.rootModule.getters.targetLabel
        }
      }).$mount().$el;
    }
  }, {
    key: "pageTitle",
    get: function get() {
      return this.rootModule.state.pageTitle;
    }
  }, {
    key: "originalHref",
    get: function get() {
      return this.rootModule.state.originalHref;
    }
  }]);

  return ErrorUnsupportedDatatype;
}(Object(vue_class_component_common["mixins"])(presentation_StateMixin));

__decorate([Object(vue_property_decorator["Prop"])({
  required: true
})], ErrorUnsupportedDatatypevue_type_script_lang_ts_ErrorUnsupportedDatatype.prototype, "dataType", void 0);

ErrorUnsupportedDatatypevue_type_script_lang_ts_ErrorUnsupportedDatatype = __decorate([vue_class_component_common_default()({
  components: {
    IconMessageBox: components_IconMessageBox,
    BailoutActions: components_BailoutActions
  }
})], ErrorUnsupportedDatatypevue_type_script_lang_ts_ErrorUnsupportedDatatype);
/* harmony default export */ var ErrorUnsupportedDatatypevue_type_script_lang_ts_ = (ErrorUnsupportedDatatypevue_type_script_lang_ts_ErrorUnsupportedDatatype);
// CONCATENATED MODULE: ./src/presentation/components/ErrorUnsupportedDatatype.vue?vue&type=script&lang=ts&
 /* harmony default export */ var components_ErrorUnsupportedDatatypevue_type_script_lang_ts_ = (ErrorUnsupportedDatatypevue_type_script_lang_ts_); 
// EXTERNAL MODULE: ./src/presentation/components/ErrorUnsupportedDatatype.vue?vue&type=style&index=0&lang=scss&
var ErrorUnsupportedDatatypevue_type_style_index_0_lang_scss_ = __webpack_require__("494d");

// CONCATENATED MODULE: ./src/presentation/components/ErrorUnsupportedDatatype.vue






/* normalize component */

var ErrorUnsupportedDatatype_component = normalizeComponent(
  components_ErrorUnsupportedDatatypevue_type_script_lang_ts_,
  ErrorUnsupportedDatatypevue_type_template_id_5dc5073c_render,
  ErrorUnsupportedDatatypevue_type_template_id_5dc5073c_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var components_ErrorUnsupportedDatatype = (ErrorUnsupportedDatatype_component.exports);
// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js?{"cacheDirectory":"node_modules/.cache/vue-loader","cacheIdentifier":"42133e48-vue-loader-template"}!./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/ErrorDeprecatedStatement.vue?vue&type=template&id=48d66377&
var ErrorDeprecatedStatementvue_type_template_id_48d66377_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"wb-db-deprecated-statement"},[_c('IconMessageBox',{staticClass:"wb-db-deprecated-statement__message",attrs:{"type":"notice","inline":true}},[_c('p',{staticClass:"wb-db-deprecated-statement__head",domProps:{"innerHTML":_vm._s(_vm.$messages.get( _vm.$messages.KEYS.DEPRECATED_STATEMENT_ERROR_HEAD, _vm.propertyLabel ))}}),_c('p',{staticClass:"wb-db-deprecated-statement__body",domProps:{"innerHTML":_vm._s(_vm.$messages.get( _vm.$messages.KEYS.DEPRECATED_STATEMENT_ERROR_BODY, _vm.propertyLabel ))}})]),_c('BailoutActions',{staticClass:"wb-db-deprecated-statement__bailout",attrs:{"original-href":_vm.originalHref,"page-title":_vm.pageTitle}})],1)}
var ErrorDeprecatedStatementvue_type_template_id_48d66377_staticRenderFns = []


// CONCATENATED MODULE: ./src/presentation/components/ErrorDeprecatedStatement.vue?vue&type=template&id=48d66377&

// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/ts-loader??ref--14-3!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/ErrorDeprecatedStatement.vue?vue&type=script&lang=ts&











/**
 * A component used to illustrate a deprecated statement error which happened when
 * the user tried to edit a statement with a deprecated rank.
 */

var ErrorDeprecatedStatementvue_type_script_lang_ts_ErrorDeprecatedStatement =
/*#__PURE__*/
function (_mixins) {
  _inherits(ErrorDeprecatedStatement, _mixins);

  function ErrorDeprecatedStatement() {
    _classCallCheck(this, ErrorDeprecatedStatement);

    return _possibleConstructorReturn(this, _getPrototypeOf(ErrorDeprecatedStatement).apply(this, arguments));
  }

  _createClass(ErrorDeprecatedStatement, [{
    key: "propertyLabel",
    get: function get() {
      return new components_TermLabel({
        propsData: {
          term: this.rootModule.getters.targetLabel
        }
      }).$mount().$el;
    }
  }, {
    key: "pageTitle",
    get: function get() {
      return this.rootModule.state.pageTitle;
    }
  }, {
    key: "originalHref",
    get: function get() {
      return this.rootModule.state.originalHref;
    }
  }]);

  return ErrorDeprecatedStatement;
}(Object(vue_class_component_common["mixins"])(presentation_StateMixin));

ErrorDeprecatedStatementvue_type_script_lang_ts_ErrorDeprecatedStatement = __decorate([vue_class_component_common_default()({
  components: {
    IconMessageBox: components_IconMessageBox,
    BailoutActions: components_BailoutActions
  }
})], ErrorDeprecatedStatementvue_type_script_lang_ts_ErrorDeprecatedStatement);
/* harmony default export */ var ErrorDeprecatedStatementvue_type_script_lang_ts_ = (ErrorDeprecatedStatementvue_type_script_lang_ts_ErrorDeprecatedStatement);
// CONCATENATED MODULE: ./src/presentation/components/ErrorDeprecatedStatement.vue?vue&type=script&lang=ts&
 /* harmony default export */ var components_ErrorDeprecatedStatementvue_type_script_lang_ts_ = (ErrorDeprecatedStatementvue_type_script_lang_ts_); 
// EXTERNAL MODULE: ./src/presentation/components/ErrorDeprecatedStatement.vue?vue&type=style&index=0&lang=scss&
var ErrorDeprecatedStatementvue_type_style_index_0_lang_scss_ = __webpack_require__("1d68");

// CONCATENATED MODULE: ./src/presentation/components/ErrorDeprecatedStatement.vue






/* normalize component */

var ErrorDeprecatedStatement_component = normalizeComponent(
  components_ErrorDeprecatedStatementvue_type_script_lang_ts_,
  ErrorDeprecatedStatementvue_type_template_id_48d66377_render,
  ErrorDeprecatedStatementvue_type_template_id_48d66377_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var components_ErrorDeprecatedStatement = (ErrorDeprecatedStatement_component.exports);
// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js?{"cacheDirectory":"node_modules/.cache/vue-loader","cacheIdentifier":"42133e48-vue-loader-template"}!./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/ErrorAmbiguousStatement.vue?vue&type=template&id=7cd4bf18&
var ErrorAmbiguousStatementvue_type_template_id_7cd4bf18_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"wb-db-ambiguous-statement"},[_c('IconMessageBox',{staticClass:"wb-db-ambiguous-statement__message",attrs:{"type":"notice","inline":true}},[_c('p',{staticClass:"wb-db-ambiguous-statement__head"},[_vm._v(" "+_vm._s(_vm.$messages.get( _vm.$messages.KEYS.AMBIGUOUS_STATEMENT_ERROR_HEAD ))+" ")]),_c('p',{staticClass:"wb-db-ambiguous-statement__body",domProps:{"innerHTML":_vm._s(_vm.$messages.get( _vm.$messages.KEYS.AMBIGUOUS_STATEMENT_ERROR_BODY, _vm.propertyLabel ))}})]),_c('BailoutActions',{attrs:{"original-href":_vm.originalHref,"page-title":_vm.pageTitle}})],1)}
var ErrorAmbiguousStatementvue_type_template_id_7cd4bf18_staticRenderFns = []


// CONCATENATED MODULE: ./src/presentation/components/ErrorAmbiguousStatement.vue?vue&type=template&id=7cd4bf18&

// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/ts-loader??ref--14-3!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/ErrorAmbiguousStatement.vue?vue&type=script&lang=ts&











/**
 * A component used to illustrate an ambiguous statement error which happened when
 * the user tried to edit a statement group with more than one value.
 */

var ErrorAmbiguousStatementvue_type_script_lang_ts_ErrorAmbiguousStatement =
/*#__PURE__*/
function (_mixins) {
  _inherits(ErrorAmbiguousStatement, _mixins);

  function ErrorAmbiguousStatement() {
    _classCallCheck(this, ErrorAmbiguousStatement);

    return _possibleConstructorReturn(this, _getPrototypeOf(ErrorAmbiguousStatement).apply(this, arguments));
  }

  _createClass(ErrorAmbiguousStatement, [{
    key: "propertyLabel",
    get: function get() {
      return new components_TermLabel({
        propsData: {
          term: this.rootModule.getters.targetLabel
        }
      }).$mount().$el;
    }
  }, {
    key: "pageTitle",
    get: function get() {
      return this.rootModule.state.pageTitle;
    }
  }, {
    key: "originalHref",
    get: function get() {
      return this.rootModule.state.originalHref;
    }
  }]);

  return ErrorAmbiguousStatement;
}(Object(vue_class_component_common["mixins"])(presentation_StateMixin));

ErrorAmbiguousStatementvue_type_script_lang_ts_ErrorAmbiguousStatement = __decorate([vue_class_component_common_default()({
  components: {
    IconMessageBox: components_IconMessageBox,
    BailoutActions: components_BailoutActions
  }
})], ErrorAmbiguousStatementvue_type_script_lang_ts_ErrorAmbiguousStatement);
/* harmony default export */ var ErrorAmbiguousStatementvue_type_script_lang_ts_ = (ErrorAmbiguousStatementvue_type_script_lang_ts_ErrorAmbiguousStatement);
// CONCATENATED MODULE: ./src/presentation/components/ErrorAmbiguousStatement.vue?vue&type=script&lang=ts&
 /* harmony default export */ var components_ErrorAmbiguousStatementvue_type_script_lang_ts_ = (ErrorAmbiguousStatementvue_type_script_lang_ts_); 
// EXTERNAL MODULE: ./src/presentation/components/ErrorAmbiguousStatement.vue?vue&type=style&index=0&lang=scss&
var ErrorAmbiguousStatementvue_type_style_index_0_lang_scss_ = __webpack_require__("c73c");

// CONCATENATED MODULE: ./src/presentation/components/ErrorAmbiguousStatement.vue






/* normalize component */

var ErrorAmbiguousStatement_component = normalizeComponent(
  components_ErrorAmbiguousStatementvue_type_script_lang_ts_,
  ErrorAmbiguousStatementvue_type_template_id_7cd4bf18_render,
  ErrorAmbiguousStatementvue_type_template_id_7cd4bf18_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var components_ErrorAmbiguousStatement = (ErrorAmbiguousStatement_component.exports);
// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/ts-loader??ref--14-3!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/ErrorWrapper.vue?vue&type=script&lang=ts&





























var ErrorWrappervue_type_script_lang_ts_ErrorWrapper =
/*#__PURE__*/
function (_mixins) {
  _inherits(ErrorWrapper, _mixins);

  function ErrorWrapper() {
    _classCallCheck(this, ErrorWrapper);

    return _possibleConstructorReturn(this, _getPrototypeOf(ErrorWrapper).apply(this, arguments));
  }

  _createClass(ErrorWrapper, [{
    key: "isPermissionError",
    value: function isPermissionError(error) {
      return Object.values(PageNotEditable).includes(error.type);
    }
  }, {
    key: "applicationErrors",
    get: function get() {
      return this.rootModule.state.applicationErrors;
    }
  }, {
    key: "permissionErrors",
    get: function get() {
      return this.applicationErrors.filter(this.isPermissionError);
    }
  }, {
    key: "unsupportedDatatypeError",
    get: function get() {
      var _iteratorNormalCompletion = true;
      var _didIteratorError = false;
      var _iteratorError = undefined;

      try {
        for (var _iterator = this.applicationErrors[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
          var applicationError = _step.value;

          if (applicationError.type === ErrorTypes.UNSUPPORTED_DATATYPE) {
            return applicationError;
          }
        }
      } catch (err) {
        _didIteratorError = true;
        _iteratorError = err;
      } finally {
        try {
          if (!_iteratorNormalCompletion && _iterator.return != null) {
            _iterator.return();
          }
        } finally {
          if (_didIteratorError) {
            throw _iteratorError;
          }
        }
      }

      return null;
    }
  }, {
    key: "statementValueIsDeprecated",
    get: function get() {
      return this.applicationErrors.some(function (applicationError) {
        return applicationError.type === ErrorTypes.UNSUPPORTED_DEPRECATED_STATEMENT;
      });
    }
  }, {
    key: "statementIsAmbiguous",
    get: function get() {
      return this.applicationErrors.some(function (applicationError) {
        return applicationError.type === ErrorTypes.UNSUPPORTED_AMBIGUOUS_STATEMENT;
      });
    }
  }, {
    key: "unsupportedSnakTypeError",
    get: function get() {
      var _iteratorNormalCompletion2 = true;
      var _didIteratorError2 = false;
      var _iteratorError2 = undefined;

      try {
        for (var _iterator2 = this.applicationErrors[Symbol.iterator](), _step2; !(_iteratorNormalCompletion2 = (_step2 = _iterator2.next()).done); _iteratorNormalCompletion2 = true) {
          var applicationError = _step2.value;

          if (applicationError.type === ErrorTypes.UNSUPPORTED_SNAK_TYPE) {
            return applicationError;
          }
        }
      } catch (err) {
        _didIteratorError2 = true;
        _iteratorError2 = err;
      } finally {
        try {
          if (!_iteratorNormalCompletion2 && _iterator2.return != null) {
            _iterator2.return();
          }
        } finally {
          if (_didIteratorError2) {
            throw _iteratorError2;
          }
        }
      }

      return null;
    }
  }]);

  return ErrorWrapper;
}(Object(vue_class_component_common["mixins"])(presentation_StateMixin));

ErrorWrappervue_type_script_lang_ts_ErrorWrapper = __decorate([vue_class_component_common_default()({
  components: {
    ErrorUnsupportedSnakType: components_ErrorUnsupportedSnakType,
    ErrorPermission: components_ErrorPermission,
    ErrorUnknown: components_ErrorUnknown,
    ErrorUnsupportedDatatype: components_ErrorUnsupportedDatatype,
    ErrorDeprecatedStatement: components_ErrorDeprecatedStatement,
    ErrorAmbiguousStatement: components_ErrorAmbiguousStatement
  }
})], ErrorWrappervue_type_script_lang_ts_ErrorWrapper);
/* harmony default export */ var ErrorWrappervue_type_script_lang_ts_ = (ErrorWrappervue_type_script_lang_ts_ErrorWrapper);
// CONCATENATED MODULE: ./src/presentation/components/ErrorWrapper.vue?vue&type=script&lang=ts&
 /* harmony default export */ var components_ErrorWrappervue_type_script_lang_ts_ = (ErrorWrappervue_type_script_lang_ts_); 
// EXTERNAL MODULE: ./src/presentation/components/ErrorWrapper.vue?vue&type=style&index=0&lang=scss&
var ErrorWrappervue_type_style_index_0_lang_scss_ = __webpack_require__("156b");

// CONCATENATED MODULE: ./src/presentation/components/ErrorWrapper.vue






/* normalize component */

var ErrorWrapper_component = normalizeComponent(
  components_ErrorWrappervue_type_script_lang_ts_,
  ErrorWrappervue_type_template_id_5426d03f_render,
  ErrorWrappervue_type_template_id_5426d03f_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var components_ErrorWrapper = (ErrorWrapper_component.exports);
// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js?{"cacheDirectory":"node_modules/.cache/vue-loader","cacheIdentifier":"42133e48-vue-loader-template"}!./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/ProcessDialogHeader.vue?vue&type=template&id=2d484206&
var ProcessDialogHeadervue_type_template_id_2d484206_render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:"wb-ui-processdialog-header"},[_vm._t("safeAction",[_c('div')]),_c('h1',{staticClass:"wb-ui-processdialog-header__title"},[_vm._t("title",[_c('span')])],2),_vm._t("primaryAction",[_c('div')])],2)}
var ProcessDialogHeadervue_type_template_id_2d484206_staticRenderFns = []


// CONCATENATED MODULE: ./src/presentation/components/ProcessDialogHeader.vue?vue&type=template&id=2d484206&

// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/ts-loader??ref--14-3!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/components/ProcessDialogHeader.vue?vue&type=script&lang=ts&







/**
 * A component to be used as header of dialogs in which users make their
 * way through a process - i.e. there is a primary action "forward" and
 * an alternative second one.
 */

var ProcessDialogHeadervue_type_script_lang_ts_ProcessDialogHeader =
/*#__PURE__*/
function (_Vue) {
  _inherits(ProcessDialogHeader, _Vue);

  function ProcessDialogHeader() {
    _classCallCheck(this, ProcessDialogHeader);

    return _possibleConstructorReturn(this, _getPrototypeOf(ProcessDialogHeader).apply(this, arguments));
  }

  return ProcessDialogHeader;
}(external_commonjs_vue2_commonjs2_vue2_amd_vue2_root_vue2_default.a);

ProcessDialogHeadervue_type_script_lang_ts_ProcessDialogHeader = __decorate([vue_class_component_common_default.a], ProcessDialogHeadervue_type_script_lang_ts_ProcessDialogHeader);
/* harmony default export */ var ProcessDialogHeadervue_type_script_lang_ts_ = (ProcessDialogHeadervue_type_script_lang_ts_ProcessDialogHeader);
// CONCATENATED MODULE: ./src/presentation/components/ProcessDialogHeader.vue?vue&type=script&lang=ts&
 /* harmony default export */ var components_ProcessDialogHeadervue_type_script_lang_ts_ = (ProcessDialogHeadervue_type_script_lang_ts_); 
// EXTERNAL MODULE: ./src/presentation/components/ProcessDialogHeader.vue?vue&type=style&index=0&lang=scss&
var ProcessDialogHeadervue_type_style_index_0_lang_scss_ = __webpack_require__("4637");

// CONCATENATED MODULE: ./src/presentation/components/ProcessDialogHeader.vue






/* normalize component */

var ProcessDialogHeader_component = normalizeComponent(
  components_ProcessDialogHeadervue_type_script_lang_ts_,
  ProcessDialogHeadervue_type_template_id_2d484206_render,
  ProcessDialogHeadervue_type_template_id_2d484206_staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var components_ProcessDialogHeader = (ProcessDialogHeader_component.exports);
// CONCATENATED MODULE: ./node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/ts-loader??ref--14-3!./node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/vue-loader/lib??vue-loader-options!./src/presentation/App.vue?vue&type=script&lang=ts&


















var Appvue_type_script_lang_ts_App =
/*#__PURE__*/
function (_mixins) {
  _inherits(App, _mixins);

  function App() {
    _classCallCheck(this, App);

    return _possibleConstructorReturn(this, _getPrototypeOf(App).apply(this, arguments));
  }

  _createClass(App, [{
    key: "saveAndClose",
    value: function saveAndClose() {
      var _this = this;

      this.rootModule.dispatch('saveBridge').then(function () {
        _this.$emit(src_events.onSaved);
      }).catch(function (_error) {// TODO store already sets application into error state. Do we need to do anything else?
      });
    }
  }, {
    key: "cancel",
    value: function cancel() {
      this.$emit(src_events.onCancel);
    }
  }, {
    key: "title",
    get: function get() {
      return this.$messages.get(this.$messages.KEYS.BRIDGE_DIALOG_TITLE, new components_TermLabel({
        propsData: {
          term: this.rootModule.getters.targetLabel
        }
      }).$mount().$el);
    }
  }, {
    key: "isInitializing",
    get: function get() {
      return this.rootModule.getters.applicationStatus === definitions_ApplicationStatus.INITIALIZING;
    }
  }, {
    key: "hasError",
    get: function get() {
      return this.rootModule.getters.applicationStatus === definitions_ApplicationStatus.ERROR;
    }
  }, {
    key: "canSave",
    get: function get() {
      return this.rootModule.getters.canSave;
    }
  }, {
    key: "publishOrSave",
    get: function get() {
      return this.$bridgeConfig.usePublish ? this.$messages.KEYS.PUBLISH_CHANGES : this.$messages.KEYS.SAVE_CHANGES;
    }
  }]);

  return App;
}(Object(vue_class_component_common["mixins"])(presentation_StateMixin));

Appvue_type_script_lang_ts_App = __decorate([Object(vue_property_decorator["Component"])({
  components: {
    DataBridge: components_DataBridge,
    ErrorWrapper: components_ErrorWrapper,
    Initializing: components_Initializing,
    EventEmittingButton: components_EventEmittingButton,
    ProcessDialogHeader: components_ProcessDialogHeader
  }
})], Appvue_type_script_lang_ts_App);
/* harmony default export */ var Appvue_type_script_lang_ts_ = (Appvue_type_script_lang_ts_App);
// CONCATENATED MODULE: ./src/presentation/App.vue?vue&type=script&lang=ts&
 /* harmony default export */ var presentation_Appvue_type_script_lang_ts_ = (Appvue_type_script_lang_ts_); 
// EXTERNAL MODULE: ./src/presentation/App.vue?vue&type=style&index=0&lang=scss&
var Appvue_type_style_index_0_lang_scss_ = __webpack_require__("ad4b");

// CONCATENATED MODULE: ./src/presentation/App.vue






/* normalize component */

var App_component = normalizeComponent(
  presentation_Appvue_type_script_lang_ts_,
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* harmony default export */ var presentation_App = (App_component.exports);
// EXTERNAL MODULE: ./node_modules/events/events.js
var events_events = __webpack_require__("faa1");

// CONCATENATED MODULE: ./src/events/repeater.ts



function repeater(app, emitter, eventNames) {
  eventNames.forEach(function (value) {
    app.$on(value, function () {
      for (var _len = arguments.length, payload = new Array(_len), _key = 0; _key < _len; _key++) {
        payload[_key] = arguments[_key];
      }

      emitter.emit.apply(emitter, [value].concat(payload));
    });
  });
}
// CONCATENATED MODULE: ./src/presentation/directives/inlanguage.ts
/* harmony default export */ var inlanguage = (function (resolver) {
  return function (el, binding, _vnode) {
    if (!binding.value) {
      return;
    }

    var language = resolver.resolve(binding.value);
    el.setAttribute('lang', language.code);
    el.setAttribute('dir', language.directionality);
  };
});
// CONCATENATED MODULE: ./src/definitions/MessageKeys.ts
var MessageKeys;

(function (MessageKeys) {
  MessageKeys["BRIDGE_DIALOG_TITLE"] = "wikibase-client-data-bridge-dialog-title";
  MessageKeys["SAVE_CHANGES"] = "savechanges";
  MessageKeys["PUBLISH_CHANGES"] = "publishchanges";
  MessageKeys["CANCEL"] = "cancel";
  MessageKeys["EDIT_DECISION_HEADING"] = "wikibase-client-data-bridge-edit-decision-heading";
  MessageKeys["EDIT_DECISION_REPLACE_LABEL"] = "wikibase-client-data-bridge-edit-decision-replace-label";
  MessageKeys["EDIT_DECISION_REPLACE_DESCRIPTION"] = "wikibase-client-data-bridge-edit-decision-replace-description";
  MessageKeys["EDIT_DECISION_UPDATE_LABEL"] = "wikibase-client-data-bridge-edit-decision-update-label";
  MessageKeys["EDIT_DECISION_UPDATE_DESCRIPTION"] = "wikibase-client-data-bridge-edit-decision-update-description";
  MessageKeys["REFERENCES_HEADING"] = "wikibase-client-data-bridge-references-heading";
  MessageKeys["REFERENCE_SNAK_SEPARATOR"] = "wikibase-client-data-bridge-reference-snak-separator";
  MessageKeys["PERMISSIONS_HEADING"] = "wikibase-client-data-bridge-permissions-error";
  MessageKeys["PERMISSIONS_MORE_INFO"] = "wikibase-client-data-bridge-permissions-error-info";
  MessageKeys["PERMISSIONS_BLOCKED_ON_CLIENT_HEADING"] = "wikibase-client-data-bridge-blocked-on-client-head";
  MessageKeys["PERMISSIONS_BLOCKED_ON_CLIENT_BODY"] = "wikibase-client-data-bridge-blocked-on-client-body";
  MessageKeys["PERMISSIONS_BLOCKED_ON_REPO_HEADING"] = "wikibase-client-data-bridge-blocked-on-repo-head";
  MessageKeys["PERMISSIONS_BLOCKED_ON_REPO_BODY"] = "wikibase-client-data-bridge-blocked-on-repo-body";
  MessageKeys["PERMISSIONS_PAGE_CASCADE_PROTECTED_HEADING"] = "wikibase-client-data-bridge-cascadeprotected-on-client-head";
  MessageKeys["PERMISSIONS_PAGE_CASCADE_PROTECTED_BODY"] = "wikibase-client-data-bridge-cascadeprotected-on-client-body";
  MessageKeys["PERMISSIONS_PROTECTED_HEADING"] = "wikibase-client-data-bridge-protected-on-repo-head";
  MessageKeys["PERMISSIONS_PROTECTED_BODY"] = "wikibase-client-data-bridge-protected-on-repo-body";
  MessageKeys["PERMISSIONS_SEMI_PROTECTED_HEADING"] = "wikibase-client-data-bridge-semiprotected-on-repo-head";
  MessageKeys["PERMISSIONS_SEMI_PROTECTED_BODY"] = "wikibase-client-data-bridge-semiprotected-on-repo-body";
  MessageKeys["PERMISSIONS_CASCADE_PROTECTED_HEADING"] = "wikibase-client-data-bridge-cascadeprotected-on-repo-head";
  MessageKeys["PERMISSIONS_CASCADE_PROTECTED_BODY"] = "wikibase-client-data-bridge-cascadeprotected-on-repo-body";
  MessageKeys["PERMISSIONS_ERROR_UNKNOWN_HEADING"] = "wikibase-client-data-bridge-permissions-error-unknown-head";
  MessageKeys["PERMISSIONS_ERROR_UNKNOWN_BODY"] = "wikibase-client-data-bridge-permissions-error-unknown-body";
  MessageKeys["BAILOUT_HEADING"] = "wikibase-client-data-bridge-bailout-heading";
  MessageKeys["BAILOUT_SUGGESTION_GO_TO_REPO"] = "wikibase-client-data-bridge-bailout-suggestion-go-to-repo";
  MessageKeys["BAILOUT_SUGGESTION_GO_TO_REPO_BUTTON"] = "wikibase-client-data-bridge-bailout-suggestion-go-to-repo-button";
  MessageKeys["BAILOUT_SUGGESTION_EDIT_ARTICLE"] = "wikibase-client-data-bridge-bailout-suggestion-edit-article";
  MessageKeys["UNSUPPORTED_DATATYPE_ERROR_HEAD"] = "wikibase-client-data-bridge-unsupported-datatype-error-head";
  MessageKeys["UNSUPPORTED_DATATYPE_ERROR_BODY"] = "wikibase-client-data-bridge-unsupported-datatype-error-body";
  MessageKeys["DEPRECATED_STATEMENT_ERROR_HEAD"] = "wikibase-client-data-bridge-deprecated-statement-error-head";
  MessageKeys["DEPRECATED_STATEMENT_ERROR_BODY"] = "wikibase-client-data-bridge-deprecated-statement-error-body";
  MessageKeys["AMBIGUOUS_STATEMENT_ERROR_HEAD"] = "wikibase-client-data-bridge-ambiguous-statement-error-head";
  MessageKeys["AMBIGUOUS_STATEMENT_ERROR_BODY"] = "wikibase-client-data-bridge-ambiguous-statement-error-body";
  MessageKeys["SOMEVALUE_ERROR_HEAD"] = "wikibase-client-data-bridge-somevalue-error-head";
  MessageKeys["SOMEVALUE_ERROR_BODY"] = "wikibase-client-data-bridge-somevalue-error-body";
  MessageKeys["NOVALUE_ERROR_HEAD"] = "wikibase-client-data-bridge-novalue-error-head";
  MessageKeys["NOVALUE_ERROR_BODY"] = "wikibase-client-data-bridge-novalue-error-body";
})(MessageKeys || (MessageKeys = {}));

/* harmony default export */ var definitions_MessageKeys = (MessageKeys);
// CONCATENATED MODULE: ./src/presentation/plugins/MessagesPlugin/Messages.ts




/**
 * Usage (assuming this has been registered as a Vue plugin):
 *
 * `this.$messages.get( this.$messages.KEYS.BRIDGE_DIALOG_TITLE )`
 */

var Messages_Messages =
/*#__PURE__*/
function () {
  function Messages(messagesRepository) {
    _classCallCheck(this, Messages);

    this.KEYS = definitions_MessageKeys;
    this.messagesRepository = messagesRepository;
  }

  _createClass(Messages, [{
    key: "get",
    value: function get(messageKey) {
      var _this$messagesReposit;

      for (var _len = arguments.length, params = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
        params[_key - 1] = arguments[_key];
      }

      return (_this$messagesReposit = this.messagesRepository).get.apply(_this$messagesReposit, [messageKey].concat(params));
    }
  }]);

  return Messages;
}();


// CONCATENATED MODULE: ./src/presentation/plugins/MessagesPlugin/index.ts

function MessagesPlugin(Vue, messages) {
  Vue.prototype.$messages = new Messages_Messages(messages);
}
// CONCATENATED MODULE: ./src/presentation/plugins/RepoRouterPlugin/index.ts
function RepoRouterPlugin(Vue, repoRouter) {
  Vue.prototype.$repoRouter = repoRouter;
}
// CONCATENATED MODULE: ./src/presentation/plugins/ClientRouterPlugin/index.ts
function ClientRouterPlugin(Vue, clientRouter) {
  Vue.prototype.$clientRouter = clientRouter;
}
// CONCATENATED MODULE: ./src/presentation/extendVueEnvironment.ts






function extendVueEnvironment(languageInfoRepo, messageRepo, bridgeConfigOptions, repoRouter, clientRouter) {
  external_commonjs_vue2_commonjs2_vue2_amd_vue2_root_vue2_default.a.directive('inlanguage', inlanguage(languageInfoRepo));
  external_commonjs_vue2_commonjs2_vue2_amd_vue2_root_vue2_default.a.use(MessagesPlugin, messageRepo);
  external_commonjs_vue2_commonjs2_vue2_amd_vue2_root_vue2_default.a.use(BridgeConfigPlugin, bridgeConfigOptions);
  external_commonjs_vue2_commonjs2_vue2_amd_vue2_root_vue2_default.a.use(RepoRouterPlugin, repoRouter);
  external_commonjs_vue2_commonjs2_vue2_amd_vue2_root_vue2_default.a.use(ClientRouterPlugin, clientRouter);
}
// EXTERNAL MODULE: ./node_modules/core-js/modules/es.set.js
var es_set = __webpack_require__("6062");

// EXTERNAL MODULE: ./node_modules/core-js/modules/es.map.js
var es_map = __webpack_require__("4ec9");

// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/isNativeFunction.js



function _isNativeFunction(fn) {
  return Function.toString.call(fn).indexOf("[native code]") !== -1;
}
// EXTERNAL MODULE: ./node_modules/core-js/modules/es.reflect.construct.js
var es_reflect_construct = __webpack_require__("4ae1");

// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/construct.js





function isNativeReflectConstruct() {
  if (typeof Reflect === "undefined" || !Reflect.construct) return false;
  if (Reflect.construct.sham) return false;
  if (typeof Proxy === "function") return true;

  try {
    Date.prototype.toString.call(Reflect.construct(Date, [], function () {}));
    return true;
  } catch (e) {
    return false;
  }
}

function construct_construct(Parent, args, Class) {
  if (isNativeReflectConstruct()) {
    construct_construct = Reflect.construct;
  } else {
    construct_construct = function _construct(Parent, args, Class) {
      var a = [null];
      a.push.apply(a, args);
      var Constructor = Function.bind.apply(Parent, a);
      var instance = new Constructor();
      if (Class) _setPrototypeOf(instance, Class.prototype);
      return instance;
    };
  }

  return construct_construct.apply(null, arguments);
}
// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/wrapNativeSuper.js









function wrapNativeSuper_wrapNativeSuper(Class) {
  var _cache = typeof Map === "function" ? new Map() : undefined;

  wrapNativeSuper_wrapNativeSuper = function _wrapNativeSuper(Class) {
    if (Class === null || !_isNativeFunction(Class)) return Class;

    if (typeof Class !== "function") {
      throw new TypeError("Super expression must either be null or a function");
    }

    if (typeof _cache !== "undefined") {
      if (_cache.has(Class)) return _cache.get(Class);

      _cache.set(Class, Wrapper);
    }

    function Wrapper() {
      return construct_construct(Class, arguments, _getPrototypeOf(this).constructor);
    }

    Wrapper.prototype = Object.create(Class.prototype, {
      constructor: {
        value: Wrapper,
        enumerable: false,
        writable: true,
        configurable: true
      }
    });
    return _setPrototypeOf(Wrapper, Class);
  };

  return wrapNativeSuper_wrapNativeSuper(Class);
}
// CONCATENATED MODULE: ./src/data-access/error/ApiErrors.ts






var ApiErrors_ApiErrors =
/*#__PURE__*/
function (_Error) {
  _inherits(ApiErrors, _Error);

  function ApiErrors(errors) {
    var _this;

    _classCallCheck(this, ApiErrors);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(ApiErrors).call(this, errors[0].code));
    _this.errors = errors;
    return _this;
  }

  return ApiErrors;
}(wrapNativeSuper_wrapNativeSuper(Error));


// CONCATENATED MODULE: ./src/data-access/error/TechnicalProblem.ts






var TechnicalProblem_TechnicalProblem =
/*#__PURE__*/
function (_Error) {
  _inherits(TechnicalProblem, _Error);

  function TechnicalProblem() {
    _classCallCheck(this, TechnicalProblem);

    return _possibleConstructorReturn(this, _getPrototypeOf(TechnicalProblem).apply(this, arguments));
  }

  return TechnicalProblem;
}(wrapNativeSuper_wrapNativeSuper(Error));


// CONCATENATED MODULE: ./src/data-access/error/JQueryTechnicalError.ts






var JQueryTechnicalError_JQueryTechnicalError =
/*#__PURE__*/
function (_Error) {
  _inherits(JQueryTechnicalError, _Error);

  function JQueryTechnicalError(error) {
    var _this;

    _classCallCheck(this, JQueryTechnicalError);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(JQueryTechnicalError).call(this, 'request error'));
    _this.error = error;
    return _this;
  }

  return JQueryTechnicalError;
}(wrapNativeSuper_wrapNativeSuper(Error));


// CONCATENATED MODULE: ./src/data-access/ApiCore.ts













/**
 * Basic implementation of Api using MwApi.
 * This turns set parameters into arrays
 * (the other parameter types MwApi can handle itself)
 * and maps rejections to appropriate error classes.
 * Other Api implementations often wrap this one.
 */

var ApiCore_ApiCore =
/*#__PURE__*/
function () {
  function ApiCore(api) {
    _classCallCheck(this, ApiCore);

    this.api = api;
  }

  _createClass(ApiCore, [{
    key: "get",
    value: function get(params) {
      for (var _i = 0, _Object$keys = Object.keys(params); _i < _Object$keys.length; _i++) {
        var name = _Object$keys[_i];
        var param = params[name];

        if (param instanceof Set) {
          params[name] = _toConsumableArray(param);
        }
      }

      return Promise.resolve( // turn jQuery promise into native one
      this.api.get(params).catch(this.mwApiRejectionToError));
    }
    /**
     * Translate a rejection from mw.Api into a single error.
     * Since mw.Api uses jQuery Deferreds, there can be up to four arguments.
     * (See mw.Api’s ajax method for the code generating the rejections.)
     */

  }, {
    key: "mwApiRejectionToError",
    value: function mwApiRejectionToError(code, arg2, _arg3, _arg4) {
      switch (code) {
        case 'http':
          {
            // jQuery AJAX failure
            var detail = arg2; // arg3 and arg4 are not defined

            throw new JQueryTechnicalError_JQueryTechnicalError(detail.xhr);
          }

        case 'ok-but-empty':
          {
            // HTTP 200, empty response body, should never happen™
            var message = arg2; // arg3 is result, arg4 is jqXHR

            throw new TechnicalProblem_TechnicalProblem(message);
          }

        default:
          {
            // API error(s)
            var result = arg2; // arg3 is also result, arg4 is jqXHR

            if (result.error) {
              throw new ApiErrors_ApiErrors([result.error]);
            } else if (result.errors) {
              throw new ApiErrors_ApiErrors(result.errors);
            } else {
              throw new TechnicalProblem_TechnicalProblem('mw.Api rejected but result does not contain error(s)');
            }
          }
      }
    }
  }]);

  return ApiCore;
}();


// CONCATENATED MODULE: ./src/datamodel/Entity.ts


var Entity_Entity = function Entity(id, statements) {
  _classCallCheck(this, Entity);

  this.id = id;
  this.statements = statements;
};


// CONCATENATED MODULE: ./src/data-access/error/EntityNotFound.ts






var EntityNotFound_EntityNotFound =
/*#__PURE__*/
function (_Error) {
  _inherits(EntityNotFound, _Error);

  function EntityNotFound() {
    _classCallCheck(this, EntityNotFound);

    return _possibleConstructorReturn(this, _getPrototypeOf(EntityNotFound).apply(this, arguments));
  }

  return EntityNotFound;
}(wrapNativeSuper_wrapNativeSuper(Error));


// EXTERNAL MODULE: ./node_modules/http-status-codes/index.js
var http_status_codes = __webpack_require__("0828");
var http_status_codes_default = /*#__PURE__*/__webpack_require__.n(http_status_codes);

// CONCATENATED MODULE: ./src/data-access/ApiWritingRepository.ts












var ApiWritingRepository_ApiWritingRepository =
/*#__PURE__*/
function () {
  function ApiWritingRepository(api, username, tags) {
    _classCallCheck(this, ApiWritingRepository);

    this.api = api;
    this.username = username || undefined;
    this.tags = tags || undefined;
  }

  _createClass(ApiWritingRepository, [{
    key: "saveEntity",
    value: function saveEntity(revision) {
      return Promise.resolve(this.api.postWithEditToken({
        action: 'wbeditentity',
        id: revision.entity.id,
        baserevid: revision.revisionId,
        data: JSON.stringify({
          claims: revision.entity.statements
        }),
        assertuser: this.username,
        tags: this.tags
      })).then(function (response) {
        if (_typeof(response) !== 'object') {
          throw new TechnicalProblem_TechnicalProblem('unknown response type.');
        }

        if (ApiWritingRepository.isError(response)) {
          throw new TechnicalProblem_TechnicalProblem(response.error.code);
        }

        return new EntityRevision_EntityRevision(new Entity_Entity(response.entity.id, response.entity.claims), response.entity.lastrevid);
      }, function (error) {
        if (error.status && error.status === http_status_codes_default.a.NOT_FOUND) {
          throw new EntityNotFound_EntityNotFound('The given api page does not exist.');
        }

        throw new JQueryTechnicalError_JQueryTechnicalError(error);
      });
    }
  }], [{
    key: "isError",
    value: function isError(response) {
      return !!response.error;
    }
  }]);

  return ApiWritingRepository;
}();


// EXTERNAL MODULE: ./node_modules/core-js/modules/es.object.get-own-property-names.js
var es_object_get_own_property_names = __webpack_require__("7039");

// CONCATENATED MODULE: ./src/data-access/BatchingApi.ts















/**
 * A service to batch API requests.
 * Compatible requests made within the same call stack
 * (i.e., synchronously) are merged into one.
 *
 * Usage example:
 *
 * ```
 * api.get( {
 *     action: 'query',
 *     prop: new Set( [ 'info' ] ),
 *     meta: new Set( [ 'siteinfo' ] ),
 *     titles: new Set( [ 'Help:Contents', 'Project:Main Page' ] ),
 *     redirects: true,
 *     inprop: new Set( [ 'url' ] ),
 *     siprop: new Set( [ 'usergroups' ] ),
 *     formatversion: 2,
 * } );
 * ```
 *
 * Whether two requests are compatible depends on their parameters.
 * Parameters that only occur in one of two requests have no effect
 * on compatibility and are always added to the resulting request.
 * If the same parameter name occurs in both requests,
 * the result depends on the type of the value:
 *
 * - If the values on both sides are sets (instances of Set),
 *   the sets are merged into one for the resulting request,
 *   and sent to the underlying API as an array of values.
 *   (Integer values are converted to strings.)
 * - If the value on either side is an array (instance of Array),
 *   the requests are incompatible.
 *   This only makes sense for parameters where duplicates are significant;
 *   for most parameters, you should use sets instead.
 * - If the values on both sides are booleans,
 *   the requests are incompatible if the values are different.
 *   Otherwise, they are sent unmodified to the underlying API.
 * - Otherwise, the values on both sides are converted to strings.
 *   If the resulting strings are different, the requests are incompatible;
 *   otherwise, they are added to the resulting request.
 *   (The string conversion ensures that, for example,
 *   formatversion: 2 and formatversion: '2' are compatible.)
 *
 * Callers should specify all the parameters that they rely on,
 * even where this means specifying the default value, so that
 * conflicts with requests specifying non-default values can be detected.
 * Using formatversion: 2 is strongly encouraged.
 */
var BatchingApi_BatchingApi =
/*#__PURE__*/
function () {
  /**
   * Create a new service for requests to the given (local or foreign) API.
   * @param api Underlying implementation responsible for
   * making the merged API calls (usually an {@link ApiCore}).
   */
  function BatchingApi(api) {
    _classCallCheck(this, BatchingApi);

    this.api = api;
    this.requests = [];
  }

  _createClass(BatchingApi, [{
    key: "get",
    value: function get(params) {
      var _this = this;

      var _iteratorNormalCompletion = true;
      var _didIteratorError = false;
      var _iteratorError = undefined;

      try {
        for (var _iterator = this.requests[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
          var request = _step.value;
          var mergedParams = this.mergeParams(request.params, params);

          if (mergedParams !== false) {
            // add to existing request
            request.params = mergedParams;
            return request.promise;
          }
        } // no matching existing request, add new request

      } catch (err) {
        _didIteratorError = true;
        _iteratorError = err;
      } finally {
        try {
          if (!_iteratorNormalCompletion && _iterator.return != null) {
            _iterator.return();
          }
        } finally {
          if (_didIteratorError) {
            throw _iteratorError;
          }
        }
      }

      var resolve = undefined,
          reject = undefined;
      var promise = new Promise(function (resolve_, reject_) {
        resolve = resolve_;
        reject = reject_;
      });
      this.requests.push({
        params: params,
        promise: promise,
        resolve: resolve,
        reject: reject
      });

      if (this.requests.length === 1) {
        Promise.resolve().then(function () {
          return _this.flush();
        });
      }

      return promise;
    }
    /**
     * Flush the queue of pending requests,
     * sending them all to the underlying API
     * and resolving or rejecting their promises as needed.
     */

  }, {
    key: "flush",
    value: function flush() {
      var _iteratorNormalCompletion2 = true;
      var _didIteratorError2 = false;
      var _iteratorError2 = undefined;

      try {
        for (var _iterator2 = this.requests[Symbol.iterator](), _step2; !(_iteratorNormalCompletion2 = (_step2 = _iterator2.next()).done); _iteratorNormalCompletion2 = true) {
          var _step2$value = _step2.value,
              params = _step2$value.params,
              resolve = _step2$value.resolve,
              reject = _step2$value.reject;
          this.api.get(params).then(resolve, reject);
        }
      } catch (err) {
        _didIteratorError2 = true;
        _iteratorError2 = err;
      } finally {
        try {
          if (!_iteratorNormalCompletion2 && _iterator2.return != null) {
            _iterator2.return();
          }
        } finally {
          if (_didIteratorError2) {
            throw _iteratorError2;
          }
        }
      }

      this.requests.length = 0; // truncate
    }
    /**
     * Merge two sets of parameters into one,
     * or return `false` if they are incompatible.
     * The original objects are never modified.
     */

  }, {
    key: "mergeParams",
    value: function mergeParams(params1, params2) {
      var paramNames = new Set([].concat(_toConsumableArray(Object.getOwnPropertyNames(params1)), _toConsumableArray(Object.getOwnPropertyNames(params2))));
      var mergedParams = {};
      var _iteratorNormalCompletion3 = true;
      var _didIteratorError3 = false;
      var _iteratorError3 = undefined;

      try {
        for (var _iterator3 = paramNames[Symbol.iterator](), _step3; !(_iteratorNormalCompletion3 = (_step3 = _iterator3.next()).done); _iteratorNormalCompletion3 = true) {
          var paramName = _step3.value;
          var inParams1 = Object.prototype.hasOwnProperty.call(params1, paramName),
              inParams2 = Object.prototype.hasOwnProperty.call(params2, paramName);

          if (inParams1 !== inParams2) {
            mergedParams[paramName] = (inParams1 ? params1 : params2)[paramName];
            continue;
          }

          var value1 = params1[paramName],
              value2 = params2[paramName];

          if (value1 instanceof Set && value2 instanceof Set) {
            var mergedSet = new Set();
            mergedParams[paramName] = mergedSet;

            for (var _i = 0, _arr = [value1, value2]; _i < _arr.length; _i++) {
              var valueSet = _arr[_i];
              var _iteratorNormalCompletion4 = true;
              var _didIteratorError4 = false;
              var _iteratorError4 = undefined;

              try {
                for (var _iterator4 = valueSet[Symbol.iterator](), _step4; !(_iteratorNormalCompletion4 = (_step4 = _iterator4.next()).done); _iteratorNormalCompletion4 = true) {
                  var member = _step4.value;
                  mergedSet.add(String(member));
                }
              } catch (err) {
                _didIteratorError4 = true;
                _iteratorError4 = err;
              } finally {
                try {
                  if (!_iteratorNormalCompletion4 && _iterator4.return != null) {
                    _iterator4.return();
                  }
                } finally {
                  if (_didIteratorError4) {
                    throw _iteratorError4;
                  }
                }
              }
            }

            continue;
          }

          if (value1 instanceof Array || value2 instanceof Array) {
            return false;
          }

          if (typeof value1 === 'boolean' && typeof value2 === 'boolean') {
            if (value1 === value2) {
              mergedParams[paramName] = value1;
              continue;
            } else {
              return false;
            }
          }

          var string1 = String(value1),
              string2 = String(value2);

          if (string1 === string2) {
            mergedParams[paramName] = string1;
            continue;
          } else {
            return false;
          }
        }
      } catch (err) {
        _didIteratorError3 = true;
        _iteratorError3 = err;
      } finally {
        try {
          if (!_iteratorNormalCompletion3 && _iterator3.return != null) {
            _iterator3.return();
          }
        } finally {
          if (_didIteratorError3) {
            throw _iteratorError3;
          }
        }
      }

      return mergedParams;
    }
  }]);

  return BatchingApi;
}();


// CONCATENATED MODULE: ./src/data-access/ClientRouter.ts



var ClientRouter_ClientRouter =
/*#__PURE__*/
function () {
  function ClientRouter(getUrl) {
    _classCallCheck(this, ClientRouter);

    this.getUrl = getUrl;
  }

  _createClass(ClientRouter, [{
    key: "getPageUrl",
    value: function getPageUrl(title, params) {
      return this.getUrl(title, params);
    }
  }]);

  return ClientRouter;
}();


// CONCATENATED MODULE: ./src/services/ServiceContainer.ts



var ServiceContainer_ServiceContainer =
/*#__PURE__*/
function () {
  function ServiceContainer() {
    _classCallCheck(this, ServiceContainer);

    this.services = {};
  }

  _createClass(ServiceContainer, [{
    key: "set",
    value: function set(key, service) {
      this.services[key] = service;
    }
  }, {
    key: "get",
    value: function get(key) {
      if (this.services[key] === undefined) {
        throw new Error("".concat(key, " is undefined"));
      }

      return this.services[key];
    }
  }]);

  return ServiceContainer;
}();


// EXTERNAL MODULE: ./node_modules/core-js/modules/es.string.replace.js
var es_string_replace = __webpack_require__("5319");

// CONCATENATED MODULE: ./src/data-access/SpecialPageReadingEntityRepository.ts















var SpecialPageReadingEntityRepository_SpecialPageReadingEntityRepository =
/*#__PURE__*/
function () {
  function SpecialPageReadingEntityRepository($, specialEntityDataUrl) {
    _classCallCheck(this, SpecialPageReadingEntityRepository);

    this.$ = $;
    this.specialEntityDataUrl = this.trimTrailingSlashes(specialEntityDataUrl);
  }

  _createClass(SpecialPageReadingEntityRepository, [{
    key: "getEntity",
    value: function getEntity(entityId, _rev) {
      var _this = this;

      return Promise.resolve(this.$.get(this.buildRequestUrl(entityId))).then(function (data) {
        if (!_this.isWellFormedResponse(data)) {
          throw new TechnicalProblem_TechnicalProblem('Result not well formed.');
        }

        if (!data.entities[entityId]) {
          throw new EntityNotFound_EntityNotFound('Result does not contain relevant entity.');
        }

        return new EntityRevision_EntityRevision(new Entity_Entity(entityId, data.entities[entityId].claims), data.entities[entityId].lastrevid);
      }, function (error) {
        if (error.status && error.status === http_status_codes_default.a.NOT_FOUND) {
          throw new EntityNotFound_EntityNotFound('Entity flagged missing in response.');
        }

        throw new JQueryTechnicalError_JQueryTechnicalError(error);
      });
    }
  }, {
    key: "isWellFormedResponse",
    value: function isWellFormedResponse(data) {
      return _typeof(data) === 'object' && data !== null && 'entities' in data;
    }
  }, {
    key: "buildRequestUrl",
    value: function buildRequestUrl(entityId) {
      return "".concat(this.specialEntityDataUrl, "/").concat(entityId, ".json");
    }
  }, {
    key: "trimTrailingSlashes",
    value: function trimTrailingSlashes(string) {
      return string.replace(/\/$/, '');
    }
  }]);

  return SpecialPageReadingEntityRepository;
}();


// CONCATENATED MODULE: ./src/data-access/MwLanguageInfoRepository.ts



var MwLanguageInfoRepository_MwLanguageInfoRepository =
/*#__PURE__*/
function () {
  function MwLanguageInfoRepository(mwLanguage, ulsDirectionality) {
    _classCallCheck(this, MwLanguageInfoRepository);

    this.directionalityResolver = ulsDirectionality.getDir;
    this.bcp47Resolver = mwLanguage.bcp47;
  }

  _createClass(MwLanguageInfoRepository, [{
    key: "resolve",
    value: function resolve(languageCode) {
      return {
        code: this.bcp47Resolver(languageCode),
        directionality: this.directionalityResolver(languageCode)
      };
    }
  }]);

  return MwLanguageInfoRepository;
}();


// CONCATENATED MODULE: ./src/data-access/RepoRouter.ts





var RepoRouter_RepoRouter =
/*#__PURE__*/
function () {
  function RepoRouter(repoConfig, wikiUrlencode, querySerializer) {
    _classCallCheck(this, RepoRouter);

    this.repoConfig = repoConfig;
    this.wikiUrlencode = wikiUrlencode;
    this.querySerializer = querySerializer;
  }

  _createClass(RepoRouter, [{
    key: "getPageUrl",
    value: function getPageUrl(title, params) {
      var url, query;

      if (params) {
        query = this.querySerializer(params);
      }

      if (query) {
        url = this.wikiScript() + '?title=' + this.wikiUrlencode(title) + '&' + query;
      } else {
        url = this.repoConfig.url + this.repoConfig.articlePath.replace('$1', this.wikiUrlencode(title).replace(/\$/g, '$$$$'));
      }

      return url;
    }
  }, {
    key: "wikiScript",
    value: function wikiScript() {
      var script = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'index.php';
      return this.repoConfig.url + this.repoConfig.scriptPath + '/' + script;
    }
  }]);

  return RepoRouter;
}();


// CONCATENATED MODULE: ./src/data-access/MwMessagesRepository.ts




var MwMessagesRepository_MwMessagesRepository =
/*#__PURE__*/
function () {
  function MwMessagesRepository(mwMessages) {
    _classCallCheck(this, MwMessagesRepository);

    this.mwMessages = mwMessages;
  }

  _createClass(MwMessagesRepository, [{
    key: "get",
    value: function get(messageKey) {
      for (var _len = arguments.length, params = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
        params[_key - 1] = arguments[_key];
      }

      return this.mwMessages.apply(this, [messageKey].concat(params)).parse();
    }
  }]);

  return MwMessagesRepository;
}();


// CONCATENATED MODULE: ./src/data-access/ApiRepoConfigRepository.ts












var ApiRepoConfigRepository_ApiRepoConfigRepository =
/*#__PURE__*/
function () {
  function ApiRepoConfigRepository(api) {
    _classCallCheck(this, ApiRepoConfigRepository);

    this.api = api;
  }

  _createClass(ApiRepoConfigRepository, [{
    key: "getRepoConfiguration",
    value: function () {
      var _getRepoConfiguration = _asyncToGenerator(
      /*#__PURE__*/
      regeneratorRuntime.mark(function _callee() {
        var response;
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _context.next = 2;
                return this.api.get({
                  action: 'query',
                  meta: new Set(['wbdatabridgeconfig']),
                  formatversion: 2,
                  errorformat: 'raw'
                });

              case 2:
                response = _context.sent;

                if (!this.responseWarnsAboutDisabledRepoConfiguration(response)) {
                  _context.next = 5;
                  break;
                }

                throw new TechnicalProblem_TechnicalProblem('Result indicates repo API is disabled (see dataBridgeEnabled).');

              case 5:
                if (this.isWellFormedResponse(response.query)) {
                  _context.next = 7;
                  break;
                }

                throw new TechnicalProblem_TechnicalProblem('Result not well formed.');

              case 7:
                return _context.abrupt("return", response.query.wbdatabridgeconfig);

              case 8:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this);
      }));

      function getRepoConfiguration() {
        return _getRepoConfiguration.apply(this, arguments);
      }

      return getRepoConfiguration;
    }() // eslint-disable-next-line @typescript-eslint/no-explicit-any

  }, {
    key: "responseWarnsAboutDisabledRepoConfiguration",
    value: function responseWarnsAboutDisabledRepoConfiguration(response) {
      return Array.isArray(response.warnings) && response.warnings.some(function (warning) {
        return warning.code === 'unrecognizedvalues' && warning.module === 'query';
      });
    }
  }, {
    key: "isWellFormedResponse",
    value: function isWellFormedResponse(response) {
      try {
        return typeof response.wbdatabridgeconfig.dataTypeLimits.string.maxLength === 'number';
      } catch (e) {
        return false;
      }
    }
  }]);

  return ApiRepoConfigRepository;
}();


// CONCATENATED MODULE: ./src/data-access/DataBridgeTrackerService.ts



var DataBridgeTrackerService_DataBridgeTrackerService =
/*#__PURE__*/
function () {
  function DataBridgeTrackerService(tracker) {
    _classCallCheck(this, DataBridgeTrackerService);

    this.tracker = tracker;
  }

  _createClass(DataBridgeTrackerService, [{
    key: "trackPropertyDatatype",
    value: function trackPropertyDatatype(datatype) {
      this.tracker.increment("MediaWiki.wikibase.client.databridge.datatype.".concat(datatype));
    }
  }]);

  return DataBridgeTrackerService;
}();


// CONCATENATED MODULE: ./src/mediawiki/facades/EventTracker.ts



var EventTracker_EventTracker =
/*#__PURE__*/
function () {
  function EventTracker(tracker) {
    _classCallCheck(this, EventTracker);

    this.tracker = tracker;
  }

  _createClass(EventTracker, [{
    key: "increment",
    value: function increment(topic) {
      this.tracker("counter.".concat(topic), 1);
    }
  }, {
    key: "recordTiming",
    value: function recordTiming(topic, timeInMS) {
      this.tracker("timing.".concat(topic), timeInMS);
    }
  }]);

  return EventTracker;
}();


// CONCATENATED MODULE: ./src/data-access/ApiWbgetentities.ts




function getApiEntity(response, entityId) {
  if (_typeof(response.entities) !== 'object') {
    throw new TechnicalProblem_TechnicalProblem('Result not well formed.');
  }

  var entity = response.entities[entityId];

  if (!entity) {
    throw new EntityNotFound_EntityNotFound('Result does not contain relevant entity.');
  }

  if ('missing' in entity) {
    throw new EntityNotFound_EntityNotFound('Entity flagged missing in response.');
  }

  return entity;
}
function convertNoSuchEntityError(error) {
  if (error instanceof ApiErrors_ApiErrors && error.errors.length === 1 && error.errors[0].code === 'no-such-entity') {
    throw new EntityNotFound_EntityNotFound('Entity flagged missing in response.');
  } else {
    throw error;
  }
}
// CONCATENATED MODULE: ./src/data-access/ApiPropertyDataTypeRepository.ts











var ApiPropertyDataTypeRepository_ApiPropertyDataTypeRepository =
/*#__PURE__*/
function () {
  function ApiPropertyDataTypeRepository(api) {
    _classCallCheck(this, ApiPropertyDataTypeRepository);

    this.api = api;
  }

  _createClass(ApiPropertyDataTypeRepository, [{
    key: "getDataType",
    value: function () {
      var _getDataType = _asyncToGenerator(
      /*#__PURE__*/
      regeneratorRuntime.mark(function _callee(entityId) {
        var response, entity;
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _context.next = 2;
                return this.api.get({
                  action: 'wbgetentities',
                  props: new Set(['datatype']),
                  ids: new Set([entityId]),
                  formatversion: 2
                }).catch(convertNoSuchEntityError);

              case 2:
                response = _context.sent;
                entity = getApiEntity(response, entityId);
                return _context.abrupt("return", entity.datatype);

              case 5:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this);
      }));

      function getDataType(_x) {
        return _getDataType.apply(this, arguments);
      }

      return getDataType;
    }()
  }]);

  return ApiPropertyDataTypeRepository;
}();


// CONCATENATED MODULE: ./src/data-access/error/EntityWithoutLabelInLanguageException.ts






var EntityWithoutLabelInLanguageException_EntityWithoutLabelInLanguageException =
/*#__PURE__*/
function (_Error) {
  _inherits(EntityWithoutLabelInLanguageException, _Error);

  function EntityWithoutLabelInLanguageException() {
    _classCallCheck(this, EntityWithoutLabelInLanguageException);

    return _possibleConstructorReturn(this, _getPrototypeOf(EntityWithoutLabelInLanguageException).apply(this, arguments));
  }

  return EntityWithoutLabelInLanguageException;
}(wrapNativeSuper_wrapNativeSuper(Error));


// CONCATENATED MODULE: ./src/data-access/ApiEntityLabelRepository.ts












var ApiEntityLabelRepository_ApiEntityLabelRepository =
/*#__PURE__*/
function () {
  function ApiEntityLabelRepository(forLanguageCode, api) {
    _classCallCheck(this, ApiEntityLabelRepository);

    this.forLanguageCode = forLanguageCode;
    this.api = api;
  }

  _createClass(ApiEntityLabelRepository, [{
    key: "getLabel",
    value: function () {
      var _getLabel = _asyncToGenerator(
      /*#__PURE__*/
      regeneratorRuntime.mark(function _callee(entityId) {
        var response, entity, label;
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _context.next = 2;
                return this.api.get({
                  action: 'wbgetentities',
                  props: new Set(['labels']),
                  ids: new Set([entityId]),
                  languages: new Set([this.forLanguageCode]),
                  languagefallback: true,
                  formatversion: 2
                }).catch(convertNoSuchEntityError);

              case 2:
                response = _context.sent;
                entity = getApiEntity(response, entityId);

                if (this.forLanguageCode in entity.labels) {
                  _context.next = 6;
                  break;
                }

                throw new EntityWithoutLabelInLanguageException_EntityWithoutLabelInLanguageException("Could not find label for language '".concat(this.forLanguageCode, "'."));

              case 6:
                label = entity.labels[this.forLanguageCode];
                return _context.abrupt("return", {
                  value: label.value,
                  language: label.language
                });

              case 8:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this);
      }));

      function getLabel(_x) {
        return _getLabel.apply(this, arguments);
      }

      return getLabel;
    }()
  }]);

  return ApiEntityLabelRepository;
}();


// CONCATENATED MODULE: ./src/definitions/data-access/PageEditPermissionErrorsRepository.ts
var PermissionErrorType;

(function (PermissionErrorType) {
  PermissionErrorType[PermissionErrorType["PROTECTED_PAGE"] = 1] = "PROTECTED_PAGE";
  PermissionErrorType[PermissionErrorType["CASCADE_PROTECTED_PAGE"] = 2] = "CASCADE_PROTECTED_PAGE";
  PermissionErrorType[PermissionErrorType["BLOCKED"] = 3] = "BLOCKED";
  PermissionErrorType[PermissionErrorType["UNKNOWN"] = -1] = "UNKNOWN";
})(PermissionErrorType || (PermissionErrorType = {}));
// CONCATENATED MODULE: ./src/data-access/CombiningPermissionsRepository.ts
















var CombiningPermissionsRepository_CombiningPermissionsRepository =
/*#__PURE__*/
function () {
  function CombiningPermissionsRepository(repoRepository, clientRepository) {
    _classCallCheck(this, CombiningPermissionsRepository);

    this.repoRepository = repoRepository;
    this.clientRepository = clientRepository;
  }

  _createClass(CombiningPermissionsRepository, [{
    key: "canUseBridgeForItemAndPage",
    value: function () {
      var _canUseBridgeForItemAndPage = _asyncToGenerator(
      /*#__PURE__*/
      regeneratorRuntime.mark(function _callee(repoItemTitle, clientPageTitle) {
        var _ref, _ref2, repoErrors, clientErrors, errors;

        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _context.next = 2;
                return Promise.all([this.repoRepository.getPermissionErrors(repoItemTitle), this.clientRepository.getPermissionErrors(clientPageTitle)]);

              case 2:
                _ref = _context.sent;
                _ref2 = _slicedToArray(_ref, 2);
                repoErrors = _ref2[0];
                clientErrors = _ref2[1];
                errors = [];
                errors.push.apply(errors, _toConsumableArray(repoErrors.map(this.repoErrorToMissingPermissionsError, this)));
                errors.push.apply(errors, _toConsumableArray(clientErrors.map(this.clientErrorToMissingPermissionsError, this)));
                return _context.abrupt("return", errors);

              case 10:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this);
      }));

      function canUseBridgeForItemAndPage(_x, _x2) {
        return _canUseBridgeForItemAndPage.apply(this, arguments);
      }

      return canUseBridgeForItemAndPage;
    }()
  }, {
    key: "repoErrorToMissingPermissionsError",
    value: function repoErrorToMissingPermissionsError(repoError) {
      switch (repoError.type) {
        case PermissionErrorType.PROTECTED_PAGE:
          return {
            type: repoError.semiProtected ? PageNotEditable.ITEM_SEMI_PROTECTED : PageNotEditable.ITEM_FULLY_PROTECTED,
            info: {
              right: repoError.right
            }
          };

        case PermissionErrorType.CASCADE_PROTECTED_PAGE:
          return {
            type: PageNotEditable.ITEM_CASCADE_PROTECTED,
            info: {
              pages: repoError.pages
            }
          };

        case PermissionErrorType.BLOCKED:
          return {
            type: PageNotEditable.BLOCKED_ON_REPO_ITEM,
            info: this.mapBlockInfoFromPermissionErrorTypeToPageNotEditable(repoError.blockinfo)
          };

        case PermissionErrorType.UNKNOWN:
          return this.unknownPermissionErrorToMissingPermissionsError(repoError);
      }
    }
  }, {
    key: "clientErrorToMissingPermissionsError",
    value: function clientErrorToMissingPermissionsError(clientError) {
      switch (clientError.type) {
        case PermissionErrorType.PROTECTED_PAGE:
          throw new TechnicalProblem_TechnicalProblem('Data Bridge should never have been opened on this protected page!');

        case PermissionErrorType.CASCADE_PROTECTED_PAGE:
          return {
            type: PageNotEditable.PAGE_CASCADE_PROTECTED,
            info: {
              pages: clientError.pages
            }
          };

        case PermissionErrorType.BLOCKED:
          return {
            type: PageNotEditable.BLOCKED_ON_CLIENT_PAGE,
            info: this.mapBlockInfoFromPermissionErrorTypeToPageNotEditable(clientError.blockinfo)
          };

        case PermissionErrorType.UNKNOWN:
          return this.unknownPermissionErrorToMissingPermissionsError(clientError);
      }
    }
  }, {
    key: "mapBlockInfoFromPermissionErrorTypeToPageNotEditable",
    value: function mapBlockInfoFromPermissionErrorTypeToPageNotEditable(blockinfo) {
      return {
        blockId: blockinfo.blockid,
        blockedBy: blockinfo.blockedby,
        blockedTimestamp: blockinfo.blockedtimestamp,
        blockExpiry: blockinfo.blockexpiry,
        blockPartial: blockinfo.blockpartial,
        blockReason: blockinfo.blockreason,
        blockedById: blockinfo.blockedbyid
      };
    }
  }, {
    key: "unknownPermissionErrorToMissingPermissionsError",
    value: function unknownPermissionErrorToMissingPermissionsError(error) {
      return {
        type: PageNotEditable.UNKNOWN,
        info: {
          code: error.code,
          messageKey: error.messageKey,
          messageParams: error.messageParams
        }
      };
    }
  }]);

  return CombiningPermissionsRepository;
}();


// EXTERNAL MODULE: ./node_modules/core-js/modules/es.array.slice.js
var es_array_slice = __webpack_require__("fb6a");

// EXTERNAL MODULE: ./node_modules/core-js/modules/es.string.ends-with.js
var es_string_ends_with = __webpack_require__("8a79");

// EXTERNAL MODULE: ./node_modules/core-js/modules/es.string.starts-with.js
var es_string_starts_with = __webpack_require__("2ca0");

// CONCATENATED MODULE: ./src/data-access/ApiQuery.ts







function getApiQueryResponsePage(response, title) {
  var _iteratorNormalCompletion = true;
  var _didIteratorError = false;
  var _iteratorError = undefined;

  try {
    for (var _iterator = (response.normalized || [])[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
      var normalized = _step.value;

      if (normalized.from === title) {
        title = normalized.to;
        break;
      }
    }
  } catch (err) {
    _didIteratorError = true;
    _iteratorError = err;
  } finally {
    try {
      if (!_iteratorNormalCompletion && _iterator.return != null) {
        _iterator.return();
      }
    } finally {
      if (_didIteratorError) {
        throw _iteratorError;
      }
    }
  }

  var _iteratorNormalCompletion2 = true;
  var _didIteratorError2 = false;
  var _iteratorError2 = undefined;

  try {
    for (var _iterator2 = (response.pages || [])[Symbol.iterator](), _step2; !(_iteratorNormalCompletion2 = (_step2 = _iterator2.next()).done); _iteratorNormalCompletion2 = true) {
      var page = _step2.value;

      if (page.title === title) {
        return page;
      }
    }
  } catch (err) {
    _didIteratorError2 = true;
    _iteratorError2 = err;
  } finally {
    try {
      if (!_iteratorNormalCompletion2 && _iterator2.return != null) {
        _iterator2.return();
      }
    } finally {
      if (_didIteratorError2) {
        throw _iteratorError2;
      }
    }
  }

  return null;
}
function isInfoTestPage(page) {
  return 'actions' in page;
}
function isRestrictionsBody(body) {
  return 'restrictions' in body;
}
// CONCATENATED MODULE: ./src/data-access/error/TitleInvalid.ts






var TitleInvalid_TitleInvalid =
/*#__PURE__*/
function (_Error) {
  _inherits(TitleInvalid, _Error);

  function TitleInvalid(title) {
    var _this;

    _classCallCheck(this, TitleInvalid);

    _this = _possibleConstructorReturn(this, _getPrototypeOf(TitleInvalid).call(this, "The title '".concat(title, "' is invalid.")));
    _this.title = title;
    return _this;
  }

  return TitleInvalid;
}(wrapNativeSuper_wrapNativeSuper(Error));


// CONCATENATED MODULE: ./src/data-access/ApiPageEditPermissionErrorsRepository.ts























function isApiErrorRawErrorformat(error) {
  return 'key' in error && 'params' in error;
}

var ApiPageEditPermissionErrorsRepository_ApiPageEditPermissionErrorsRepository =
/*#__PURE__*/
function () {
  function ApiPageEditPermissionErrorsRepository(api) {
    _classCallCheck(this, ApiPageEditPermissionErrorsRepository);

    this.api = api;
  }

  _createClass(ApiPageEditPermissionErrorsRepository, [{
    key: "getPermissionErrors",
    value: function () {
      var _getPermissionErrors = _asyncToGenerator(
      /*#__PURE__*/
      regeneratorRuntime.mark(function _callee(title) {
        var _this = this;

        var response, queryBody, page, semiProtectedLevels;
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _context.next = 2;
                return this.api.get({
                  action: 'query',
                  titles: new Set([title]),
                  prop: new Set(['info']),
                  meta: new Set(['siteinfo']),
                  intestactions: new Set(['edit']),
                  intestactionsdetail: 'full',
                  siprop: new Set(['restrictions']),
                  errorformat: 'raw',
                  formatversion: 2
                });

              case 2:
                response = _context.sent;
                queryBody = response.query;
                page = getApiQueryResponsePage(queryBody, title);

                if (!(page === null)) {
                  _context.next = 7;
                  break;
                }

                throw new TechnicalProblem_TechnicalProblem("API did not return information for page '".concat(title, "'."));

              case 7:
                if (!page.invalid) {
                  _context.next = 9;
                  break;
                }

                throw new TitleInvalid_TitleInvalid(title);

              case 9:
                if (isInfoTestPage(page)) {
                  _context.next = 11;
                  break;
                }

                throw new TechnicalProblem_TechnicalProblem('API info did not return test actions.');

              case 11:
                if (isRestrictionsBody(queryBody)) {
                  _context.next = 13;
                  break;
                }

                throw new TechnicalProblem_TechnicalProblem('API siteinfo did not return restrictions.');

              case 13:
                semiProtectedLevels = queryBody.restrictions.semiprotectedlevels.map(this.rewriteCompatibilityRight);
                return _context.abrupt("return", page.actions.edit.map(function (error) {
                  return _this.apiErrorToPermissionError(error, semiProtectedLevels);
                }));

              case 15:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this);
      }));

      function getPermissionErrors(_x) {
        return _getPermissionErrors.apply(this, arguments);
      }

      return getPermissionErrors;
    }()
  }, {
    key: "apiErrorToPermissionError",
    value: function apiErrorToPermissionError(error, semiProtectedLevels) {
      if (!isApiErrorRawErrorformat(error)) {
        throw new TechnicalProblem_TechnicalProblem('API returned wrong error format.');
      }

      switch (error.code) {
        case 'protectedpage':
          {
            var right = error.params[0];
            var permissionError = {
              type: PermissionErrorType.PROTECTED_PAGE,
              right: right,
              semiProtected: semiProtectedLevels.includes(right)
            };
            return permissionError;
          }

        case 'cascadeprotected':
          {
            var pages = this.parseWikitextPagesList(error.params[1]);

            if (pages.length !== error.params[0]) {
              throw new TechnicalProblem_TechnicalProblem("API reported ".concat(error.params[0], " cascade-protected pages but we parsed ").concat(pages.length, "."));
            }

            var _permissionError = {
              type: PermissionErrorType.CASCADE_PROTECTED_PAGE,
              pages: pages
            };
            return _permissionError;
          }

        case 'blocked':
          {
            var _permissionError2 = {
              type: PermissionErrorType.BLOCKED,
              blockinfo: error.data.blockinfo
            };
            return _permissionError2;
          }

        default:
          {
            var _permissionError3 = {
              type: PermissionErrorType.UNKNOWN,
              code: error.code,
              messageKey: error.key,
              messageParams: error.params
            };
            return _permissionError3;
          }
      }
    }
    /**
     * Account for MediaWiki backwards compatibility –
     * a protection level can be not only a right,
     * but also the 'sysop' group (rewritten to 'editprotected' right)
     * or the 'autoconfirmed' group (rewritten to 'editsemiprotected' right).
     * API errors always use the rewritten right,
     * but the $wgSemiprotectedRestrictionLevels setting may contain a group.
     */

  }, {
    key: "rewriteCompatibilityRight",
    value: function rewriteCompatibilityRight(rightOrGroup) {
      switch (rightOrGroup) {
        case 'sysop':
          return 'editprotected';

        case 'autoconfirmed':
          return 'editsemiprotected';

        default:
          return rightOrGroup;
      }
    }
    /**
     * Parse a list of pages from (very limited) wikitext.
     * See PermissionManager::checkCascadingSourcesRestrictions()
     * for the PHP code generating the list.
     */

  }, {
    key: "parseWikitextPagesList",
    value: function parseWikitextPagesList(wikitext) {
      var lines = wikitext.split('\n');
      var trailingLine = lines.pop();

      if (trailingLine !== '') {
        throw new TechnicalProblem_TechnicalProblem("Wikitext did not end in blank line: ".concat(trailingLine));
      }

      return lines.map(function (line) {
        if (!line.startsWith('*')) {
          throw new TechnicalProblem_TechnicalProblem("Line does not look like a list item: ".concat(line));
        }

        var listItem = line.slice(1);

        if (listItem.startsWith(' ')) {
          listItem = listItem.slice(1);
        }

        if (!listItem.startsWith('[[') || !listItem.endsWith(']]')) {
          throw new TechnicalProblem_TechnicalProblem("List item does not look like a wikilink: ".concat(listItem));
        }

        var title = listItem.slice(2, -2);

        if (title.startsWith(':')) {
          title = title.slice(1);
        }

        return title;
      });
    }
  }]);

  return ApiPageEditPermissionErrorsRepository;
}();


// CONCATENATED MODULE: ./src/services/createServices.ts

















function createServices(mwWindow, editTags) {
  var services = new ServiceContainer_ServiceContainer();
  var repoConfig = mwWindow.mw.config.get('wbRepo'),
      repoRouter = new RepoRouter_RepoRouter(repoConfig, mwWindow.mw.util.wikiUrlencode, mwWindow.$.param);
  services.set('readingEntityRepository', new SpecialPageReadingEntityRepository_SpecialPageReadingEntityRepository(mwWindow.$, repoRouter.getPageUrl('Special:EntityData')));

  if (mwWindow.mw.ForeignApi === undefined) {
    throw new Error('mw.ForeignApi was not loaded!');
  }

  var repoMwApi = new mwWindow.mw.ForeignApi( // TODO use repoRouter with a getScript() method maybe
  "".concat(repoConfig.url).concat(repoConfig.scriptPath, "/api.php"));
  var repoApi = new BatchingApi_BatchingApi(new ApiCore_ApiCore(repoMwApi));
  services.set('writingEntityRepository', new ApiWritingRepository_ApiWritingRepository(repoMwApi, mwWindow.mw.config.get('wgUserName'), editTags.length === 0 ? undefined : editTags));
  services.set('entityLabelRepository', new ApiEntityLabelRepository_ApiEntityLabelRepository(mwWindow.mw.config.get('wgPageContentLanguage'), repoApi));
  services.set('propertyDatatypeRepository', new ApiPropertyDataTypeRepository_ApiPropertyDataTypeRepository(repoApi));

  if (mwWindow.$.uls === undefined) {
    throw new Error('$.uls was not loaded!');
  }

  services.set('languageInfoRepository', new MwLanguageInfoRepository_MwLanguageInfoRepository(mwWindow.mw.language, mwWindow.$.uls.data));
  services.set('messagesRepository', new MwMessagesRepository_MwMessagesRepository(mwWindow.mw.message));
  services.set('wikibaseRepoConfigRepository', new ApiRepoConfigRepository_ApiRepoConfigRepository(repoApi));
  services.set('tracker', new DataBridgeTrackerService_DataBridgeTrackerService(new EventTracker_EventTracker(mwWindow.mw.track)));
  var clientApi = new ApiCore_ApiCore(new mwWindow.mw.Api());
  services.set('editAuthorizationChecker', new CombiningPermissionsRepository_CombiningPermissionsRepository(new ApiPageEditPermissionErrorsRepository_ApiPageEditPermissionErrorsRepository(repoApi), new ApiPageEditPermissionErrorsRepository_ApiPageEditPermissionErrorsRepository(clientApi)));
  services.set('repoRouter', repoRouter);
  services.set('clientRouter', new ClientRouter_ClientRouter(mwWindow.mw.util.getUrl));
  return services;
}
// CONCATENATED MODULE: ./src/main.ts









external_commonjs_vue2_commonjs2_vue2_amd_vue2_root_vue2_default.a.config.productionTip = false;
function launch(config, information, services) {
  extendVueEnvironment(services.get('languageInfoRepository'), services.get('messagesRepository'), information.client, services.get('repoRouter'), services.get('clientRouter'));
  var store = store_createStore(services);
  store.dispatch('initBridge', information);
  var app = new presentation_App({
    store: store
  }).$mount(config.containerSelector);
  var emitter = new events_events["EventEmitter"]();
  repeater(app, emitter, Object.values(src_events));
  return emitter;
}

// CONCATENATED MODULE: ./node_modules/@vue/cli-service/lib/commands/build/entry-lib-no-default.js
/* concated harmony reexport launch */__webpack_require__.d(__webpack_exports__, "launch", function() { return launch; });
/* concated harmony reexport createServices */__webpack_require__.d(__webpack_exports__, "createServices", function() { return createServices; });




/***/ }),

/***/ "fb6a":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var $ = __webpack_require__("23e7");
var isObject = __webpack_require__("861d");
var isArray = __webpack_require__("e8b5");
var toAbsoluteIndex = __webpack_require__("23cb");
var toLength = __webpack_require__("50c4");
var toIndexedObject = __webpack_require__("fc6a");
var createProperty = __webpack_require__("8418");
var wellKnownSymbol = __webpack_require__("b622");
var arrayMethodHasSpeciesSupport = __webpack_require__("1dde");
var arrayMethodUsesToLength = __webpack_require__("ae40");

var HAS_SPECIES_SUPPORT = arrayMethodHasSpeciesSupport('slice');
var USES_TO_LENGTH = arrayMethodUsesToLength('slice', { ACCESSORS: true, 0: 0, 1: 2 });

var SPECIES = wellKnownSymbol('species');
var nativeSlice = [].slice;
var max = Math.max;

// `Array.prototype.slice` method
// https://tc39.github.io/ecma262/#sec-array.prototype.slice
// fallback for not array-like ES3 strings and DOM objects
$({ target: 'Array', proto: true, forced: !HAS_SPECIES_SUPPORT || !USES_TO_LENGTH }, {
  slice: function slice(start, end) {
    var O = toIndexedObject(this);
    var length = toLength(O.length);
    var k = toAbsoluteIndex(start, length);
    var fin = toAbsoluteIndex(end === undefined ? length : end, length);
    // inline `ArraySpeciesCreate` for usage native `Array#slice` where it's possible
    var Constructor, result, n;
    if (isArray(O)) {
      Constructor = O.constructor;
      // cross-realm fallback
      if (typeof Constructor == 'function' && (Constructor === Array || isArray(Constructor.prototype))) {
        Constructor = undefined;
      } else if (isObject(Constructor)) {
        Constructor = Constructor[SPECIES];
        if (Constructor === null) Constructor = undefined;
      }
      if (Constructor === Array || Constructor === undefined) {
        return nativeSlice.call(O, k, fin);
      }
    }
    result = new (Constructor === undefined ? Array : Constructor)(max(fin - k, 0));
    for (n = 0; k < fin; k++, n++) if (k in O) createProperty(result, n, O[k]);
    result.length = n;
    return result;
  }
});


/***/ }),

/***/ "fc6a":
/***/ (function(module, exports, __webpack_require__) {

// toObject with fallback for non-array-like ES3 strings
var IndexedObject = __webpack_require__("44ad");
var requireObjectCoercible = __webpack_require__("1d80");

module.exports = function (it) {
  return IndexedObject(requireObjectCoercible(it));
};


/***/ }),

/***/ "fdbc":
/***/ (function(module, exports) {

// iterable DOM collections
// flag - `iterable` interface - 'entries', 'keys', 'values', 'forEach' methods
module.exports = {
  CSSRuleList: 0,
  CSSStyleDeclaration: 0,
  CSSValueList: 0,
  ClientRectList: 0,
  DOMRectList: 0,
  DOMStringList: 0,
  DOMTokenList: 1,
  DataTransferItemList: 0,
  FileList: 0,
  HTMLAllCollection: 0,
  HTMLCollection: 0,
  HTMLFormElement: 0,
  HTMLSelectElement: 0,
  MediaList: 0,
  MimeTypeArray: 0,
  NamedNodeMap: 0,
  NodeList: 1,
  PaintRequestList: 0,
  Plugin: 0,
  PluginArray: 0,
  SVGLengthList: 0,
  SVGNumberList: 0,
  SVGPathSegList: 0,
  SVGPointList: 0,
  SVGStringList: 0,
  SVGTransformList: 0,
  SourceBufferList: 0,
  StyleSheetList: 0,
  TextTrackCueList: 0,
  TextTrackList: 0,
  TouchList: 0
};


/***/ }),

/***/ "fdbf":
/***/ (function(module, exports, __webpack_require__) {

var NATIVE_SYMBOL = __webpack_require__("4930");

module.exports = NATIVE_SYMBOL
  // eslint-disable-next-line no-undef
  && !Symbol.sham
  // eslint-disable-next-line no-undef
  && typeof Symbol.iterator == 'symbol';


/***/ }),

/***/ "fea9":
/***/ (function(module, exports, __webpack_require__) {

var global = __webpack_require__("da84");

module.exports = global.Promise;


/***/ }),

/***/ "ff8e":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var bind = __webpack_require__("0f7c");

var GetIntrinsic = __webpack_require__("2d78");

var $Function = GetIntrinsic('%Function%');
var $apply = $Function.apply;
var $call = $Function.call;

module.exports = function callBind() {
	return bind.apply($call, arguments);
};

module.exports.apply = function applyBind() {
	return bind.apply($apply, arguments);
};


/***/ })

/******/ });
//# sourceMappingURL=data-bridge.common.js.map