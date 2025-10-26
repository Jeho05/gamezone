import React, { useState, useEffect } from 'react';
'use client';
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
import { useState, useEffect } from 'react';
import React, { useState, useEffect } from 'react';
import Navigation from '../../../components/Navigation';
import React, { useState, useEffect } from 'react';
import ImageUpload from '../../../components/ImageUpload';
import React, { useState, useEffect } from 'react';
import PackageModal from '../../../components/admin/PackageModal';
import React, { useState, useEffect } from 'react';
import PaymentMethodModal from '../../../components/admin/PaymentMethodModal';
import React, { useState, useEffect } from 'react';
import { 
import React, { useState, useEffect } from 'react';
  Gamepad2, 
import React, { useState, useEffect } from 'react';
  Package, 
import React, { useState, useEffect } from 'react';
  CreditCard, 
import React, { useState, useEffect } from 'react';
  ShoppingCart,
import React, { useState, useEffect } from 'react';
  Plus,
import React, { useState, useEffect } from 'react';
  Edit,
import React, { useState, useEffect } from 'react';
  Trash2,
import React, { useState, useEffect } from 'react';
  Eye,
import React, { useState, useEffect } from 'react';
  Search,
import React, { useState, useEffect } from 'react';
  Filter,
import React, { useState, useEffect } from 'react';
  CheckCircle,
import React, { useState, useEffect } from 'react';
  XCircle,
import React, { useState, useEffect } from 'react';
  DollarSign,
import React, { useState, useEffect } from 'react';
  Calendar
import React, { useState, useEffect } from 'react';
} from 'lucide-react';
import React, { useState, useEffect } from 'react';
import API_BASE from '../../../utils/apiBase';
import React, { useState, useEffect } from 'react';
import { toast } from 'sonner';
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
export default function AdminShop() {
import React, { useState, useEffect } from 'react';
  const [activeTab, setActiveTab] = useState('games');
import React, { useState, useEffect } from 'react';
  const [games, setGames] = useState([]);
import React, { useState, useEffect } from 'react';
  const [packages, setPackages] = useState([]);
import React, { useState, useEffect } from 'react';
  const [paymentMethods, setPaymentMethods] = useState([]);
import React, { useState, useEffect } from 'react';
  const [purchases, setPurchases] = useState([]);
import React, { useState, useEffect } from 'react';
  const [reservations, setReservations] = useState([]);
import React, { useState, useEffect } from 'react';
  const [loading, setLoading] = useState(false);
import React, { useState, useEffect } from 'react';
  const [searchTerm, setSearchTerm] = useState('');
import React, { useState, useEffect } from 'react';
  const [showGameModal, setShowGameModal] = useState(false);
import React, { useState, useEffect } from 'react';
  const [editingGame, setEditingGame] = useState(null);
import React, { useState, useEffect } from 'react';
  const [showPackageModal, setShowPackageModal] = useState(false);
import React, { useState, useEffect } from 'react';
  const [editingPackage, setEditingPackage] = useState(null);
import React, { useState, useEffect } from 'react';
  const [showPaymentModal, setShowPaymentModal] = useState(false);
import React, { useState, useEffect } from 'react';
  const [editingPayment, setEditingPayment] = useState(null);
import React, { useState, useEffect } from 'react';
  const [packageForm, setPackageForm] = useState({
import React, { useState, useEffect } from 'react';
    game_id: '',
import React, { useState, useEffect } from 'react';
    name: '',
import React, { useState, useEffect } from 'react';
    duration_minutes: 60,
import React, { useState, useEffect } from 'react';
    price: 0,
import React, { useState, useEffect } from 'react';
    original_price: null,
import React, { useState, useEffect } from 'react';
    points_earned: 0,
import React, { useState, useEffect } from 'react';
    bonus_multiplier: 1.0,
import React, { useState, useEffect } from 'react';
    is_promotional: false,
import React, { useState, useEffect } from 'react';
    promotional_label: '',
import React, { useState, useEffect } from 'react';
    max_purchases_per_user: null,
import React, { useState, useEffect } from 'react';
    is_active: true,
import React, { useState, useEffect } from 'react';
    display_order: 0
import React, { useState, useEffect } from 'react';
  });
import React, { useState, useEffect } from 'react';
  const [paymentForm, setPaymentForm] = useState({
import React, { useState, useEffect } from 'react';
    name: '',
import React, { useState, useEffect } from 'react';
    description: '',
import React, { useState, useEffect } from 'react';
    provider: 'manual',
import React, { useState, useEffect } from 'react';
    fee_percentage: 0,
import React, { useState, useEffect } from 'react';
    fee_fixed: 0,
import React, { useState, useEffect } from 'react';
    is_active: true,
import React, { useState, useEffect } from 'react';
    auto_confirm: false,
import React, { useState, useEffect } from 'react';
    requires_online_payment: false,
import React, { useState, useEffect } from 'react';
    display_order: 0
import React, { useState, useEffect } from 'react';
  });
import React, { useState, useEffect } from 'react';
  const [gameForm, setGameForm] = useState({
import React, { useState, useEffect } from 'react';
    name: '',
import React, { useState, useEffect } from 'react';
    slug: '',
import React, { useState, useEffect } from 'react';
    description: '',
import React, { useState, useEffect } from 'react';
    short_description: '',
import React, { useState, useEffect } from 'react';
    image_url: '',
import React, { useState, useEffect } from 'react';
    thumbnail_url: '',
import React, { useState, useEffect } from 'react';
    category: 'action',
import React, { useState, useEffect } from 'react';
    platform: '',
import React, { useState, useEffect } from 'react';
    min_players: 1,
import React, { useState, useEffect } from 'react';
    max_players: 1,
import React, { useState, useEffect } from 'react';
    age_rating: '',
import React, { useState, useEffect } from 'react';
    points_per_hour: 10,
import React, { useState, useEffect } from 'react';
    base_price: 0,
import React, { useState, useEffect } from 'react';
    is_reservable: false,
import React, { useState, useEffect } from 'react';
    reservation_fee: 0,
import React, { useState, useEffect } from 'react';
    is_featured: false
import React, { useState, useEffect } from 'react';
  });
import React, { useState, useEffect } from 'react';
  const [submitting, setSubmitting] = useState(false);
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  // Helpers: formatters for UI
import React, { useState, useEffect } from 'react';
  const formatPriceXOF = (value) => {
import React, { useState, useEffect } from 'react';
    const n = Number(value ?? 0);
import React, { useState, useEffect } from 'react';
    try {
import React, { useState, useEffect } from 'react';
      return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(n);
import React, { useState, useEffect } from 'react';
    } catch {
import React, { useState, useEffect } from 'react';
      return `${n.toLocaleString('fr-FR')} XOF`;
import React, { useState, useEffect } from 'react';
    }
import React, { useState, useEffect } from 'react';
  };
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const loadReservations = async () => {
import React, { useState, useEffect } from 'react';
    try {
import React, { useState, useEffect } from 'react';
      setLoading(true);
import React, { useState, useEffect } from 'react';
      console.log('🔄 Chargement des réservations...');
import React, { useState, useEffect } from 'react';
      const res = await fetch(`${API_BASE}/admin/reservations.php`, { credentials: 'include' });
import React, { useState, useEffect } from 'react';
      const data = await res.json();
import React, { useState, useEffect } from 'react';
      console.log('📅 Réservations reçues:', data);
import React, { useState, useEffect } from 'react';
      if (data.reservations) {
import React, { useState, useEffect } from 'react';
        setReservations(data.reservations);
import React, { useState, useEffect } from 'react';
        console.log('✅ Réservations chargées:', data.reservations.length);
import React, { useState, useEffect } from 'react';
      } else {
import React, { useState, useEffect } from 'react';
        console.log('⚠️ Aucune réservation dans la réponse');
import React, { useState, useEffect } from 'react';
      }
import React, { useState, useEffect } from 'react';
    } catch (err) {
import React, { useState, useEffect } from 'react';
      console.error('❌ Erreur chargement réservations:', err);
import React, { useState, useEffect } from 'react';
      toast.error('Erreur chargement réservations');
import React, { useState, useEffect } from 'react';
    } finally {
import React, { useState, useEffect } from 'react';
      setLoading(false);
import React, { useState, useEffect } from 'react';
    }
import React, { useState, useEffect } from 'react';
  };
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const confirmReservation = async (reservationId) => {
import React, { useState, useEffect } from 'react';
    if (!confirm('Confirmer cette réservation ? Le paiement sera validé.')) return;
import React, { useState, useEffect } from 'react';
    
import React, { useState, useEffect } from 'react';
    try {
import React, { useState, useEffect } from 'react';
      const res = await fetch(`${API_BASE}/admin/reservations.php`, {
import React, { useState, useEffect } from 'react';
        method: 'PATCH',
import React, { useState, useEffect } from 'react';
        credentials: 'include',
import React, { useState, useEffect } from 'react';
        headers: { 'Content-Type': 'application/json' },
import React, { useState, useEffect } from 'react';
        body: JSON.stringify({ id: reservationId, action: 'confirm' })
import React, { useState, useEffect } from 'react';
      });
import React, { useState, useEffect } from 'react';
      
import React, { useState, useEffect } from 'react';
      const data = await res.json();
import React, { useState, useEffect } from 'react';
      if (data.success) {
import React, { useState, useEffect } from 'react';
        toast.success('Réservation confirmée avec succès');
import React, { useState, useEffect } from 'react';
        loadReservations();
import React, { useState, useEffect } from 'react';
      } else {
import React, { useState, useEffect } from 'react';
        toast.error(data.error || 'Erreur lors de la confirmation');
import React, { useState, useEffect } from 'react';
      }
import React, { useState, useEffect } from 'react';
    } catch (err) {
import React, { useState, useEffect } from 'react';
      console.error('Erreur confirmation réservation:', err);
import React, { useState, useEffect } from 'react';
      toast.error('Erreur lors de la confirmation');
import React, { useState, useEffect } from 'react';
    }
import React, { useState, useEffect } from 'react';
  };
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const cancelReservation = async (reservationId) => {
import React, { useState, useEffect } from 'react';
    if (!confirm('Annuler cette réservation ?')) return;
import React, { useState, useEffect } from 'react';
    
import React, { useState, useEffect } from 'react';
    try {
import React, { useState, useEffect } from 'react';
      const res = await fetch(`${API_BASE}/admin/reservations.php`, {
import React, { useState, useEffect } from 'react';
        method: 'PATCH',
import React, { useState, useEffect } from 'react';
        credentials: 'include',
import React, { useState, useEffect } from 'react';
        headers: { 'Content-Type': 'application/json' },
import React, { useState, useEffect } from 'react';
        body: JSON.stringify({ id: reservationId, action: 'cancel' })
import React, { useState, useEffect } from 'react';
      });
import React, { useState, useEffect } from 'react';
      
import React, { useState, useEffect } from 'react';
      const data = await res.json();
import React, { useState, useEffect } from 'react';
      if (data.success) {
import React, { useState, useEffect } from 'react';
        toast.success('Réservation annulée');
import React, { useState, useEffect } from 'react';
        loadReservations();
import React, { useState, useEffect } from 'react';
      } else {
import React, { useState, useEffect } from 'react';
        toast.error(data.error || 'Erreur lors de l\'annulation');
import React, { useState, useEffect } from 'react';
      }
import React, { useState, useEffect } from 'react';
    } catch (err) {
import React, { useState, useEffect } from 'react';
      console.error('Erreur annulation réservation:', err);
import React, { useState, useEffect } from 'react';
      toast.error('Erreur lors de l\'annulation');
import React, { useState, useEffect } from 'react';
    }
import React, { useState, useEffect } from 'react';
  };
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const markReservationCompleted = async (reservationId) => {
import React, { useState, useEffect } from 'react';
    if (!confirm('Marquer cette réservation comme complétée ?')) return;
import React, { useState, useEffect } from 'react';
    
import React, { useState, useEffect } from 'react';
    try {
import React, { useState, useEffect } from 'react';
      const res = await fetch(`${API_BASE}/admin/reservations.php`, {
import React, { useState, useEffect } from 'react';
        method: 'PATCH',
import React, { useState, useEffect } from 'react';
        credentials: 'include',
import React, { useState, useEffect } from 'react';
        headers: { 'Content-Type': 'application/json' },
import React, { useState, useEffect } from 'react';
        body: JSON.stringify({ id: reservationId, action: 'mark_completed' })
import React, { useState, useEffect } from 'react';
      });
import React, { useState, useEffect } from 'react';
      
import React, { useState, useEffect } from 'react';
      const data = await res.json();
import React, { useState, useEffect } from 'react';
      if (data.success) {
import React, { useState, useEffect } from 'react';
        toast.success('Réservation marquée comme complétée');
import React, { useState, useEffect } from 'react';
        loadReservations();
import React, { useState, useEffect } from 'react';
      } else {
import React, { useState, useEffect } from 'react';
        toast.error(data.error || 'Erreur');
import React, { useState, useEffect } from 'react';
      }
import React, { useState, useEffect } from 'react';
    } catch (err) {
import React, { useState, useEffect } from 'react';
      console.error('Erreur:', err);
import React, { useState, useEffect } from 'react';
      toast.error('Erreur');
import React, { useState, useEffect } from 'react';
    }
import React, { useState, useEffect } from 'react';
  };
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const markReservationNoShow = async (reservationId) => {
import React, { useState, useEffect } from 'react';
    if (!confirm('Marquer cette réservation comme no-show ?')) return;
import React, { useState, useEffect } from 'react';
    
import React, { useState, useEffect } from 'react';
    try {
import React, { useState, useEffect } from 'react';
      const res = await fetch(`${API_BASE}/admin/reservations.php`, {
import React, { useState, useEffect } from 'react';
        method: 'PATCH',
import React, { useState, useEffect } from 'react';
        credentials: 'include',
import React, { useState, useEffect } from 'react';
        headers: { 'Content-Type': 'application/json' },
import React, { useState, useEffect } from 'react';
        body: JSON.stringify({ id: reservationId, action: 'mark_no_show' })
import React, { useState, useEffect } from 'react';
      });
import React, { useState, useEffect } from 'react';
      
import React, { useState, useEffect } from 'react';
      const data = await res.json();
import React, { useState, useEffect } from 'react';
      if (data.success) {
import React, { useState, useEffect } from 'react';
        toast.success('Marqué comme no-show');
import React, { useState, useEffect } from 'react';
        loadReservations();
import React, { useState, useEffect } from 'react';
      } else {
import React, { useState, useEffect } from 'react';
        toast.error(data.error || 'Erreur');
import React, { useState, useEffect } from 'react';
      }
import React, { useState, useEffect } from 'react';
    } catch (err) {
import React, { useState, useEffect } from 'react';
      console.error('Erreur:', err);
import React, { useState, useEffect } from 'react';
      toast.error('Erreur');
import React, { useState, useEffect } from 'react';
    }
import React, { useState, useEffect } from 'react';
  };
import React, { useState, useEffect } from 'react';
  const formatNumber = (value) => Number(value ?? 0).toLocaleString('fr-FR');
import React, { useState, useEffect } from 'react';
  const formatDateTime = (value) => (value ? new Date(value).toLocaleString('fr-FR') : '—');
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const emptyForm = {
import React, { useState, useEffect } from 'react';
    name: '',
import React, { useState, useEffect } from 'react';
    slug: '',
import React, { useState, useEffect } from 'react';
    description: '',
import React, { useState, useEffect } from 'react';
    short_description: '',
import React, { useState, useEffect } from 'react';
    image_url: '',
import React, { useState, useEffect } from 'react';
    thumbnail_url: '',
import React, { useState, useEffect } from 'react';
    category: 'action',
import React, { useState, useEffect } from 'react';
    platform: '',
import React, { useState, useEffect } from 'react';
    min_players: 1,
import React, { useState, useEffect } from 'react';
    max_players: 1,
import React, { useState, useEffect } from 'react';
    age_rating: '',
import React, { useState, useEffect } from 'react';
    points_per_hour: 10,
import React, { useState, useEffect } from 'react';
    base_price: 0,
import React, { useState, useEffect } from 'react';
    is_reservable: false,
import React, { useState, useEffect } from 'react';
    reservation_fee: 0,
import React, { useState, useEffect } from 'react';
    is_featured: false
import React, { useState, useEffect } from 'react';
  };
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  useEffect(() => {
import React, { useState, useEffect } from 'react';
    console.log('🔀 Onglet actif changé:', activeTab);
import React, { useState, useEffect } from 'react';
    if (activeTab === 'games') loadGames();
import React, { useState, useEffect } from 'react';
    if (activeTab === 'packages') loadPackages();
import React, { useState, useEffect } from 'react';
    if (activeTab === 'payment-methods') loadPaymentMethods();
import React, { useState, useEffect } from 'react';
    if (activeTab === 'purchases') loadPurchases();
import React, { useState, useEffect } from 'react';
    if (activeTab === 'reservations') loadReservations();
import React, { useState, useEffect } from 'react';
  }, [activeTab]);
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const loadGames = async () => {
import React, { useState, useEffect } from 'react';
    try {
import React, { useState, useEffect } from 'react';
      setLoading(true);
import React, { useState, useEffect } from 'react';
      const res = await fetch(`${API_BASE}/admin/games.php`, { credentials: 'include' });
import React, { useState, useEffect } from 'react';
      const data = await res.json();
import React, { useState, useEffect } from 'react';
      if (data.games) setGames(data.games);
import React, { useState, useEffect } from 'react';
    } catch (err) {
import React, { useState, useEffect } from 'react';
      toast.error('Erreur chargement jeux');
import React, { useState, useEffect } from 'react';
    } finally {
import React, { useState, useEffect } from 'react';
      setLoading(false);
import React, { useState, useEffect } from 'react';
    }
import React, { useState, useEffect } from 'react';
  };
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const loadPackages = async () => {
import React, { useState, useEffect } from 'react';
    try {
import React, { useState, useEffect } from 'react';
      setLoading(true);
import React, { useState, useEffect } from 'react';
      console.log('🔄 Chargement des packages...');
import React, { useState, useEffect } from 'react';
      // Ajouter un timestamp pour éviter le cache
import React, { useState, useEffect } from 'react';
      const res = await fetch(`${API_BASE}/admin/game_packages.php?t=${Date.now()}`, { 
import React, { useState, useEffect } from 'react';
        credentials: 'include',
import React, { useState, useEffect } from 'react';
        cache: 'no-cache'
import React, { useState, useEffect } from 'react';
      });
import React, { useState, useEffect } from 'react';
      const data = await res.json();
import React, { useState, useEffect } from 'react';
      console.log('📦 Packages reçus:', data);
import React, { useState, useEffect } from 'react';
      if (data.packages) {
import React, { useState, useEffect } from 'react';
        setPackages(data.packages);
import React, { useState, useEffect } from 'react';
        console.log('✅ Packages chargés:', data.packages.length);
import React, { useState, useEffect } from 'react';
      } else {
import React, { useState, useEffect } from 'react';
        console.log('⚠️ Aucun package dans la réponse');
import React, { useState, useEffect } from 'react';
      }
import React, { useState, useEffect } from 'react';
    } catch (err) {
import React, { useState, useEffect } from 'react';
      console.error('❌ Erreur chargement packages:', err);
import React, { useState, useEffect } from 'react';
      toast.error('Erreur chargement packages');
import React, { useState, useEffect } from 'react';
    } finally {
import React, { useState, useEffect } from 'react';
      setLoading(false);
import React, { useState, useEffect } from 'react';
    }
import React, { useState, useEffect } from 'react';
  };
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const loadPaymentMethods = async () => {
import React, { useState, useEffect } from 'react';
    try {
import React, { useState, useEffect } from 'react';
      setLoading(true);
import React, { useState, useEffect } from 'react';
      console.log('🔄 Chargement des méthodes de paiement...');
import React, { useState, useEffect } from 'react';
      const res = await fetch(`${API_BASE}/admin/payment_methods_simple.php`, { credentials: 'include' });
import React, { useState, useEffect } from 'react';
      const data = await res.json();
import React, { useState, useEffect } from 'react';
      console.log('💳 Méthodes de paiement reçues:', data);
import React, { useState, useEffect } from 'react';
      if (data.payment_methods) {
import React, { useState, useEffect } from 'react';
        setPaymentMethods(data.payment_methods);
import React, { useState, useEffect } from 'react';
        console.log('✅ Méthodes chargées:', data.payment_methods.length);
import React, { useState, useEffect } from 'react';
      } else {
import React, { useState, useEffect } from 'react';
        console.log('⚠️ Aucune méthode dans la réponse');
import React, { useState, useEffect } from 'react';
      }
import React, { useState, useEffect } from 'react';
    } catch (err) {
import React, { useState, useEffect } from 'react';
      console.error('❌ Erreur chargement méthodes paiement:', err);
import React, { useState, useEffect } from 'react';
      toast.error('Erreur chargement méthodes paiement');
import React, { useState, useEffect } from 'react';
    } finally {
import React, { useState, useEffect } from 'react';
      setLoading(false);
import React, { useState, useEffect } from 'react';
    }
import React, { useState, useEffect } from 'react';
  };
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const loadPurchases = async () => {
import React, { useState, useEffect } from 'react';
    try {
import React, { useState, useEffect } from 'react';
      setLoading(true);
import React, { useState, useEffect } from 'react';
      console.log('🔄 Chargement des achats...');
import React, { useState, useEffect } from 'react';
      const res = await fetch(`${API_BASE}/admin/purchases.php`, { credentials: 'include' });
import React, { useState, useEffect } from 'react';
      const data = await res.json();
import React, { useState, useEffect } from 'react';
      console.log('🛒 Achats reçus:', data);
import React, { useState, useEffect } from 'react';
      if (data.purchases) {
import React, { useState, useEffect } from 'react';
        setPurchases(data.purchases);
import React, { useState, useEffect } from 'react';
        console.log('✅ Achats chargés:', data.purchases.length);
import React, { useState, useEffect } from 'react';
      } else {
import React, { useState, useEffect } from 'react';
        console.log('⚠️ Aucun achat dans la réponse');
import React, { useState, useEffect } from 'react';
      }
import React, { useState, useEffect } from 'react';
    } catch (err) {
import React, { useState, useEffect } from 'react';
      console.error('❌ Erreur chargement achats:', err);
import React, { useState, useEffect } from 'react';
      toast.error('Erreur chargement achats');
import React, { useState, useEffect } from 'react';
    } finally {
import React, { useState, useEffect } from 'react';
      setLoading(false);
import React, { useState, useEffect } from 'react';
    }
import React, { useState, useEffect } from 'react';
  };
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const confirmPurchase = async (purchaseId) => {
import React, { useState, useEffect } from 'react';
    if (!confirm('Confirmer ce paiement ?')) return;
import React, { useState, useEffect } from 'react';
    
import React, { useState, useEffect } from 'react';
    try {
import React, { useState, useEffect } from 'react';
      const res = await fetch(`${API_BASE}/admin/purchases.php`, {
import React, { useState, useEffect } from 'react';
        method: 'PATCH',
import React, { useState, useEffect } from 'react';
        credentials: 'include',
import React, { useState, useEffect } from 'react';
        headers: { 'Content-Type': 'application/json' },
import React, { useState, useEffect } from 'react';
        body: JSON.stringify({ id: purchaseId, action: 'confirm_payment' })
import React, { useState, useEffect } from 'react';
      });
import React, { useState, useEffect } from 'react';
      
import React, { useState, useEffect } from 'react';
      const data = await res.json();
import React, { useState, useEffect } from 'react';
      if (data.success) {
import React, { useState, useEffect } from 'react';
        toast.success('Paiement confirmé avec succès');
import React, { useState, useEffect } from 'react';
        loadPurchases();
import React, { useState, useEffect } from 'react';
      } else {
import React, { useState, useEffect } from 'react';
        toast.error(data.error || 'Erreur lors de la confirmation');
import React, { useState, useEffect } from 'react';
      }
import React, { useState, useEffect } from 'react';
    } catch (err) {
import React, { useState, useEffect } from 'react';
      toast.error('Erreur lors de la confirmation');
import React, { useState, useEffect } from 'react';
    }
import React, { useState, useEffect } from 'react';
  };
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const deleteGame = async (gameId) => {
import React, { useState, useEffect } from 'react';
    if (!confirm('Supprimer ce jeu ? Cette action est irréversible.')) return;
import React, { useState, useEffect } from 'react';
    
import React, { useState, useEffect } from 'react';
    try {
import React, { useState, useEffect } from 'react';
      const res = await fetch(`${API_BASE}/admin/games.php?id=${gameId}`, {
import React, { useState, useEffect } from 'react';
        method: 'DELETE',
import React, { useState, useEffect } from 'react';
        credentials: 'include'
import React, { useState, useEffect } from 'react';
      });
import React, { useState, useEffect } from 'react';
      
import React, { useState, useEffect } from 'react';
      const data = await res.json();
import React, { useState, useEffect } from 'react';
      if (data.success) {
import React, { useState, useEffect } from 'react';
        toast.success('Jeu supprimé avec succès');
import React, { useState, useEffect } from 'react';
        loadGames();
import React, { useState, useEffect } from 'react';
      } else {
import React, { useState, useEffect } from 'react';
        toast.error(data.error || 'Erreur lors de la suppression');
import React, { useState, useEffect } from 'react';
      }
import React, { useState, useEffect } from 'react';
    } catch (err) {
import React, { useState, useEffect } from 'react';
      toast.error('Erreur lors de la suppression');
import React, { useState, useEffect } from 'react';
    }
import React, { useState, useEffect } from 'react';
  };
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const deletePackage = async (packageId) => {
import React, { useState, useEffect } from 'react';
    if (!confirm('Supprimer ce package ?')) return;
import React, { useState, useEffect } from 'react';
    
import React, { useState, useEffect } from 'react';
    try {
import React, { useState, useEffect } from 'react';
      const res = await fetch(`${API_BASE}/admin/game_packages.php?id=${packageId}`, {
import React, { useState, useEffect } from 'react';
        method: 'DELETE',
import React, { useState, useEffect } from 'react';
        credentials: 'include'
import React, { useState, useEffect } from 'react';
      });
import React, { useState, useEffect } from 'react';
      
import React, { useState, useEffect } from 'react';
      const data = await res.json();
import React, { useState, useEffect } from 'react';
      if (data.success) {
import React, { useState, useEffect } from 'react';
        toast.success('Package supprimé avec succès');
import React, { useState, useEffect } from 'react';
        loadPackages();
import React, { useState, useEffect } from 'react';
      } else {
import React, { useState, useEffect } from 'react';
        toast.error(data.error || 'Erreur lors de la suppression');
import React, { useState, useEffect } from 'react';
      }
import React, { useState, useEffect } from 'react';
    } catch (err) {
import React, { useState, useEffect } from 'react';
      toast.error('Erreur lors de la suppression');
import React, { useState, useEffect } from 'react';
    }
import React, { useState, useEffect } from 'react';
  };
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const deletePaymentMethod = async (methodId) => {
import React, { useState, useEffect } from 'react';
    if (!confirm('Supprimer cette méthode de paiement ?')) return;
import React, { useState, useEffect } from 'react';
    
import React, { useState, useEffect } from 'react';
    try {
import React, { useState, useEffect } from 'react';
      const res = await fetch(`${API_BASE}/admin/payment_methods_simple.php?id=${methodId}`, {
import React, { useState, useEffect } from 'react';
        method: 'DELETE',
import React, { useState, useEffect } from 'react';
        credentials: 'include'
import React, { useState, useEffect } from 'react';
      });
import React, { useState, useEffect } from 'react';
      
import React, { useState, useEffect } from 'react';
      const data = await res.json();
import React, { useState, useEffect } from 'react';
      if (data.success) {
import React, { useState, useEffect } from 'react';
        toast.success('Méthode supprimée avec succès');
import React, { useState, useEffect } from 'react';
        loadPaymentMethods();
import React, { useState, useEffect } from 'react';
      } else {
import React, { useState, useEffect } from 'react';
        toast.error(data.error || 'Erreur lors de la suppression');
import React, { useState, useEffect } from 'react';
      }
import React, { useState, useEffect } from 'react';
    } catch (err) {
import React, { useState, useEffect } from 'react';
      toast.error('Erreur lors de la suppression');
import React, { useState, useEffect } from 'react';
    }
import React, { useState, useEffect } from 'react';
  };
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const handleGameFormChange = (field, value) => {
import React, { useState, useEffect } from 'react';
    setGameForm(prev => ({ ...prev, [field]: value }));
import React, { useState, useEffect } from 'react';
    // Auto-generate slug from name (only when creating, not editing)
import React, { useState, useEffect } from 'react';
    if (field === 'name' && !editingGame && !gameForm.slug) {
import React, { useState, useEffect } from 'react';
      const slug = value.toLowerCase()
import React, { useState, useEffect } from 'react';
        .replace(/[^a-z0-9]+/g, '-')
import React, { useState, useEffect } from 'react';
        .replace(/^-+|-+$/g, '');
import React, { useState, useEffect } from 'react';
      setGameForm(prev => ({ ...prev, slug }));
import React, { useState, useEffect } from 'react';
    }
import React, { useState, useEffect } from 'react';
  };
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const handleOpenCreateModal = () => {
import React, { useState, useEffect } from 'react';
    setEditingGame(null);
import React, { useState, useEffect } from 'react';
    setGameForm(emptyForm);
import React, { useState, useEffect } from 'react';
    setShowGameModal(true);
import React, { useState, useEffect } from 'react';
  };
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const handleOpenEditModal = (game) => {
import React, { useState, useEffect } from 'react';
    setEditingGame(game);
import React, { useState, useEffect } from 'react';
    setGameForm({
import React, { useState, useEffect } from 'react';
      name: game.name || '',
import React, { useState, useEffect } from 'react';
      slug: game.slug || '',
import React, { useState, useEffect } from 'react';
      description: game.description || '',
import React, { useState, useEffect } from 'react';
      short_description: game.short_description || '',
import React, { useState, useEffect } from 'react';
      image_url: game.image_url || '',
import React, { useState, useEffect } from 'react';
      thumbnail_url: game.thumbnail_url || '',
import React, { useState, useEffect } from 'react';
      category: game.category || 'action',
import React, { useState, useEffect } from 'react';
      platform: game.platform || '',
import React, { useState, useEffect } from 'react';
      min_players: game.min_players || 1,
import React, { useState, useEffect } from 'react';
      max_players: game.max_players || 1,
import React, { useState, useEffect } from 'react';
      age_rating: game.age_rating || '',
import React, { useState, useEffect } from 'react';
      points_per_hour: game.points_per_hour || 10,
import React, { useState, useEffect } from 'react';
      base_price: parseFloat(game.base_price) || 0,
import React, { useState, useEffect } from 'react';
      is_reservable: game.is_reservable == 1,
import React, { useState, useEffect } from 'react';
      reservation_fee: parseFloat(game.reservation_fee) || 0,
import React, { useState, useEffect } from 'react';
      is_featured: game.is_featured == 1
import React, { useState, useEffect } from 'react';
    });
import React, { useState, useEffect } from 'react';
    setShowGameModal(true);
import React, { useState, useEffect } from 'react';
  };
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const handleCreateGame = async (e) => {
import React, { useState, useEffect } from 'react';
    e.preventDefault();
import React, { useState, useEffect } from 'react';
    
import React, { useState, useEffect } from 'react';
    if (!gameForm.name) {
import React, { useState, useEffect } from 'react';
      toast.error('Le nom du jeu est requis');
import React, { useState, useEffect } from 'react';
      return;
import React, { useState, useEffect } from 'react';
    }
import React, { useState, useEffect } from 'react';
    
import React, { useState, useEffect } from 'react';
    // Générer le slug automatiquement si non fourni
import React, { useState, useEffect } from 'react';
    const slug = gameForm.slug || gameForm.name.toLowerCase()
import React, { useState, useEffect } from 'react';
      .trim()
import React, { useState, useEffect } from 'react';
      .replace(/[^a-z0-9]+/g, '-')
import React, { useState, useEffect } from 'react';
      .replace(/^-+|-+$/g, '');
import React, { useState, useEffect } from 'react';
    
import React, { useState, useEffect } from 'react';
    try {
import React, { useState, useEffect } from 'react';
      setSubmitting(true);
import React, { useState, useEffect } from 'react';
      const res = await fetch(`${API_BASE}/admin/games.php`, {
import React, { useState, useEffect } from 'react';
        method: 'POST',
import React, { useState, useEffect } from 'react';
        credentials: 'include',
import React, { useState, useEffect } from 'react';
        headers: { 'Content-Type': 'application/json' },
import React, { useState, useEffect } from 'react';
        body: JSON.stringify({
import React, { useState, useEffect } from 'react';
          ...gameForm,
import React, { useState, useEffect } from 'react';
          slug: slug
import React, { useState, useEffect } from 'react';
        })
import React, { useState, useEffect } from 'react';
      });
import React, { useState, useEffect } from 'react';
      
import React, { useState, useEffect } from 'react';
      const data = await res.json();
import React, { useState, useEffect } from 'react';
      
import React, { useState, useEffect } from 'react';
      if (data.success) {
import React, { useState, useEffect } from 'react';
        toast.success('Jeu créé avec succès !');
import React, { useState, useEffect } from 'react';
        setShowGameModal(false);
import React, { useState, useEffect } from 'react';
        setGameForm(emptyForm);
import React, { useState, useEffect } from 'react';
        setEditingGame(null);
import React, { useState, useEffect } from 'react';
        loadGames();
import React, { useState, useEffect } from 'react';
      } else {
import React, { useState, useEffect } from 'react';
        toast.error(data.error || 'Erreur lors de la création');
import React, { useState, useEffect } from 'react';
      }
import React, { useState, useEffect } from 'react';
    } catch (err) {
import React, { useState, useEffect } from 'react';
      toast.error('Erreur lors de la création du jeu');
import React, { useState, useEffect } from 'react';
      console.error(err);
import React, { useState, useEffect } from 'react';
    } finally {
import React, { useState, useEffect } from 'react';
      setSubmitting(false);
import React, { useState, useEffect } from 'react';
    }
import React, { useState, useEffect } from 'react';
  };
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const handleUpdateGame = async (e) => {
import React, { useState, useEffect } from 'react';
    e.preventDefault();
import React, { useState, useEffect } from 'react';
    
import React, { useState, useEffect } from 'react';
    if (!gameForm.name) {
import React, { useState, useEffect } from 'react';
      toast.error('Le nom du jeu est requis');
import React, { useState, useEffect } from 'react';
      return;
import React, { useState, useEffect } from 'react';
    }
import React, { useState, useEffect } from 'react';
    
import React, { useState, useEffect } from 'react';
    // Générer le slug automatiquement si non fourni
import React, { useState, useEffect } from 'react';
    const slug = gameForm.slug || gameForm.name.toLowerCase()
import React, { useState, useEffect } from 'react';
      .trim()
import React, { useState, useEffect } from 'react';
      .replace(/[^a-z0-9]+/g, '-')
import React, { useState, useEffect } from 'react';
      .replace(/^-+|-+$/g, '');
import React, { useState, useEffect } from 'react';
    
import React, { useState, useEffect } from 'react';
    try {
import React, { useState, useEffect } from 'react';
      setSubmitting(true);
import React, { useState, useEffect } from 'react';
      const res = await fetch(`${API_BASE}/admin/games.php`, {
import React, { useState, useEffect } from 'react';
        method: 'PUT',
import React, { useState, useEffect } from 'react';
        credentials: 'include',
import React, { useState, useEffect } from 'react';
        headers: { 'Content-Type': 'application/json' },
import React, { useState, useEffect } from 'react';
        body: JSON.stringify({
import React, { useState, useEffect } from 'react';
          id: editingGame.id,
import React, { useState, useEffect } from 'react';
          ...gameForm,
import React, { useState, useEffect } from 'react';
          slug: slug
import React, { useState, useEffect } from 'react';
        })
import React, { useState, useEffect } from 'react';
      });
import React, { useState, useEffect } from 'react';
      
import React, { useState, useEffect } from 'react';
      const data = await res.json();
import React, { useState, useEffect } from 'react';
      
import React, { useState, useEffect } from 'react';
      if (data.success) {
import React, { useState, useEffect } from 'react';
        toast.success('Jeu mis à jour avec succès !');
import React, { useState, useEffect } from 'react';
        setShowGameModal(false);
import React, { useState, useEffect } from 'react';
        setGameForm(emptyForm);
import React, { useState, useEffect } from 'react';
        setEditingGame(null);
import React, { useState, useEffect } from 'react';
        loadGames();
import React, { useState, useEffect } from 'react';
      } else {
import React, { useState, useEffect } from 'react';
        toast.error(data.error || 'Erreur lors de la mise à jour');
import React, { useState, useEffect } from 'react';
      }
import React, { useState, useEffect } from 'react';
    } catch (err) {
import React, { useState, useEffect } from 'react';
      toast.error('Erreur lors de la mise à jour du jeu');
import React, { useState, useEffect } from 'react';
      console.error(err);
import React, { useState, useEffect } from 'react';
    } finally {
import React, { useState, useEffect } from 'react';
      setSubmitting(false);
import React, { useState, useEffect } from 'react';
    }
import React, { useState, useEffect } from 'react';
  };
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const tabs = [
import React, { useState, useEffect } from 'react';
    { id: 'games', label: 'Jeux', icon: Gamepad2 },
import React, { useState, useEffect } from 'react';
    { id: 'packages', label: 'Packages', icon: Package },
import React, { useState, useEffect } from 'react';
    { id: 'payment-methods', label: 'Paiements', icon: CreditCard },
import React, { useState, useEffect } from 'react';
    { id: 'purchases', label: 'Achats', icon: ShoppingCart },
import React, { useState, useEffect } from 'react';
    { id: 'reservations', label: 'Réservations', icon: Calendar }
import React, { useState, useEffect } from 'react';
  ];
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  return (
import React, { useState, useEffect } from 'react';
    <div className="min-h-screen bg-gradient-to-br from-purple-900 via-indigo-900 to-blue-900">
import React, { useState, useEffect } from 'react';
      <Navigation userType="admin" />
import React, { useState, useEffect } from 'react';
      
import React, { useState, useEffect } from 'react';
      {/* Main Content with Sidebar Offset */}
import React, { useState, useEffect } from 'react';
      <div className="lg:pl-64">
import React, { useState, useEffect } from 'react';
        <div className="container mx-auto px-4 py-8">
import React, { useState, useEffect } from 'react';
          {/* Header */}
import React, { useState, useEffect } from 'react';
          <div className="bg-white rounded-xl shadow-lg p-6 mb-6 text-gray-900">
import React, { useState, useEffect } from 'react';
            <h1 className="text-3xl font-bold text-purple-600 mb-2 flex items-center gap-3">
import React, { useState, useEffect } from 'react';
              <Gamepad2 className="w-8 h-8" />
import React, { useState, useEffect } from 'react';
              Gestion Boutique de Jeux
import React, { useState, useEffect } from 'react';
            </h1>
import React, { useState, useEffect } from 'react';
            <p className="text-gray-600">Gérez vos jeux, packages et méthodes de paiement</p>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
            {/* Tabs */}
import React, { useState, useEffect } from 'react';
            <div className="flex gap-4 mt-6 border-b">
import React, { useState, useEffect } from 'react';
              {tabs.map((tab) => {
import React, { useState, useEffect } from 'react';
                const Icon = tab.icon;
import React, { useState, useEffect } from 'react';
                return (
import React, { useState, useEffect } from 'react';
                  <button
import React, { useState, useEffect } from 'react';
                    key={tab.id}
import React, { useState, useEffect } from 'react';
                    onClick={() => setActiveTab(tab.id)}
import React, { useState, useEffect } from 'react';
                    className={`flex items-center gap-2 px-4 py-2 font-semibold border-b-2 transition-colors ${
import React, { useState, useEffect } from 'react';
                      activeTab === tab.id
import React, { useState, useEffect } from 'react';
                        ? 'border-purple-600 text-purple-600'
import React, { useState, useEffect } from 'react';
                        : 'border-transparent text-gray-500 hover:text-purple-600'
import React, { useState, useEffect } from 'react';
                    }`}
import React, { useState, useEffect } from 'react';
                  >
import React, { useState, useEffect } from 'react';
                    <Icon className="w-5 h-5" />
import React, { useState, useEffect } from 'react';
                    {tab.label}
import React, { useState, useEffect } from 'react';
                  </button>
import React, { useState, useEffect } from 'react';
                );
import React, { useState, useEffect } from 'react';
              })}
import React, { useState, useEffect } from 'react';
            </div>
import React, { useState, useEffect } from 'react';
          </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
          {/* Games Tab */}
import React, { useState, useEffect } from 'react';
          {activeTab === 'games' && (
import React, { useState, useEffect } from 'react';
          <div className="bg-white rounded-xl shadow-lg p-6 text-gray-900">
import React, { useState, useEffect } from 'react';
            <div className="flex justify-between items-center mb-6">
import React, { useState, useEffect } from 'react';
              <div className="relative flex-1 max-w-md">
import React, { useState, useEffect } from 'react';
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
import React, { useState, useEffect } from 'react';
                <input
import React, { useState, useEffect } from 'react';
                  type="text"
import React, { useState, useEffect } from 'react';
                  placeholder="Rechercher un jeu..."
import React, { useState, useEffect } from 'react';
                  value={searchTerm}
import React, { useState, useEffect } from 'react';
                  onChange={(e) => setSearchTerm(e.target.value)}
import React, { useState, useEffect } from 'react';
                  className="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
import React, { useState, useEffect } from 'react';
                />
import React, { useState, useEffect } from 'react';
              </div>
import React, { useState, useEffect } from 'react';
              <button
import React, { useState, useEffect } from 'react';
                onClick={handleOpenCreateModal}
import React, { useState, useEffect } from 'react';
                className="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
import React, { useState, useEffect } from 'react';
              >
import React, { useState, useEffect } from 'react';
                <Plus className="w-5 h-5" />
import React, { useState, useEffect } from 'react';
                Ajouter Jeu
import React, { useState, useEffect } from 'react';
              </button>
import React, { useState, useEffect } from 'react';
            </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
            {loading ? (
import React, { useState, useEffect } from 'react';
              <div className="text-center py-12">
import React, { useState, useEffect } from 'react';
                <div className="inline-block animate-spin rounded-full h-12 w-12 border-t-4 border-b-4 border-purple-600"></div>
import React, { useState, useEffect } from 'react';
                <p className="text-gray-600 mt-4">Chargement des jeux...</p>
import React, { useState, useEffect } from 'react';
              </div>
import React, { useState, useEffect } from 'react';
            ) : games.length === 0 ? (
import React, { useState, useEffect } from 'react';
              <div className="text-center py-12">
import React, { useState, useEffect } from 'react';
                <Gamepad2 className="w-16 h-16 text-gray-400 mx-auto mb-4" />
import React, { useState, useEffect } from 'react';
                <h3 className="text-lg font-semibold text-gray-700 mb-2">Aucun jeu disponible</h3>
import React, { useState, useEffect } from 'react';
                <p className="text-gray-500 mb-4">Commencez par créer votre premier jeu</p>
import React, { useState, useEffect } from 'react';
                <button
import React, { useState, useEffect } from 'react';
                  onClick={handleOpenCreateModal}
import React, { useState, useEffect } from 'react';
                  className="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
import React, { useState, useEffect } from 'react';
                >
import React, { useState, useEffect } from 'react';
                  <Plus className="w-5 h-5" />
import React, { useState, useEffect } from 'react';
                  Créer le Premier Jeu
import React, { useState, useEffect } from 'react';
                </button>
import React, { useState, useEffect } from 'react';
              </div>
import React, { useState, useEffect } from 'react';
            ) : (
import React, { useState, useEffect } from 'react';
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
import React, { useState, useEffect } from 'react';
                {games.map((game) => (
import React, { useState, useEffect } from 'react';
                  <div key={game.id} className="border rounded-lg overflow-hidden hover:shadow-lg transition-shadow">
import React, { useState, useEffect } from 'react';
                    <img
import React, { useState, useEffect } from 'react';
                      src={game.image_url || 'https://via.placeholder.com/400x200'}
import React, { useState, useEffect } from 'react';
                      alt={game.name}
import React, { useState, useEffect } from 'react';
                      className="w-full h-40 object-cover"
import React, { useState, useEffect } from 'react';
                    />
import React, { useState, useEffect } from 'react';
                    <div className="p-4">
import React, { useState, useEffect } from 'react';
                      <h3 className="font-bold text-lg mb-2">{game.name}</h3>
import React, { useState, useEffect } from 'react';
                      <p className="text-sm text-gray-600 mb-2">{game.short_description}</p>
import React, { useState, useEffect } from 'react';
                      <div className="flex gap-2 mb-3">
import React, { useState, useEffect } from 'react';
                        <span className={`px-2 py-1 text-xs rounded ${
import React, { useState, useEffect } from 'react';
                          game.is_active == 1 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
import React, { useState, useEffect } from 'react';
                        }`}>
import React, { useState, useEffect } from 'react';
                          {game.is_active == 1 ? 'Actif' : 'Inactif'}
import React, { useState, useEffect } from 'react';
                        </span>
import React, { useState, useEffect } from 'react';
                        <span className="px-2 py-1 text-xs rounded bg-blue-100 text-blue-700 capitalize">
import React, { useState, useEffect } from 'react';
                          {game.category}
import React, { useState, useEffect } from 'react';
                        </span>
import React, { useState, useEffect } from 'react';
                        {game.is_reservable == 1 && (
import React, { useState, useEffect } from 'react';
                          <span className="px-2 py-1 text-xs rounded bg-purple-100 text-purple-700">
import React, { useState, useEffect } from 'react';
                            Réservable
import React, { useState, useEffect } from 'react';
                          </span>
import React, { useState, useEffect } from 'react';
                        )}
import React, { useState, useEffect } from 'react';
                      </div>
import React, { useState, useEffect } from 'react';
                      <div className="text-sm mb-3">
import React, { useState, useEffect } from 'react';
                        <strong>{game.points_per_hour} pts/h</strong> • 
import React, { useState, useEffect } from 'react';
                        <strong> {game.base_price} XOF/h</strong>
import React, { useState, useEffect } from 'react';
                      </div>
import React, { useState, useEffect } from 'react';
                      {game.is_reservable == 1 && (
import React, { useState, useEffect } from 'react';
                        <div className="text-xs text-gray-600 mb-3">
import React, { useState, useEffect } from 'react';
                          Frais de réservation: <strong className="text-purple-700">{game.reservation_fee} XOF</strong>
import React, { useState, useEffect } from 'react';
                        </div>
import React, { useState, useEffect } from 'react';
                      )}
import React, { useState, useEffect } from 'react';
                      <div className="text-xs text-gray-500 mb-3">
import React, { useState, useEffect } from 'react';
                        📦 {game.active_packages_count || 0} packages • 
import React, { useState, useEffect } from 'react';
                        🛒 {game.total_purchases || 0} achats
import React, { useState, useEffect } from 'react';
                      </div>
import React, { useState, useEffect } from 'react';
                      <div className="flex gap-2">
import React, { useState, useEffect } from 'react';
                        <button
import React, { useState, useEffect } from 'react';
                          onClick={() => handleOpenEditModal(game)}
import React, { useState, useEffect } from 'react';
                          className="flex-1 bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600"
import React, { useState, useEffect } from 'react';
                        >
import React, { useState, useEffect } from 'react';
                          <Edit className="w-4 h-4 inline-block mr-1" />
import React, { useState, useEffect } from 'react';
                          Modifier
import React, { useState, useEffect } from 'react';
                        </button>
import React, { useState, useEffect } from 'react';
                        <button
import React, { useState, useEffect } from 'react';
                          onClick={() => deleteGame(game.id)}
import React, { useState, useEffect } from 'react';
                          className="bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600"
import React, { useState, useEffect } from 'react';
                        >
import React, { useState, useEffect } from 'react';
                          <Trash2 className="w-4 h-4" />
import React, { useState, useEffect } from 'react';
                        </button>
import React, { useState, useEffect } from 'react';
                      </div>
import React, { useState, useEffect } from 'react';
                    </div>
import React, { useState, useEffect } from 'react';
                  </div>
import React, { useState, useEffect } from 'react';
                ))}
import React, { useState, useEffect } from 'react';
              </div>
import React, { useState, useEffect } from 'react';
            )}
import React, { useState, useEffect } from 'react';
          </div>
import React, { useState, useEffect } from 'react';
          )}
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
          {/* Packages Tab */}
import React, { useState, useEffect } from 'react';
          {activeTab === 'packages' && (
import React, { useState, useEffect } from 'react';
          <div className="bg-white rounded-xl shadow-lg p-6 text-gray-900">
import React, { useState, useEffect } from 'react';
            <div className="flex justify-end mb-6">
import React, { useState, useEffect } from 'react';
              <button
import React, { useState, useEffect } from 'react';
                onClick={() => {
import React, { useState, useEffect } from 'react';
                  setEditingPackage(null);
import React, { useState, useEffect } from 'react';
                  setShowPackageModal(true);
import React, { useState, useEffect } from 'react';
                }}
import React, { useState, useEffect } from 'react';
                className="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
import React, { useState, useEffect } from 'react';
              >
import React, { useState, useEffect } from 'react';
                <Plus className="w-5 h-5" />
import React, { useState, useEffect } from 'react';
                Ajouter Package
import React, { useState, useEffect } from 'react';
              </button>
import React, { useState, useEffect } from 'react';
            </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
            {loading ? (
import React, { useState, useEffect } from 'react';
              <div className="text-center py-12">
import React, { useState, useEffect } from 'react';
                <div className="inline-block animate-spin rounded-full h-12 w-12 border-t-4 border-b-4 border-purple-600"></div>
import React, { useState, useEffect } from 'react';
                <p className="text-gray-600 mt-4">Chargement des packages...</p>
import React, { useState, useEffect } from 'react';
              </div>
import React, { useState, useEffect } from 'react';
            ) : packages.length === 0 ? (
import React, { useState, useEffect } from 'react';
              <div className="text-center py-12">
import React, { useState, useEffect } from 'react';
                <Package className="w-16 h-16 text-gray-400 mx-auto mb-4" />
import React, { useState, useEffect } from 'react';
                <h3 className="text-lg font-semibold text-gray-700 mb-2">Aucun package</h3>
import React, { useState, useEffect } from 'react';
                <p className="text-gray-500 mb-4">Commencez par ajouter un package de jeu</p>
import React, { useState, useEffect } from 'react';
                <button
import React, { useState, useEffect } from 'react';
                  onClick={() => {
import React, { useState, useEffect } from 'react';
                    setEditingPackage(null);
import React, { useState, useEffect } from 'react';
                    setShowPackageModal(true);
import React, { useState, useEffect } from 'react';
                  }}
import React, { useState, useEffect } from 'react';
                  className="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
import React, { useState, useEffect } from 'react';
                >
import React, { useState, useEffect } from 'react';
                  <Plus className="w-5 h-5" />
import React, { useState, useEffect } from 'react';
                  Ajouter le Premier Package
import React, { useState, useEffect } from 'react';
                </button>
import React, { useState, useEffect } from 'react';
              </div>
import React, { useState, useEffect } from 'react';
            ) : (
import React, { useState, useEffect } from 'react';
              <div className="overflow-x-auto">
import React, { useState, useEffect } from 'react';
                <table className="w-full min-w-[1100px]">
import React, { useState, useEffect } from 'react';
                  <thead className="bg-gray-50">
import React, { useState, useEffect } from 'react';
                    <tr>
import React, { useState, useEffect } from 'react';
                      <th className="px-4 py-3 text-left text-sm font-semibold text-gray-700 sticky top-0 bg-gray-50 z-10">Jeu</th>
import React, { useState, useEffect } from 'react';
                      <th className="px-4 py-3 text-left text-sm font-semibold text-gray-700 sticky top-0 bg-gray-50 z-10">Package</th>
import React, { useState, useEffect } from 'react';
                      <th className="px-4 py-3 text-left text-sm font-semibold text-gray-700 whitespace-nowrap sticky top-0 bg-gray-50 z-10">Durée</th>
import React, { useState, useEffect } from 'react';
                      <th className="px-4 py-3 text-right text-sm font-semibold text-gray-700 whitespace-nowrap sticky top-0 bg-gray-50 z-10">Prix</th>
import React, { useState, useEffect } from 'react';
                      <th className="px-4 py-3 text-right text-sm font-semibold text-gray-700 whitespace-nowrap sticky top-0 bg-gray-50 z-10">Points</th>
import React, { useState, useEffect } from 'react';
                      <th className="px-4 py-3 text-right text-sm font-semibold text-gray-700 whitespace-nowrap sticky top-0 bg-gray-50 z-10">Achats</th>
import React, { useState, useEffect } from 'react';
                      <th className="px-4 py-3 text-right text-sm font-semibold text-gray-700 whitespace-nowrap sticky top-0 bg-gray-50 z-10">Revenus</th>
import React, { useState, useEffect } from 'react';
                      <th className="px-4 py-3 text-left text-sm font-semibold text-gray-700 sticky top-0 bg-gray-50 z-10">Statut</th>
import React, { useState, useEffect } from 'react';
                      <th className="px-4 py-3 text-left text-sm font-semibold text-gray-700 sticky top-0 bg-gray-50 z-10">Actions</th>
import React, { useState, useEffect } from 'react';
                    </tr>
import React, { useState, useEffect } from 'react';
                  </thead>
import React, { useState, useEffect } from 'react';
                  <tbody className="text-gray-900">
import React, { useState, useEffect } from 'react';
                    {packages.map((pkg) => (
import React, { useState, useEffect } from 'react';
                      <tr key={pkg.id} className="border-b hover:bg-gray-50 odd:bg-white even:bg-gray-50/60">
import React, { useState, useEffect } from 'react';
                        <td className="px-4 py-3 text-sm max-w-[220px]"><div className="truncate" title={pkg.game_name}>{pkg.game_name}</div></td>
import React, { useState, useEffect } from 'react';
                        <td className="px-4 py-3 text-sm font-medium max-w-[260px]"><div className="truncate" title={pkg.name}>{pkg.name}</div></td>
import React, { useState, useEffect } from 'react';
                        <td className="px-4 py-3 text-sm whitespace-nowrap">{formatNumber(pkg.duration_minutes)} min</td>
import React, { useState, useEffect } from 'react';
                        <td className="px-4 py-3 text-sm text-right whitespace-nowrap">{formatPriceXOF(pkg.price)}</td>
import React, { useState, useEffect } from 'react';
                        <td className="px-4 py-3 text-sm text-right whitespace-nowrap">{formatNumber(pkg.points_earned)} pts</td>
import React, { useState, useEffect } from 'react';
                        <td className="px-4 py-3 text-sm text-right whitespace-nowrap">{formatNumber(pkg.purchases_count ?? 0)}</td>
import React, { useState, useEffect } from 'react';
                        <td className="px-4 py-3 text-sm text-right whitespace-nowrap">{pkg.revenue != null ? formatPriceXOF(pkg.revenue) : '—'}</td>
import React, { useState, useEffect } from 'react';
                        <td className="px-4 py-3">
import React, { useState, useEffect } from 'react';
                          <span className={`px-2 py-1 text-xs rounded font-medium ${
import React, { useState, useEffect } from 'react';
                            pkg.is_active == 1 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
import React, { useState, useEffect } from 'react';
                          }`}>
import React, { useState, useEffect } from 'react';
                            {pkg.is_active == 1 ? 'Actif' : 'Inactif'}
import React, { useState, useEffect } from 'react';
                          </span>
import React, { useState, useEffect } from 'react';
                        </td>
import React, { useState, useEffect } from 'react';
                        <td className="px-4 py-3 whitespace-nowrap">
import React, { useState, useEffect } from 'react';
                          <button
import React, { useState, useEffect } from 'react';
                            onClick={() => {
import React, { useState, useEffect } from 'react';
                              setEditingPackage(pkg);
import React, { useState, useEffect } from 'react';
                              setShowPackageModal(true);
import React, { useState, useEffect } from 'react';
                            }}
import React, { useState, useEffect } from 'react';
                            className="text-blue-600 hover:underline text-sm mr-3 font-medium"
import React, { useState, useEffect } from 'react';
                          >
import React, { useState, useEffect } from 'react';
                            Modifier
import React, { useState, useEffect } from 'react';
                          </button>
import React, { useState, useEffect } from 'react';
                          <button
import React, { useState, useEffect } from 'react';
                            onClick={() => deletePackage(pkg.id)}
import React, { useState, useEffect } from 'react';
                            className="text-red-600 hover:underline text-sm font-medium"
import React, { useState, useEffect } from 'react';
                          >
import React, { useState, useEffect } from 'react';
                            Supprimer
import React, { useState, useEffect } from 'react';
                          </button>
import React, { useState, useEffect } from 'react';
                        </td>
import React, { useState, useEffect } from 'react';
                      </tr>
import React, { useState, useEffect } from 'react';
                    ))}
import React, { useState, useEffect } from 'react';
                  </tbody>
import React, { useState, useEffect } from 'react';
                </table>
import React, { useState, useEffect } from 'react';
              </div>
import React, { useState, useEffect } from 'react';
            )}
import React, { useState, useEffect } from 'react';
          </div>
import React, { useState, useEffect } from 'react';
          )}
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
          {/* Payment Methods Tab */}
import React, { useState, useEffect } from 'react';
          {activeTab === 'payment-methods' && (
import React, { useState, useEffect } from 'react';
          <div className="bg-white rounded-xl shadow-lg p-6 text-gray-900">
import React, { useState, useEffect } from 'react';
            <div className="flex justify-end mb-6">
import React, { useState, useEffect } from 'react';
              <button
import React, { useState, useEffect } from 'react';
                onClick={() => {
import React, { useState, useEffect } from 'react';
                  setEditingPayment(null);
import React, { useState, useEffect } from 'react';
                  setShowPaymentModal(true);
import React, { useState, useEffect } from 'react';
                }}
import React, { useState, useEffect } from 'react';
                className="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
import React, { useState, useEffect } from 'react';
              >
import React, { useState, useEffect } from 'react';
                <Plus className="w-5 h-5" />
import React, { useState, useEffect } from 'react';
                Ajouter Méthode
import React, { useState, useEffect } from 'react';
              </button>
import React, { useState, useEffect } from 'react';
            </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
            {loading ? (
import React, { useState, useEffect } from 'react';
              <div className="text-center py-12">
import React, { useState, useEffect } from 'react';
                <div className="inline-block animate-spin rounded-full h-12 w-12 border-t-4 border-b-4 border-purple-600"></div>
import React, { useState, useEffect } from 'react';
                <p className="text-gray-600 mt-4">Chargement des méthodes de paiement...</p>
import React, { useState, useEffect } from 'react';
              </div>
import React, { useState, useEffect } from 'react';
            ) : paymentMethods.length === 0 ? (
import React, { useState, useEffect } from 'react';
              <div className="text-center py-12">
import React, { useState, useEffect } from 'react';
                <CreditCard className="w-16 h-16 text-gray-400 mx-auto mb-4" />
import React, { useState, useEffect } from 'react';
                <h3 className="text-lg font-semibold text-gray-700 mb-2">Aucune méthode de paiement</h3>
import React, { useState, useEffect } from 'react';
                <p className="text-gray-500 mb-4">Ajoutez une première méthode de paiement</p>
import React, { useState, useEffect } from 'react';
                <button
import React, { useState, useEffect } from 'react';
                  onClick={() => {
import React, { useState, useEffect } from 'react';
                    setEditingPayment(null);
import React, { useState, useEffect } from 'react';
                    setShowPaymentModal(true);
import React, { useState, useEffect } from 'react';
                  }}
import React, { useState, useEffect } from 'react';
                  className="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
import React, { useState, useEffect } from 'react';
                >
import React, { useState, useEffect } from 'react';
                  <Plus className="w-5 h-5" />
import React, { useState, useEffect } from 'react';
                  Ajouter la Première Méthode
import React, { useState, useEffect } from 'react';
                </button>
import React, { useState, useEffect } from 'react';
              </div>
import React, { useState, useEffect } from 'react';
            ) : (
import React, { useState, useEffect } from 'react';
              <div className="overflow-x-auto">
import React, { useState, useEffect } from 'react';
                <table className="w-full min-w-[900px]">
import React, { useState, useEffect } from 'react';
                  <thead className="bg-gray-50">
import React, { useState, useEffect } from 'react';
                    <tr>
import React, { useState, useEffect } from 'react';
                      <th className="px-4 py-3 text-left text-sm font-semibold text-gray-700 sticky top-0 bg-gray-50 z-10">Nom</th>
import React, { useState, useEffect } from 'react';
                      <th className="px-4 py-3 text-left text-sm font-semibold text-gray-700 sticky top-0 bg-gray-50 z-10">Slug</th>
import React, { useState, useEffect } from 'react';
                      <th className="px-4 py-3 text-left text-sm font-semibold text-gray-700 sticky top-0 bg-gray-50 z-10">Provider</th>
import React, { useState, useEffect } from 'react';
                      <th className="px-4 py-3 text-left text-sm font-semibold text-gray-700 sticky top-0 bg-gray-50 z-10">Type</th>
import React, { useState, useEffect } from 'react';
                      <th className="px-4 py-3 text-right text-sm font-semibold text-gray-700 sticky top-0 bg-gray-50 z-10">Frais</th>
import React, { useState, useEffect } from 'react';
                      <th className="px-4 py-3 text-left text-sm font-semibold text-gray-700 sticky top-0 bg-gray-50 z-10">Statut</th>
import React, { useState, useEffect } from 'react';
                      <th className="px-4 py-3 text-left text-sm font-semibold text-gray-700 sticky top-0 bg-gray-50 z-10">Actions</th>
import React, { useState, useEffect } from 'react';
                    </tr>
import React, { useState, useEffect } from 'react';
                  </thead>
import React, { useState, useEffect } from 'react';
                  <tbody className="text-gray-900">
import React, { useState, useEffect } from 'react';
                    {paymentMethods.map((pm) => (
import React, { useState, useEffect } from 'react';
                      <tr key={pm.id} className="border-b hover:bg-gray-50 odd:bg-white even:bg-gray-50/60">
import React, { useState, useEffect } from 'react';
                        <td className="px-4 py-3 text-sm font-medium max-w-[240px]"><div className="truncate" title={pm.name}>{pm.name}</div></td>
import React, { useState, useEffect } from 'react';
                        <td className="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">{pm.slug || '—'}</td>
import React, { useState, useEffect } from 'react';
                        <td className="px-4 py-3 text-sm">{pm.provider || 'N/A'}</td>
import React, { useState, useEffect } from 'react';
                        <td className="px-4 py-3 text-sm">{pm.requires_online_payment ? '🌐 En ligne' : '🏪 Sur place'}</td>
import React, { useState, useEffect } from 'react';
                        <td className="px-4 py-3 text-sm text-right whitespace-nowrap">{formatNumber(pm.fee_percentage ?? 0)}% + {formatPriceXOF(pm.fee_fixed ?? 0)}</td>
import React, { useState, useEffect } from 'react';
                        <td className="px-4 py-3">
import React, { useState, useEffect } from 'react';
                          <span className={`px-2 py-1 text-xs rounded font-medium ${
import React, { useState, useEffect } from 'react';
                            pm.is_active == 1 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
import React, { useState, useEffect } from 'react';
                          }`}>
import React, { useState, useEffect } from 'react';
                            {pm.is_active == 1 ? 'Actif' : 'Inactif'}
import React, { useState, useEffect } from 'react';
                          </span>
import React, { useState, useEffect } from 'react';
                        </td>
import React, { useState, useEffect } from 'react';
                        <td className="px-4 py-3 whitespace-nowrap">
import React, { useState, useEffect } from 'react';
                          <button
import React, { useState, useEffect } from 'react';
                            onClick={() => {
import React, { useState, useEffect } from 'react';
                              setEditingPayment(pm);
import React, { useState, useEffect } from 'react';
                              setShowPaymentModal(true);
import React, { useState, useEffect } from 'react';
                            }}
import React, { useState, useEffect } from 'react';
                            className="text-blue-600 hover:underline text-sm mr-3 font-medium"
import React, { useState, useEffect } from 'react';
                          >
import React, { useState, useEffect } from 'react';
                            Modifier
import React, { useState, useEffect } from 'react';
                          </button>
import React, { useState, useEffect } from 'react';
                          <button
import React, { useState, useEffect } from 'react';
                            onClick={() => deletePaymentMethod(pm.id)}
import React, { useState, useEffect } from 'react';
                            className="text-red-600 hover:underline text-sm font-medium"
import React, { useState, useEffect } from 'react';
                          >
import React, { useState, useEffect } from 'react';
                            Supprimer
import React, { useState, useEffect } from 'react';
                          </button>
import React, { useState, useEffect } from 'react';
                        </td>
import React, { useState, useEffect } from 'react';
                      </tr>
import React, { useState, useEffect } from 'react';
                    ))}
import React, { useState, useEffect } from 'react';
                  </tbody>
import React, { useState, useEffect } from 'react';
                </table>
import React, { useState, useEffect } from 'react';
              </div>
import React, { useState, useEffect } from 'react';
            )}
import React, { useState, useEffect } from 'react';
          </div>
import React, { useState, useEffect } from 'react';
          )}
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
          {/* Purchases Tab */}
import React, { useState, useEffect } from 'react';
          {activeTab === 'purchases' && (
import React, { useState, useEffect } from 'react';
          <div className="bg-white rounded-xl shadow-lg p-6 text-gray-900">
import React, { useState, useEffect } from 'react';
            {loading ? (
import React, { useState, useEffect } from 'react';
              <div className="text-center py-12">
import React, { useState, useEffect } from 'react';
                <div className="inline-block animate-spin rounded-full h-12 w-12 border-t-4 border-b-4 border-purple-600"></div>
import React, { useState, useEffect } from 'react';
                <p className="text-gray-600 mt-4">Chargement des achats...</p>
import React, { useState, useEffect } from 'react';
              </div>
import React, { useState, useEffect } from 'react';
            ) : purchases.length === 0 ? (
import React, { useState, useEffect } from 'react';
              <div className="text-center py-12">
import React, { useState, useEffect } from 'react';
                <ShoppingCart className="w-16 h-16 text-gray-400 mx-auto mb-4" />
import React, { useState, useEffect } from 'react';
                <h3 className="text-lg font-semibold text-gray-700 mb-2">Aucun achat</h3>
import React, { useState, useEffect } from 'react';
                <p className="text-gray-500">Les achats des joueurs apparaîtront ici</p>
import React, { useState, useEffect } from 'react';
              </div>
import React, { useState, useEffect } from 'react';
            ) : (
import React, { useState, useEffect } from 'react';
              <div className="overflow-x-auto">
import React, { useState, useEffect } from 'react';
                <table className="w-full min-w-[1000px]">
import React, { useState, useEffect } from 'react';
                  <thead className="bg-gray-50">
import React, { useState, useEffect } from 'react';
                    <tr>
import React, { useState, useEffect } from 'react';
                      <th className="px-4 py-3 text-left text-sm font-semibold text-gray-700 sticky top-0 bg-gray-50 z-10">Utilisateur</th>
import React, { useState, useEffect } from 'react';
                      <th className="px-4 py-3 text-left text-sm font-semibold text-gray-700 sticky top-0 bg-gray-50 z-10">Jeu</th>
import React, { useState, useEffect } from 'react';
                      <th className="px-4 py-3 text-right text-sm font-semibold text-gray-700 whitespace-nowrap sticky top-0 bg-gray-50 z-10">Durée</th>
import React, { useState, useEffect } from 'react';
                      <th className="px-4 py-3 text-right text-sm font-semibold text-gray-700 whitespace-nowrap sticky top-0 bg-gray-50 z-10">Prix</th>
import React, { useState, useEffect } from 'react';
                      <th className="px-4 py-3 text-left text-sm font-semibold text-gray-700 sticky top-0 bg-gray-50 z-10">Méthode</th>
import React, { useState, useEffect } from 'react';
                      <th className="px-4 py-3 text-left text-sm font-semibold text-gray-700 sticky top-0 bg-gray-50 z-10">Paiement</th>
import React, { useState, useEffect } from 'react';
                      <th className="px-4 py-3 text-left text-sm font-semibold text-gray-700 sticky top-0 bg-gray-50 z-10">Date</th>
import React, { useState, useEffect } from 'react';
                      <th className="px-4 py-3 text-left text-sm font-semibold text-gray-700 sticky top-0 bg-gray-50 z-10">Actions</th>
import React, { useState, useEffect } from 'react';
                    </tr>
import React, { useState, useEffect } from 'react';
                  </thead>
import React, { useState, useEffect } from 'react';
                  <tbody className="text-gray-900">
import React, { useState, useEffect } from 'react';
                    {purchases.map((p) => (
import React, { useState, useEffect } from 'react';
                      <tr key={p.id} className="border-b hover:bg-gray-50 odd:bg-white even:bg-gray-50/60">
import React, { useState, useEffect } from 'react';
                        <td className="px-4 py-3 text-sm font-medium max-w-[220px]"><div className="truncate" title={p.username}>{p.username}</div></td>
import React, { useState, useEffect } from 'react';
                        <td className="px-4 py-3 text-sm max-w-[260px]"><div className="truncate" title={p.game_name}>{p.game_name}</div></td>
import React, { useState, useEffect } from 'react';
                        <td className="px-4 py-3 text-sm text-right whitespace-nowrap">{formatNumber(p.duration_minutes)} min</td>
import React, { useState, useEffect } from 'react';
                        <td className="px-4 py-3 text-sm text-right whitespace-nowrap">{formatPriceXOF(p.price)}</td>
import React, { useState, useEffect } from 'react';
                        <td className="px-4 py-3 text-sm whitespace-nowrap">{p.payment_method_name || '—'}</td>
import React, { useState, useEffect } from 'react';
                        <td className="px-4 py-3">
import React, { useState, useEffect } from 'react';
                          <span className={`px-2 py-1 text-xs rounded font-medium ${
import React, { useState, useEffect } from 'react';
                            p.payment_status === 'completed'
import React, { useState, useEffect } from 'react';
                              ? 'bg-green-100 text-green-700'
import React, { useState, useEffect } from 'react';
                              : p.payment_status === 'failed'
import React, { useState, useEffect } from 'react';
                              ? 'bg-red-100 text-red-700'
import React, { useState, useEffect } from 'react';
                              : 'bg-yellow-100 text-yellow-700'
import React, { useState, useEffect } from 'react';
                          }`}>
import React, { useState, useEffect } from 'react';
                            {p.payment_status}
import React, { useState, useEffect } from 'react';
                          </span>
import React, { useState, useEffect } from 'react';
                        </td>
import React, { useState, useEffect } from 'react';
                        <td className="px-4 py-3 text-sm whitespace-nowrap">{formatDateTime(p.created_at)}</td>
import React, { useState, useEffect } from 'react';
                        <td className="px-4 py-3">
import React, { useState, useEffect } from 'react';
                          {p.payment_status === 'pending' && (
import React, { useState, useEffect } from 'react';
                            <button
import React, { useState, useEffect } from 'react';
                              onClick={() => confirmPurchase(p.id)}
import React, { useState, useEffect } from 'react';
                              className="text-green-600 hover:underline text-sm flex items-center gap-1 font-medium"
import React, { useState, useEffect } from 'react';
                            >
import React, { useState, useEffect } from 'react';
                              <CheckCircle className="w-4 h-4" />
import React, { useState, useEffect } from 'react';
                              Confirmer
import React, { useState, useEffect } from 'react';
                            </button>
import React, { useState, useEffect } from 'react';
                          )}
import React, { useState, useEffect } from 'react';
                        </td>
import React, { useState, useEffect } from 'react';
                      </tr>
import React, { useState, useEffect } from 'react';
                    ))}
import React, { useState, useEffect } from 'react';
                  </tbody>
import React, { useState, useEffect } from 'react';
                </table>
import React, { useState, useEffect } from 'react';
              </div>
import React, { useState, useEffect } from 'react';
            )}
import React, { useState, useEffect } from 'react';
          </div>
import React, { useState, useEffect } from 'react';
          )}
import React, { useState, useEffect } from 'react';
        </div>
import React, { useState, useEffect } from 'react';
      </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
      {/* Reservations Tab */}
import React, { useState, useEffect } from 'react';
      {activeTab === 'reservations' && (
import React, { useState, useEffect } from 'react';
      <div className="bg-white rounded-xl shadow-lg p-6 text-gray-900">
import React, { useState, useEffect } from 'react';
        {loading ? (
import React, { useState, useEffect } from 'react';
          <div className="text-center py-12">
import React, { useState, useEffect } from 'react';
            <div className="inline-block animate-spin rounded-full h-12 w-12 border-t-4 border-b-4 border-purple-600"></div>
import React, { useState, useEffect } from 'react';
            <p className="text-gray-600 mt-4">Chargement des réservations...</p>
import React, { useState, useEffect } from 'react';
          </div>
import React, { useState, useEffect } from 'react';
        ) : reservations.length === 0 ? (
import React, { useState, useEffect } from 'react';
          <div className="text-center py-12">
import React, { useState, useEffect } from 'react';
            <Calendar className="w-16 h-16 text-gray-400 mx-auto mb-4" />
import React, { useState, useEffect } from 'react';
            <h3 className="text-lg font-semibold text-gray-700 mb-2">Aucune réservation</h3>
import React, { useState, useEffect } from 'react';
            <p className="text-gray-500">Les réservations des joueurs apparaîtront ici</p>
import React, { useState, useEffect } from 'react';
          </div>
import React, { useState, useEffect } from 'react';
        ) : (
import React, { useState, useEffect } from 'react';
          <div className="overflow-x-auto">
import React, { useState, useEffect } from 'react';
            <table className="w-full min-w-[1150px]">
import React, { useState, useEffect } from 'react';
              <thead className="bg-gray-50">
import React, { useState, useEffect } from 'react';
                <tr>
import React, { useState, useEffect } from 'react';
                  <th className="px-4 py-3 text-left text-sm font-semibold text-gray-700 sticky top-0 bg-gray-50 z-10">Utilisateur</th>
import React, { useState, useEffect } from 'react';
                  <th className="px-4 py-3 text-left text-sm font-semibold text-gray-700 sticky top-0 bg-gray-50 z-10">Jeu</th>
import React, { useState, useEffect } from 'react';
                  <th className="px-4 py-3 text-left text-sm font-semibold text-gray-700 whitespace-nowrap sticky top-0 bg-gray-50 z-10">Début</th>
import React, { useState, useEffect } from 'react';
                  <th className="px-4 py-3 text-left text-sm font-semibold text-gray-700 whitespace-nowrap sticky top-0 bg-gray-50 z-10">Fin</th>
import React, { useState, useEffect } from 'react';
                  <th className="px-4 py-3 text-right text-sm font-semibold text-gray-700 whitespace-nowrap sticky top-0 bg-gray-50 z-10">Durée</th>
import React, { useState, useEffect } from 'react';
                  <th className="px-4 py-3 text-left text-sm font-semibold text-gray-700 sticky top-0 bg-gray-50 z-10">Statut</th>
import React, { useState, useEffect } from 'react';
                  <th className="px-4 py-3 text-right text-sm font-semibold text-gray-700 whitespace-nowrap sticky top-0 bg-gray-50 z-10">Prix</th>
import React, { useState, useEffect } from 'react';
                  <th className="px-4 py-3 text-right text-sm font-semibold text-gray-700 whitespace-nowrap sticky top-0 bg-gray-50 z-10">Frais</th>
import React, { useState, useEffect } from 'react';
                  <th className="px-4 py-3 text-right text-sm font-semibold text-gray-700 whitespace-nowrap sticky top-0 bg-gray-50 z-10">Total</th>
import React, { useState, useEffect } from 'react';
                  <th className="px-4 py-3 text-center text-sm font-semibold text-gray-700 whitespace-nowrap sticky top-0 bg-gray-50 z-10">Actions</th>
import React, { useState, useEffect } from 'react';
                </tr>
import React, { useState, useEffect } from 'react';
              </thead>
import React, { useState, useEffect } from 'react';
              <tbody className="text-gray-900">
import React, { useState, useEffect } from 'react';
                {reservations.map((r) => (
import React, { useState, useEffect } from 'react';
                  <tr key={r.id} className="border-b hover:bg-gray-50 odd:bg-white even:bg-gray-50/60">
import React, { useState, useEffect } from 'react';
                    <td className="px-4 py-3 text-sm font-medium max-w-[220px]"><div className="truncate" title={r.username}>{r.username}</div></td>
import React, { useState, useEffect } from 'react';
                    <td className="px-4 py-3 text-sm max-w-[260px]"><div className="truncate" title={r.game_name}>{r.game_name}</div></td>
import React, { useState, useEffect } from 'react';
                    <td className="px-4 py-3 text-sm whitespace-nowrap">{formatDateTime(r.scheduled_start)}</td>
import React, { useState, useEffect } from 'react';
                    <td className="px-4 py-3 text-sm whitespace-nowrap">{formatDateTime(r.scheduled_end)}</td>
import React, { useState, useEffect } from 'react';
                    <td className="px-4 py-3 text-sm text-right whitespace-nowrap">{formatNumber(r.duration_minutes)} min</td>
import React, { useState, useEffect } from 'react';
                    <td className="px-4 py-3">
import React, { useState, useEffect } from 'react';
                      <span className={`px-2 py-1 text-xs rounded font-medium ${
import React, { useState, useEffect } from 'react';
                        r.status === 'paid'
import React, { useState, useEffect } from 'react';
                          ? 'bg-green-100 text-green-700'
import React, { useState, useEffect } from 'react';
                          : r.status === 'cancelled'
import React, { useState, useEffect } from 'react';
                          ? 'bg-red-100 text-red-700'
import React, { useState, useEffect } from 'react';
                          : r.status === 'no_show'
import React, { useState, useEffect } from 'react';
                          ? 'bg-gray-200 text-gray-700'
import React, { useState, useEffect } from 'react';
                          : 'bg-yellow-100 text-yellow-700'
import React, { useState, useEffect } from 'react';
                      }`}>
import React, { useState, useEffect } from 'react';
                        {r.status}
import React, { useState, useEffect } from 'react';
                      </span>
import React, { useState, useEffect } from 'react';
                    </td>
import React, { useState, useEffect } from 'react';
                    <td className="px-4 py-3 text-sm text-right whitespace-nowrap">{formatPriceXOF(r.base_price)}</td>
import React, { useState, useEffect } from 'react';
                    <td className="px-4 py-3 text-sm text-right whitespace-nowrap">{formatPriceXOF(r.reservation_fee)}</td>
import React, { useState, useEffect } from 'react';
                    <td className="px-4 py-3 text-sm text-right whitespace-nowrap">{formatPriceXOF(r.total_price)}</td>
import React, { useState, useEffect } from 'react';
                    <td className="px-4 py-3">
import React, { useState, useEffect } from 'react';
                      <div className="flex items-center justify-center gap-2">
import React, { useState, useEffect } from 'react';
                        {r.status === 'pending_payment' && (
import React, { useState, useEffect } from 'react';
                          <>
import React, { useState, useEffect } from 'react';
                            <button
import React, { useState, useEffect } from 'react';
                              onClick={() => confirmReservation(r.id)}
import React, { useState, useEffect } from 'react';
                              className="px-3 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700 transition-colors"
import React, { useState, useEffect } from 'react';
                              title="Confirmer la réservation"
import React, { useState, useEffect } from 'react';
                            >
import React, { useState, useEffect } from 'react';
                              ✓ Confirmer
import React, { useState, useEffect } from 'react';
                            </button>
import React, { useState, useEffect } from 'react';
                            <button
import React, { useState, useEffect } from 'react';
                              onClick={() => cancelReservation(r.id)}
import React, { useState, useEffect } from 'react';
                              className="px-3 py-1 bg-red-600 text-white text-xs rounded hover:bg-red-700 transition-colors"
import React, { useState, useEffect } from 'react';
                              title="Annuler la réservation"
import React, { useState, useEffect } from 'react';
                            >
import React, { useState, useEffect } from 'react';
                              ✕ Annuler
import React, { useState, useEffect } from 'react';
                            </button>
import React, { useState, useEffect } from 'react';
                          </>
import React, { useState, useEffect } from 'react';
                        )}
import React, { useState, useEffect } from 'react';
                        {r.status === 'paid' && (
import React, { useState, useEffect } from 'react';
                          <>
import React, { useState, useEffect } from 'react';
                            <button
import React, { useState, useEffect } from 'react';
                              onClick={() => markReservationCompleted(r.id)}
import React, { useState, useEffect } from 'react';
                              className="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition-colors"
import React, { useState, useEffect } from 'react';
                              title="Marquer comme complétée"
import React, { useState, useEffect } from 'react';
                            >
import React, { useState, useEffect } from 'react';
                              ✓ Complétée
import React, { useState, useEffect } from 'react';
                            </button>
import React, { useState, useEffect } from 'react';
                            <button
import React, { useState, useEffect } from 'react';
                              onClick={() => markReservationNoShow(r.id)}
import React, { useState, useEffect } from 'react';
                              className="px-3 py-1 bg-gray-600 text-white text-xs rounded hover:bg-gray-700 transition-colors"
import React, { useState, useEffect } from 'react';
                              title="Marquer comme no-show"
import React, { useState, useEffect } from 'react';
                            >
import React, { useState, useEffect } from 'react';
                              ⊘ No-show
import React, { useState, useEffect } from 'react';
                            </button>
import React, { useState, useEffect } from 'react';
                            <button
import React, { useState, useEffect } from 'react';
                              onClick={() => cancelReservation(r.id)}
import React, { useState, useEffect } from 'react';
                              className="px-3 py-1 bg-red-600 text-white text-xs rounded hover:bg-red-700 transition-colors"
import React, { useState, useEffect } from 'react';
                              title="Annuler"
import React, { useState, useEffect } from 'react';
                            >
import React, { useState, useEffect } from 'react';
                              ✕
import React, { useState, useEffect } from 'react';
                            </button>
import React, { useState, useEffect } from 'react';
                          </>
import React, { useState, useEffect } from 'react';
                        )}
import React, { useState, useEffect } from 'react';
                        {(r.status === 'cancelled' || r.status === 'completed' || r.status === 'no_show') && (
import React, { useState, useEffect } from 'react';
                          <span className="text-gray-400 text-xs">-</span>
import React, { useState, useEffect } from 'react';
                        )}
import React, { useState, useEffect } from 'react';
                      </div>
import React, { useState, useEffect } from 'react';
                    </td>
import React, { useState, useEffect } from 'react';
                  </tr>
import React, { useState, useEffect } from 'react';
                ))}
import React, { useState, useEffect } from 'react';
              </tbody>
import React, { useState, useEffect } from 'react';
            </table>
import React, { useState, useEffect } from 'react';
          </div>
import React, { useState, useEffect } from 'react';
        )}
import React, { useState, useEffect } from 'react';
      </div>
import React, { useState, useEffect } from 'react';
      )}
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
      {/* Game Form Modal */}
import React, { useState, useEffect } from 'react';
      {showGameModal && (
import React, { useState, useEffect } from 'react';
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 overflow-y-auto">
import React, { useState, useEffect } from 'react';
          <div className="bg-white rounded-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
import React, { useState, useEffect } from 'react';
            <div className="sticky top-0 bg-white border-b p-6 flex justify-between items-center">
import React, { useState, useEffect } from 'react';
              <h2 className="text-2xl font-bold text-purple-600">
import React, { useState, useEffect } from 'react';
                {editingGame ? 'Modifier le Jeu' : 'Ajouter un Nouveau Jeu'}
import React, { useState, useEffect } from 'react';
              </h2>
import React, { useState, useEffect } from 'react';
              <button
import React, { useState, useEffect } from 'react';
                onClick={() => {
import React, { useState, useEffect } from 'react';
                  setShowGameModal(false);
import React, { useState, useEffect } from 'react';
                  setEditingGame(null);
import React, { useState, useEffect } from 'react';
                  setGameForm(emptyForm);
import React, { useState, useEffect } from 'react';
                }}
import React, { useState, useEffect } from 'react';
                className="text-gray-500 hover:text-gray-700 text-2xl"
import React, { useState, useEffect } from 'react';
              >
import React, { useState, useEffect } from 'react';
                ×
import React, { useState, useEffect } from 'react';
              </button>
import React, { useState, useEffect } from 'react';
            </div>
import React, { useState, useEffect } from 'react';
            
import React, { useState, useEffect } from 'react';
            <form onSubmit={editingGame ? handleUpdateGame : handleCreateGame} className="p-6">
import React, { useState, useEffect } from 'react';
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
import React, { useState, useEffect } from 'react';
                {/* Nom */}
import React, { useState, useEffect } from 'react';
                <div className="md:col-span-2">
import React, { useState, useEffect } from 'react';
                  <label className="block text-sm font-semibold mb-2">Nom du Jeu *</label>
import React, { useState, useEffect } from 'react';
                  <input
import React, { useState, useEffect } from 'react';
                    type="text"
import React, { useState, useEffect } from 'react';
                    required
import React, { useState, useEffect } from 'react';
                    value={gameForm.name}
import React, { useState, useEffect } from 'react';
                    onChange={(e) => handleGameFormChange('name', e.target.value)}
import React, { useState, useEffect } from 'react';
                    className="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
import React, { useState, useEffect } from 'react';
                    placeholder="Ex: FIFA 2024"
import React, { useState, useEffect } from 'react';
                  />
import React, { useState, useEffect } from 'react';
                </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
                {/* Slug */}
import React, { useState, useEffect } from 'react';
                <div className="md:col-span-2">
import React, { useState, useEffect } from 'react';
                  <label className="block text-sm font-semibold mb-2">Slug (URL) - Auto-généré</label>
import React, { useState, useEffect } from 'react';
                  <input
import React, { useState, useEffect } from 'react';
                    type="text"
import React, { useState, useEffect } from 'react';
                    value={gameForm.slug}
import React, { useState, useEffect } from 'react';
                    onChange={(e) => handleGameFormChange('slug', e.target.value)}
import React, { useState, useEffect } from 'react';
                    className="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-gray-50"
import React, { useState, useEffect } from 'react';
                    placeholder="Laissez vide pour génération automatique"
import React, { useState, useEffect } from 'react';
                  />
import React, { useState, useEffect } from 'react';
                  <p className="text-xs text-gray-500 mt-1">Si vide, sera généré automatiquement à partir du nom</p>
import React, { useState, useEffect } from 'react';
                </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
                {/* Short Description */}
import React, { useState, useEffect } from 'react';
                <div className="md:col-span-2">
import React, { useState, useEffect } from 'react';
                  <label className="block text-sm font-semibold mb-2">Description Courte</label>
import React, { useState, useEffect } from 'react';
                  <input
import React, { useState, useEffect } from 'react';
                    type="text"
import React, { useState, useEffect } from 'react';
                    value={gameForm.short_description}
import React, { useState, useEffect } from 'react';
                    onChange={(e) => handleGameFormChange('short_description', e.target.value)}
import React, { useState, useEffect } from 'react';
                    className="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
import React, { useState, useEffect } from 'react';
                    placeholder="Jeu de football avec tous les championnats officiels"
import React, { useState, useEffect } from 'react';
                  />
import React, { useState, useEffect } from 'react';
                </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
                {/* Description */}
import React, { useState, useEffect } from 'react';
                <div className="md:col-span-2">
import React, { useState, useEffect } from 'react';
                  <label className="block text-sm font-semibold mb-2">Description Complète</label>
import React, { useState, useEffect } from 'react';
                  <textarea
import React, { useState, useEffect } from 'react';
                    value={gameForm.description}
import React, { useState, useEffect } from 'react';
                    onChange={(e) => handleGameFormChange('description', e.target.value)}
import React, { useState, useEffect } from 'react';
                    className="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
import React, { useState, useEffect } from 'react';
                    rows="3"
import React, { useState, useEffect } from 'react';
                    placeholder="Description détaillée du jeu..."
import React, { useState, useEffect } from 'react';
                  />
import React, { useState, useEffect } from 'react';
                </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
                {/* Image Upload */}
import React, { useState, useEffect } from 'react';
                <div className="md:col-span-2">
import React, { useState, useEffect } from 'react';
                  <ImageUpload
import React, { useState, useEffect } from 'react';
                    label="Image du Jeu"
import React, { useState, useEffect } from 'react';
                    value={gameForm.image_url}
import React, { useState, useEffect } from 'react';
                    onChange={(url) => handleGameFormChange('image_url', url)}
import React, { useState, useEffect } from 'react';
                  />
import React, { useState, useEffect } from 'react';
                </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
                {/* Category */}
import React, { useState, useEffect } from 'react';
                <div>
import React, { useState, useEffect } from 'react';
                  <label className="block text-sm font-semibold mb-2">Catégorie *</label>
import React, { useState, useEffect } from 'react';
                  <select
import React, { useState, useEffect } from 'react';
                    value={gameForm.category}
import React, { useState, useEffect } from 'react';
                    onChange={(e) => handleGameFormChange('category', e.target.value)}
import React, { useState, useEffect } from 'react';
                    className="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
import React, { useState, useEffect } from 'react';
                  >
import React, { useState, useEffect } from 'react';
                    <option value="action">Action</option>
import React, { useState, useEffect } from 'react';
                    <option value="adventure">Adventure</option>
import React, { useState, useEffect } from 'react';
                    <option value="sports">Sports</option>
import React, { useState, useEffect } from 'react';
                    <option value="racing">Racing</option>
import React, { useState, useEffect } from 'react';
                    <option value="strategy">Strategy</option>
import React, { useState, useEffect } from 'react';
                    <option value="rpg">RPG</option>
import React, { useState, useEffect } from 'react';
                    <option value="fighting">Fighting</option>
import React, { useState, useEffect } from 'react';
                    <option value="simulation">Simulation</option>
import React, { useState, useEffect } from 'react';
                    <option value="vr">VR</option>
import React, { useState, useEffect } from 'react';
                    <option value="retro">Retro</option>
import React, { useState, useEffect } from 'react';
                    <option value="other">Other</option>
import React, { useState, useEffect } from 'react';
                  </select>
import React, { useState, useEffect } from 'react';
                </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
                {/* Platform */}
import React, { useState, useEffect } from 'react';
                <div>
import React, { useState, useEffect } from 'react';
                  <label className="block text-sm font-semibold mb-2">Plateforme</label>
import React, { useState, useEffect } from 'react';
                  <input
import React, { useState, useEffect } from 'react';
                    type="text"
import React, { useState, useEffect } from 'react';
                    value={gameForm.platform}
import React, { useState, useEffect } from 'react';
                    onChange={(e) => handleGameFormChange('platform', e.target.value)}
import React, { useState, useEffect } from 'react';
                    className="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
import React, { useState, useEffect } from 'react';
                    placeholder="PS5, Xbox, PC..."
import React, { useState, useEffect } from 'react';
                  />
import React, { useState, useEffect } from 'react';
                </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
                {/* Min Players */}
import React, { useState, useEffect } from 'react';
                <div>
import React, { useState, useEffect } from 'react';
                  <label className="block text-sm font-semibold mb-2">Joueurs Min</label>
import React, { useState, useEffect } from 'react';
                  <input
import React, { useState, useEffect } from 'react';
                    type="number"
import React, { useState, useEffect } from 'react';
                    min="1"
import React, { useState, useEffect } from 'react';
                    value={gameForm.min_players}
import React, { useState, useEffect } from 'react';
                    onChange={(e) => handleGameFormChange('min_players', parseInt(e.target.value))}
import React, { useState, useEffect } from 'react';
                    className="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
import React, { useState, useEffect } from 'react';
                  />
import React, { useState, useEffect } from 'react';
                </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
                {/* Max Players */}
import React, { useState, useEffect } from 'react';
                <div>
import React, { useState, useEffect } from 'react';
                  <label className="block text-sm font-semibold mb-2">Joueurs Max</label>
import React, { useState, useEffect } from 'react';
                  <input
import React, { useState, useEffect } from 'react';
                    type="number"
import React, { useState, useEffect } from 'react';
                    min="1"
import React, { useState, useEffect } from 'react';
                    value={gameForm.max_players}
import React, { useState, useEffect } from 'react';
                    onChange={(e) => handleGameFormChange('max_players', parseInt(e.target.value))}
import React, { useState, useEffect } from 'react';
                    className="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
import React, { useState, useEffect } from 'react';
                  />
import React, { useState, useEffect } from 'react';
                </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
                {/* Age Rating */}
import React, { useState, useEffect } from 'react';
                <div>
import React, { useState, useEffect } from 'react';
                  <label className="block text-sm font-semibold mb-2">Classification d'âge</label>
import React, { useState, useEffect } from 'react';
                  <input
import React, { useState, useEffect } from 'react';
                    type="text"
import React, { useState, useEffect } from 'react';
                    value={gameForm.age_rating}
import React, { useState, useEffect } from 'react';
                    onChange={(e) => handleGameFormChange('age_rating', e.target.value)}
import React, { useState, useEffect } from 'react';
                    className="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
import React, { useState, useEffect } from 'react';
                    placeholder="PEGI 3, 12, 18..."
import React, { useState, useEffect } from 'react';
                  />
import React, { useState, useEffect } from 'react';
                </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
                {/* Points per Hour */}
import React, { useState, useEffect } from 'react';
                <div>
import React, { useState, useEffect } from 'react';
                  <label className="block text-sm font-semibold mb-2">Points par Heure</label>
import React, { useState, useEffect } from 'react';
                  <input
import React, { useState, useEffect } from 'react';
                    type="number"
import React, { useState, useEffect } from 'react';
                    min="0"
import React, { useState, useEffect } from 'react';
                    value={gameForm.points_per_hour}
import React, { useState, useEffect } from 'react';
                    onChange={(e) => handleGameFormChange('points_per_hour', parseInt(e.target.value))}
import React, { useState, useEffect } from 'react';
                    className="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
import React, { useState, useEffect } from 'react';
                  />
import React, { useState, useEffect } from 'react';
                </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
                {/* Base Price */}
import React, { useState, useEffect } from 'react';
                <div>
import React, { useState, useEffect } from 'react';
                  <label className="block text-sm font-semibold mb-2">Prix de Base (XOF/h)</label>
import React, { useState, useEffect } from 'react';
                  <input
import React, { useState, useEffect } from 'react';
                    type="number"
import React, { useState, useEffect } from 'react';
                    min="0"
import React, { useState, useEffect } from 'react';
                    step="0.01"
import React, { useState, useEffect } from 'react';
                    value={gameForm.base_price}
import React, { useState, useEffect } from 'react';
                    onChange={(e) => handleGameFormChange('base_price', parseFloat(e.target.value))}
import React, { useState, useEffect } from 'react';
                    className="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
import React, { useState, useEffect } from 'react';
                  />
import React, { useState, useEffect } from 'react';
                </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
                {/* Reservable Checkbox */}
import React, { useState, useEffect } from 'react';
                <div className="md:col-span-2">
import React, { useState, useEffect } from 'react';
                  <label className="flex items-center gap-2 cursor-pointer">
import React, { useState, useEffect } from 'react';
                    <input
import React, { useState, useEffect } from 'react';
                      type="checkbox"
import React, { useState, useEffect } from 'react';
                      checked={gameForm.is_reservable}
import React, { useState, useEffect } from 'react';
                      onChange={(e) => handleGameFormChange('is_reservable', e.target.checked)}
import React, { useState, useEffect } from 'react';
                      className="w-5 h-5 text-purple-600 rounded focus:ring-2 focus:ring-purple-500"
import React, { useState, useEffect } from 'react';
                    />
import React, { useState, useEffect } from 'react';
                    <span className="text-sm font-semibold">Jeu réservable (avec créneau horaire)</span>
import React, { useState, useEffect } from 'react';
                  </label>
import React, { useState, useEffect } from 'react';
                </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
                {/* Reservation Fee (shown only if reservable) */}
import React, { useState, useEffect } from 'react';
                {gameForm.is_reservable && (
import React, { useState, useEffect } from 'react';
                  <div className="md:col-span-2">
import React, { useState, useEffect } from 'react';
                    <label className="block text-sm font-semibold mb-2">Frais de Réservation (XOF)</label>
import React, { useState, useEffect } from 'react';
                    <input
import React, { useState, useEffect } from 'react';
                      type="number"
import React, { useState, useEffect } from 'react';
                      min="0"
import React, { useState, useEffect } from 'react';
                      step="0.01"
import React, { useState, useEffect } from 'react';
                      value={gameForm.reservation_fee}
import React, { useState, useEffect } from 'react';
                      onChange={(e) => handleGameFormChange('reservation_fee', parseFloat(e.target.value))}
import React, { useState, useEffect } from 'react';
                      className="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
import React, { useState, useEffect } from 'react';
                      placeholder="Ex: 500"
import React, { useState, useEffect } from 'react';
                    />
import React, { useState, useEffect } from 'react';
                    <p className="text-xs text-gray-500 mt-1">Frais supplémentaires pour réserver un créneau horaire précis</p>
import React, { useState, useEffect } from 'react';
                  </div>
import React, { useState, useEffect } from 'react';
                )}
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
                {/* Featured */}
import React, { useState, useEffect } from 'react';
                <div className="md:col-span-2">
import React, { useState, useEffect } from 'react';
                  <label className="flex items-center gap-2 cursor-pointer">
import React, { useState, useEffect } from 'react';
                    <input
import React, { useState, useEffect } from 'react';
                      type="checkbox"
import React, { useState, useEffect } from 'react';
                      checked={gameForm.is_featured}
import React, { useState, useEffect } from 'react';
                      onChange={(e) => handleGameFormChange('is_featured', e.target.checked)}
import React, { useState, useEffect } from 'react';
                      className="w-5 h-5 text-purple-600 rounded focus:ring-2 focus:ring-purple-500"
import React, { useState, useEffect } from 'react';
                    />
import React, { useState, useEffect } from 'react';
                    <span className="text-sm font-semibold">Mettre en avant (Featured)</span>
import React, { useState, useEffect } from 'react';
                  </label>
import React, { useState, useEffect } from 'react';
                </div>
import React, { useState, useEffect } from 'react';
              </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
              {/* Buttons */}
import React, { useState, useEffect } from 'react';
              <div className="flex gap-3 mt-6">
import React, { useState, useEffect } from 'react';
                <button
import React, { useState, useEffect } from 'react';
                  type="button"
import React, { useState, useEffect } from 'react';
                  onClick={() => {
import React, { useState, useEffect } from 'react';
                    setShowGameModal(false);
import React, { useState, useEffect } from 'react';
                    setEditingGame(null);
import React, { useState, useEffect } from 'react';
                    setGameForm(emptyForm);
import React, { useState, useEffect } from 'react';
                  }}
import React, { useState, useEffect } from 'react';
                  className="flex-1 px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-semibold"
import React, { useState, useEffect } from 'react';
                  disabled={submitting}
import React, { useState, useEffect } from 'react';
                >
import React, { useState, useEffect } from 'react';
                  Annuler
import React, { useState, useEffect } from 'react';
                </button>
import React, { useState, useEffect } from 'react';
                <button
import React, { useState, useEffect } from 'react';
                  type="submit"
import React, { useState, useEffect } from 'react';
                  className="flex-1 px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
import React, { useState, useEffect } from 'react';
                  disabled={submitting}
import React, { useState, useEffect } from 'react';
                >
import React, { useState, useEffect } from 'react';
                  {submitting 
import React, { useState, useEffect } from 'react';
                    ? (editingGame ? 'Mise à jour...' : 'Création...') 
import React, { useState, useEffect } from 'react';
                    : (editingGame ? 'Mettre à Jour' : 'Créer le Jeu')
import React, { useState, useEffect } from 'react';
                  }
import React, { useState, useEffect } from 'react';
                </button>
import React, { useState, useEffect } from 'react';
              </div>
import React, { useState, useEffect } from 'react';
            </form>
import React, { useState, useEffect } from 'react';
          </div>
import React, { useState, useEffect } from 'react';
        </div>
import React, { useState, useEffect } from 'react';
      )}
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
      {/* Package Modal */}
import React, { useState, useEffect } from 'react';
      <PackageModal
import React, { useState, useEffect } from 'react';
        isOpen={showPackageModal}
import React, { useState, useEffect } from 'react';
        onClose={() => {
import React, { useState, useEffect } from 'react';
          setShowPackageModal(false);
import React, { useState, useEffect } from 'react';
          setEditingPackage(null);
import React, { useState, useEffect } from 'react';
        }}
import React, { useState, useEffect } from 'react';
        editingPackage={editingPackage}
import React, { useState, useEffect } from 'react';
        games={games}
import React, { useState, useEffect } from 'react';
        onSuccess={loadPackages}
import React, { useState, useEffect } from 'react';
      />
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
      {/* Payment Method Modal */}
import React, { useState, useEffect } from 'react';
      <PaymentMethodModal
import React, { useState, useEffect } from 'react';
        isOpen={showPaymentModal}
import React, { useState, useEffect } from 'react';
        onClose={() => {
import React, { useState, useEffect } from 'react';
          setShowPaymentModal(false);
import React, { useState, useEffect } from 'react';
          setEditingPayment(null);
import React, { useState, useEffect } from 'react';
        }}
import React, { useState, useEffect } from 'react';
        editingPayment={editingPayment}
import React, { useState, useEffect } from 'react';
        onSuccess={loadPaymentMethods}
import React, { useState, useEffect } from 'react';
      />
import React, { useState, useEffect } from 'react';
    </div>
import React, { useState, useEffect } from 'react';
  );
import React, { useState, useEffect } from 'react';
}
