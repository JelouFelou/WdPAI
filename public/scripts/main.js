const menuIcon = document.querySelector(".display-mobile.fa-bars");
const navList = document.querySelector("nav > div.container > ul");

menuIcon.addEventListener("click", () => {
  if (navList.style.display === "block") {
    navList.style.display = "none";
  } else {
    navList.style.display = "block";
  }
});

document.addEventListener('DOMContentLoaded', function() {
    const slider = document.getElementById('column-slider');
    const columnText = document.getElementById('column-count');
    const grid = document.querySelector('.dashboard-grid');

    if (slider && grid) {
        slider.addEventListener('input', function() {
            const val = this.value;
            
            // 1. Aktualizujemy tekst obok suwaka
            columnText.innerText = val;
            
            // 2. Aktualizujemy zmienną CSS bezpośrednio na elemencie lub dokumencie
            document.documentElement.style.setProperty('--grid-columns', val);
            
            // Opcjonalnie: Zapisz preferencję użytkownika w LocalStorage, 
            // aby po odświeżeniu strony układ został zapamiętany
            localStorage.setItem('dashboard-columns', val);
        });

        // Wczytywanie zapisanego ustawienia po starcie strony
        const savedValue = localStorage.getItem('dashboard-columns');
        if (savedValue) {
            slider.value = savedValue;
            columnText.innerText = savedValue;
            document.documentElement.style.setProperty('--grid-columns', savedValue);
        }
    }
});