import { Controller } from '@hotwired/stimulus';

/**
 * Affiche raisonSociale/activites uniquement quand typeProspect = Professionnel
 * (les champs restent cachés et vides pour un particulier), et motifResil
 * uniquement quand lastAssure = Oui (pas de motif de résiliation à saisir
 * si le prospect n'a jamais été assuré).
 */
export default class extends Controller {
    static targets = ['typeProspect', 'professionnelField', 'lastAssure', 'motifResilField'];

    connect() {
        this.refresh();
        this.refreshMotifResil();
    }

    refresh() {
        const isProfessionnel = this.typeProspectTarget.value === 'Professionnel';

        this.professionnelFieldTargets.forEach((field) => {
            field.hidden = !isProfessionnel;
        });
    }

    refreshMotifResil() {
        const isAssure = this.lastAssureTarget.value === 'Oui';

        this.motifResilFieldTargets.forEach((field) => {
            field.hidden = !isAssure;
        });
    }
}
