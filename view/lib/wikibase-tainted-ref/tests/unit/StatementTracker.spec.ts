import StatementTracker from '@/StatementTracker';
import getMockStatement from './getMockStatement';
import ReferenceListChangeCounter from '@/ReferenceListChangeCounter';
import { ReferenceList } from '@/definitions/wikibase-js-datamodel/ReferenceList';

function getOldAndNewStatement(
	referenceListLength: number,
	hasEqualReferencesList: boolean,
	mainSnakChanged = true,
	qualifiersChanged = false,
): any {
	const oldStatement = getMockStatement( !mainSnakChanged, true, false, true, !qualifiersChanged );
	const newStatement = getMockStatement( !mainSnakChanged, true, false, true, !qualifiersChanged );

	const getReferences = (): ReferenceList => {
		return {
			equals: () => hasEqualReferencesList,
			isEmpty: () => false,
			hasItem: () => true,
			length: referenceListLength,
			each: () => { throw new Error( 'Not implemented' ); },
		};
	};

	oldStatement.getReferences = getReferences;
	newStatement.getReferences = getReferences;

	return [ oldStatement, newStatement ];
}

describe( 'StatementTracker', () => {
	let statementTracker: StatementTracker;
	let refChangeCounter: ReferenceListChangeCounter;
	let trackerFn: any;

	beforeEach( () => {
		refChangeCounter = new ReferenceListChangeCounter();
		statementTracker = new StatementTracker( ( topic: string, data?: string | number | object ) => {
			trackerFn( topic, data );
		}, refChangeCounter );
	} );

	describe( 'when the main snaks are changed', () => {
		it( 'should invoke the allReferencesChanged tracking key when the ref change count is equal to the' +
			'number of old references changed count', () => {
			trackerFn = jest.fn();
			refChangeCounter.countOldReferencesRemovedOrChanged = jest.fn();
			( refChangeCounter.countOldReferencesRemovedOrChanged as any ).mockReturnValue( 1 );

			const [ oldStatement, newStatement ] = getOldAndNewStatement( 1, false );
			statementTracker.trackChanges( oldStatement, newStatement );

			expect( trackerFn ).toHaveBeenCalledWith(
				'counter.wikibase.view.tainted-ref.mainSnakChanged.allReferencesChanged',
				1,
			);
		} );

		it( 'should invoke the someNotAllReferencesChanged tracking key when the oldReferencesChanged count' +
			' is greater than 0 and not equal to the number of old references', () => {
			trackerFn = jest.fn();
			refChangeCounter.countOldReferencesRemovedOrChanged = jest.fn();
			// Pretend that 2 out of 3 references changed.
			( refChangeCounter.countOldReferencesRemovedOrChanged as any ).mockReturnValue( 2 );

			const [ oldStatement, newStatement ] = getOldAndNewStatement( 3, false );

			statementTracker.trackChanges( oldStatement, newStatement );

			expect( trackerFn ).toHaveBeenCalledWith(
				'counter.wikibase.view.tainted-ref.mainSnakChanged.someNotAllReferencesChanged',
				1,
			);
		} );

		it( 'should invoke the noReferencesChanged tracking key', () => {
			trackerFn = jest.fn();
			refChangeCounter.countOldReferencesRemovedOrChanged = jest.fn();
			// Pretend that no references changed
			( refChangeCounter.countOldReferencesRemovedOrChanged as any ).mockReturnValue( 0 );

			const [ oldStatement, newStatement ] = getOldAndNewStatement( 3, true );

			statementTracker.trackChanges( oldStatement, newStatement );

			expect( trackerFn ).toHaveBeenCalledWith(
				'counter.wikibase.view.tainted-ref.mainSnakChanged.noReferencesChanged',
				1,
			);
		} );
	} );

	describe( 'when the main snaks are unchanged', () => {
		it( 'should invoke the someReferencesChanged tracking key ' +
			'when the oldReferences count is greater than 0', () => {
			trackerFn = jest.fn();
			refChangeCounter.countOldReferencesRemovedOrChanged = jest.fn();
			// Pretend that no references changed
			( refChangeCounter.countOldReferencesRemovedOrChanged as any ).mockReturnValue( 1 );
			const [ oldStatement, newStatement ] = getOldAndNewStatement( 3, true, false );

			statementTracker.trackChanges( oldStatement, newStatement );

			expect( trackerFn ).toHaveBeenCalledWith(
				'counter.wikibase.view.tainted-ref.mainSnakUnchanged.someReferencesChanged',
				1,
			);
		} );
		it( 'should invoke the someQualifierChanged tracking key and someReferencesChanged' +
			' when a qualifier is changed and when the oldReferences count is greater than 0', () => {
			trackerFn = jest.fn();
			refChangeCounter.countOldReferencesRemovedOrChanged = jest.fn();
			( refChangeCounter.countOldReferencesRemovedOrChanged as any ).mockReturnValue( 1 );
			const [ oldStatement, newStatement ] = getOldAndNewStatement( 3, true, false, true );

			statementTracker.trackChanges( oldStatement, newStatement );

			expect( trackerFn ).toHaveBeenCalledWith(
				'counter.wikibase.view.tainted-ref.mainSnakUnchanged.someQualifierChanged',
				1,
			);
			expect( trackerFn ).toHaveBeenCalledWith(
				'counter.wikibase.view.tainted-ref.mainSnakUnchanged.someReferencesChanged',
				1,
			);
		} );
	} );

	describe( 'when there is no old statement', () => {
		it( 'should not track anything', () => {
			trackerFn = jest.fn();

			const newStatement = getOldAndNewStatement( 0, true, false )[ 1 ];
			statementTracker.trackChanges( null, newStatement );

			expect( trackerFn ).not.toHaveBeenCalled();
		} );
	} );

} );
