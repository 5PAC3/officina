// Elementi DOM
const tipoRicerca = document.getElementById('tipoRicerca');
const elementoSelect = document.getElementById('elementoSelect');
const cercaBtn = document.getElementById('cercaBtn');
const risultatiDiv = document.getElementById('risultati');
const listaOfficine = document.getElementById('listaOfficine');

// Carica elementi in base al tipo selezionato
async function caricaElementi(tipo) {
    try {
        let url = '';
        switch(tipo) {
            case 'servizio':
                url = 'get_servizi.php';
                break;
            case 'pezzo':
                url = 'get_pezzi.php';
                break;
            case 'accessorio':
                url = 'get_accessori.php';
                break;
        }
        
        const response = await fetch(API_BASE + url);
        const data = await response.json();
        
        elementoSelect.innerHTML = '<option value="">-- Seleziona --</option>';
        if (data.success && data.elementi) {
            data.elementi.forEach(el => {
                const option = document.createElement('option');
                option.value = el.codice;
                option.textContent = `${el.codice} - ${el.descrizione}`;
                elementoSelect.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Errore:', error);
        elementoSelect.innerHTML = '<option value="">Errore nel caricamento</option>';
    }
}

// API per ottenere i servizi
async function getServizi() {
    const response = await fetch(API_BASE + 'get_servizi.php');
    return await response.json();
}

// Funzione di ricerca officine compatibili
async function cercaOfficine() {
    const tipo = tipoRicerca.value;
    const codice = elementoSelect.value;
    
    if (!codice) {
        alert('Seleziona un elemento valido');
        return;
    }
    
    cercaBtn.disabled = true;
    cercaBtn.textContent = 'Ricerca in corso...';
    
    try {
        const response = await fetch(API_BASE + 'ricerca.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                tipo: tipo,
                codice: codice
            })
        });
        
        const data = await response.json();
        
        if (data.success && data.officine.length > 0) {
            mostraRisultati(data.officine);
        } else {
            mostraNessunRisultato();
        }
    } catch (error) {
        console.error('Errore ricerca:', error);
        alert('Errore durante la ricerca');
    } finally {
        cercaBtn.disabled = false;
        cercaBtn.textContent = 'Cerca officine compatibili';
    }
}

function mostraRisultati(officine) {
    risultatiDiv.style.display = 'block';
    listaOfficine.innerHTML = '';
    
    officine.forEach(officina => {
        const card = document.createElement('div');
        card.className = 'officina-card';
        card.innerHTML = `
            <h4>${officina.denominazione}</h4>
            <p>${officina.indirizzo}</p>
            <p>${officina.telefono || 'Non disponibile'}</p>
            <p>${officina.costo || 'Contattare per preventivo'}</p>
            ${officina.info ? `<p>${officina.info}</p>` : ''}
            <div class="badge">${officina.tipo_servizio || 'Disponibile'}</div>
        `;
        listaOfficine.appendChild(card);
    });
}

function mostraNessunRisultato() {
    risultatiDiv.style.display = 'block';
    listaOfficine.innerHTML = `
            <div style="text-align: center; padding: 40px;">
            <p>Nessuna officina trovata per la tua ricerca.</p>
            <p>Prova con un'altra selezione o contatta il supporto.</p>
        </div>
    `;
}

// Event Listeners
tipoRicerca.addEventListener('change', (e) => {
    caricaElementi(e.target.value);
});

cercaBtn.addEventListener('click', cercaOfficine);

// Inizializzazione
caricaElementi('servizio');
