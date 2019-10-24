export class Claim {
    getGuid(): string;
    getMainSnak(): Snak;
}

export class Snak {
    equals( snak: Snak ): boolean;
}

export class Statement {
    getClaim(): Claim;
    getReferences(): ReferenceList;
}

class ReferenceList {
    equals( referenceList: ReferenceList ): boolean;
    isEmpty(): boolean;
}
