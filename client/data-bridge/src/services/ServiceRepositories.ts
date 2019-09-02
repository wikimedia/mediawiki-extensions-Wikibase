import EntityRepository from '@/definitions/data-access/EntityRepository';
import WritingEntityRepository from '@/definitions/data-access/WritingEntityRepository';

export default class ServiceRepositories {
	private entityRepository?: EntityRepository;
	private writingEntityRepository?: WritingEntityRepository;

	public setEntityRepository( lookup: EntityRepository ): void {
		this.entityRepository = lookup;
	}

	public getEntityRepository(): EntityRepository {
		if ( this.entityRepository ) {
			return this.entityRepository;
		} else {
			throw new Error( 'EntityRepository is undefined' );
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
}
