import ReadingEntityRepository from '@/definitions/data-access/ReadingEntityRepository';
import WritingEntityRepository from '@/definitions/data-access/WritingEntityRepository';
import LanguageInfoRepository from '@/definitions/data-access/LanguageInfoRepository';
import EntityLabelRepository from '@/definitions/data-access/EntityLabelRepository';
import MessagesRepository from '@/definitions/data-access/MessagesRepository';

export default class ServiceRepositories {
	private readingEntityRepository?: ReadingEntityRepository;
	private writingEntityRepository?: WritingEntityRepository;
	private languageInfoRepository?: LanguageInfoRepository;
	private entityLabelRepository?: EntityLabelRepository;
	private messagesRepository?: MessagesRepository;

	public setReadingEntityRepository( lookup: ReadingEntityRepository ): void {
		this.readingEntityRepository = lookup;
	}

	public getReadingEntityRepository(): ReadingEntityRepository {
		if ( this.readingEntityRepository ) {
			return this.readingEntityRepository;
		} else {
			throw new Error( 'ReadingEntityRepository is undefined' );
		}
	}

	public setWritingEntityRepository( repository: WritingEntityRepository ): void {
		this.writingEntityRepository = repository;
	}

	public getWritingEntityRepository(): WritingEntityRepository {
		if ( this.writingEntityRepository ) {
			return this.writingEntityRepository;
		} else {
			throw new Error( 'WritingEntityRepository is undefined' );
		}
	}

	public setLanguageInfoRepository( lookup: LanguageInfoRepository ): void {
		this.languageInfoRepository = lookup;
	}

	public getLanguageInfoRepository(): LanguageInfoRepository {
		if ( this.languageInfoRepository ) {
			return this.languageInfoRepository;
		} else {
			throw new Error( 'LanguageInfoRepository is undefined' );
		}
	}

	public setEntityLabelRepository( entityLabelRepository: EntityLabelRepository ): void {
		this.entityLabelRepository = entityLabelRepository;
	}

	public getEntityLabelRepository(): EntityLabelRepository {
		if ( this.entityLabelRepository ) {
			return this.entityLabelRepository;
		} else {
			throw new Error( 'EntityLabelRepository is undefined' );
		}
	}

	public setMessagesRepository( messagesRepository: MessagesRepository ): void {
		this.messagesRepository = messagesRepository;
	}

	public getMessagesRepository(): MessagesRepository {
		if ( this.messagesRepository ) {
			return this.messagesRepository;
		} else {
			throw new Error( 'MessagesRepository is undefined' );
		}
	}
}
