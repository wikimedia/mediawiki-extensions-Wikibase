import { SET_ALL_TAINTED, SET_TAINTED, SET_UNTAINTED } from '@/store/mutationTypes';
import actions from '@/store/actions';
import {
	STATEMENT_TAINTED_STATE_INIT,
	STATEMENT_TAINTED_STATE_TAINT,
	STATEMENT_TAINTED_STATE_UNTAINT,
} from '@/store/actionTypes';
import newMockStore from '@wmde/vuex-helpers/dist/newMockStore';

describe( 'actions', () => {
	it( `commits to ${SET_ALL_TAINTED}`, async () => {
		const context = newMockStore( {} );
		const payload = [ 'foo' ];
		await ( actions as Function )()[ STATEMENT_TAINTED_STATE_INIT ]( context, payload );
		expect( context.commit ).toBeCalledWith( SET_ALL_TAINTED, payload );
	} );
	it( `commits to ${SET_UNTAINTED}`, async () => {
		const context = newMockStore( {} );
		await ( actions as Function )()[ STATEMENT_TAINTED_STATE_UNTAINT ]( context, 'blah' );
		expect( context.commit ).toBeCalledWith( SET_UNTAINTED, 'blah' );
	} );
	it( `commits to ${SET_TAINTED}`, async () => {
		const context = newMockStore( {} );
		await ( actions as Function )()[ STATEMENT_TAINTED_STATE_TAINT ]( context, 'blah' );
		expect( context.commit ).toBeCalledWith( SET_TAINTED, 'blah' );
	} );
} );
