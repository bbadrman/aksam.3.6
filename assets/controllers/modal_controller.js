import { Controller } from '@hotwired/stimulus';

/**
 * Pilote une <dialog> native (affichage, fermeture, clic sur le fond).
 * Usage :
 *   <div data-controller="modal">
 *     <button data-action="modal#open">Ouvrir</button>
 *     <dialog data-modal-target="dialog" data-action="click->modal#closeOnBackdrop">...</dialog>
 *   </div>
 */
export default class extends Controller {
    static targets = ['dialog'];

    open() {
        this.dialogTarget.showModal();
    }

    close() {
        this.dialogTarget.close();
    }

    closeOnBackdrop(event) {
        if (event.target === this.dialogTarget) {
            this.dialogTarget.close();
        }
    }
}
