import { Controller } from '@hotwired/stimulus';

/**
 * - Cache le champ "Prochaine relance" quand le motif sélectionné clôture
 *   définitivement la fiche (elle ne réapparaît plus dans les files de
 *   relance — voir RelanceMotif::isCloture()) : ces motifs n'appellent
 *   pas de nouvelle relance programmée.
 * - Affiche les champs de conversion en client (téléphone, email, raison
 *   sociale, adresse) quand le motif est "Passage client" — voir
 *   ProspectController::traiter() + ClientService::convertFromProspect().
 */
export default class extends Controller {
    static targets = ['motif', 'relanceField', 'relance', 'clientFields'];
    static values = { motifsSansRelance: Array, motifClient: String };

    connect() {
        this.refresh();
    }

    refresh() {
        const value = this.motifTarget.value;
        const hideRelance = this.motifsSansRelanceValue.includes(value);

        this.relanceFieldTarget.hidden = hideRelance;
        if (hideRelance) {
            this.relanceTarget.value = '';
        }

        if (this.hasClientFieldsTarget) {
            this.clientFieldsTarget.hidden = value !== this.motifClientValue;
        }
    }
}
