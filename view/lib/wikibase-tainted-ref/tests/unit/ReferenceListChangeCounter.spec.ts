import ReferenceListChangeCounter from '@/ReferenceListChangeCounter';
import { ReferenceList } from '@/definitions/wikibase-js-datamodel/ReferenceList';

const getMockRefList = (
	hasEqualReferencesList = true,
	referenceListLength = 1,
	hasItem = true,
): ReferenceList => {
	return {
		equals: () => hasEqualReferencesList,
		isEmpty: () => false,
		hasItem: () => hasItem,
		length: referenceListLength,
		each: ( lambda: Function ) => {
			for ( let i = 0; i < referenceListLength; i++ ) {
				lambda();
			}
		},
	};
};

describe( 'ReferenceListChangeCounter', () => {
	const changeCounter = new ReferenceListChangeCounter();
	it( 'should return 0 if no references have changed', () => {
		const oldRefs = getMockRefList();
		const newRefs = getMockRefList();
		expect( changeCounter.countOldReferencesRemovedOrChanged( oldRefs, newRefs ) ).toBe( 0 );
	} );

	it( 'should return 0 if all old references are still present', () => {
		const oldRefs = getMockRefList( false, 1, true );
		const newRefs = getMockRefList( false, 2, true );
		expect( changeCounter.countOldReferencesRemovedOrChanged( oldRefs, newRefs ) ).toBe( 0 );
	} );

	it( 'should return 1 if a reference has been removed', () => {
		const oldRefs = getMockRefList( false, 1, false );
		const newRefs = getMockRefList( false, 0, false );
		expect( changeCounter.countOldReferencesRemovedOrChanged( oldRefs, newRefs ) ).toBe( 1 );
	} );

	it( 'should return 1 if a reference has been removed and a different one added', () => {
		const oldRefs = getMockRefList( false, 1, false );
		const newRefs = getMockRefList( false, 1, false );
		expect( changeCounter.countOldReferencesRemovedOrChanged( oldRefs, newRefs ) ).toBe( 1 );
	} );
} );
