console.log("Character Creator Script Loaded!");

async function loadTemplateFields(templateId) {
    if (!templateId) return;

    try {
        const response = await fetch(`/getTemplateData?id=${templateId}`);
        const template = await response.json();

        if (template.error) {
            console.error(template.error);
            return;
        }

        const leftContainer = document.getElementById('left-fields-container');
        const rightContainer = document.getElementById('right-fields-container');

        leftContainer.innerHTML = '';
        rightContainer.innerHTML = '';

        if (!template.fields || template.fields.length === 0) {
            leftContainer.innerHTML = '<p style="color: var(--text-muted);">Ten szablon nie zawiera żadnych pól.</p>';
            return;
        }

        // Sprawdzamy czy w widoku istnieje tablica wartości dla pól (przekazana z PHP w zmiennej)
        // Ewentualnie możemy odczytać je bezpośrednio z istniejących obiektów
        // Wewnątrz funkcji loadTemplateFields:
        template.fields.forEach(field => {
            const fieldWrapper = document.createElement('div');
            fieldWrapper.className = 'input-group';

            const label = document.createElement('label');
            label.innerText = field.label;

            let inputElement;

            if (field.field_type === 'list') {
                inputElement = document.createElement('textarea');
                inputElement.placeholder = "Wprowadź wartości (rozdzielone przecinkami)...";
            } else {
                inputElement = document.createElement('input');
                inputElement.type = 'text';
                inputElement.placeholder = "Wpisz wartość...";
            }

            inputElement.name = `field_values[${field.id}]`;

            // Sprawdzamy czy istnieją zapisane wartości do uzupełnienia w formularzu
            if (typeof window.characterFieldValues !== 'undefined' && window.characterFieldValues[field.id]) {
                inputElement.value = window.characterFieldValues[field.id];
            }

            fieldWrapper.appendChild(label);
            fieldWrapper.appendChild(inputElement);

            if (field.location === 'right') {
                rightContainer.appendChild(fieldWrapper);
            } else {
                leftContainer.appendChild(fieldWrapper);
            }
        });

    } catch (error) {
        console.error('Wystąpił błąd podczas pobierania danych:', error);
    }
}