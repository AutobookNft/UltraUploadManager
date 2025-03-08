if (import.meta.env.MODE === 'development') {
    console.log('Dentro sendErrorEmail');
}

function sendErrorEmail(params) {

    fetch('/send-error-email', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(params)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            console.log(data.message);
        } else {
            console.error(data.message);
        }
    })
    .catch(error => {
        console.error('Errore durante la richiesta:', error);
    });
}

window.sendErrorEmail = sendErrorEmail;

