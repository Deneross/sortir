document.querySelector("form").addEventListener("submit", event => {
    //IL faut que ce soit dans les sélectionnés
    const selectedDivs = document.querySelectorAll(".place-item[data-selected='true']");

    const lieux = [];
    selectedDivs.forEach((el) => {
        lieux.push(JSON.parse(el.dataset.place));
    })

    //Ca marchait pas sans un input invisible pour rémplir mon form...
    let input = document.createElement("input");
    input.type = "hidden";
    input.name = "lieux_choosen";
    input.value = JSON.stringify(lieux);

    event.target.appendChild(input);
})
