console.log("Template Creator Script Loaded!");

let currentTargetLocation = 'left';

// Funkcja pomocnicza: aktualizuje ukryte inputy 'location' zależnie od tego, gdzie pole wylądowało
function updateAllFieldsLocations() {
    document.querySelectorAll('#left-fields .field-location').forEach(input => input.value = 'left');
    document.querySelectorAll('#right-fields .field-location').forEach(input => input.value = 'right');
}

function getDragAfterElement(container, y) {
    // Szukamy wszystkich elementów draggable, które NIE są aktualnie ciągnięte
    const draggableElements = [...container.querySelectorAll('.field-item:not(.dragging)')];

    return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        
        if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
        } else {
            return closest;
        }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

function openFieldModal(location) {
    console.log("Opening modal for:", location);
    currentTargetLocation = location;

    const modal = document.getElementById('type-modal');
    const optList = document.getElementById('opt-list');

    if (!modal) {
        console.error("Błąd: Nie znaleziono elementu #type-modal");
        return;
    }

    // Pokaż listę tylko po prawej stronie
    if (optList) {
        optList.style.display = (location === 'right') ? 'flex' : 'none';
    }

    modal.style.display = 'flex';
}

function closeModal() {
    const modal = document.getElementById('type-modal');
    if (modal) modal.style.display = 'none';
}

function createField(type) {
    const container = document.getElementById(currentTargetLocation + '-fields');
    const template = document.getElementById('field-template');

    if (!container || !template) return;

    const clone = template.content.cloneNode(true);
    const fieldItem = clone.querySelector('.field-item');
    
    // Ustawienie typu i lokalizacji
    fieldItem.querySelector('.field-location').value = currentTargetLocation;
    fieldItem.querySelector('.field-type').value = type;

    // Aktualizacja wizualna taga
    const tag = fieldItem.querySelector('.type-preview-tag small');
    if (type === 'list') {
        tag.innerHTML = '<i class="fa-solid fa-list"></i> Typ: Lista';
        tag.parentElement.style.color = 'var(--primary)';
    } else {
        tag.innerHTML = '<i class="fa-solid fa-font"></i> Typ: Tekst';
    }

    container.appendChild(clone);
    updateAllFieldsLocations(); // Odśwież lokalizacje od razu
    closeModal();
}

// Inicjalizacja przycisków wewnątrz modala
document.addEventListener('DOMContentLoaded', () => {
    const btnText = document.getElementById('opt-text');
    const btnList = document.getElementById('opt-list');

    if (btnText) {
        btnText.addEventListener('click', (e) => {
            e.preventDefault();
            createField('text');
        });
    }

    if (btnList) {
        btnList.addEventListener('click', (e) => {
            e.preventDefault();
            createField('list');
        });
    }

    // Zamknij modal przy kliknięciu poza okno
    const modal = document.getElementById('type-modal');
    window.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });

    const containers = document.querySelectorAll('.fields-container');

    containers.forEach(container => {
        // 1. Obsługa rozpoczęcia przeciągania (Delegacja)
        container.addEventListener('dragstart', (e) => {
            // Szukamy najbliższego rodzica z klasą field-item, jeśli kliknięto np. w ikonkę grip
            const draggable = e.target.closest('.field-item');
            if (draggable) {
                draggable.classList.add('dragging');
            }
        });

        // 2. Obsługa zakończenia
        container.addEventListener('dragend', (e) => {
            const draggable = e.target.closest('.field-item');
            if (draggable) {
                draggable.classList.remove('dragging');
                updateAllFieldsLocations();
            }
        });

        // 3. Logika przesuwania (Kluczowe!)
        container.addEventListener('dragover', (e) => {
            e.preventDefault(); // To pozwala na "drop"
            const dragging = document.querySelector('.dragging');
            if (!dragging) return;

            const afterElement = getDragAfterElement(container, e.clientY);
            
            // Ważne: Sprawdzamy czy nie próbujemy wrzucić elementu do tego samego miejsca
            if (afterElement == null) {
                container.appendChild(dragging);
            } else {
                container.insertBefore(dragging, afterElement);
            }
        });
    });
});