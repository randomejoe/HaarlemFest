const selectorBtn = document.getElementById('open-selector-btn');
const options = document.getElementById('options');

selectorBtn.addEventListener('click', () => {
options.style.display = options.style.display === 'none' ? 'flex' : 'none';
selectorBtn.classList.toggle("open", options.style.display !== "none");
});

document.querySelectorAll('[data-param]').forEach(button => {
    button.addEventListener('click', () => {
        updateParams(
            button.dataset.param,
            button.dataset.value
        );
    });
});

function updateParams(param, value) {
    const url = new URL(window.location);

    url.searchParams.set(param, value);

    window.location = url;
}