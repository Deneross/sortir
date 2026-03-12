const SEARCH_BTN = document.getElementById('search-place-btn');
const SEARCH_WHAT = document.getElementById('sortie_lieuNom');
const SEARCH_WHERE = document.getElementById('sortie_lieuVille');
const SEARCH_RESULTS = document.getElementById('places_container');

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
                const select = document.createElement("select");
                select.id='place-select';
                select.className = 'form-select';
                select.multiple = true;

                data.forEach((place) => {
                    const option = document.createElement("option");
                    option.value = JSON.stringify(place);
                    option.text=place.name + " (" + place.address + ")"
                    select.appendChild(option);
                });

                SEARCH_RESULTS.appendChild(select);
            } else {
                const aucunReult = document.createElement("p");
                aucunReult.textContent = "Aucun lieu trouvé pour votre recherche. Modifiez là et rechercher à nouveau";
                aucunReult.classList.add('text-danger','text-opacity-75');

                SEARCH_RESULTS.appendChild(aucunReult);
            }
        })
}
