import { STATEMENT_TAINTED_STATE_UNTAINT } from '@/store/actionTypes';
import MWHookHandler from '@/MWHookHandler';
import Vuex from 'vuex';
import Vue from 'vue';

Vue.use( Vuex );
describe( 'MWHookHandler', () => {
	it( `should dispatch ${STATEMENT_TAINTED_STATE_UNTAINT} with statement guid on hook firing`, () => {
		const dummyHook = ( randomFunction: Function ): void => {
			randomFunction( 'gooGuid' );
		};
		const hookHandler = new MWHookHandler( dummyHook );
		const store = new Vuex.Store( { state: { statementsTaintedState: {} } } );
		store.dispatch = jest.fn();
		hookHandler.addStore( store );
		expect( store.dispatch ).toHaveBeenCalledWith( STATEMENT_TAINTED_STATE_UNTAINT, 'gooGuid' );
	} );
} );
