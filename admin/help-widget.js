(function(){
  function ready(fn){ if(document.readyState!=='loading'){ fn(); } else { document.addEventListener('DOMContentLoaded', fn); } }

  function getFileName(){
    try { return (location.pathname.split('/').pop() || '').toLowerCase(); } catch(e){ return ''; }
  }

  const helpByPage = {
    'index.html': {
      title: 'Aide - Tableau de bord',
      items: [
        'Cartes de statistiques: utilisateurs, événements, points, galerie, sanctions.',
        'Top utilisateurs: classement avec points et niveaux.',
        'Événements récents: type, date, statut, participants.',
        'Bouton Actualiser: recharge les données en direct.',
        'Bouton Déconnexion: termine la session administrateur.'
      ]
    },
    'dashboard_v2.html': {
      title: 'Aide - Tableau de bord v2',
      items: [
        'Cartes chiffrées (Utilisateurs, Actifs, Événements, Points, Galerie, Sanctions).',
        'Top 5 Utilisateurs: tri par points, niveaux visibles.',
        'Événements récents: type, date, nombre de participants.',
        'Actualisation automatique toutes les 30 secondes.'
      ]
    },
    'login.html': {
      title: 'Aide - Connexion Admin',
      items: [
        'Entrez votre email et mot de passe administrateur.',
        'En cas d\'erreur, vérifiez les identifiants ou les droits admin.',
        'Une session existante redirige automatiquement vers le dashboard.'
      ]
    },
    'login_v2.html': {
      title: 'Aide - Connexion Admin v2',
      items: [
        'Renseignez les identifiants administrateur puis cliquez Se connecter.',
        'Si vous êtes déjà connecté, redirection automatique vers le dashboard.',
        'En cas d\'échec, vérifiez l\'API et les informations saisies.'
      ]
    },
    'content_manager.html': {
      title: 'Aide - Gestion de Contenu',
      items: [
        'Onglets: Actualités, Événements, Galerie, Streams.',
        'Bouton + Créer pour ajouter un élément selon le type sélectionné.',
        'Champs spécifiques selon le type (date/lieu pour événement, URL pour stream...).',
        'Actions: Modifier, Supprimer. Statuts: Publié/Brouillon, Épinglé.'
      ]
    },
    'game_packages_manager.html': {
      title: 'Aide - Packages de Jeux',
      items: [
        'Filtre par jeu et liste des packages (durée, prix, points, statut).',
        '+ Créer un Package pour ajouter une offre (durée, prix, points, promo).',
        'Modifier/Supprimer via les actions. Champs promotionnels optionnels.'
      ]
    },
    'game_shop_manager.html': {
      title: 'Aide - Boutique',
      items: [
        'Onglets: Jeux, Packages, Paiements, Achats.',
        'Jeux: gestion des fiches (status, catégorie, prix/points).',
        'Packages: offres par jeu, durée/prix/points.',
        'Paiements: méthodes actives/suspendues. Achats: filtrage et confirmation.'
      ]
    },
    'invoice_scanner.html': {
      title: 'Aide - Scanner de Factures',
      items: [
        'Saisissez le code de validation (16 caractères).',
        'Cliquez sur Scanner & Activer pour vérifier la facture.',
        'Consultez le résultat (statut, détails, actions) et l\'historique des scans.'
      ]
    },
    'test_dashboard.html': {
      title: 'Aide - Test Dashboard',
      items: [
        'Page de test pour valider la connectivité et les endpoints.',
        'Utilisez-la pour diagnostiquer rapidement l\'API admin/statistics.'
      ]
    }
  };

  function getHelp(){
    const key = getFileName();
    return helpByPage[key] || {
      title: 'Aide',
      items: [
        'Ce bouton explique les éléments de la page et comment les utiliser.',
        'Selon la page, vous verrez des conseils spécifiques.'
      ]
    };
  }

  function build(){
    const btn = document.createElement('button');
    btn.id = 'help-widget-btn';
    btn.type = 'button';
    btn.title = 'Aide';
    btn.textContent = '!';

    const overlay = document.createElement('div');
    overlay.id = 'help-widget-overlay';

    const modal = document.createElement('div');
    modal.id = 'help-widget-modal';

    const header = document.createElement('div');
    header.id = 'help-widget-header';

    const title = document.createElement('h2');
    title.textContent = getHelp().title;

    const close = document.createElement('button');
    close.id = 'help-widget-close';
    close.setAttribute('aria-label','Fermer');
    close.textContent = '×';

    header.appendChild(title);
    header.appendChild(close);

    const list = document.createElement('ul');
    list.id = 'help-widget-list';
    getHelp().items.forEach(t => {
      const li = document.createElement('li');
      li.textContent = t;
      li.style.marginBottom = '6px';
      list.appendChild(li);
    });

    modal.appendChild(header);
    modal.appendChild(list);
    overlay.appendChild(modal);

    btn.addEventListener('click', ()=> overlay.classList.add('show'));
    close.addEventListener('click', ()=> overlay.classList.remove('show'));
    overlay.addEventListener('click', (e)=>{ if(e.target===overlay) overlay.classList.remove('show'); });
    document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') overlay.classList.remove('show'); });

    document.body.appendChild(btn);
    document.body.appendChild(overlay);
  }

  ready(build);
})();
