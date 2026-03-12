const SEARCH_BTN = document.getElementById('search-place-btn');
const SEARCH_WHAT = document.getElementById('sortie_lieuNom');
const SEARCH_WHERE = document.getElementById('sortie_lieuVille');
const SEARCH_RESULTS = document.getElementById('places_research_container');
const SELECTION = document.getElementById('places_selected_container');

document.addEventListener('DOMContentLoaded', () => {
    SEARCH_BTN.addEventListener('click', () => {
        const recherche = SEARCH_WHAT.value;
        const ville = SEARCH_WHERE.options[SEARCH_WHERE.selectedIndex].text;
        console.log(ville);

        SEARCH_RESULTS.innerHTML= '';

        if(!recherche){
            alert("Indiquez un lieu à rechercher comme Restaurant ou Bowling dans le champ Lieu(x)");
            return;
        }
        if(!ville){
            alert("Sélectionnez d'abord une ville avant de lancer la recherche.");
            return;
        }
        searchPlaces(recherche, ville);
    })
})

function searchPlaces(recherche, ville) {
    fetch("api/places", {
        method: 'POST',
        headers: {"content-type": "application/json"},
        body: JSON.stringify({
            recherche: recherche,
            ville: ville,
        })
    })
        .then(res => res.json())
        .then(data => {
            if (data && data.length > 0) {
                const title = document.createElement("p");
                title.textContent = "Résultat de la recherche : ";
                SEARCH_RESULTS.appendChild(title);

                data.forEach((place) => {
                    const placeDiv = document.createElement("div");
                    placeDiv.dataset.place = JSON.stringify(place);
                    placeDiv.classList.add("place-item");

                    const label = document.createElement("span");
                    label.textContent = place.name + " (" + place.address + ")";

                    const ajout = creationBtnAjout(placeDiv)

                    placeDiv.appendChild(label);
                    placeDiv.appendChild(ajout);
                    SEARCH_RESULTS.appendChild(placeDiv);
                });
            } else {
                const aucunReult = document.createElement("p");
                aucunReult.textContent = "Aucun lieu trouvé pour votre recherche. Modifiez là et rechercher à nouveau";
                aucunReult.classList.add('text-danger','text-opacity-75');

                SEARCH_RESULTS.appendChild(aucunReult);
            }
        })
}

function ajouterLieu(place){
    place.querySelector("button").remove();

    const remove = document.createElement("button");
    remove.classList.add("btn","btn-outline-danger","btn-sm","m-2");
    remove.textContent = "retirer";

    remove.addEventListener("click", ()=>removeLieu(place));

    place.appendChild(remove);
    SELECTION.appendChild(place);
}

function removeLieu(place) {
    place.querySelector("button").remove();

    const ajout = creationBtnAjout(place);
    place.appendChild(ajout);

    SEARCH_RESULTS.appendChild(place);
}

function creationBtnAjout(place){
    const ajout = document.createElement("button");
    ajout.classList.add("btn","btn-outline-success","btn-sm", "m-2");
    ajout.textContent = "ajouter";

    ajout.addEventListener("click", ()=>ajouterLieu(place));

    return ajout;
}
