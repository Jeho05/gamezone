(function(){
  function ready(fn){ if(document.readyState!=='loading'){ fn(); } else { document.addEventListener('DOMContentLoaded', fn); } }
  function getFileName(){ try { return (location.pathname.split('/').pop() || '').toLowerCase(); } catch(e){ return ''; } }
  const helpByPage = {
    'my_invoices.html': {
      title: 'Aide - Mes Factures',
      items: [
        'Utilisez les filtres pour afficher les factures par statut (Toutes, En attente, Actives, Utilisées, Expirées).',
        'Chaque carte montre le numéro, la date, le jeu, la durée, le montant et la date d\'expiration.',
        'Bouton Afficher le QR Code pour présenter votre facture à l\'accueil.',
        'Bouton Détails pour consulter les informations complètes.',
        'Actualisation automatique toutes les 30 secondes.'
      ]
    }
  };
  function getHelp(){ const key = getFileName(); return helpByPage[key] || { title: 'Aide', items: ['Ce bouton explique la page actuelle et comment l\'utiliser.'] }; }
  function build(){
    const btn = document.createElement('button'); btn.id = 'help-widget-btn'; btn.type = 'button'; btn.title = 'Aide'; btn.textContent = '!';
    const overlay = document.createElement('div'); overlay.id = 'help-widget-overlay';
    const modal = document.createElement('div'); modal.id = 'help-widget-modal';
    const header = document.createElement('div'); header.id = 'help-widget-header';
    const title = document.createElement('h2'); title.textContent = getHelp().title;
    const close = document.createElement('button'); close.id = 'help-widget-close'; close.setAttribute('aria-label','Fermer'); close.textContent = '×';
    header.appendChild(title); header.appendChild(close);
    const list = document.createElement('ul'); list.id = 'help-widget-list';
    getHelp().items.forEach(t=>{ const li=document.createElement('li'); li.textContent=t; li.style.marginBottom='6px'; list.appendChild(li); });
    modal.appendChild(header); modal.appendChild(list); overlay.appendChild(modal);
    btn.addEventListener('click', ()=> overlay.classList.add('show'));
    close.addEventListener('click', ()=> overlay.classList.remove('show'));
    overlay.addEventListener('click', (e)=>{ if(e.target===overlay) overlay.classList.remove('show'); });
    document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') overlay.classList.remove('show'); });
    document.body.appendChild(btn); document.body.appendChild(overlay);
  }
  ready(build);
})();
