document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formAfiliacionUsuario');
    if (!form) return;

    const idEPSSelect = document.getElementById('id_eps');
    const idRegimenSelect = document.getElementById('id_regimen');
    const idARLSelect = document.getElementById('id_arl');
    const idEstadoSelect = document.getElementById('id_estado');
    
    form.addEventListener('submit', function(event) {
        let isValid = true;
        let primerError = null;

        function validateSelect(selectElement, errorMessage) {
            if (selectElement && selectElement.value === '') {
                isValid = false;
                showError(selectElement, errorMessage);
                if (!primerError) primerError = selectElement;
                return false;
            } else if (selectElement) {
                clearError(selectElement);
                return true;
            }
            return true;
        }

        validateSelect(idRegimenSelect, 'El régimen es obligatorio.');
        validateSelect(idEstadoSelect, 'El estado de afiliación es obligatorio.');
        
        const epsValue = idEPSSelect ? idEPSSelect.value : '';
        const arlValue = idARLSelect ? idARLSelect.value : '';
        const isEpsSelected = epsValue !== '';
        const isArlSelected = arlValue !== '';
        
        if (!isEpsSelected && !isArlSelected) {
            isValid = false;
            if(idEPSSelect) showError(idEPSSelect, 'Debe seleccionar una EPS o una ARL.');
            if(idARLSelect) showError(idARLSelect, 'Debe seleccionar una EPS o una ARL.');
            if (!primerError && idEPSSelect) primerError = idEPSSelect;
            else if (!primerError && idARLSelect) primerError = idARLSelect;
        } else {
            if(idEPSSelect) clearError(idEPSSelect);
            if(idARLSelect) clearError(idARLSelect);
        }

        if (!isValid) {
            event.preventDefault();
            if (primerError) {
                primerError.focus();
            }
        }
    });

    function showError(inputElement, message) {
        inputElement.classList.add('is-invalid');
        const errorSpanId = `error-${inputElement.id}`;
        let errorSpan = document.getElementById(errorSpanId);
        if (!errorSpan) {
            errorSpan = document.createElement('span');
            errorSpan.id = errorSpanId;
            errorSpan.className = 'invalid-feedback d-block error-msg';
            inputElement.parentNode.appendChild(errorSpan);
        }
        errorSpan.textContent = message;
        errorSpan.style.display = 'block';
    }

    function clearError(inputElement) {
        inputElement.classList.remove('is-invalid');
        const errorSpan = document.getElementById(`error-${inputElement.id}`);
        if (errorSpan) {
            errorSpan.textContent = '';
            errorSpan.style.display = 'none';
        }
    }
    
    [idEPSSelect, idRegimenSelect, idARLSelect, idEstadoSelect].forEach(select => {
        if (select) {
            select.addEventListener('change', () => {
                if (select.value !== '') {
                    clearError(select);
                }
                if((select === idEPSSelect || select === idARLSelect) && (idEPSSelect.value !== '' || idARLSelect.value !== '')){
                    if(idEPSSelect.classList.contains('is-invalid') && (idEPSSelect.value !== '' || idARLSelect.value !== '')) clearError(idEPSSelect);
                    if(idARLSelect.classList.contains('is-invalid') && (idEPSSelect.value !== '' || idARLSelect.value !== '')) clearError(idARLSelect);
                }
            });
        }
    });
});