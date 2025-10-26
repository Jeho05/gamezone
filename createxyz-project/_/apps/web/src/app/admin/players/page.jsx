import React, { useState, useEffect } from 'react';
import { useState, useEffect, useCallback } from 'react';
import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import React, { useState, useEffect } from 'react';
import Navigation from '../../../components/Navigation';
import React, { useState, useEffect } from 'react';
import { 
import React, { useState, useEffect } from 'react';
  Users, 
import React, { useState, useEffect } from 'react';
  Search, 
import React, { useState, useEffect } from 'react';
  Plus,
import React, { useState, useEffect } from 'react';
  Minus,
import React, { useState, useEffect } from 'react';
  Edit3,
import React, { useState, useEffect } from 'react';
  Eye,
import React, { useState, useEffect } from 'react';
  Coins,
import React, { useState, useEffect } from 'react';
  Star,
import React, { useState, useEffect } from 'react';
  Calendar,
import React, { useState, useEffect } from 'react';
  Filter
import React, { useState, useEffect } from 'react';
} from 'lucide-react';
import React, { useState, useEffect } from 'react';
import { toast } from 'sonner';
import React, { useState, useEffect } from 'react';
import API_BASE from '../../../utils/apiBase';
import React, { useState, useEffect } from 'react';
import { resolveAvatarUrl } from '../../../utils/avatarUrl';
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
export default function PlayersManagement() {
import React, { useState, useEffect } from 'react';
  const navigate = useNavigate();
import React, { useState, useEffect } from 'react';
  const [searchTerm, setSearchTerm] = useState('');
import React, { useState, useEffect } from 'react';
  const [selectedFilter, setSelectedFilter] = useState('all');
import React, { useState, useEffect } from 'react';
  const [showPointsModal, setShowPointsModal] = useState(false);
import React, { useState, useEffect } from 'react';
  const [selectedPlayer, setSelectedPlayer] = useState(null);
import React, { useState, useEffect } from 'react';
  const [pointsToAdd, setPointsToAdd] = useState(0);
import React, { useState, useEffect } from 'react';
  const [playersData, setPlayersData] = useState([]);
import React, { useState, useEffect } from 'react';
  const [loading, setLoading] = useState(false);
import React, { useState, useEffect } from 'react';
  const [error, setError] = useState(null);
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const fetchPlayers = useCallback(async () => {
import React, { useState, useEffect } from 'react';
    try {
import React, { useState, useEffect } from 'react';
      setLoading(true);
import React, { useState, useEffect } from 'react';
      setError(null);
import React, { useState, useEffect } from 'react';
      const res = await fetch(`${API_BASE}/users/index.php?limit=100`, { credentials: 'include' });
import React, { useState, useEffect } from 'react';
      const data = await res.json();
import React, { useState, useEffect } from 'react';
      if (!res.ok) throw new Error(data?.error || 'Échec du chargement des joueurs');
import React, { useState, useEffect } from 'react';
      const items = (data.items || []).map((u) => ({
import React, { useState, useEffect } from 'react';
        id: u.id,
import React, { useState, useEffect } from 'react';
        username: u.username,
import React, { useState, useEffect } from 'react';
        email: u.email,
import React, { useState, useEffect } from 'react';
        avatar: resolveAvatarUrl(u.avatar_url, u.username),
import React, { useState, useEffect } from 'react';
        points: u.points ?? 0,
import React, { useState, useEffect } from 'react';
        level: u.level || 'Gamer',
import React, { useState, useEffect } from 'react';
        joinDate: u.join_date || '',
import React, { useState, useEffect } from 'react';
        lastActive: u.last_active || '',
import React, { useState, useEffect } from 'react';
        totalSessions: u.totalSessions || 0,
import React, { useState, useEffect } from 'react';
        status: u.status || 'active',
import React, { useState, useEffect } from 'react';
      }));
import React, { useState, useEffect } from 'react';
      setPlayersData(items);
import React, { useState, useEffect } from 'react';
    } catch (e) {
import React, { useState, useEffect } from 'react';
      setError(e.message);
import React, { useState, useEffect } from 'react';
    } finally {
import React, { useState, useEffect } from 'react';
      setLoading(false);
import React, { useState, useEffect } from 'react';
    }
import React, { useState, useEffect } from 'react';
  }, []);
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  useEffect(() => {
import React, { useState, useEffect } from 'react';
    fetchPlayers();
import React, { useState, useEffect } from 'react';
  }, [fetchPlayers]);
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const filteredPlayers = playersData.filter(player => {
import React, { useState, useEffect } from 'react';
    const matchesSearch = player.username.toLowerCase().includes(searchTerm.toLowerCase()) ||
import React, { useState, useEffect } from 'react';
                         player.email.toLowerCase().includes(searchTerm.toLowerCase());
import React, { useState, useEffect } from 'react';
    
import React, { useState, useEffect } from 'react';
    if (selectedFilter === 'all') return matchesSearch;
import React, { useState, useEffect } from 'react';
    return matchesSearch && player.status === selectedFilter;
import React, { useState, useEffect } from 'react';
  });
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const handlePointsAdjustment = (player, action) => {
import React, { useState, useEffect } from 'react';
    setSelectedPlayer(player);
import React, { useState, useEffect } from 'react';
    setShowPointsModal(true);
import React, { useState, useEffect } from 'react';
  };
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const applyPointsChange = async () => {
import React, { useState, useEffect } from 'react';
    if (selectedPlayer && pointsToAdd !== 0) {
import React, { useState, useEffect } from 'react';
      try {
import React, { useState, useEffect } from 'react';
        const res = await fetch(`${API_BASE}/points/adjust.php`, {
import React, { useState, useEffect } from 'react';
          method: 'POST',
import React, { useState, useEffect } from 'react';
          headers: { 'Content-Type': 'application/json' },
import React, { useState, useEffect } from 'react';
          credentials: 'include',
import React, { useState, useEffect } from 'react';
          body: JSON.stringify({ user_id: selectedPlayer.id, amount: pointsToAdd, reason: 'Ajustement admin', type: 'adjustment' })
import React, { useState, useEffect } from 'react';
        });
import React, { useState, useEffect } from 'react';
        const data = await res.json();
import React, { useState, useEffect } from 'react';
        if (!res.ok) throw new Error(data?.error || 'Échec de l\'ajustement');
import React, { useState, useEffect } from 'react';
        setPlayersData(prev => prev.map(player => 
import React, { useState, useEffect } from 'react';
          player.id === selectedPlayer.id 
import React, { useState, useEffect } from 'react';
            ? { ...player, points: Math.max(0, player.points + pointsToAdd) }
import React, { useState, useEffect } from 'react';
            : player
import React, { useState, useEffect } from 'react';
        ));
import React, { useState, useEffect } from 'react';
        toast.success('Points ajustés avec succès', {
import React, { useState, useEffect } from 'react';
          description: `${pointsToAdd > 0 ? '+' : ''}${pointsToAdd} points pour ${selectedPlayer.username}`,
import React, { useState, useEffect } from 'react';
          duration: 3000
import React, { useState, useEffect } from 'react';
        });
import React, { useState, useEffect } from 'react';
      } catch (e) {
import React, { useState, useEffect } from 'react';
        toast.error('Erreur lors de l\'ajustement', { description: e.message });
import React, { useState, useEffect } from 'react';
      } finally {
import React, { useState, useEffect } from 'react';
        setShowPointsModal(false);
import React, { useState, useEffect } from 'react';
        setPointsToAdd(0);
import React, { useState, useEffect } from 'react';
        setSelectedPlayer(null);
import React, { useState, useEffect } from 'react';
      }
import React, { useState, useEffect } from 'react';
    }
import React, { useState, useEffect } from 'react';
  };
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const formatDate = (dateString) => {
import React, { useState, useEffect } from 'react';
    const date = new Date(dateString);
import React, { useState, useEffect } from 'react';
    return date.toLocaleDateString('fr-FR');
import React, { useState, useEffect } from 'react';
  };
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const getLevelColor = (level) => {
import React, { useState, useEffect } from 'react';
    switch (level) {
import React, { useState, useEffect } from 'react';
      case 'Diamond Elite': return 'text-cyan-400 bg-cyan-400/20';
import React, { useState, useEffect } from 'react';
      case 'Platinum Pro': return 'text-slate-300 bg-slate-300/20';
import React, { useState, useEffect } from 'react';
      case 'Gold Gamer': return 'text-yellow-400 bg-yellow-400/20';
import React, { useState, useEffect } from 'react';
      case 'Silver Star': return 'text-gray-400 bg-gray-400/20';
import React, { useState, useEffect } from 'react';
      default: return 'text-gray-400 bg-gray-400/20';
import React, { useState, useEffect } from 'react';
    }
import React, { useState, useEffect } from 'react';
  };
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const getStatusColor = (status) => {
import React, { useState, useEffect } from 'react';
    return status === 'active' 
import React, { useState, useEffect } from 'react';
      ? 'text-green-400 bg-green-400/20 border-green-400/30'
import React, { useState, useEffect } from 'react';
      : 'text-red-400 bg-red-400/20 border-red-400/30';
import React, { useState, useEffect } from 'react';
  };
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  return (
import React, { useState, useEffect } from 'react';
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900">
import React, { useState, useEffect } from 'react';
      <Navigation userType="admin" currentPage="players" />
import React, { useState, useEffect } from 'react';
      
import React, { useState, useEffect } from 'react';
      <div className="lg:pl-64">
import React, { useState, useEffect } from 'react';
        <div className="p-4 lg:p-8">
import React, { useState, useEffect } from 'react';
          {/* Header */}
import React, { useState, useEffect } from 'react';
          <div className="mb-8">
import React, { useState, useEffect } from 'react';
            <h1 className="text-3xl lg:text-4xl font-bold text-white mb-2 flex items-center space-x-3">
import React, { useState, useEffect } from 'react';
              <Users className="w-10 h-10 text-blue-400" />
import React, { useState, useEffect } from 'react';
              <span>Gestion des Joueurs</span>
import React, { useState, useEffect } from 'react';
            </h1>
import React, { useState, useEffect } from 'react';
            <p className="text-gray-300">Gérez les comptes et points de vos joueurs</p>
import React, { useState, useEffect } from 'react';
          </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
          {/* Search and Filter Bar */}
import React, { useState, useEffect } from 'react';
          <div className="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6 mb-8">
import React, { useState, useEffect } from 'react';
            <div className="flex flex-col lg:flex-row gap-4">
import React, { useState, useEffect } from 'react';
              {/* Search */}
import React, { useState, useEffect } from 'react';
              <div className="flex-1 relative">
import React, { useState, useEffect } from 'react';
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
import React, { useState, useEffect } from 'react';
                <input
import React, { useState, useEffect } from 'react';
                  type="text"
import React, { useState, useEffect } from 'react';
                  placeholder="Rechercher par pseudo ou email..."
import React, { useState, useEffect } from 'react';
                  value={searchTerm}
import React, { useState, useEffect } from 'react';
                  onChange={(e) => setSearchTerm(e.target.value)}
import React, { useState, useEffect } from 'react';
                  className="w-full bg-white/10 border border-white/20 rounded-xl pl-10 pr-4 py-3 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-transparent"
import React, { useState, useEffect } from 'react';
                />
import React, { useState, useEffect } from 'react';
              </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
              {/* Filter */}
import React, { useState, useEffect } from 'react';
              <div className="flex space-x-2">
import React, { useState, useEffect } from 'react';
                <button
import React, { useState, useEffect } from 'react';
                  onClick={() => setSelectedFilter('all')}
import React, { useState, useEffect } from 'react';
                  className={`px-4 py-2 rounded-xl font-semibold transition-all duration-200 flex items-center space-x-2 ${
import React, { useState, useEffect } from 'react';
                    selectedFilter === 'all'
import React, { useState, useEffect } from 'react';
                      ? 'bg-gradient-to-r from-purple-600 to-blue-600 text-white'
import React, { useState, useEffect } from 'react';
                      : 'bg-white/10 text-gray-300 hover:bg-white/20'
import React, { useState, useEffect } from 'react';
                  }`}
import React, { useState, useEffect } from 'react';
                >
import React, { useState, useEffect } from 'react';
                  <Filter className="w-4 h-4" />
import React, { useState, useEffect } from 'react';
                  <span>Tous</span>
import React, { useState, useEffect } from 'react';
                </button>
import React, { useState, useEffect } from 'react';
                <button
import React, { useState, useEffect } from 'react';
                  onClick={() => setSelectedFilter('active')}
import React, { useState, useEffect } from 'react';
                  className={`px-4 py-2 rounded-xl font-semibold transition-all duration-200 ${
import React, { useState, useEffect } from 'react';
                    selectedFilter === 'active'
import React, { useState, useEffect } from 'react';
                      ? 'bg-gradient-to-r from-green-600 to-green-500 text-white'
import React, { useState, useEffect } from 'react';
                      : 'bg-white/10 text-gray-300 hover:bg-white/20'
import React, { useState, useEffect } from 'react';
                  }`}
import React, { useState, useEffect } from 'react';
                >
import React, { useState, useEffect } from 'react';
                  Actifs
import React, { useState, useEffect } from 'react';
                </button>
import React, { useState, useEffect } from 'react';
                <button
import React, { useState, useEffect } from 'react';
                  onClick={() => setSelectedFilter('inactive')}
import React, { useState, useEffect } from 'react';
                  className={`px-4 py-2 rounded-xl font-semibold transition-all duration-200 ${
import React, { useState, useEffect } from 'react';
                    selectedFilter === 'inactive'
import React, { useState, useEffect } from 'react';
                      ? 'bg-gradient-to-r from-red-600 to-red-500 text-white'
import React, { useState, useEffect } from 'react';
                      : 'bg-white/10 text-gray-300 hover:bg-white/20'
import React, { useState, useEffect } from 'react';
                  }`}
import React, { useState, useEffect } from 'react';
                >
import React, { useState, useEffect } from 'react';
                  Inactifs
import React, { useState, useEffect } from 'react';
                </button>
import React, { useState, useEffect } from 'react';
              </div>
import React, { useState, useEffect } from 'react';
            </div>
import React, { useState, useEffect } from 'react';
          </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
          {/* Players Table */}
import React, { useState, useEffect } from 'react';
          <div className="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl overflow-hidden">
import React, { useState, useEffect } from 'react';
            <div className="overflow-x-auto">
import React, { useState, useEffect } from 'react';
              {error && (
import React, { useState, useEffect } from 'react';
                <div className="p-4 text-red-200 bg-red-500/20 border-b border-red-400/30">{error}</div>
import React, { useState, useEffect } from 'react';
              )}
import React, { useState, useEffect } from 'react';
              {loading && (
import React, { useState, useEffect } from 'react';
                <div className="p-4 text-gray-300">Chargement...</div>
import React, { useState, useEffect } from 'react';
              )}
import React, { useState, useEffect } from 'react';
              <table className="w-full">
import React, { useState, useEffect } from 'react';
                <thead className="bg-white/5 border-b border-white/10">
import React, { useState, useEffect } from 'react';
                  <tr>
import React, { useState, useEffect } from 'react';
                    <th className="text-left py-4 px-6 text-gray-300 font-semibold">Joueur</th>
import React, { useState, useEffect } from 'react';
                    <th className="text-left py-4 px-6 text-gray-300 font-semibold">Points</th>
import React, { useState, useEffect } from 'react';
                    <th className="text-left py-4 px-6 text-gray-300 font-semibold">Niveau</th>
import React, { useState, useEffect } from 'react';
                    <th className="text-left py-4 px-6 text-gray-300 font-semibold">Sessions</th>
import React, { useState, useEffect } from 'react';
                    <th className="text-left py-4 px-6 text-gray-300 font-semibold">Statut</th>
import React, { useState, useEffect } from 'react';
                    <th className="text-left py-4 px-6 text-gray-300 font-semibold">Dernière activité</th>
import React, { useState, useEffect } from 'react';
                    <th className="text-center py-4 px-6 text-gray-300 font-semibold">Actions</th>
import React, { useState, useEffect } from 'react';
                  </tr>
import React, { useState, useEffect } from 'react';
                </thead>
import React, { useState, useEffect } from 'react';
                <tbody>
import React, { useState, useEffect } from 'react';
                  {filteredPlayers.map((player) => (
import React, { useState, useEffect } from 'react';
                    <tr key={player.id} className="border-b border-white/5 hover:bg-white/5 transition-colors">
import React, { useState, useEffect } from 'react';
                      <td className="py-4 px-6">
import React, { useState, useEffect } from 'react';
                        <div className="flex items-center space-x-3">
import React, { useState, useEffect } from 'react';
                          <img
import React, { useState, useEffect } from 'react';
                            src={player.avatar}
import React, { useState, useEffect } from 'react';
                            alt={player.username}
import React, { useState, useEffect } from 'react';
                            className="w-10 h-10 rounded-full border-2 border-white/20"
import React, { useState, useEffect } from 'react';
                          />
import React, { useState, useEffect } from 'react';
                          <div>
import React, { useState, useEffect } from 'react';
                            <p className="text-white font-semibold">{player.username}</p>
import React, { useState, useEffect } from 'react';
                            <p className="text-gray-400 text-sm">{player.email}</p>
import React, { useState, useEffect } from 'react';
                          </div>
import React, { useState, useEffect } from 'react';
                        </div>
import React, { useState, useEffect } from 'react';
                      </td>
import React, { useState, useEffect } from 'react';
                      <td className="py-4 px-6">
import React, { useState, useEffect } from 'react';
                        <div className="flex items-center space-x-2 text-yellow-400">
import React, { useState, useEffect } from 'react';
                          <Coins className="w-4 h-4" />
import React, { useState, useEffect } from 'react';
                          <span className="font-semibold">{player.points.toLocaleString()}</span>
import React, { useState, useEffect } from 'react';
                        </div>
import React, { useState, useEffect } from 'react';
                      </td>
import React, { useState, useEffect } from 'react';
                      <td className="py-4 px-6">
import React, { useState, useEffect } from 'react';
                        <span className={`px-3 py-1 rounded-full text-xs font-semibold border ${getLevelColor(player.level)}`}>
import React, { useState, useEffect } from 'react';
                          {player.level}
import React, { useState, useEffect } from 'react';
                        </span>
import React, { useState, useEffect } from 'react';
                      </td>
import React, { useState, useEffect } from 'react';
                      <td className="py-4 px-6">
import React, { useState, useEffect } from 'react';
                        <span className="text-white font-medium">{player.totalSessions}</span>
import React, { useState, useEffect } from 'react';
                      </td>
import React, { useState, useEffect } from 'react';
                      <td className="py-4 px-6">
import React, { useState, useEffect } from 'react';
                        <span className={`px-3 py-1 rounded-full text-xs font-semibold border ${getStatusColor(player.status)}`}>
import React, { useState, useEffect } from 'react';
                          {player.status === 'active' ? 'Actif' : 'Inactif'}
import React, { useState, useEffect } from 'react';
                        </span>
import React, { useState, useEffect } from 'react';
                      </td>
import React, { useState, useEffect } from 'react';
                      <td className="py-4 px-6">
import React, { useState, useEffect } from 'react';
                        <span className="text-gray-300">{formatDate(player.lastActive)}</span>
import React, { useState, useEffect } from 'react';
                      </td>
import React, { useState, useEffect } from 'react';
                      <td className="py-4 px-6">
import React, { useState, useEffect } from 'react';
                        <div className="flex items-center justify-center space-x-2">
import React, { useState, useEffect } from 'react';
                          <button
import React, { useState, useEffect } from 'react';
                            onClick={() => navigate(`/admin/players/${player.id}`)}
import React, { useState, useEffect } from 'react';
                            className="p-2 bg-blue-500/20 hover:bg-blue-500/30 rounded-lg transition-colors"
import React, { useState, useEffect } from 'react';
                            title="Voir le profil"
import React, { useState, useEffect } from 'react';
                          >
import React, { useState, useEffect } from 'react';
                            <Eye className="w-4 h-4 text-blue-400" />
import React, { useState, useEffect } from 'react';
                          </button>
import React, { useState, useEffect } from 'react';
                          <button
import React, { useState, useEffect } from 'react';
                            onClick={() => handlePointsAdjustment(player, 'add')}
import React, { useState, useEffect } from 'react';
                            className="p-2 bg-green-500/20 hover:bg-green-500/30 rounded-lg transition-colors"
import React, { useState, useEffect } from 'react';
                            title="Ajuster les points"
import React, { useState, useEffect } from 'react';
                          >
import React, { useState, useEffect } from 'react';
                            <Coins className="w-4 h-4 text-green-400" />
import React, { useState, useEffect } from 'react';
                          </button>
import React, { useState, useEffect } from 'react';
                          <button
import React, { useState, useEffect } from 'react';
                            onClick={() => navigate(`/admin/players/${player.id}`)}
import React, { useState, useEffect } from 'react';
                            className="p-2 bg-purple-500/20 hover:bg-purple-500/30 rounded-lg transition-colors"
import React, { useState, useEffect } from 'react';
                            title="Éditer"
import React, { useState, useEffect } from 'react';
                          >
import React, { useState, useEffect } from 'react';
                            <Edit3 className="w-4 h-4 text-purple-400" />
import React, { useState, useEffect } from 'react';
                          </button>
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
          </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
          {/* Stats Summary */}
import React, { useState, useEffect } from 'react';
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
import React, { useState, useEffect } from 'react';
            <div className="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6 text-center">
import React, { useState, useEffect } from 'react';
              <Users className="w-8 h-8 text-blue-400 mx-auto mb-3" />
import React, { useState, useEffect } from 'react';
              <h3 className="text-white font-bold text-lg">Total Joueurs</h3>
import React, { useState, useEffect } from 'react';
              <p className="text-blue-400 text-2xl font-bold">{playersData.length}</p>
import React, { useState, useEffect } from 'react';
            </div>
import React, { useState, useEffect } from 'react';
            
import React, { useState, useEffect } from 'react';
            <div className="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6 text-center">
import React, { useState, useEffect } from 'react';
              <Star className="w-8 h-8 text-green-400 mx-auto mb-3" />
import React, { useState, useEffect } from 'react';
              <h3 className="text-white font-bold text-lg">Joueurs Actifs</h3>
import React, { useState, useEffect } from 'react';
              <p className="text-green-400 text-2xl font-bold">
import React, { useState, useEffect } from 'react';
                {playersData.filter(p => p.status === 'active').length}
import React, { useState, useEffect } from 'react';
              </p>
import React, { useState, useEffect } from 'react';
            </div>
import React, { useState, useEffect } from 'react';
            
import React, { useState, useEffect } from 'react';
            <div className="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6 text-center">
import React, { useState, useEffect } from 'react';
              <Coins className="w-8 h-8 text-yellow-400 mx-auto mb-3" />
import React, { useState, useEffect } from 'react';
              <h3 className="text-white font-bold text-lg">Points Totaux</h3>
import React, { useState, useEffect } from 'react';
              <p className="text-yellow-400 text-2xl font-bold">
import React, { useState, useEffect } from 'react';
                {playersData.reduce((sum, p) => sum + p.points, 0).toLocaleString()}
import React, { useState, useEffect } from 'react';
              </p>
import React, { useState, useEffect } from 'react';
            </div>
import React, { useState, useEffect } from 'react';
          </div>
import React, { useState, useEffect } from 'react';
        </div>
import React, { useState, useEffect } from 'react';
      </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
      {/* Points Adjustment Modal */}
import React, { useState, useEffect } from 'react';
      {showPointsModal && selectedPlayer && (
import React, { useState, useEffect } from 'react';
        <div className="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
import React, { useState, useEffect } from 'react';
          <div className="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-8 max-w-md w-full">
import React, { useState, useEffect } from 'react';
            <h3 className="text-2xl font-bold text-white mb-4">
import React, { useState, useEffect } from 'react';
              Ajuster les points de {selectedPlayer.username}
import React, { useState, useEffect } from 'react';
            </h3>
import React, { useState, useEffect } from 'react';
            
import React, { useState, useEffect } from 'react';
            <div className="mb-6">
import React, { useState, useEffect } from 'react';
              <p className="text-gray-300 mb-2">Points actuels: 
import React, { useState, useEffect } from 'react';
                <span className="text-yellow-400 font-semibold ml-2">
import React, { useState, useEffect } from 'react';
                  {selectedPlayer.points.toLocaleString()}
import React, { useState, useEffect } from 'react';
                </span>
import React, { useState, useEffect } from 'react';
              </p>
import React, { useState, useEffect } from 'react';
              
import React, { useState, useEffect } from 'react';
              <div className="flex items-center space-x-4 mb-4">
import React, { useState, useEffect } from 'react';
                <button
import React, { useState, useEffect } from 'react';
                  onClick={() => setPointsToAdd(prev => prev - 10)}
import React, { useState, useEffect } from 'react';
                  className="p-2 bg-red-500/20 hover:bg-red-500/30 rounded-lg transition-colors"
import React, { useState, useEffect } from 'react';
                >
import React, { useState, useEffect } from 'react';
                  <Minus className="w-4 h-4 text-red-400" />
import React, { useState, useEffect } from 'react';
                </button>
import React, { useState, useEffect } from 'react';
                
import React, { useState, useEffect } from 'react';
                <input
import React, { useState, useEffect } from 'react';
                  type="number"
import React, { useState, useEffect } from 'react';
                  value={pointsToAdd}
import React, { useState, useEffect } from 'react';
                  onChange={(e) => setPointsToAdd(parseInt(e.target.value) || 0)}
import React, { useState, useEffect } from 'react';
                  className="flex-1 bg-white/10 border border-white/20 rounded-xl px-4 py-2 text-white text-center focus:outline-none focus:ring-2 focus:ring-purple-400"
import React, { useState, useEffect } from 'react';
                  placeholder="0"
import React, { useState, useEffect } from 'react';
                />
import React, { useState, useEffect } from 'react';
                
import React, { useState, useEffect } from 'react';
                <button
import React, { useState, useEffect } from 'react';
                  onClick={() => setPointsToAdd(prev => prev + 10)}
import React, { useState, useEffect } from 'react';
                  className="p-2 bg-green-500/20 hover:bg-green-500/30 rounded-lg transition-colors"
import React, { useState, useEffect } from 'react';
                >
import React, { useState, useEffect } from 'react';
                  <Plus className="w-4 h-4 text-green-400" />
import React, { useState, useEffect } from 'react';
                </button>
import React, { useState, useEffect } from 'react';
              </div>
import React, { useState, useEffect } from 'react';
              
import React, { useState, useEffect } from 'react';
              <p className="text-gray-300 text-sm">
import React, { useState, useEffect } from 'react';
                Nouveaux points: 
import React, { useState, useEffect } from 'react';
                <span className={`font-semibold ml-2 ${
import React, { useState, useEffect } from 'react';
                  selectedPlayer.points + pointsToAdd >= 0 ? 'text-green-400' : 'text-red-400'
import React, { useState, useEffect } from 'react';
                }`}>
import React, { useState, useEffect } from 'react';
                  {Math.max(0, selectedPlayer.points + pointsToAdd).toLocaleString()}
import React, { useState, useEffect } from 'react';
                </span>
import React, { useState, useEffect } from 'react';
              </p>
import React, { useState, useEffect } from 'react';
            </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
            <div className="flex space-x-4">
import React, { useState, useEffect } from 'react';
              <button
import React, { useState, useEffect } from 'react';
                onClick={() => {
import React, { useState, useEffect } from 'react';
                  setShowPointsModal(false);
import React, { useState, useEffect } from 'react';
                  setPointsToAdd(0);
import React, { useState, useEffect } from 'react';
                  setSelectedPlayer(null);
import React, { useState, useEffect } from 'react';
                }}
import React, { useState, useEffect } from 'react';
                className="flex-1 bg-gray-600 hover:bg-gray-700 text-white py-3 rounded-xl font-semibold transition-colors"
import React, { useState, useEffect } from 'react';
              >
import React, { useState, useEffect } from 'react';
                Annuler
import React, { useState, useEffect } from 'react';
              </button>
import React, { useState, useEffect } from 'react';
              <button
import React, { useState, useEffect } from 'react';
                onClick={applyPointsChange}
import React, { useState, useEffect } from 'react';
                disabled={pointsToAdd === 0}
import React, { useState, useEffect } from 'react';
                className="flex-1 bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 disabled:from-gray-600 disabled:to-gray-700 text-white py-3 rounded-xl font-semibold transition-colors disabled:cursor-not-allowed"
import React, { useState, useEffect } from 'react';
              >
import React, { useState, useEffect } from 'react';
                Appliquer
import React, { useState, useEffect } from 'react';
              </button>
import React, { useState, useEffect } from 'react';
            </div>
import React, { useState, useEffect } from 'react';
          </div>
import React, { useState, useEffect } from 'react';
        </div>
import React, { useState, useEffect } from 'react';
      )}
import React, { useState, useEffect } from 'react';
    </div>
import React, { useState, useEffect } from 'react';
  );
import React, { useState, useEffect } from 'react';
}
