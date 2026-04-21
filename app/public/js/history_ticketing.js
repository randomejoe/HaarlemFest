const dateSelect = document.getElementById('dateSelect');
const timeSelect = document.getElementById('timeSelect');
const languageSelect = document.getElementById('languageSelect');
const tourSelect = document.getElementById('tourSelect');
const ticketCount = document.getElementById('ticketCount');
const familyTicketSelect = document.getElementById('familyTicket')

const locationField = document.getElementById('location');
const dateTimeField = document.getElementById('dateTime');
const languageField = document.getElementById('language');
const typeField = document.getElementById('type');
const ticketField = document.getElementById('tickets');
const priceField = document.getElementById('price');

const eventIdField = document.getElementById('eventId');
const submitButton = document.getElementById('submitButton');

populate(dateSelect, Object.keys(schedule));

function populate(select, values) {
    select.replaceChildren();
    if (values != null) {
        values = ['', ...values]
    }
    else {
        values = [];
    }
    
    values.forEach(v => {
        const opt = document.createElement('option');
        opt.value = v;
        opt.textContent = v;
        select.appendChild(opt);
    });

    select.disabled = values.length === 0;
    updateTicketText();
}

function updateTicketText() {
    const date = dateSelect.value;
    const time = timeSelect.value;
    const language = languageSelect.value;
    const tour = tourSelect.value;

    dateTimeText = `${date || ''} ${time || ''}`;
    if (dateTimeText == ' ') {
        dateTimeText = "Choose a date";
    }
    dateTimeField.textContent = dateTimeText;

    languageField.textContent = `${language || 'Choose a language'}`

    ticketField.textContent = ticketCount.value;

    typeField.textContent = familyTicket.checked ? 'Family ticket' : 'Single ticket';

    if (familyTicket.checked) {
        priceField.textContent = (familyTicketPrice * Math.ceil(ticketCount.value/4)).toFixed(2);
    }
    else {
        priceField.textContent = (singleTicketPrice * ticketCount.value).toFixed(2);
    }

    if (tourSelect.options.length == 2) {
        tourSelect.value = 1;
        tourSelect.disabled = true;
    }
    // 1 option is empty option
    if (tourSelect.options.length == 1) {
        tourSelect.selectedIndex = 0;
    }

    if (date && time && language && tour) {
        eventIdField.value = schedule[date][time][language][tour-1].id;
        submitButton.disabled = false;
    }
    else {
        eventIdField.value = null;
        submitButton.disabled = true;
    }
}

dateSelect.addEventListener('change', () => {
    const selectedDate = dateSelect.value;
    if (selectedDate != '') {
        populate(timeSelect, Object.keys(schedule[selectedDate]));
    }
    else {
        populate(timeSelect, null);
    }
    populate(languageSelect, null);
    populate(tourSelect, null);
});
timeSelect.addEventListener('change', () => {
    const selectedDate = dateSelect.value;
    const selectedTime = timeSelect.value;
    if (selectedTime != '') {
        populate(languageSelect, Object.keys(schedule[selectedDate][selectedTime]));
    }
    else {
        populate(languageSelect, null);
    }
    populate(tourSelect, null);
});
languageSelect.addEventListener('change', () => {
    const selectedDate = dateSelect.value;
    const selectedTime = timeSelect.value;
    const selectedLanguage = languageSelect.value;

    if (selectedLanguage != '') {
        populate(tourSelect, Array.from({length: schedule[selectedDate][selectedTime][selectedLanguage].length}, (_, i) => i + 1));
    }
    else {
        populate(tourSelect, null);
    }
    updateTicketText();
});
ticketCount.addEventListener('change', () => {
    updateTicketText();
});
familyTicketSelect.addEventListener('change', () => {
    updateTicketText();
});
tourSelect.addEventListener('change', () => {
    updateTicketText();
})