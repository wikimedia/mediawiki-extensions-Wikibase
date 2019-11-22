import { Reference } from '@/definitions/wikibase-js-datamodel/Reference';

export interface ReferenceList {
	length: number;
	equals( referenceList: ReferenceList ): boolean;
	isEmpty(): boolean;
	each( callback: eachCallback ): void;
	hasItem( listItem: Reference ): boolean;
}

type eachCallback = ( index: number, reference: Reference ) => void;
