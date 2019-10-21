import { STATEMENT_TAINTED_STATE_TAINT, STATEMENT_TAINTED_STATE_UNTAINT } from '@/store/actionTypes';
import MWHookHandler from '@/MWHookHandler';
import Vuex from 'vuex';
import Vue from 'vue';
import { Hook, HookRegistry } from '@/@types/mediawiki/MwWindow';

Vue.use( Vuex );
describe( 'MWHookHandler', () => {
	it( `should dispatch ${STATEMENT_TAINTED_STATE_UNTAINT} with statement guid on edit hook firing`, () => {
		const dummyEditHook = ( randomFunction: Function ): Hook => {
			randomFunction( 'gooGuid' );
			return { add: jest.fn() };
		};

		const mwHookRegistry: HookRegistry = jest.fn( ( _hookName: string ) => {
			return { add: dummyEditHook };
		} );

		const hookHandler = new MWHookHandler( mwHookRegistry );
		const store = new Vuex.Store( { state: { statementsTaintedState: {} } } );
		store.dispatch = jest.fn();
		hookHandler.addStore( store );
		expect( mwHookRegistry ).toHaveBeenCalledWith( 'wikibase.statement.startEditing' );
		expect( store.dispatch ).toHaveBeenCalledWith( STATEMENT_TAINTED_STATE_UNTAINT, 'gooGuid' );
	} );

	it( 'should dispatch ${STATEMENT_TAINTED_STATE_TAINT} with statement guid on save hook firing', () => {
		const dummySaveHook = ( randomFunction: Function ): Hook => {
			randomFunction( 'Q1', 'gooGuid' );
			return { add: jest.fn() };
		};

		const mwHookRegistry = jest.fn( ( _hookName: string ) => {
			return { add: dummySaveHook };
		} );

		const hookHandler = new MWHookHandler( mwHookRegistry );
		const store = new Vuex.Store( { state: { statementsTaintedState: {} } } );
		store.dispatch = jest.fn();
		hookHandler.addStore( store );
		expect( mwHookRegistry ).toHaveBeenCalledWith( 'wikibase.statement.saved' );
		expect( store.dispatch ).toHaveBeenCalledWith( STATEMENT_TAINTED_STATE_TAINT, 'gooGuid' );
	} );
} );
