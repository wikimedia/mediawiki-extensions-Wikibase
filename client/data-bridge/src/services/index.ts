import EntityRepository from '@/definitions/data-access/EntityRepository';
import ApplicationInformationRepository from '@/definitions/data-access/ApplicationInformationRepository';

class ServiceRepositories {
	private entityRepository?: EntityRepository;
	private applicationInformationRepository?: ApplicationInformationRepository;

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

	public setApplicationInformationRepository( lookup: ApplicationInformationRepository ): void {
		this.applicationInformationRepository = lookup;
	}

	public getApplicationInformationRepository(): ApplicationInformationRepository {
		if ( this.applicationInformationRepository ) {
			return this.applicationInformationRepository;
		} else {
			throw new Error( 'ApplicationInformationRepository is undefined' );
		}
	}
}

const services = new ServiceRepositories();

export {
	services,
	ServiceRepositories,
};
