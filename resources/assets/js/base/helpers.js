export function inIframe() {
    try {
        return window.self !== window.top;
    } catch (e) {
        return true;
    }
}

/**
 * 
 * @param {HTMLFormElement} form 
 * @returns {FomData}
 */
export function getFormData(form) {
    let data = new FormData(form);

    form.querySelectorAll('input[type="checkbox"]').forEach((element) => {
        if (element.name && element.checked === false) {
            if (element.value === '1') {
                data.append(element.name, '0');
            } else if (element.value === '0') {
                data.append(element.name, '1');
            } else if (element.value === 'true') {
                data.append(element.name, 'false');
            } else if (element.value === 'false') {
                data.append(element.name, 'true');
            }
        }
    });

    return data;
}