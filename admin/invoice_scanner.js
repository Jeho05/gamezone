const API_BASE = '/projet%20ismo/api';
let currentInvoice = null;
let currentSession = null;

function getScanHistory() {
    return JSON.parse(localStorage.getItem('scanHistory') || '[]');
}

function addToHistory(code, result, timestamp) {
    const history = getScanHistory();
    history.unshift({ code, result, timestamp });
    if (history.length > 10) history.pop();
    localStorage.setItem('scanHistory', JSON.stringify(history));
    displayHistory();
}

function displayHistory() {
    const history = getScanHistory();
    const container = document.getElementById('scanHistory');
    
    if (history.length === 0) {
        container.style.display = 'none';
        return;
    }
    
    container.style.display = 'block';
    const items = history.map(item => `
        <div class="history-item">
            <div>
                <div class="history-code">${item.code}</div>
                <div class="history-time">${new Date(item.timestamp).toLocaleString('fr-FR')}</div>
            </div>
            <span class="status-badge status-${item.result}">${item.result}</span>
        </div>
    `).join('');
    
    container.innerHTML = '<h3 style="color: white; margin-bottom: 15px;">📋 Historique des Scans</h3>' + items;
}

async function scanInvoice() {
    const code = document.getElementById('validationCode').value.trim().toUpperCase();
    
    if (!code || code.length !== 16) {
        showResult('error', 'Code invalide', 'Le code doit contenir exactement 16 caractères');
        return;
    }
    
    showLoading(true);
    hideResult();
    
    try {
        const response = await fetch(`${API_BASE}/admin/scan_invoice.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ validation_code: code })
        });
        
        const data = await response.json();
        showLoading(false);
        
        if (data.success) {
            currentInvoice = data.invoice;
            currentSession = { id: data.invoice.session_id };
            
            showResult('success', '✅ Facture Activée !', data.message);
            displayInvoiceDetails(data.invoice);
            displaySessionActions();
            addToHistory(code, 'success', new Date().toISOString());
            
            document.getElementById('validationCode').value = '';
        } else {
            showResult('error', '❌ Échec', data.message);
            addToHistory(code, 'error', new Date().toISOString());
        }
        
    } catch (error) {
        showLoading(false);
        showResult('error', '❌ Erreur', 'Erreur de connexion au serveur');
        console.error(error);
    }
}

function displayInvoiceDetails(invoice) {
    const details = `
        <div class="invoice-details">
            <div class="detail-row">
                <span class="detail-label">Numéro de Facture:</span>
                <span class="detail-value">${invoice.invoice_number}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Joueur:</span>
                <span class="detail-value">${invoice.username} (${invoice.email})</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Jeu:</span>
                <span class="detail-value">${invoice.game_name}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Durée:</span>
                <span class="detail-value"><strong>${invoice.duration_minutes} minutes</strong></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Montant:</span>
                <span class="detail-value"><strong>${invoice.amount} ${invoice.currency}</strong></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Statut Session:</span>
                <span class="detail-value">
                    <span class="status-badge status-${invoice.session_status}">${invoice.session_status}</span>
                </span>
            </div>
        </div>
    `;
    document.getElementById('invoiceDetails').innerHTML = details;
}

function displaySessionActions() {
    const actions = `
        <button class="btn btn-success" onclick="startSession()">▶️ Démarrer la Session</button>
        <button class="btn btn-danger" onclick="resetScanner()">🔄 Scanner un Autre Code</button>
    `;
    document.getElementById('actions').innerHTML = actions;
}

async function startSession() {
    if (!currentSession) return;
    
    showLoading(true);
    
    try {
        const response = await fetch(`${API_BASE}/admin/manage_session.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ 
                session_id: currentSession.id,
                action: 'start'
            })
        });
        
        const data = await response.json();
        showLoading(false);
        
        if (data.success) {
            showResult('success', '🎮 Session Démarrée !', 'Le joueur peut maintenant commencer à jouer.');
            document.getElementById('actions').innerHTML = `
                <button class="btn btn-danger" onclick="resetScanner()">✅ Terminer & Scanner un Nouveau Code</button>
            `;
        } else {
            showResult('error', '❌ Erreur', data.error || 'Impossible de démarrer la session');
        }
        
    } catch (error) {
        showLoading(false);
        showResult('error', '❌ Erreur', 'Erreur de connexion au serveur');
    }
}

function showResult(type, title, message) {
    const card = document.getElementById('resultCard');
    card.className = `result-card ${type}`;
    document.getElementById('resultTitle').textContent = title;
    document.getElementById('resultMessage').textContent = message;
}

function hideResult() {
    document.getElementById('resultCard').style.display = 'none';
}

function showLoading(show) {
    const loading = document.getElementById('loading');
    const btn = document.getElementById('scanBtn');
    loading.classList.toggle('active', show);
    btn.disabled = show;
}

function resetScanner() {
    currentInvoice = null;
    currentSession = null;
    hideResult();
    document.getElementById('invoiceDetails').innerHTML = '';
    document.getElementById('actions').innerHTML = '';
    document.getElementById('validationCode').value = '';
    document.getElementById('validationCode').focus();
}

document.getElementById('scanBtn').addEventListener('click', scanInvoice);
document.getElementById('validationCode').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') scanInvoice();
});

displayHistory();
