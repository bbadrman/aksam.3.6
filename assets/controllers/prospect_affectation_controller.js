import { Controller } from '@hotwired/stimulus';

/**
 * Filtre dynamiquement le select "Commercial" selon l'équipe choisie —
 * ne montre que les commerciaux rattachés à cette équipe (même principe
 * que l'ancien assets/js/adaffect.js, sans jQuery).
 */
export default class extends Controller {
    static targets = ['team', 'commercialField', 'commercial'];
    static values = { url: String };

    connect() {
        this.teams = null;
        this.refresh();
    }

    async refresh() {
        const teamId = this.teamTarget.value;

        if (!teamId) {
            this.commercialFieldTarget.hidden = true;
            return;
        }

        this.commercialFieldTarget.hidden = false;

        const teams = await this.loadTeams();
        const team = teams.find((t) => String(t.id) === String(teamId));
        const commercials = team ? team.commercials : [];
        const previousValue = this.commercialTarget.value;

        this.commercialTarget.innerHTML = '';
        this.commercialTarget.appendChild(new Option('— Aucun —', ''));
        commercials.forEach((commercial) => {
            this.commercialTarget.appendChild(new Option(commercial.username, commercial.id));
        });

        if (commercials.some((commercial) => String(commercial.id) === previousValue)) {
            this.commercialTarget.value = previousValue;
        }
    }

    async loadTeams() {
        if (!this.teams) {
            const response = await fetch(this.urlValue);
            this.teams = await response.json();
        }

        return this.teams;
    }
}
