// admin/admin.js
// Main JavaScript for admin panel

const API_BASE = '/projet%20ismo/api';
const ADMIN_API = `${API_BASE}/admin`;

// Authentication
let currentUser = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', async () => {
    await checkAuth();
    loadDashboard();
});

// Check if user is authenticated and is admin
async function checkAuth() {
    try {
        const response = await fetch(`${API_BASE}/auth/check.php`, {
            credentials: 'include'
        });
        
        if (!response.ok) {
            window.location.href = '/login.html';
            return;
        }
        
        const data = await response.json();
        
        if (data.role !== 'admin') {
            showAlert('Accès refusé. Vous devez être administrateur.', 'error');
            setTimeout(() => {
                window.location.href = '/';
            }, 2000);
            return;
        }
        
        currentUser = data;
        document.getElementById('userName').textContent = data.username || data.email;
        
    } catch (error) {
        console.error('Auth check failed:', error);
        window.location.href = '/login.html';
    }
}

// Logout
async function logout() {
    try {
        await fetch(`${API_BASE}/auth/logout.php`, {
            method: 'POST',
            credentials: 'include'
        });
        window.location.href = '/login.html';
    } catch (error) {
        console.error('Logout failed:', error);
    }
}

// Tab switching
function switchTab(tabName) {
    // Update tab buttons
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Update tab content
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById(tabName).classList.add('active');
    
    // Load tab data
    switch(tabName) {
        case 'dashboard':
            loadDashboard();
            break;
        case 'users':
            loadUsers();
            break;
        case 'leaderboard':
            loadLeaderboard();
            break;
        case 'events':
            loadEvents();
            break;
        case 'gallery':
            loadGallery();
            break;
        case 'tournaments':
            loadTournaments();
            break;
        case 'streams':
            loadStreams();
            break;
        case 'news':
            loadNews();
            break;
    }
}

// Alert notifications
function showAlert(message, type = 'success') {
    const alertContainer = document.getElementById('alertContainer');
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} show`;
    alert.innerHTML = `
        <strong>${type === 'error' ? '❌ Erreur' : type === 'success' ? '✅ Succès' : 'ℹ️ Info'}</strong>
        <p>${message}</p>
    `;
    
    alertContainer.appendChild(alert);
    
    setTimeout(() => {
        alert.classList.remove('show');
        setTimeout(() => alert.remove(), 300);
    }, 5000);
}

// Load Dashboard
async function loadDashboard() {
    try {
        // Load statistics from new API
        const statsRes = await fetch(`${ADMIN_API}/statistics.php`, { credentials: 'include' });
        const statsData = await statsRes.json();
        
        if (statsData.success) {
            const stats = statsData.statistics;
            
            // Update stats cards
            document.getElementById('totalUsers').textContent = stats.users.total || 0;
            document.getElementById('activeUsers').textContent = stats.users.active || 0;
            document.getElementById('totalEvents').textContent = stats.events.total || 0;
            document.getElementById('totalGallery').textContent = stats.gallery.total || 0;
            document.getElementById('totalPointsDistributed').textContent = formatNumber(stats.gamification.totalPointsDistributed || 0);
            document.getElementById('rewardsClaimed').textContent = stats.gamification.rewardsClaimed || 0;
            
            // Display top users
            displayTopUsers(statsData.topUsers || []);
            
            // Display quick stats
            displayQuickStats(stats);
            
            // Display recent events
            displayRecentEvents(statsData.recentEvents || []);
        }
        
    } catch (error) {
        console.error('Failed to load dashboard:', error);
        showAlert('Erreur lors du chargement du tableau de bord', 'error');
    }
}

function displayTopUsers(users) {
    const container = document.getElementById('topUsersTable');
    
    if (!users || users.length === 0) {
        container.innerHTML = '<div class="empty-state"><p>Aucun utilisateur</p></div>';
        return;
    }
    
    const html = `
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Rang</th>
                        <th>Utilisateur</th>
                        <th>Email</th>
                        <th>Points</th>
                        <th>Niveau</th>
                    </tr>
                </thead>
                <tbody>
                    ${users.map((user, index) => `
                        <tr>
                            <td><strong>#${index + 1}</strong></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    ${user.avatar_url ? `<img src="${user.avatar_url}" alt="Avatar" style="width: 30px; height: 30px; border-radius: 50%;">` : ''}
                                    <span>${escapeHtml(user.username)}</span>
                                </div>
                            </td>
                            <td>${escapeHtml(user.email)}</td>
                            <td><strong>${formatNumber(user.points)}</strong></td>
                            <td><span class="badge badge-info">Niv. ${user.level}</span></td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
    
    container.innerHTML = html;
}

function displayQuickStats(stats) {
    const container = document.getElementById('quickStats');
    
    const html = `
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <div style="display: flex; justify-content: space-between; padding: 0.75rem; background: var(--bg); border-radius: 0.5rem;">
                <span style="color: var(--gray); font-size: 0.875rem;">Nouveaux utilisateurs (7 jours)</span>
                <strong style="color: var(--primary);">${stats.users.new || 0}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 0.75rem; background: var(--bg); border-radius: 0.5rem;">
                <span style="color: var(--gray); font-size: 0.875rem;">Tournois</span>
                <strong style="color: var(--secondary);">${stats.events.byType?.tournament || 0}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 0.75rem; background: var(--bg); border-radius: 0.5rem;">
                <span style="color: var(--gray); font-size: 0.875rem;">Streams</span>
                <strong style="color: var(--warning);">${stats.events.byType?.stream || 0}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 0.75rem; background: var(--bg); border-radius: 0.5rem;">
                <span style="color: var(--gray); font-size: 0.875rem;">Actualités</span>
                <strong style="color: var(--success);">${stats.events.byType?.news || 0}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 0.75rem; background: var(--bg); border-radius: 0.5rem;">
                <span style="color: var(--gray); font-size: 0.875rem;">Sanctions actives</span>
                <strong style="color: var(--danger);">${stats.gamification.activeSanctions || 0}</strong>
            </div>
        </div>
    `;
    
    container.innerHTML = html;
}

function displayRecentEvents(events) {
    const container = document.getElementById('recentEventsTable');
    
    if (!events || events.length === 0) {
        container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">📭</div><p>Aucun événement récent</p></div>';
        return;
    }
    
    const html = `
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Créé par</th>
                    </tr>
                </thead>
                <tbody>
                    ${events.map(event => `
                        <tr>
                            <td>${escapeHtml(event.title)}</td>
                            <td><span class="badge badge-info">${getTypeLabel(event.type)}</span></td>
                            <td>${formatDate(event.date)}</td>
                            <td>${getStatusBadge(event.status)}</td>
                            <td>${escapeHtml(event.creator_username || 'Admin')}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
    
    container.innerHTML = html;
}

// Load Events
async function loadEvents(type = null) {
    try {
        showLoading('eventsTable');
        
        const url = type ? `${ADMIN_API}/events.php?type=${type}` : `${ADMIN_API}/events.php`;
        const response = await fetch(url, { credentials: 'include' });
        const data = await response.json();
        
        displayEvents(data.events || []);
        
    } catch (error) {
        console.error('Failed to load events:', error);
        showAlert('Erreur lors du chargement des événements', 'error');
    }
}

function displayEvents(events) {
    const container = document.getElementById('eventsTable');
    
    if (!events || events.length === 0) {
        container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">📅</div><p>Aucun événement</p></div>';
        return;
    }
    
    const html = `
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titre</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Participants</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${events.map(event => `
                        <tr>
                            <td>#${event.id}</td>
                            <td>${escapeHtml(event.title)}</td>
                            <td><span class="badge badge-info">${getTypeLabel(event.type)}</span></td>
                            <td>${formatDate(event.date)}</td>
                            <td>${getStatusBadge(event.status)}</td>
                            <td>${event.participants || '-'}</td>
                            <td class="table-actions">
                                <button class="btn btn-primary" onclick="editEvent(${event.id})" style="font-size: 0.75rem;">✏️ Modifier</button>
                                <button class="btn btn-danger" onclick="deleteEvent(${event.id})" style="font-size: 0.75rem;">🗑️</button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
    
    container.innerHTML = html;
}

// Event Modal
function openEventModal(eventId = null) {
    document.getElementById('eventModal').classList.add('show');
    document.getElementById('eventForm').reset();
    document.getElementById('eventId').value = '';
    document.getElementById('eventImagePreview').classList.remove('show');
    
    if (eventId) {
        loadEventData(eventId);
        document.getElementById('eventModalTitle').textContent = 'Modifier l\'événement';
    } else {
        document.getElementById('eventModalTitle').textContent = 'Nouvel événement';
    }
}

function closeEventModal() {
    document.getElementById('eventModal').classList.remove('show');
}

async function loadEventData(id) {
    try {
        const response = await fetch(`${ADMIN_API}/events.php?id=${id}`, {
            credentials: 'include'
        });
        const event = await response.json();
        
        document.getElementById('eventId').value = event.id;
        document.getElementById('eventTitle').value = event.title;
        document.getElementById('eventDate').value = event.date;
        document.getElementById('eventType').value = event.type;
        document.getElementById('eventStatus').value = event.status;
        document.getElementById('eventDescription').value = event.description || '';
        document.getElementById('eventContent').value = event.content || '';
        document.getElementById('eventParticipants').value = event.participants || '';
        document.getElementById('eventWinner').value = event.winner || '';
        document.getElementById('eventImageUrl').value = event.image_url || '';
        
        if (event.image_url) {
            const preview = document.getElementById('eventImagePreview');
            preview.src = event.image_url;
            preview.classList.add('show');
        }
        
    } catch (error) {
        console.error('Failed to load event:', error);
        showAlert('Erreur lors du chargement de l\'événement', 'error');
    }
}

async function saveEvent(e) {
    e.preventDefault();
    
    const id = document.getElementById('eventId').value;
    const data = {
        title: document.getElementById('eventTitle').value,
        date: document.getElementById('eventDate').value,
        type: document.getElementById('eventType').value,
        status: document.getElementById('eventStatus').value,
        description: document.getElementById('eventDescription').value,
        content: document.getElementById('eventContent').value,
        participants: document.getElementById('eventParticipants').value || null,
        winner: document.getElementById('eventWinner').value || null,
        image_url: document.getElementById('eventImageUrl').value || null
    };
    
    try {
        const url = id ? `${ADMIN_API}/events.php?id=${id}` : `${ADMIN_API}/events.php`;
        const method = id ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(id ? { ...data, id } : data)
        });
        
        const result = await response.json();
        
        if (response.ok) {
            showAlert(id ? 'Événement mis à jour avec succès' : 'Événement créé avec succès', 'success');
            closeEventModal();
            loadEvents();
            loadDashboard();
        } else {
            showAlert(result.error || 'Erreur lors de la sauvegarde', 'error');
        }
        
    } catch (error) {
        console.error('Failed to save event:', error);
        showAlert('Erreur lors de la sauvegarde', 'error');
    }
}

async function editEvent(id) {
    openEventModal(id);
}

async function deleteEvent(id) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cet événement ?')) {
        return;
    }
    
    try {
        const response = await fetch(`${ADMIN_API}/events.php?id=${id}`, {
            method: 'DELETE',
            credentials: 'include'
        });
        
        const result = await response.json();
        
        if (response.ok) {
            showAlert('Événement supprimé avec succès', 'success');
            loadEvents();
            loadDashboard();
        } else {
            showAlert(result.error || 'Erreur lors de la suppression', 'error');
        }
        
    } catch (error) {
        console.error('Failed to delete event:', error);
        showAlert('Erreur lors de la suppression', 'error');
    }
}

// Load Gallery
async function loadGallery() {
    try {
        showLoading('galleryGrid');
        
        const response = await fetch(`${ADMIN_API}/gallery.php`, {
            credentials: 'include'
        });
        const data = await response.json();
        
        displayGallery(data.items || []);
        
    } catch (error) {
        console.error('Failed to load gallery:', error);
        showAlert('Erreur lors du chargement de la galerie', 'error');
    }
}

function displayGallery(items) {
    const container = document.getElementById('galleryGrid');
    
    if (!items || items.length === 0) {
        container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">🖼️</div><p>Aucune image dans la galerie</p></div>';
        return;
    }
    
    const html = `
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem;">
            ${items.map(item => `
                <div style="background: white; border-radius: 0.75rem; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <img src="${item.thumbnail_url || item.image_url}" alt="${escapeHtml(item.title)}" 
                         style="width: 100%; height: 200px; object-fit: cover;">
                    <div style="padding: 1rem;">
                        <h4 style="margin: 0 0 0.5rem 0; font-size: 1rem;">${escapeHtml(item.title)}</h4>
                        <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <span class="badge badge-info">${getCategoryLabel(item.category)}</span>
                            ${item.status === 'active' ? '<span class="badge badge-success">Actif</span>' : '<span class="badge badge-secondary">Archivé</span>'}
                        </div>
                        <p style="font-size: 0.875rem; color: var(--gray); margin-bottom: 1rem;">${escapeHtml(item.description || '').substring(0, 80)}${item.description?.length > 80 ? '...' : ''}</p>
                        <div style="display: flex; gap: 0.5rem;">
                            <button class="btn btn-primary" onclick="editGalleryItem(${item.id})" style="font-size: 0.75rem; padding: 0.375rem 0.75rem;">✏️ Modifier</button>
                            <button class="btn btn-danger" onclick="deleteGalleryItem(${item.id})" style="font-size: 0.75rem; padding: 0.375rem 0.75rem;">🗑️</button>
                        </div>
                    </div>
                </div>
            `).join('')}
        </div>
    `;
    
    container.innerHTML = html;
}

// Gallery Modal
function openGalleryModal(itemId = null) {
    document.getElementById('galleryModal').classList.add('show');
    document.getElementById('galleryForm').reset();
    document.getElementById('galleryId').value = '';
    document.getElementById('galleryImagePreview').classList.remove('show');
    
    if (itemId) {
        loadGalleryItemData(itemId);
        document.getElementById('galleryModalTitle').textContent = 'Modifier l\'image';
    } else {
        document.getElementById('galleryModalTitle').textContent = 'Ajouter une image';
    }
}

function closeGalleryModal() {
    document.getElementById('galleryModal').classList.remove('show');
}

async function loadGalleryItemData(id) {
    try {
        const response = await fetch(`${ADMIN_API}/gallery.php?id=${id}`, {
            credentials: 'include'
        });
        const item = await response.json();
        
        document.getElementById('galleryId').value = item.id;
        document.getElementById('galleryTitle').value = item.title;
        document.getElementById('galleryDescription').value = item.description || '';
        document.getElementById('galleryCategory').value = item.category;
        document.getElementById('galleryStatus').value = item.status;
        document.getElementById('galleryOrder').value = item.display_order;
        document.getElementById('galleryImageUrl').value = item.image_url;
        
        if (item.image_url) {
            const preview = document.getElementById('galleryImagePreview');
            preview.src = item.image_url;
            preview.classList.add('show');
        }
        
    } catch (error) {
        console.error('Failed to load gallery item:', error);
        showAlert('Erreur lors du chargement de l\'image', 'error');
    }
}

async function saveGalleryItem(e) {
    e.preventDefault();
    
    const id = document.getElementById('galleryId').value;
    const data = {
        title: document.getElementById('galleryTitle').value,
        description: document.getElementById('galleryDescription').value,
        category: document.getElementById('galleryCategory').value,
        status: document.getElementById('galleryStatus').value,
        display_order: parseInt(document.getElementById('galleryOrder').value),
        image_url: document.getElementById('galleryImageUrl').value
    };
    
    if (!data.image_url) {
        showAlert('Veuillez uploader une image', 'error');
        return;
    }
    
    try {
        const url = id ? `${ADMIN_API}/gallery.php?id=${id}` : `${ADMIN_API}/gallery.php`;
        const method = id ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(id ? { ...data, id } : data)
        });
        
        const result = await response.json();
        
        if (response.ok) {
            showAlert(id ? 'Image mise à jour avec succès' : 'Image ajoutée avec succès', 'success');
            closeGalleryModal();
            loadGallery();
            loadDashboard();
        } else {
            showAlert(result.error || 'Erreur lors de la sauvegarde', 'error');
        }
        
    } catch (error) {
        console.error('Failed to save gallery item:', error);
        showAlert('Erreur lors de la sauvegarde', 'error');
    }
}

async function editGalleryItem(id) {
    openGalleryModal(id);
}

async function deleteGalleryItem(id) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette image ?')) {
        return;
    }
    
    try {
        const response = await fetch(`${ADMIN_API}/gallery.php?id=${id}`, {
            method: 'DELETE',
            credentials: 'include'
        });
        
        const result = await response.json();
        
        if (response.ok) {
            showAlert('Image supprimée avec succès', 'success');
            loadGallery();
            loadDashboard();
        } else {
            showAlert(result.error || 'Erreur lors de la suppression', 'error');
        }
        
    } catch (error) {
        console.error('Failed to delete gallery item:', error);
        showAlert('Erreur lors de la suppression', 'error');
    }
}

// Image upload handler
async function handleImageUpload(input, previewId, urlInputId) {
    const file = input.files[0];
    if (!file) return;
    
    // Show preview
    const reader = new FileReader();
    reader.onload = (e) => {
        const preview = document.getElementById(previewId);
        preview.src = e.target.result;
        preview.classList.add('show');
    };
    reader.readAsDataURL(file);
    
    // Upload to server
    try {
        const formData = new FormData();
        formData.append('image', file);
        
        const response = await fetch(`${ADMIN_API}/upload.php`, {
            method: 'POST',
            credentials: 'include',
            body: formData
        });
        
        const result = await response.json();
        
        if (response.ok) {
            document.getElementById(urlInputId).value = result.image_url;
            showAlert('Image uploadée avec succès', 'success');
        } else {
            showAlert(result.error || 'Erreur lors de l\'upload', 'error');
        }
        
    } catch (error) {
        console.error('Upload failed:', error);
        showAlert('Erreur lors de l\'upload de l\'image', 'error');
    }
}

// Load Tournaments
async function loadTournaments() {
    loadEvents('tournament');
}

function openTournamentModal() {
    openEventModal();
    document.getElementById('eventType').value = 'tournament';
    document.getElementById('eventModalTitle').textContent = 'Nouveau Tournoi';
}

// Load Streams
async function loadStreams() {
    loadEvents('stream');
}

function openStreamModal() {
    openEventModal();
    document.getElementById('eventType').value = 'stream';
    document.getElementById('eventModalTitle').textContent = 'Nouveau Stream';
}

// Load News
async function loadNews() {
    loadEvents('news');
}

function openNewsModal() {
    openEventModal();
    document.getElementById('eventType').value = 'news';
    document.getElementById('eventModalTitle').textContent = 'Nouvelle Actualité';
}

// Load Users
let userSearchTimeout = null;
async function loadUsers() {
    try {
        showLoading('usersTable');
        
        const status = document.getElementById('userStatusFilter')?.value || '';
        const search = document.getElementById('userSearch')?.value || '';
        
        let url = `${ADMIN_API}/users.php?limit=50`;
        if (status) url += `&status=${status}`;
        if (search) url += `&search=${encodeURIComponent(search)}`;
        
        const response = await fetch(url, { credentials: 'include' });
        const data = await response.json();
        
        displayUsers(data.users || []);
        
    } catch (error) {
        console.error('Failed to load users:', error);
        showAlert('Erreur lors du chargement des utilisateurs', 'error');
    }
}

function searchUsers() {
    clearTimeout(userSearchTimeout);
    userSearchTimeout = setTimeout(() => {
        loadUsers();
    }, 300);
}

function displayUsers(users) {
    const container = document.getElementById('usersTable');
    
    if (!users || users.length === 0) {
        container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">👥</div><p>Aucun utilisateur trouvé</p></div>';
        return;
    }
    
    const html = `
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Utilisateur</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Points</th>
                        <th>Niveau</th>
                        <th>Statut</th>
                        <th>Sanctions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${users.map(user => `
                        <tr>
                            <td>#${user.id}</td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    ${user.avatar_url ? `<img src="${user.avatar_url}" alt="Avatar" style="width: 30px; height: 30px; border-radius: 50%;">` : ''}
                                    <span>${escapeHtml(user.username)}</span>
                                </div>
                            </td>
                            <td>${escapeHtml(user.email)}</td>
                            <td><span class="badge ${user.role === 'admin' ? 'badge-danger' : 'badge-info'}">${user.role}</span></td>
                            <td><strong>${formatNumber(user.points)}</strong></td>
                            <td><span class="badge badge-secondary">Niv. ${user.level}</span></td>
                            <td>${getUserStatusBadge(user.status)}</td>
                            <td>${user.active_sanctions > 0 ? `<span class="badge badge-warning">${user.active_sanctions}</span>` : '-'}</td>
                            <td class="table-actions">
                                <button class="btn btn-primary" onclick="viewUser(${user.id})">👁️ Voir</button>
                                <button class="btn btn-secondary" onclick="editUserStatus(${user.id}, '${user.status}')">✏️</button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
    
    container.innerHTML = html;
}

function getUserStatusBadge(status) {
    const badges = {
        'active': '<span class="badge badge-success">Actif</span>',
        'inactive': '<span class="badge badge-secondary">Inactif</span>',
        'banned': '<span class="badge badge-danger">Banni</span>',
        'suspended': '<span class="badge badge-warning">Suspendu</span>'
    };
    return badges[status] || `<span class="badge badge-info">${status}</span>`;
}

function viewUser(userId) {
    window.open(`/admin/user-details.html?id=${userId}`, '_blank');
}

async function editUserStatus(userId, currentStatus) {
    const newStatus = prompt(`Changer le statut de l'utilisateur (actuel: ${currentStatus})\n\nOptions: active, inactive, banned, suspended`, currentStatus);
    
    if (!newStatus || newStatus === currentStatus) return;
    
    try {
        const response = await fetch(`${ADMIN_API}/users.php?id=${userId}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ id: userId, status: newStatus })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            showAlert('Statut mis à jour avec succès', 'success');
            loadUsers();
        } else {
            showAlert(result.error || 'Erreur lors de la mise à jour', 'error');
        }
        
    } catch (error) {
        console.error('Failed to update user status:', error);
        showAlert('Erreur lors de la mise à jour', 'error');
    }
}

// Load Leaderboard
async function loadLeaderboard() {
    try {
        showLoading('leaderboardTable');
        
        const period = document.getElementById('leaderboardPeriod')?.value || 'weekly';
        const response = await fetch(`${API_BASE}/leaderboard/index.php?period=${period}&limit=50`, {
            credentials: 'include'
        });
        const data = await response.json();
        
        displayLeaderboard(data.items || [], data.period);
        
    } catch (error) {
        console.error('Failed to load leaderboard:', error);
        showAlert('Erreur lors du chargement du classement', 'error');
    }
}

function displayLeaderboard(items, period) {
    const container = document.getElementById('leaderboardTable');
    
    if (!items || items.length === 0) {
        container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">🏅</div><p>Aucun classement disponible</p></div>';
        return;
    }
    
    const periodLabel = {
        'weekly': 'Cette semaine',
        'monthly': 'Ce mois',
        'all': 'Tous les temps'
    }[period] || period;
    
    const html = `
        <div style="margin-bottom: 1rem; padding: 1rem; background: var(--bg); border-radius: 0.5rem;">
            <strong>Période:</strong> ${periodLabel} | <strong>Total:</strong> ${items.length} joueurs
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Rang</th>
                        <th>Joueur</th>
                        <th>Points</th>
                        <th>Médaille</th>
                    </tr>
                </thead>
                <tbody>
                    ${items.map(item => `
                        <tr ${item.isCurrentUser ? 'style="background: rgba(99, 102, 241, 0.1);"' : ''}>
                            <td><strong style="font-size: 1.1rem;">#${item.rank}</strong></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    ${item.avatar_url ? `<img src="${item.avatar_url}" alt="Avatar" style="width: 35px; height: 35px; border-radius: 50%;">` : ''}
                                    <span style="font-weight: 500;">${escapeHtml(item.username)}</span>
                                    ${item.isCurrentUser ? '<span class="badge badge-info">Vous</span>' : ''}
                                </div>
                            </td>
                            <td><strong style="color: var(--primary); font-size: 1.1rem;">${formatNumber(item.points)}</strong></td>
                            <td>${getRankMedal(item.rank)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
    
    container.innerHTML = html;
}

function getRankMedal(rank) {
    if (rank === 1) return '<span style="font-size: 1.5rem;">🥇</span>';
    if (rank === 2) return '<span style="font-size: 1.5rem;">🥈</span>';
    if (rank === 3) return '<span style="font-size: 1.5rem;">🥉</span>';
    if (rank <= 10) return '<span class="badge badge-warning">Top 10</span>';
    return '-';
}

function formatNumber(num) {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
    return num.toString();
}

// Utility functions
function showLoading(containerId) {
    const container = document.getElementById(containerId);
    container.innerHTML = '<div class="loading"><div class="spinner"></div><p>Chargement...</p></div>';
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', { year: 'numeric', month: 'long', day: 'numeric' });
}

function getTypeLabel(type) {
    const labels = {
        'event': 'Événement',
        'tournament': 'Tournoi',
        'stream': 'Stream',
        'news': 'Actualité'
    };
    return labels[type] || type;
}

function getCategoryLabel(category) {
    const labels = {
        'general': 'Général',
        'tournament': 'Tournoi',
        'event': 'Événement',
        'stream': 'Stream',
        'vr': 'VR',
        'retro': 'Rétro'
    };
    return labels[category] || category;
}

function getStatusBadge(status) {
    const badges = {
        'draft': '<span class="badge badge-secondary">Brouillon</span>',
        'published': '<span class="badge badge-success">Publié</span>',
        'archived': '<span class="badge badge-warning">Archivé</span>',
        'active': '<span class="badge badge-success">Actif</span>'
    };
    return badges[status] || `<span class="badge badge-info">${status}</span>`;
}

// Auto-refresh dashboard every 30 seconds
setInterval(() => {
    const activeTab = document.querySelector('.tab-content.active');
    if (activeTab && activeTab.id === 'dashboard') {
        loadDashboard();
    }
}, 30000);
