import ReadingEntityRepository from '@/definitions/data-access/ReadingEntityRepository';
import WritingEntityRepository from '@/definitions/data-access/WritingEntityRepository';
import LanguageInfoRepository from '@/definitions/data-access/LanguageInfoRepository';
import EntityLabelRepository from '@/definitions/data-access/EntityLabelRepository';
import MessagesRepository from '@/definitions/data-access/MessagesRepository';
import WikibaseRepoConfigRepository from '@/definitions/data-access/WikibaseRepoConfigRepository';
import BridgeTracker from '@/definitions/data-access/BridgeTracker';
import PropertyDatatypeRepository from '@/definitions/data-access/PropertyDatatypeRepository';

export interface Services {
	readingEntityRepository: ReadingEntityRepository;
	writingEntityRepository: WritingEntityRepository;
	languageInfoRepository: LanguageInfoRepository;
	entityLabelRepository: EntityLabelRepository;
	propertyDatatypeRepository: PropertyDatatypeRepository;
	messagesRepository: MessagesRepository;
	wikibaseRepoConfigRepository: WikibaseRepoConfigRepository;
	tracker: BridgeTracker;
}

export default class ServiceContainer {
	private readonly services: Partial<Services>;

	public constructor() {
		this.services = {};
	}

	public set<K extends keyof Services>( key: K, service: Services[ K ] ): void {
		this.services[ key ] = service;
	}

	public get<K extends keyof Services>( key: K ): Services[ K ] {
		if ( this.services[ key ] === undefined ) {
			throw new Error( `${key} is undefined` );
		}

		return this.services[ key ] as Services[ K ];
	}

}
