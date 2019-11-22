import { Claim } from '@/definitions/wikibase-js-datamodel/Claim';
import { ReferenceList } from '@/definitions/wikibase-js-datamodel/ReferenceList';

export interface Statement {
	getClaim(): Claim;
	getReferences(): ReferenceList;
}
