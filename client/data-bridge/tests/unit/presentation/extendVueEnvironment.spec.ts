jest.mock( 'vue', () => {
	return {
		directive: jest.fn(),
		use: jest.fn(),
	};
} );

import Vue from 'vue';
import extendVueEnvironment from '@/presentation/extendVueEnvironment';
import MessagesPlugin from '@/presentation/plugins/MessagesPlugin';

const inlanguageDirective = {};
const mockInlanguage = jest.fn( ( _x: any ) => inlanguageDirective );
jest.mock( '@/presentation/directives/inlanguage', () => ( {
	__esModule: true,
	default: ( languageRepo: any ) => mockInlanguage( languageRepo ),
} ) );

describe( 'extendVueEnvironment', () => {
	it( 'attaches inlanguage directive', () => {
		const languageInfoRepo = new ( jest.fn() )();
		extendVueEnvironment( languageInfoRepo, new ( jest.fn() )() );
		expect( mockInlanguage ).toHaveBeenCalledWith( languageInfoRepo );
		expect( Vue.directive ).toHaveBeenCalledWith( 'inlanguage', inlanguageDirective );
	} );

	it( 'invokes the Messages plugin', () => {
		const messageRepo = new ( jest.fn() )();
		extendVueEnvironment( new ( jest.fn() )(), messageRepo );
		expect( Vue.use ).toHaveBeenCalledWith( MessagesPlugin, messageRepo );
	} );
} );
