'use strict';

const { debounce } = require( 'lodash' );
const { config } = require( '@vue/test-utils' );
/**
 * Mock for the calls to Core's $i18n plugin which returns a mw.Message object.
 *
 * @param {string} key The key of the message to parse.
 * @param {...*} args Arbitrary number of arguments to be parsed.
 * @return {Object} mw.Message-like object with .text() and .parse() methods.
 */
function $i18nMock( key, ...args ) {
	function serializeArgs() {
		return args.length ? `${ key }:[${ args.join( ',' ) }]` : key;
	}
	return {
		text: () => serializeArgs(),
		parse: () => serializeArgs()
	};
}
// Mock Vue plugins in test suites.
config.global.provide = {
	i18n: $i18nMock
};
config.global.mocks = {
	$i18n: $i18nMock
};
config.global.directives = {
	'i18n-html': ( el, binding ) => {
		el.innerHTML = `${ binding.arg } (${ binding.value })`;
	}
};

function ApiMock() {}
ApiMock.prototype.get = jest.fn();
ApiMock.prototype.assertCurrentUser = jest.fn();
ApiMock.prototype.postWithEditToken = jest.fn();

function TitleMock() {}
TitleMock.prototype.getMainText = jest.fn();
TitleMock.prototype.getNameText = jest.fn();
TitleMock.prototype.getUrl = jest.fn();

global.mw = {
	Api: ApiMock,
	ForeignApi: ApiMock,
	msg: jest.fn( ( key ) => key ),
	message: $i18nMock,
	Title: TitleMock,
	config: new Map(),
	util: { debounce }
};
