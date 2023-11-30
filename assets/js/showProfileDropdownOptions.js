let button = document.getElementById("profile_dropdown_prompt");
let options = document.getElementById("profile_dropdown_options");
let isOpen = false;

button.addEventListener("click", () => {
    if(!isOpen) {
        options.style.display = "flex";
        isOpen = true;
    } else {
        options.style.display = "none";
        isOpen = false;
    }
});

document.addEventListener("click", (e) => {
    if(e.target !== button) {
        options.style.display = "none";
        isOpen = false;
    }
})