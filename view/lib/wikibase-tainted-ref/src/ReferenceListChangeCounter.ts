import { Reference } from '@/definitions/wikibase-js-datamodel/Reference';
import { ReferenceList } from '@/definitions/wikibase-js-datamodel/ReferenceList';

export default class ReferenceListChangeCounter {

	public countOldReferencesRemovedOrChanged( oldReferenceList: ReferenceList, newReferenceList: ReferenceList ):
	number {
		let diffRefCount = 0;
		oldReferenceList.each( ( _index: number, oldRef: Reference ) => {
			if ( !newReferenceList.hasItem( oldRef ) ) {
				diffRefCount++;
			}
		} );

		return diffRefCount;
	}

}
