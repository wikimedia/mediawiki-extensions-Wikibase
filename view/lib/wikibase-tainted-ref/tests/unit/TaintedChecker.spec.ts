import TaintedChecker from '@/TaintedChecker';
import getMockStatement from './getMockStatement';

describe( 'TaintedChecker', () => {
	let taintedChecker: TaintedChecker;

	beforeEach( () => {
		taintedChecker = new TaintedChecker();
	} );

	it( 'should return false if the value has not changed', () => {
		const oldStatement = getMockStatement( true );
		const newStatement = getMockStatement( true );
		expect( taintedChecker.check( oldStatement, newStatement ) ).toBeFalsy();
	} );

	it( 'should return false if the value has changed and there are no references', () => {
		const oldStatement = getMockStatement( false, true, true );
		const newStatement = getMockStatement( false, true, true );
		expect( taintedChecker.check( oldStatement, newStatement ) ).toBeFalsy();
	} );

	it( 'should return true if the value has changed and the reference has not', () => {
		const oldStatement = getMockStatement( false, true );
		const newStatement = getMockStatement( false, true );
		expect( taintedChecker.check( oldStatement, newStatement ) ).toBeTruthy();
	} );

	it( 'should return false if the value has changed and a reference has changed', () => {
		const oldStatement = getMockStatement( false, false );
		const newStatement = getMockStatement( false, false );
		expect( taintedChecker.check( oldStatement, newStatement ) ).toBeFalsy();
	} );

	it( 'should return false if there is no old statement', () => {
		const oldStatement = null;
		const newStatement = getMockStatement( true );
		expect( taintedChecker.check( oldStatement, newStatement ) ).toBeFalsy();
	} );
} );
