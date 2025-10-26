'use client';
import React, { useState, useEffect } from 'react';
import Navigation from '../../../components/Navigation';
import { 
  Gamepad2, 
  Package, 
  CreditCard, 
  ShoppingCart,
  Plus,
  Edit,
  Trash2,
  Eye,
  Search,
  Filter,
  CheckCircle,
  XCircle,
  DollarSign,
  Calendar
} from 'lucide-react';
import API_BASE from '../../../utils/apiBase';
import { toast } from 'sonner';

export default function AdminShop() {
  const [activeTab, setActiveTab] = useState('games');
  const [games, setGames] = useState([]);
  const [packages, setPackages] = useState([]);
  const [paymentMethods, setPaymentMethods] = useState([]);
  const [purchases, setPurchases] = useState([]);
  const [reservations, setReservations] = useState([]);
  const [loading, setLoading] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [showGameModal, setShowGameModal] = useState(false);
  const [editingGame, setEditingGame] = useState(null);
  const [showPackageModal, setShowPackageModal] = useState(false);
  const [editingPackage, setEditingPackage] = useState(null);
  const [showPaymentModal, setShowPaymentModal] = useState(false);
  const [editingPayment, setEditingPayment] = useState(null);
  const [packageForm, setPackageForm] = useState({
    game_id: '',
    name: '',
    duration_minutes: 60,
    price: 0,
    original_price: null,
    points_earned: 0,
    bonus_multiplier: 1.0,
    is_promotional: false,
    promotional_label: '',
    max_purchases_per_user: null,
    is_active: true,
    display_order: 0
  });
  const [paymentForm, setPaymentForm] = useState({
    name: '',
    description: '',
    provider: 'manual',
    fee_percentage: 0,
    fee_fixed: 0,
    is_active: true,
    auto_confirm: false,
    requires_online_payment: false,
    display_order: 0
  });
  const [gameForm, setGameForm] = useState({
    name: '',
    slug: '',
    description: '',
    short_description: '',
    image_url: '',
    thumbnail_url: '',
    category: 'action',
    platform: '',
    min_players: 1,
    max_players: 1,
    age_rating: '',
    points_per_hour: 10,
    base_price: 0,
    is_reservable: false,
    reservation_fee: 0,
    is_featured: false
  });
  const [submitting, setSubmitting] = useState(false);

  // Helpers: formatters for UI
  const formatPriceXOF = (value) => {
    const n = Number(value ?? 0);
    try {
      return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(n);
    } catch {
      return `${n.toLocaleString('fr-FR')} XOF`;
    }
  };

  // Load all data
  useEffect(() => {
    loadGames();
    loadPackages();
    loadPaymentMethods();
    loadPurchases();
    loadReservations();
  }, []);

  const loadGames = async () => {
    try {
      setLoading(true);
      const res = await fetch(`${API_BASE}/admin/games.php`, { credentials: 'include' });
      const data = await res.json();
      if (data.games) {
        setGames(data.games);
      }
    } catch (err) {
      console.error('Erreur chargement jeux:', err);
      toast.error('Erreur chargement jeux');
    } finally {
      setLoading(false);
    }
  };

  const loadPackages = async () => {
    try {
      setLoading(true);
      const res = await fetch(`${API_BASE}/admin/packages.php`, { credentials: 'include' });
      const data = await res.json();
      if (data.packages) {
        setPackages(data.packages);
      }
    } catch (err) {
      console.error('Erreur chargement packages:', err);
      toast.error('Erreur chargement packages');
    } finally {
      setLoading(false);
    }
  };

  const loadPaymentMethods = async () => {
    try {
      setLoading(true);
      const res = await fetch(`${API_BASE}/admin/payment_methods.php`, { credentials: 'include' });
      const data = await res.json();
      if (data.methods) {
        setPaymentMethods(data.methods);
      }
    } catch (err) {
      console.error('Erreur chargement méthodes de paiement:', err);
      toast.error('Erreur chargement méthodes de paiement');
    } finally {
      setLoading(false);
    }
  };

  const loadPurchases = async () => {
    try {
      setLoading(true);
      const res = await fetch(`${API_BASE}/admin/purchases.php`, { credentials: 'include' });
      const data = await res.json();
      if (data.purchases) {
        setPurchases(data.purchases);
      }
    } catch (err) {
      console.error('Erreur chargement achats:', err);
      toast.error('Erreur chargement achats');
    } finally {
      setLoading(false);
    }
  };

  const loadReservations = async () => {
    try {
      setLoading(true);
      const res = await fetch(`${API_BASE}/admin/reservations.php`, { credentials: 'include' });
      const data = await res.json();
      if (data.reservations) {
        setReservations(data.reservations);
      }
    } catch (err) {
      console.error('Erreur chargement réservations:', err);
      toast.error('Erreur chargement réservations');
    } finally {
      setLoading(false);
    }
  };

  // Game CRUD
  const handleGameSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    
    try {
      const method = editingGame ? 'PUT' : 'POST';
      const url = editingGame 
        ? `${API_BASE}/admin/games.php?id=${editingGame.id}`
        : `${API_BASE}/admin/games.php`;
        
      const res = await fetch(url, {
        method,
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(gameForm)
      });
      
      const data = await res.json();
      if (data.success) {
        toast.success(editingGame ? 'Jeu mis à jour' : 'Jeu créé');
        setShowGameModal(false);
        setEditingGame(null);
        setGameForm({
          name: '',
          slug: '',
          description: '',
          short_description: '',
          image_url: '',
          thumbnail_url: '',
          category: 'action',
          platform: '',
          min_players: 1,
          max_players: 1,
          age_rating: '',
          points_per_hour: 10,
          base_price: 0,
          is_reservable: false,
          reservation_fee: 0,
          is_featured: false
        });
        loadGames();
      } else {
        toast.error(data.error || 'Erreur');
      }
    } catch (err) {
      console.error('Erreur:', err);
      toast.error('Erreur réseau');
    } finally {
      setSubmitting(false);
    }
  };

  // Package CRUD
  const handlePackageSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    
    try {
      const method = editingPackage ? 'PUT' : 'POST';
      const url = editingPackage 
        ? `${API_BASE}/admin/packages.php?id=${editingPackage.id}`
        : `${API_BASE}/admin/packages.php`;
        
      const res = await fetch(url, {
        method,
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(packageForm)
      });
      
      const data = await res.json();
      if (data.success) {
        toast.success(editingPackage ? 'Package mis à jour' : 'Package créé');
        setShowPackageModal(false);
        setEditingPackage(null);
        setPackageForm({
          game_id: '',
          name: '',
          duration_minutes: 60,
          price: 0,
          original_price: null,
          points_earned: 0,
          bonus_multiplier: 1.0,
          is_promotional: false,
          promotional_label: '',
          max_purchases_per_user: null,
          is_active: true,
          display_order: 0
        });
        loadPackages();
      } else {
        toast.error(data.error || 'Erreur');
      }
    } catch (err) {
      console.error('Erreur:', err);
      toast.error('Erreur réseau');
    } finally {
      setSubmitting(false);
    }
  };

  // Payment Method CRUD
  const handlePaymentSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    
    try {
      const method = editingPayment ? 'PUT' : 'POST';
      const url = editingPayment 
        ? `${API_BASE}/admin/payment_methods.php?id=${editingPayment.id}`
        : `${API_BASE}/admin/payment_methods.php`;
        
      const res = await fetch(url, {
        method,
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(paymentForm)
      });
      
      const data = await res.json();
      if (data.success) {
        toast.success(editingPayment ? 'Méthode mise à jour' : 'Méthode créée');
        setShowPaymentModal(false);
        setEditingPayment(null);
        setPaymentForm({
          name: '',
          description: '',
          provider: 'manual',
          fee_percentage: 0,
          fee_fixed: 0,
          is_active: true,
          auto_confirm: false,
          requires_online_payment: false,
          display_order: 0
        });
        loadPaymentMethods();
      } else {
        toast.error(data.error || 'Erreur');
      }
    } catch (err) {
      console.error('Erreur:', err);
      toast.error('Erreur réseau');
    } finally {
      setSubmitting(false);
    }
  };

  // Edit/Delete handlers
  const editGame = (game) => {
    setEditingGame(game);
    setGameForm(game);
    setShowGameModal(true);
  };

  const deleteGame = async (id) => {
    if (!confirm('Supprimer ce jeu ?')) return;
    
    try {
      const res = await fetch(`${API_BASE}/admin/games.php?id=${id}`, {
        method: 'DELETE',
        credentials: 'include'
      });
      
      const data = await res.json();
      if (data.success) {
        toast.success('Jeu supprimé');
        loadGames();
      } else {
        toast.error(data.error || 'Erreur');
      }
    } catch (err) {
      console.error('Erreur:', err);
      toast.error('Erreur réseau');
    }
  };

  const editPackage = (pkg) => {
    setEditingPackage(pkg);
    setPackageForm(pkg);
    setShowPackageModal(true);
  };

  const deletePackage = async (id) => {
    if (!confirm('Supprimer ce package ?')) return;
    
    try {
      const res = await fetch(`${API_BASE}/admin/packages.php?id=${id}`, {
        method: 'DELETE',
        credentials: 'include'
      });
      
      const data = await res.json();
      if (data.success) {
        toast.success('Package supprimé');
        loadPackages();
      } else {
        toast.error(data.error || 'Erreur');
      }
    } catch (err) {
      console.error('Erreur:', err);
      toast.error('Erreur réseau');
    }
  };

  const editPaymentMethod = (method) => {
    setEditingPayment(method);
    setPaymentForm(method);
    setShowPaymentModal(true);
  };

  const deletePaymentMethod = async (id) => {
    if (!confirm('Supprimer cette méthode ?')) return;
    
    try {
      const res = await fetch(`${API_BASE}/admin/payment_methods.php?id=${id}`, {
        method: 'DELETE',
        credentials: 'include'
      });
      
      const data = await res.json();
      if (data.success) {
        toast.success('Méthode supprimée');
        loadPaymentMethods();
      } else {
        toast.error(data.error || 'Erreur');
      }
    } catch (err) {
      console.error('Erreur:', err);
      toast.error('Erreur réseau');
    }
  };

  // Filter data
  const filteredGames = games.filter(game => 
    game.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    game.category.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const filteredPackages = packages.filter(pkg => 
    pkg.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    (pkg.game && pkg.game.name.toLowerCase().includes(searchTerm.toLowerCase()))
  );

  const filteredPurchases = purchases.filter(purchase => 
    purchase.user?.username.toLowerCase().includes(searchTerm.toLowerCase()) ||
    purchase.package?.name.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const filteredReservations = reservations.filter(reservation => 
    reservation.user?.username.toLowerCase().includes(searchTerm.toLowerCase()) ||
    reservation.game?.name.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const filteredPaymentMethods = paymentMethods.filter(method => 
    method.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    method.provider.toLowerCase().includes(searchTerm.toLowerCase())
  );

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900">
      <Navigation userType="admin" currentPage="shop" />
      
      <div className="lg:pl-64">
        <div className="p-4 lg:p-8">
          {/* Header */}
          <div className="mb-8">
            <h1 className="text-3xl lg:text-4xl font-bold text-white mb-2 flex items-center gap-3">
              <ShoppingCart className="w-10 h-10 text-purple-400" />
              Boutique et Réservations
            </h1>
            <p className="text-gray-300">Gérez les jeux, packages, paiements et réservations</p>
          </div>

          {/* Tabs */}
          <div className="mb-8">
            <div className="flex flex-wrap gap-2 border-b border-gray-700">
              <button
                onClick={() => setActiveTab('games')}
                className={`px-4 py-2 rounded-t-lg font-medium transition-colors ${
                  activeTab === 'games' 
                    ? 'bg-purple-600 text-white' 
                    : 'text-gray-400 hover:text-white hover:bg-gray-800'
                }`}
              >
                <Gamepad2 className="w-4 h-4 inline mr-2" />
                Jeux
              </button>
              <button
                onClick={() => setActiveTab('packages')}
                className={`px-4 py-2 rounded-t-lg font-medium transition-colors ${
                  activeTab === 'packages' 
                    ? 'bg-purple-600 text-white' 
                    : 'text-gray-400 hover:text-white hover:bg-gray-800'
                }`}
              >
                <Package className="w-4 h-4 inline mr-2" />
                Packages
              </button>
              <button
                onClick={() => setActiveTab('payments')}
                className={`px-4 py-2 rounded-t-lg font-medium transition-colors ${
                  activeTab === 'payments' 
                    ? 'bg-purple-600 text-white' 
                    : 'text-gray-400 hover:text-white hover:bg-gray-800'
                }`}
              >
                <CreditCard className="w-4 h-4 inline mr-2" />
                Paiements
              </button>
              <button
                onClick={() => setActiveTab('purchases')}
                className={`px-4 py-2 rounded-t-lg font-medium transition-colors ${
                  activeTab === 'purchases' 
                    ? 'bg-purple-600 text-white' 
                    : 'text-gray-400 hover:text-white hover:bg-gray-800'
                }`}
              >
                <DollarSign className="w-4 h-4 inline mr-2" />
                Achats
              </button>
              <button
                onClick={() => setActiveTab('reservations')}
                className={`px-4 py-2 rounded-t-lg font-medium transition-colors ${
                  activeTab === 'reservations' 
                    ? 'bg-purple-600 text-white' 
                    : 'text-gray-400 hover:text-white hover:bg-gray-800'
                }`}
              >
                <Calendar className="w-4 h-4 inline mr-2" />
                Réservations
              </button>
            </div>
          </div>

          {/* Search */}
          <div className="mb-6">
            <div className="relative max-w-md">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
              <input
                type="text"
                placeholder="Rechercher..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full pl-10 pr-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              />
            </div>
          </div>

          {/* Content based on active tab */}
          <div className="bg-gray-800/50 backdrop-blur-sm rounded-2xl border border-gray-700 p-6">
            {activeTab === 'games' && (
              <div>
                <div className="flex justify-between items-center mb-6">
                  <h2 className="text-2xl font-bold text-white">Jeux Disponibles</h2>
                  <button
                    onClick={() => {
                      setEditingGame(null);
                      setGameForm({
                        name: '',
                        slug: '',
                        description: '',
                        short_description: '',
                        image_url: '',
                        thumbnail_url: '',
                        category: 'action',
                        platform: '',
                        min_players: 1,
                        max_players: 1,
                        age_rating: '',
                        points_per_hour: 10,
                        base_price: 0,
                        is_reservable: false,
                        reservation_fee: 0,
                        is_featured: false
                      });
                      setShowGameModal(true);
                    }}
                    className="flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition-colors"
                  >
                    <Plus className="w-5 h-5" />
                    Ajouter un jeu
                  </button>
                </div>

                {loading ? (
                  <div className="flex justify-center items-center h-64">
                    <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-purple-500"></div>
                  </div>
                ) : (
                  <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {filteredGames.map((game) => (
                      <div key={game.id} className="bg-gray-700/50 rounded-xl p-6 border border-gray-600 hover:border-purple-500 transition-colors">
                        <div className="flex justify-between items-start mb-4">
                          <div>
                            <h3 className="text-xl font-bold text-white">{game.name}</h3>
                            <p className="text-gray-400 text-sm">{game.category}</p>
                          </div>
                          <div className="flex gap-2">
                            <button
                              onClick={() => editGame(game)}
                              className="p-2 text-gray-400 hover:text-blue-400 hover:bg-gray-600 rounded-lg transition-colors"
                            >
                              <Edit className="w-4 h-4" />
                            </button>
                            <button
                              onClick={() => deleteGame(game.id)}
                              className="p-2 text-gray-400 hover:text-red-400 hover:bg-gray-600 rounded-lg transition-colors"
                            >
                              <Trash2 className="w-4 h-4" />
                            </button>
                          </div>
                        </div>
                        
                        {game.image_url && (
                          <img 
                            src={game.image_url} 
                            alt={game.name}
                            className="w-full h-32 object-cover rounded-lg mb-4"
                          />
                        )}
                        
                        <p className="text-gray-300 text-sm mb-4 line-clamp-2">
                          {game.short_description || game.description}
                        </p>
                        
                        <div className="flex justify-between items-center text-sm">
                          <span className="text-purple-400 font-medium">
                            {formatPriceXOF(game.base_price)}
                          </span>
                          <span className={`px-2 py-1 rounded-full text-xs ${
                            game.is_featured 
                              ? 'bg-green-500/20 text-green-400' 
                              : 'bg-gray-600 text-gray-300'
                          }`}>
                            {game.is_featured ? 'Mis en avant' : 'Standard'}
                          </span>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )}

            {activeTab === 'packages' && (
              <div>
                <div className="flex justify-between items-center mb-6">
                  <h2 className="text-2xl font-bold text-white">Packages de Jeu</h2>
                  <button
                    onClick={() => {
                      setEditingPackage(null);
                      setPackageForm({
                        game_id: '',
                        name: '',
                        duration_minutes: 60,
                        price: 0,
                        original_price: null,
                        points_earned: 0,
                        bonus_multiplier: 1.0,
                        is_promotional: false,
                        promotional_label: '',
                        max_purchases_per_user: null,
                        is_active: true,
                        display_order: 0
                      });
                      setShowPackageModal(true);
                    }}
                    className="flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition-colors"
                  >
                    <Plus className="w-5 h-5" />
                    Ajouter un package
                  </button>
                </div>

                {loading ? (
                  <div className="flex justify-center items-center h-64">
                    <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-purple-500"></div>
                  </div>
                ) : (
                  <div className="overflow-x-auto">
                    <table className="w-full text-left text-gray-300">
                      <thead className="text-gray-400 uppercase text-sm">
                        <tr>
                          <th className="py-3 px-4">Package</th>
                          <th className="py-3 px-4">Jeu</th>
                          <th className="py-3 px-4">Durée</th>
                          <th className="py-3 px-4">Prix</th>
                          <th className="py-3 px-4">Points</th>
                          <th className="py-3 px-4">Statut</th>
                          <th className="py-3 px-4">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        {filteredPackages.map((pkg) => (
                          <tr key={pkg.id} className="border-b border-gray-700 hover:bg-gray-700/30">
                            <td className="py-3 px-4 font-medium text-white">
                              {pkg.name}
                              {pkg.is_promotional && (
                                <span className="ml-2 px-2 py-1 bg-yellow-500/20 text-yellow-400 text-xs rounded-full">
                                  Promo
                                </span>
                              )}
                            </td>
                            <td className="py-3 px-4">
                              {pkg.game?.name || 'Tous les jeux'}
                            </td>
                            <td className="py-3 px-4">
                              {Math.floor(pkg.duration_minutes / 60)}h{pkg.duration_minutes % 60 || ''}
                            </td>
                            <td className="py-3 px-4 text-purple-400 font-medium">
                              {formatPriceXOF(pkg.price)}
                              {pkg.original_price && pkg.original_price > pkg.price && (
                                <div className="text-gray-500 text-sm line-through">
                                  {formatPriceXOF(pkg.original_price)}
                                </div>
                              )}
                            </td>
                            <td className="py-3 px-4">
                              {pkg.points_earned} pts
                              {pkg.bonus_multiplier > 1 && (
                                <span className="ml-1 text-green-400">x{pkg.bonus_multiplier}</span>
                              )}
                            </td>
                            <td className="py-3 px-4">
                              <span className={`px-2 py-1 rounded-full text-xs ${
                                pkg.is_active 
                                  ? 'bg-green-500/20 text-green-400' 
                                  : 'bg-red-500/20 text-red-400'
                              }`}>
                                {pkg.is_active ? 'Actif' : 'Inactif'}
                              </span>
                            </td>
                            <td className="py-3 px-4">
                              <div className="flex gap-2">
                                <button
                                  onClick={() => editPackage(pkg)}
                                  className="p-2 text-gray-400 hover:text-blue-400 hover:bg-gray-600 rounded-lg transition-colors"
                                >
                                  <Edit className="w-4 h-4" />
                                </button>
                                <button
                                  onClick={() => deletePackage(pkg.id)}
                                  className="p-2 text-gray-400 hover:text-red-400 hover:bg-gray-600 rounded-lg transition-colors"
                                >
                                  <Trash2 className="w-4 h-4" />
                                </button>
                              </div>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </div>
            )}

            {activeTab === 'payments' && (
              <div>
                <div className="flex justify-between items-center mb-6">
                  <h2 className="text-2xl font-bold text-white">Méthodes de Paiement</h2>
                  <button
                    onClick={() => {
                      setEditingPayment(null);
                      setPaymentForm({
                        name: '',
                        description: '',
                        provider: 'manual',
                        fee_percentage: 0,
                        fee_fixed: 0,
                        is_active: true,
                        auto_confirm: false,
                        requires_online_payment: false,
                        display_order: 0
                      });
                      setShowPaymentModal(true);
                    }}
                    className="flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition-colors"
                  >
                    <Plus className="w-5 h-5" />
                    Ajouter une méthode
                  </button>
                </div>

                {loading ? (
                  <div className="flex justify-center items-center h-64">
                    <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-purple-500"></div>
                  </div>
                ) : (
                  <div className="overflow-x-auto">
                    <table className="w-full text-left text-gray-300">
                      <thead className="text-gray-400 uppercase text-sm">
                        <tr>
                          <th className="py-3 px-4">Nom</th>
                          <th className="py-3 px-4">Fournisseur</th>
                          <th className="py-3 px-4">Frais</th>
                          <th className="py-3 px-4">Statut</th>
                          <th className="py-3 px-4">Confirmation</th>
                          <th className="py-3 px-4">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        {filteredPaymentMethods.map((method) => (
                          <tr key={method.id} className="border-b border-gray-700 hover:bg-gray-700/30">
                            <td className="py-3 px-4 font-medium text-white">{method.name}</td>
                            <td className="py-3 px-4 capitalize">{method.provider}</td>
                            <td className="py-3 px-4">
                              {method.fee_percentage > 0 && `${method.fee_percentage}%`}
                              {method.fee_fixed > 0 && ` + ${formatPriceXOF(method.fee_fixed)}`}
                            </td>
                            <td className="py-3 px-4">
                              <span className={`px-2 py-1 rounded-full text-xs ${
                                method.is_active 
                                  ? 'bg-green-500/20 text-green-400' 
                                  : 'bg-red-500/20 text-red-400'
                              }`}>
                                {method.is_active ? 'Actif' : 'Inactif'}
                              </span>
                            </td>
                            <td className="py-3 px-4">
                              <span className={`px-2 py-1 rounded-full text-xs ${
                                method.auto_confirm 
                                  ? 'bg-green-500/20 text-green-400' 
                                  : 'bg-yellow-500/20 text-yellow-400'
                              }`}>
                                {method.auto_confirm ? 'Auto' : 'Manuelle'}
                              </span>
                            </td>
                            <td className="py-3 px-4">
                              <div className="flex gap-2">
                                <button
                                  onClick={() => editPaymentMethod(method)}
                                  className="p-2 text-gray-400 hover:text-blue-400 hover:bg-gray-600 rounded-lg transition-colors"
                                >
                                  <Edit className="w-4 h-4" />
                                </button>
                                <button
                                  onClick={() => deletePaymentMethod(method.id)}
                                  className="p-2 text-gray-400 hover:text-red-400 hover:bg-gray-600 rounded-lg transition-colors"
                                >
                                  <Trash2 className="w-4 h-4" />
                                </button>
                              </div>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </div>
            )}

            {activeTab === 'purchases' && (
              <div>
                <h2 className="text-2xl font-bold text-white mb-6">Historique des Achats</h2>

                {loading ? (
                  <div className="flex justify-center items-center h-64">
                    <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-purple-500"></div>
                  </div>
                ) : (
                  <div className="overflow-x-auto">
                    <table className="w-full text-left text-gray-300">
                      <thead className="text-gray-400 uppercase text-sm">
                        <tr>
                          <th className="py-3 px-4">Utilisateur</th>
                          <th className="py-3 px-4">Package</th>
                          <th className="py-3 px-4">Montant</th>
                          <th className="py-3 px-4">Points</th>
                          <th className="py-3 px-4">Date</th>
                          <th className="py-3 px-4">Statut</th>
                        </tr>
                      </thead>
                      <tbody>
                        {filteredPurchases.map((purchase) => (
                          <tr key={purchase.id} className="border-b border-gray-700 hover:bg-gray-700/30">
                            <td className="py-3 px-4 font-medium text-white">
                              {purchase.user?.username || 'Utilisateur supprimé'}
                            </td>
                            <td className="py-3 px-4">
                              {purchase.package?.name || 'Package supprimé'}
                            </td>
                            <td className="py-3 px-4 text-purple-400 font-medium">
                              {formatPriceXOF(purchase.amount)}
                            </td>
                            <td className="py-3 px-4">
                              +{purchase.points_earned} pts
                            </td>
                            <td className="py-3 px-4 text-sm">
                              {new Date(purchase.created_at).toLocaleDateString('fr-FR')}
                            </td>
                            <td className="py-3 px-4">
                              <span className={`px-2 py-1 rounded-full text-xs ${
                                purchase.status === 'completed' 
                                  ? 'bg-green-500/20 text-green-400' 
                                  : purchase.status === 'pending' 
                                    ? 'bg-yellow-500/20 text-yellow-400'
                                    : 'bg-red-500/20 text-red-400'
                              }`}>
                                {purchase.status === 'completed' ? 'Complété' : 
                                 purchase.status === 'pending' ? 'En attente' : 'Annulé'}
                              </span>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </div>
            )}

            {activeTab === 'reservations' && (
              <div>
                <h2 className="text-2xl font-bold text-white mb-6">Réservations</h2>

                {loading ? (
                  <div className="flex justify-center items-center h-64">
                    <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-purple-500"></div>
                  </div>
                ) : (
                  <div className="overflow-x-auto">
                    <table className="w-full text-left text-gray-300">
                      <thead className="text-gray-400 uppercase text-sm">
                        <tr>
                          <th className="py-3 px-4">Utilisateur</th>
                          <th className="py-3 px-4">Jeu</th>
                          <th className="py-3 px-4">Date</th>
                          <th className="py-3 px-4">Durée</th>
                          <th className="py-3 px-4">Montant</th>
                          <th className="py-3 px-4">Statut</th>
                          <th className="py-3 px-4">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        {filteredReservations.map((reservation) => (
                          <tr key={reservation.id} className="border-b border-gray-700 hover:bg-gray-700/30">
                            <td className="py-3 px-4 font-medium text-white">
                              {reservation.user?.username || 'Utilisateur supprimé'}
                            </td>
                            <td className="py-3 px-4">
                              {reservation.game?.name || 'Jeu supprimé'}
                            </td>
                            <td className="py-3 px-4 text-sm">
                              {new Date(reservation.start_time).toLocaleDateString('fr-FR')}
                            </td>
                            <td className="py-3 px-4">
                              {reservation.duration_minutes} min
                            </td>
                            <td className="py-3 px-4 text-purple-400 font-medium">
                              {formatPriceXOF(reservation.total_amount)}
                            </td>
                            <td className="py-3 px-4">
                              <span className={`px-2 py-1 rounded-full text-xs ${
                                reservation.status === 'confirmed' 
                                  ? 'bg-green-500/20 text-green-400' 
                                  : reservation.status === 'pending' 
                                    ? 'bg-yellow-500/20 text-yellow-400'
                                    : reservation.status === 'cancelled' 
                                      ? 'bg-red-500/20 text-red-400'
                                      : 'bg-blue-500/20 text-blue-400'
                              }`}>
                                {reservation.status === 'confirmed' ? 'Confirmé' : 
                                 reservation.status === 'pending' ? 'En attente' : 
                                 reservation.status === 'cancelled' ? 'Annulé' : 'Complété'}
                              </span>
                            </td>
                            <td className="py-3 px-4">
                              <div className="flex gap-1">
                                {reservation.status === 'pending' && (
                                  <>
                                    <button
                                      onClick={() => confirmReservation(reservation.id)}
                                      className="p-1 text-green-400 hover:bg-green-500/20 rounded"
                                      title="Confirmer"
                                    >
                                      <CheckCircle className="w-4 h-4" />
                                    </button>
                                    <button
                                      onClick={() => cancelReservation(reservation.id)}
                                      className="p-1 text-red-400 hover:bg-red-500/20 rounded"
                                      title="Annuler"
                                    >
                                      <XCircle className="w-4 h-4" />
                                    </button>
                                  </>
                                )}
                                {reservation.status === 'confirmed' && (
                                  <button
                                    onClick={() => markReservationCompleted(reservation.id)}
                                    className="p-1 text-blue-400 hover:bg-blue-500/20 rounded"
                                    title="Marquer comme complété"
                                  >
                                    <CheckCircle className="w-4 h-4" />
                                  </button>
                                )}
                                {reservation.status === 'confirmed' && (
                                  <button
                                    onClick={() => markReservationNoShow(reservation.id)}
                                    className="p-1 text-orange-400 hover:bg-orange-500/20 rounded"
                                    title="No-show"
                                  >
                                    <XCircle className="w-4 h-4" />
                                  </button>
                                )}
                              </div>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Game Modal */}
      {showGameModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
          <div className="bg-gray-800 rounded-2xl border border-gray-700 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div className="p-6">
              <div className="flex justify-between items-center mb-6">
                <h2 className="text-2xl font-bold text-white">
                  {editingGame ? 'Modifier le jeu' : 'Ajouter un jeu'}
                </h2>
                <button
                  onClick={() => setShowGameModal(false)}
                  className="text-gray-400 hover:text-white"
                >
                  ✕
                </button>
              </div>

              <form onSubmit={handleGameSubmit} className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Nom du jeu *
                    </label>
                    <input
                      type="text"
                      required
                      value={gameForm.name}
                      onChange={(e) => setGameForm({...gameForm, name: e.target.value})}
                      className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Slug
                    </label>
                    <input
                      type="text"
                      value={gameForm.slug}
                      onChange={(e) => setGameForm({...gameForm, slug: e.target.value})}
                      className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-300 mb-2">
                    Description courte
                  </label>
                  <textarea
                    value={gameForm.short_description}
                    onChange={(e) => setGameForm({...gameForm, short_description: e.target.value})}
                    rows={2}
                    className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-300 mb-2">
                    Description complète
                  </label>
                  <textarea
                    value={gameForm.description}
                    onChange={(e) => setGameForm({...gameForm, description: e.target.value})}
                    rows={4}
                    className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                  />
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Catégorie
                    </label>
                    <select
                      value={gameForm.category}
                      onChange={(e) => setGameForm({...gameForm, category: e.target.value})}
                      className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    >
                      <option value="action">Action</option>
                      <option value="aventure">Aventure</option>
                      <option value="fps">FPS</option>
                      <option value="plateforme">Plateforme</option>
                      <option value="strategie">Stratégie</option>
                      <option value="rpg">RPG</option>
                      <option value="sport">Sport</option>
                      <option value="course">Course</option>
                      <option value="simulation">Simulation</option>
                      <option value="retro">Rétro</option>
                      <option value="vr">VR</option>
                    </select>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Plateforme
                    </label>
                    <input
                      type="text"
                      value={gameForm.platform}
                      onChange={(e) => setGameForm({...gameForm, platform: e.target.value})}
                      className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Joueurs min
                    </label>
                    <input
                      type="number"
                      min="1"
                      value={gameForm.min_players}
                      onChange={(e) => setGameForm({...gameForm, min_players: parseInt(e.target.value)})}
                      className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Joueurs max
                    </label>
                    <input
                      type="number"
                      min="1"
                      value={gameForm.max_players}
                      onChange={(e) => setGameForm({...gameForm, max_players: parseInt(e.target.value)})}
                      className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Âge minimum
                    </label>
                    <input
                      type="text"
                      value={gameForm.age_rating}
                      onChange={(e) => setGameForm({...gameForm, age_rating: e.target.value})}
                      className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Points/heure
                    </label>
                    <input
                      type="number"
                      min="0"
                      value={gameForm.points_per_hour}
                      onChange={(e) => setGameForm({...gameForm, points_per_hour: parseInt(e.target.value)})}
                      className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Prix de base (XOF)
                    </label>
                    <input
                      type="number"
                      min="0"
                      value={gameForm.base_price}
                      onChange={(e) => setGameForm({...gameForm, base_price: parseFloat(e.target.value)})}
                      className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Image URL
                    </label>
                    <input
                      type="url"
                      value={gameForm.image_url}
                      onChange={(e) => setGameForm({...gameForm, image_url: e.target.value})}
                      className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Thumbnail URL
                    </label>
                    <input
                      type="url"
                      value={gameForm.thumbnail_url}
                      onChange={(e) => setGameForm({...gameForm, thumbnail_url: e.target.value})}
                      className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                  </div>
                </div>

                <div className="flex items-center gap-4">
                  <label className="flex items-center gap-2 text-gray-300">
                    <input
                      type="checkbox"
                      checked={gameForm.is_reservable}
                      onChange={(e) => setGameForm({...gameForm, is_reservable: e.target.checked})}
                      className="rounded bg-gray-700 border-gray-600 text-purple-600 focus:ring-purple-500"
                    />
                    Réservable
                  </label>

                  <label className="flex items-center gap-2 text-gray-300">
                    <input
                      type="checkbox"
                      checked={gameForm.is_featured}
                      onChange={(e) => setGameForm({...gameForm, is_featured: e.target.checked})}
                      className="rounded bg-gray-700 border-gray-600 text-purple-600 focus:ring-purple-500"
                    />
                    Mis en avant
                  </label>
                </div>

                {gameForm.is_reservable && (
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Frais de réservation (XOF)
                    </label>
                    <input
                      type="number"
                      min="0"
                      value={gameForm.reservation_fee}
                      onChange={(e) => setGameForm({...gameForm, reservation_fee: parseFloat(e.target.value)})}
                      className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                  </div>
                )}

                <div className="flex justify-end gap-3 pt-4">
                  <button
                    type="button"
                    onClick={() => setShowGameModal(false)}
                    className="px-4 py-2 text-gray-300 hover:text-white border border-gray-600 rounded-lg hover:bg-gray-700 transition-colors"
                  >
                    Annuler
                  </button>
                  <button
                    type="submit"
                    disabled={submitting}
                    className="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors disabled:opacity-50"
                  >
                    {submitting ? 'Enregistrement...' : (editingGame ? 'Modifier' : 'Créer')}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}

      {/* Package Modal */}
      {showPackageModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
          <div className="bg-gray-800 rounded-2xl border border-gray-700 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div className="p-6">
              <div className="flex justify-between items-center mb-6">
                <h2 className="text-2xl font-bold text-white">
                  {editingPackage ? 'Modifier le package' : 'Ajouter un package'}
                </h2>
                <button
                  onClick={() => setShowPackageModal(false)}
                  className="text-gray-400 hover:text-white"
                >
                  ✕
                </button>
              </div>

              <form onSubmit={handlePackageSubmit} className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Jeu (optionnel)
                    </label>
                    <select
                      value={packageForm.game_id}
                      onChange={(e) => setPackageForm({...packageForm, game_id: e.target.value})}
                      className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    >
                      <option value="">Tous les jeux</option>
                      {games.map(game => (
                        <option key={game.id} value={game.id}>
                          {game.name}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Nom du package *
                    </label>
                    <input
                      type="text"
                      required
                      value={packageForm.name}
                      onChange={(e) => setPackageForm({...packageForm, name: e.target.value})}
                      className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Durée (minutes) *
                    </label>
                    <input
                      type="number"
                      min="1"
                      required
                      value={packageForm.duration_minutes}
                      onChange={(e) => setPackageForm({...packageForm, duration_minutes: parseInt(e.target.value)})}
                      className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Points gagnés *
                    </label>
                    <input
                      type="number"
                      min="0"
                      required
                      value={packageForm.points_earned}
                      onChange={(e) => setPackageForm({...packageForm, points_earned: parseInt(e.target.value)})}
                      className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Prix (XOF) *
                    </label>
                    <input
                      type="number"
                      min="0"
                      required
                      value={packageForm.price}
                      onChange={(e) => setPackageForm({...packageForm, price: parseFloat(e.target.value)})}
                      className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Prix original (XOF)
                    </label>
                    <input
                      type="number"
                      min="0"
                      value={packageForm.original_price || ''}
                      onChange={(e) => setPackageForm({...packageForm, original_price: e.target.value ? parseFloat(e.target.value) : null})}
                      className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Multiplicateur bonus
                    </label>
                    <input
                      type="number"
                      min="1"
                      step="0.1"
                      value={packageForm.bonus_multiplier}
                      onChange={(e) => setPackageForm({...packageForm, bonus_multiplier: parseFloat(e.target.value)})}
                      className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Ordre d'affichage
                    </label>
                    <input
                      type="number"
                      min="0"
                      value={packageForm.display_order}
                      onChange={(e) => setPackageForm({...packageForm, display_order: parseInt(e.target.value)})}
                      className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                  </div>
                </div>

                <div className="flex items-center gap-4">
                  <label className="flex items-center gap-2 text-gray-300">
                    <input
                      type="checkbox"
                      checked={packageForm.is_promotional}
                      onChange={(e) => setPackageForm({...packageForm, is_promotional: e.target.checked})}
                      className="rounded bg-gray-700 border-gray-600 text-purple-600 focus:ring-purple-500"
                    />
                    Promotionnel
                  </label>

                  <label className="flex items-center gap-2 text-gray-300">
                    <input
                      type="checkbox"
                      checked={packageForm.is_active}
                      onChange={(e) => setPackageForm({...packageForm, is_active: e.target.checked})}
                      className="rounded bg-gray-700 border-gray-600 text-purple-600 focus:ring-purple-500"
                    />
                    Actif
                  </label>
                </div>

                {packageForm.is_promotional && (
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Label promotionnel
                    </label>
                    <input
                      type="text"
                      value={packageForm.promotional_label}
                      onChange={(e) => setPackageForm({...packageForm, promotional_label: e.target.value})}
                      className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                  </div>
                )}

                <div className="flex justify-end gap-3 pt-4">
                  <button
                    type="button"
                    onClick={() => setShowPackageModal(false)}
                    className="px-4 py-2 text-gray-300 hover:text-white border border-gray-600 rounded-lg hover:bg-gray-700 transition-colors"
                  >
                    Annuler
                  </button>
                  <button
                    type="submit"
                    disabled={submitting}
                    className="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors disabled:opacity-50"
                  >
                    {submitting ? 'Enregistrement...' : (editingPackage ? 'Modifier' : 'Créer')}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}

      {/* Payment Method Modal */}
      {showPaymentModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
          <div className="bg-gray-800 rounded-2xl border border-gray-700 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div className="p-6">
              <div className="flex justify-between items-center mb-6">
                <h2 className="text-2xl font-bold text-white">
                  {editingPayment ? 'Modifier la méthode' : 'Ajouter une méthode'}
                </h2>
                <button
                  onClick={() => setShowPaymentModal(false)}
                  className="text-gray-400 hover:text-white"
                >
                  ✕
                </button>
              </div>

              <form onSubmit={handlePaymentSubmit} className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Nom *
                    </label>
                    <input
                      type="text"
                      required
                      value={paymentForm.name}
                      onChange={(e) => setPaymentForm({...paymentForm, name: e.target.value})}
                      className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Fournisseur
                    </label>
                    <select
                      value={paymentForm.provider}
                      onChange={(e) => setPaymentForm({...paymentForm, provider: e.target.value})}
                      className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    >
                      <option value="manual">Manuel</option>
                      <option value="kkiapay">Kkiapay</option>
                      <option value="wave">Wave</option>
                      <option value="orange_money">Orange Money</option>
                      <option value="mtn_momo">MTN Mobile Money</option>
                      <option value="paypal">PayPal</option>
                      <option value="stripe">Stripe</option>
                    </select>
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-300 mb-2">
                    Description
                  </label>
                  <textarea
                    value={paymentForm.description}
                    onChange={(e) => setPaymentForm({...paymentForm, description: e.target.value})}
                    rows={3}
                    className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                  />
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Frais (%) 
                    </label>
                    <input
                      type="number"
                      min="0"
                      step="0.01"
                      value={paymentForm.fee_percentage}
                      onChange={(e) => setPaymentForm({...paymentForm, fee_percentage: parseFloat(e.target.value)})}
                      className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Frais fixes (XOF)
                    </label>
                    <input
                      type="number"
                      min="0"
                      value={paymentForm.fee_fixed}
                      onChange={(e) => setPaymentForm({...paymentForm, fee_fixed: parseFloat(e.target.value)})}
                      className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Ordre d'affichage
                    </label>
                    <input
                      type="number"
                      min="0"
                      value={paymentForm.display_order}
                      onChange={(e) => setPaymentForm({...paymentForm, display_order: parseInt(e.target.value)})}
                      className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                  </div>
                </div>

                <div className="flex items-center gap-4">
                  <label className="flex items-center gap-2 text-gray-300">
                    <input
                      type="checkbox"
                      checked={paymentForm.is_active}
                      onChange={(e) => setPaymentForm({...paymentForm, is_active: e.target.checked})}
                      className="rounded bg-gray-700 border-gray-600 text-purple-600 focus:ring-purple-500"
                    />
                    Actif
                  </label>

                  <label className="flex items-center gap-2 text-gray-300">
                    <input
                      type="checkbox"
                      checked={paymentForm.auto_confirm}
                      onChange={(e) => setPaymentForm({...paymentForm, auto_confirm: e.target.checked})}
                      className="rounded bg-gray-700 border-gray-600 text-purple-600 focus:ring-purple-500"
                    />
                    Confirmation automatique
                  </label>

                  <label className="flex items-center gap-2 text-gray-300">
                    <input
                      type="checkbox"
                      checked={paymentForm.requires_online_payment}
                      onChange={(e) => setPaymentForm({...paymentForm, requires_online_payment: e.target.checked})}
                      className="rounded bg-gray-700 border-gray-600 text-purple-600 focus:ring-purple-500"
                    />
                    Paiement en ligne requis
                  </label>
                </div>

                <div className="flex justify-end gap-3 pt-4">
                  <button
                    type="button"
                    onClick={() => setShowPaymentModal(false)}
                    className="px-4 py-2 text-gray-300 hover:text-white border border-gray-600 rounded-lg hover:bg-gray-700 transition-colors"
                  >
                    Annuler
                  </button>
                  <button
                    type="submit"
                    disabled={submitting}
                    className="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors disabled:opacity-50"
                  >
                    {submitting ? 'Enregistrement...' : (editingPayment ? 'Modifier' : 'Créer')}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}