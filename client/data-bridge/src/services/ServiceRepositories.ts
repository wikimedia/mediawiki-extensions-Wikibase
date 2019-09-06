import ReadingEntityRepository from '@/definitions/data-access/ReadingEntityRepository';
import WritingEntityRepository from '@/definitions/data-access/WritingEntityRepository';
import LanguageInfoRepository from '@/definitions/data-access/LanguageInfoRepository';

export default class ServiceRepositories {
	private readingEntityRepository?: ReadingEntityRepository;
	private writingEntityRepository?: WritingEntityRepository;
	private languageInfoRepository?: LanguageInfoRepository;

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
}
