import { Controller } from '@hotwired/stimulus';

/**
 * Bascule le statut actif/inactif d'un user depuis la liste, sans recharger
 * la page. Le switch revient à son état précédent si la requête échoue.
 */
export default class extends Controller {
    static targets = ['label'];
    static values = { url: String, token: String };

    async toggle(event) {
        const checkbox = event.currentTarget;
        const previousChecked = !checkbox.checked;
        checkbox.disabled = true;

        try {
            const response = await fetch(this.urlValue, {
                method: 'POST',
                headers: { 'X-CSRF-Token': this.tokenValue },
            });

            if (!response.ok) {
                throw new Error('Requête échouée');
            }

            const data = await response.json();
            checkbox.checked = data.actif;
            this.updateLabel(data.actif);
        } catch (error) {
            checkbox.checked = previousChecked;
        } finally {
            checkbox.disabled = false;
        }
    }

    updateLabel(actif) {
        if (!this.hasLabelTarget) {
            return;
        }

        this.labelTarget.textContent = actif ? 'Actif' : 'Inactif';
        this.labelTarget.classList.toggle('text-primary', actif);
        this.labelTarget.classList.toggle('text-destructive', !actif);
    }
}
