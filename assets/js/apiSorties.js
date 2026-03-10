class ApiSorties {
    tbodyId = "sortiesTable";
    paginationId = "pagination";

    currentPage = 1;
    limit = 10;

    constructor() {
        this.bindEvent();
        this.init();
    }

    async init() {
       await this.loadCampus();
       await this.loadSorties();
    }

    bindEvent() {
        document.getElementById("btnSearch")?.addEventListener("click", (e) => {
            e.preventDefault();
            this.currentPage = 1;
            this.loadSorties();
        });

        document.getElementById("search")?.addEventListener("keydown", (e) => {
            if (e.key === "Enter") {
                e.preventDefault();
                this.currentPage = 1;
                this.loadSorties();
            }
        });

        ["campus", "dateMin", "dateMax", "orga", "inscrit", "nonInscrit", "terminees"].forEach((id) => {
            const el = document.getElementById(id);
            if (!el) return;

            el.addEventListener("change", () => {
                this.currentPage = 1;
                this.loadSorties();
            });
        });
    }

    buildUrl() {
        const params = new URLSearchParams();

        const campus = document.getElementById("campus")?.value;
        const search = document.getElementById("search")?.value?.trim();
        const dateMin = document.getElementById("dateMin")?.value;
        const dateMax = document.getElementById("dateMax")?.value;

        if (campus) params.set("campus", campus);
        if (search) params.set("search", search);
        if (dateMin) params.set("dateMin", dateMin);
        if (dateMax) params.set("dateMax", dateMax);

        if (document.getElementById("orga")?.checked) params.set("orga", "1");
        if (document.getElementById("inscrit")?.checked) params.set("inscrit", "1");
        if (document.getElementById("nonInscrit")?.checked) params.set("nonInscrit", "1");
        if (document.getElementById("terminees")?.checked) params.set("terminees", "1");

        params.set("page", String(this.currentPage));
        params.set("limit", String(this.limit));

        const qs = params.toString();
        return "/api/sorties" + (qs ? `?${qs}` : "");
    }

    async loadSorties() {
        try {
            const url = this.buildUrl();
            const res = await fetch(url, { headers: { Accept: "application/json" } });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);

            const json = await res.json();

            this.updateTable(json.data);
            this.renderPagination(json.pagination);
        } catch (e) {
            console.error(e);
            this.renderError();
            this.renderPagination({ page: this.currentPage, pages: 1, total: 0 });
        }
    }

    applyDefaultCampusFromDataset() {
        const select = document.getElementById("campus");
        if (!select) return;

        const userCampusId = select.dataset.userCampusId;

        if (!userCampusId) return;

        // applique si rien sélectionné
        if (select.value === "") {
            select.value = String(userCampusId);
        }
    }

    async loadCampus() {
        try {
            const res = await fetch("/api/campus", { headers: { Accept: "application/json" } });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);

            const campusList = await res.json();
            this.updateCampusFilter(campusList);
            this.applyDefaultCampusFromDataset();
        } catch (e) {
            console.error(e);
        }
    }


    updateCampusFilter(campusList) {
        const select = document.getElementById("campus");
        if (!select) return;

        if (select.dataset.hydrated === "1") return;

        const options = (Array.isArray(campusList) ? campusList : [])
            .map(c => `<option value="${this.escapeHtml(c.id)}">${this.escapeHtml(c.name)}</option>`)
            .join("");

        select.insertAdjacentHTML("beforeend", options);
        select.dataset.hydrated = "1";
    }

    updateTable(sorties) {
        const tbody = document.getElementById(this.tbodyId);
        if (!tbody) return;

        if (!Array.isArray(sorties) || sorties.length === 0) {
            this.renderEmpty();
            return;
        }

        tbody.innerHTML = sorties
            .map((s) => {
                const nom = this.escapeHtml(s.nom);
                const debut = this.escapeHtml(s.dateHeureDebut);
                const cloture = this.escapeHtml(s.dateLimiteInscription);
                const inscritsPlaces = `${this.escapeHtml(s.nbInscrits)} / ${this.escapeHtml(s.nbInscriptionMax)}`;
                const etat = this.escapeHtml(s.etat);

                const inscritX = s.isFull ? "X" : "";

                const orgaLink = s.organisateurUrl
                    ? `<a href="${this.escapeHtml(s.organisateurUrl)}" title="Détails organisateur">${this.escapeHtml(s.organisateurPseudo)}</a>`
                    : this.escapeHtml(s.organisateurPseudo);

                const actionsHtml = this.renderActions(s.actions);

                return `
          <tr>
            <td>${nom}</td>
            <td>${debut}</td>
            <td>${cloture}</td>
            <td>${inscritsPlaces}</td>
            <td>${etat}</td>
            <td class="text-center">${inscritX}</td>
            <td>${orgaLink}</td>
            <td>${actionsHtml}</td>
          </tr>
        `;
            })
            .join("");
    }

    renderActions(actions) {
        return actions.map(a => {
            const label = this.escapeHtml(a.label);
            const title = this.escapeHtml(a.title || "");
            const href = this.escapeHtml(a.href);
            const cls = this.escapeHtml(a.class || "btn btn-sm btn-primary");
            const method = (a.method || "GET").toUpperCase();

            if (method === "POST") {
                const csrf = a.csrf
                    ? `<input type="hidden" name="_token" value="${this.escapeHtml(a.csrf)}">`
                    : "";

                return `
        <form action="${href}" method="post" style="display:inline">
          ${csrf}
          <button type="submit" class="${cls}" title="${title}">${label}</button>
        </form>
      `;
            }

            return `<a class="${cls}" href="${href}" title="${title}">${label}</a>`;
        }).join(" ");
    }

    renderPagination(pagination) {
        const container = document.getElementById(this.paginationId);
        if (!container) return;

        const page = Number(pagination?.page ?? 1);
        const pages = Number(pagination?.pages ?? 1);
        const total = Number(pagination?.total ?? 0);

        if (pages <= 1) {
            container.innerHTML = total ? `<div class="text-muted">Page ${page} / ${pages}</div>` : "";
            return;
        }

        const maxButtons = 7;
        let start = Math.max(1, page - Math.floor(maxButtons / 2));
        let end = start + maxButtons - 1;

        if (end > pages) {
            end = pages;
            start = Math.max(1, end - maxButtons + 1);
        }

        const btn = (p, label = String(p), disabled = false, active = false) => `
      <button
        type="button"
        class="btn btn-sm ${active ? "btn-primary" : "btn-outline-primary"}"
        data-page="${p}"
        ${disabled ? "disabled" : ""}>
        ${label}
      </button>
    `;

        let html = `<div class="d-flex gap-2 justify-content-center flex-wrap align-items-center">`;

        html += btn(page - 1, "«", page <= 1, false);

        if (start > 1) {
            html += btn(1, "1", false, page === 1);
            if (start > 2) html += `<span class="px-1">…</span>`;
        }

        for (let p = start; p <= end; p++) {
            html += btn(p, String(p), false, p === page);
        }

        if (end < pages) {
            if (end < pages - 1) html += `<span class="px-1">…</span>`;
            html += btn(pages, String(pages), false, page === pages);
        }

        html += btn(page + 1, "»", page >= pages, false);

        html += `</div>`;
        container.innerHTML = html;

        container.querySelectorAll("button[data-page]").forEach((b) => {
            b.addEventListener("click", () => {
                const p = Number(b.dataset.page);
                if (!Number.isFinite(p) || p < 1 || p === this.currentPage) return;

                this.currentPage = p;
                this.loadSorties();
            });
        });
    }

    escapeHtml(v) {
        return String(v ?? "")
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
    }

    renderEmpty() {
        const tbody = document.getElementById(this.tbodyId);
        if (!tbody) return;
        tbody.innerHTML = `<tr><td colspan="8">Aucune sortie programmée</td></tr>`;
    }

    renderError() {
        const tbody = document.getElementById(this.tbodyId);
        if (!tbody) return;
        tbody.innerHTML = `<tr><td colspan="8">Erreur de chargement</td></tr>`;
    }
}

function mountSorties() {
    const tbody = document.getElementById("sortiesTable");
    if (!tbody) return;

    window.__sortiesInstance = new ApiSorties();
}

document.addEventListener("turbo:load", mountSorties);
