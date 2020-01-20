import {
	START_EDIT,
	STOP_EDIT,
	STATEMENT_TAINTED_STATE_TAINT,
	STATEMENT_TAINTED_STATE_UNTAINT,
} from '@/store/actionTypes';
import MWHookHandler from '@/MWHookHandler';
import Vuex from 'vuex';
import Vue from 'vue';
import { Hook, HookRegistry } from '@/@types/mediawiki/MwWindow';
import getMockStatement from './getMockStatement';
import StatementTracker from '@/StatementTracker';

Vue.use( Vuex );

function getMockStatementTracker(): StatementTracker {
	return { trackChanges: jest.fn() } as any;
}

describe( 'MWHookHandler', () => {
	it( `should dispatch ${START_EDIT} with statement guid on edit hook firing`, () => {
		const dummyEditHook = ( randomFunction: Function ): Hook => {
			randomFunction( 'gooGuid' );
			return { add: jest.fn() };
		};

		const mwHookRegistry: HookRegistry = jest.fn( ( hookName: string ) => {
			if ( hookName === 'wikibase.statement.startEditing' ) {
				return { add: dummyEditHook };
			} else {
				return { add: jest.fn() };
			}
		} );

		const mockTaintedChecker = { check: () => true };

		const hookHandler = new MWHookHandler( mwHookRegistry, mockTaintedChecker, getMockStatementTracker() );
		const store = new Vuex.Store( { state: { statementsTaintedState: {}, statementsPopperIsOpen: {} } as any } );
		store.dispatch = jest.fn();
		hookHandler.addStore( store );
		expect( mwHookRegistry ).toHaveBeenCalledWith( 'wikibase.statement.startEditing' );
		expect( store.dispatch ).toHaveBeenCalledWith( START_EDIT, 'gooGuid' );
	} );

	it( `should dispatch ${STOP_EDIT} with statement guid on edit stop hook firing`, () => {
		const dummyEditHook = ( randomFunction: Function ): Hook => {
			randomFunction( 'gooGuid' );
			return { add: jest.fn() };
		};

		const mwHookRegistry: HookRegistry = jest.fn( ( hookName: string ) => {
			if ( hookName === 'wikibase.statement.stopEditing' ) {
				return { add: dummyEditHook };
			} else {
				return { add: jest.fn() };
			}
		} );

		const mockTaintedChecker = { check: () => true };

		const hookHandler = new MWHookHandler( mwHookRegistry, mockTaintedChecker, getMockStatementTracker() );
		const store = new Vuex.Store( { state: { statementsTaintedState: {}, statementsPopperIsOpen: {} } as any } );
		store.dispatch = jest.fn();
		hookHandler.addStore( store );
		expect( mwHookRegistry ).toHaveBeenCalledWith( 'wikibase.statement.stopEditing' );
		expect( store.dispatch ).toHaveBeenCalledWith( STOP_EDIT, 'gooGuid' );
	} );

	it( `should dispatch ${STATEMENT_TAINTED_STATE_TAINT} with statement guid` +
		'on save hook firing and checker is true', () => {
		const dummySaveHook = ( randomFunction: Function ): Hook => {
			randomFunction( 'Q1', 'gooGuid' );
			return { add: jest.fn() };
		};

		const mwHookRegistry = jest.fn( ( _hookName: string ) => {
			return { add: dummySaveHook };
		} );

		const mockTaintedChecker = { check: () => true };

		const hookHandler = new MWHookHandler( mwHookRegistry, mockTaintedChecker, getMockStatementTracker() );
		const store = new Vuex.Store( { state: { statementsTaintedState: {}, statementsPopperIsOpen: {} } as any } );
		store.dispatch = jest.fn();
		hookHandler.addStore( store );
		expect( mwHookRegistry ).toHaveBeenCalledWith( 'wikibase.statement.saved' );
		expect( store.dispatch ).toHaveBeenCalledWith( STATEMENT_TAINTED_STATE_TAINT, 'gooGuid' );
	} );

	it( 'should not dispatch ${STATEMENT_TAINTED_STATE_TAINT} ' +
		'on save hook firing when checker is false', () => {
		const s1 = getMockStatement( false );
		const s2 = getMockStatement( false );

		const dummySaveHook = ( randomFunction: Function ): Hook => {
			randomFunction( 'Q1', 'gooGuid', s1, s2 );
			return { add: jest.fn() };
		};

		const mwHookRegistry = jest.fn( ( _hookName: string ) => {
			return { add: dummySaveHook };
		} );

		const mockTaintedChecker = { check: jest.fn() };
		mockTaintedChecker.check.mockReturnValue( false );

		const hookHandler = new MWHookHandler( mwHookRegistry, mockTaintedChecker, getMockStatementTracker() );
		const store = new Vuex.Store( { state: { statementsTaintedState: {}, statementsPopperIsOpen: {} } as any } );
		store.dispatch = jest.fn();
		hookHandler.addStore( store );
		expect( mockTaintedChecker.check ).toHaveBeenCalledWith( s1, s2 );
		expect( mwHookRegistry ).toHaveBeenCalledWith( 'wikibase.statement.saved' );
		expect( store.dispatch ).not.toHaveBeenCalledWith( STATEMENT_TAINTED_STATE_TAINT, 'gooGuid' );
	} );

	it( `should dispatch ${STATEMENT_TAINTED_STATE_UNTAINT} with statement guid ` +
		'on save hook firing, if statement was tainted', () => {
		const dummySaveHook = ( randomFunction: Function ): Hook => {
			randomFunction( 'Q1', 'gooGuid' );
			return { add: jest.fn() };
		};

		const mwHookRegistry = jest.fn( ( _hookName: string ) => {
			return { add: dummySaveHook };
		} );

		const mockTaintedChecker = { check: () => true };

		const hookHandler = new MWHookHandler( mwHookRegistry, mockTaintedChecker, getMockStatementTracker() );
		const store = new Vuex.Store( {
			state: {
				statementsTaintedState: { 'gooGuid': true },
				statementsPopperIsOpen: {},
			} as any,
		} );
		store.dispatch = jest.fn();
		hookHandler.addStore( store );
		expect( mwHookRegistry ).toHaveBeenCalledWith( 'wikibase.statement.saved' );
		expect( store.dispatch ).toHaveBeenCalledWith( STATEMENT_TAINTED_STATE_UNTAINT, 'gooGuid' );
	} );

	it( 'should call the statementTracker on save hook firing', () => {
		const s1 = getMockStatement( false );
		const s2 = getMockStatement( false );
		const dummySaveHook = ( randomFunction: Function ): Hook => {
			randomFunction( 'Q1', 'gooGuid', s1, s2 );
			return { add: jest.fn() };
		};

		const mwHookRegistry = jest.fn( ( _hookName: string ) => {
			return { add: dummySaveHook };
		} );

		const mockTaintedChecker = { check: () => true };

		const trackChanges = jest.fn();
		const store = new Vuex.Store( { state: { statementsTaintedState: {}, statementsPopperIsOpen: {} } as any } );
		store.dispatch = jest.fn();
		const hookHandler = new MWHookHandler( mwHookRegistry, mockTaintedChecker, { trackChanges } as any );
		hookHandler.addStore( store );
		expect( trackChanges ).toHaveBeenCalledWith( s1, s2 );
	} );
} );
