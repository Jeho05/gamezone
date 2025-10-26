import React, { useState, useEffect } from 'react';
import { useState, useEffect } from 'react';
import React, { useState, useEffect } from 'react';
import Navigation from '../../../components/Navigation';
import React, { useState, useEffect } from 'react';
import ImageUpload from '../../../components/ImageUpload';
import React, { useState, useEffect } from 'react';
import { 
import React, { useState, useEffect } from 'react';
  Newspaper, 
import React, { useState, useEffect } from 'react';
  Calendar, 
import React, { useState, useEffect } from 'react';
  Image as ImageIcon, 
import React, { useState, useEffect } from 'react';
  Video,
import React, { useState, useEffect } from 'react';
  Plus,
import React, { useState, useEffect } from 'react';
  Edit,
import React, { useState, useEffect } from 'react';
  Trash2,
import React, { useState, useEffect } from 'react';
  Eye,
import React, { useState, useEffect } from 'react';
  EyeOff,
import React, { useState, useEffect } from 'react';
  Pin,
import React, { useState, useEffect } from 'react';
  Search
import React, { useState, useEffect } from 'react';
} from 'lucide-react';
import React, { useState, useEffect } from 'react';
import API_BASE from '../../../utils/apiBase';
import React, { useState, useEffect } from 'react';
import { toast } from 'sonner';
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
export default function AdminContent() {
import React, { useState, useEffect } from 'react';
  const [activeTab, setActiveTab] = useState('news');
import React, { useState, useEffect } from 'react';
  const [contents, setContents] = useState([]);
import React, { useState, useEffect } from 'react';
  const [loading, setLoading] = useState(false);
import React, { useState, useEffect } from 'react';
  const [showModal, setShowModal] = useState(false);
import React, { useState, useEffect } from 'react';
  const [editingContent, setEditingContent] = useState(null);
import React, { useState, useEffect } from 'react';
  const [searchTerm, setSearchTerm] = useState('');
import React, { useState, useEffect } from 'react';
  const [stats, setStats] = useState(null);
import React, { useState, useEffect } from 'react';
  const [form, setForm] = useState({
import React, { useState, useEffect } from 'react';
    type: 'news',
import React, { useState, useEffect } from 'react';
    title: '',
import React, { useState, useEffect } from 'react';
    description: '',
import React, { useState, useEffect } from 'react';
    content: '',
import React, { useState, useEffect } from 'react';
    image_url: '',
import React, { useState, useEffect } from 'react';
    video_url: '',
import React, { useState, useEffect } from 'react';
    external_link: '',
import React, { useState, useEffect } from 'react';
    event_date: '',
import React, { useState, useEffect } from 'react';
    event_location: '',
import React, { useState, useEffect } from 'react';
    stream_url: '',
import React, { useState, useEffect } from 'react';
    is_published: true,
import React, { useState, useEffect } from 'react';
    is_pinned: false
import React, { useState, useEffect } from 'react';
  });
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const tabs = [
import React, { useState, useEffect } from 'react';
    { id: 'news', label: 'Actualités', icon: Newspaper },
import React, { useState, useEffect } from 'react';
    { id: 'event', label: 'Événements', icon: Calendar },
import React, { useState, useEffect } from 'react';
    { id: 'gallery', label: 'Galerie', icon: ImageIcon },
import React, { useState, useEffect } from 'react';
    { id: 'stream', label: 'Streams', icon: Video }
import React, { useState, useEffect } from 'react';
  ];
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  useEffect(() => {
import React, { useState, useEffect } from 'react';
    loadContent();
import React, { useState, useEffect } from 'react';
    loadStats();
import React, { useState, useEffect } from 'react';
  }, [activeTab]);
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const loadStats = async () => {
import React, { useState, useEffect } from 'react';
    try {
import React, { useState, useEffect } from 'react';
      const res = await fetch(`${API_BASE}/content/stats.php`, {
import React, { useState, useEffect } from 'react';
        credentials: 'include'
import React, { useState, useEffect } from 'react';
      });
import React, { useState, useEffect } from 'react';
      if (res.ok) {
import React, { useState, useEffect } from 'react';
        const data = await res.json();
import React, { useState, useEffect } from 'react';
        if (data.success) {
import React, { useState, useEffect } from 'react';
          setStats(data.stats);
import React, { useState, useEffect } from 'react';
          console.log('[Admin] Stats loaded:', data.stats);
import React, { useState, useEffect } from 'react';
        }
import React, { useState, useEffect } from 'react';
      }
import React, { useState, useEffect } from 'react';
    } catch (e) {
import React, { useState, useEffect } from 'react';
      console.error('[Admin] Error loading stats:', e);
import React, { useState, useEffect } from 'react';
    }
import React, { useState, useEffect } from 'react';
  };
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const loadContent = async () => {
import React, { useState, useEffect } from 'react';
    try {
import React, { useState, useEffect } from 'react';
      setLoading(true);
import React, { useState, useEffect } from 'react';
      console.log('[Content] Loading content for type:', activeTab);
import React, { useState, useEffect } from 'react';
      const res = await fetch(`${API_BASE}/admin/content.php?type=${activeTab}`, {
import React, { useState, useEffect } from 'react';
        credentials: 'include'
import React, { useState, useEffect } from 'react';
      });
import React, { useState, useEffect } from 'react';
      console.log('[Content] Response status:', res.status);
import React, { useState, useEffect } from 'react';
      
import React, { useState, useEffect } from 'react';
      if (!res.ok) {
import React, { useState, useEffect } from 'react';
        const errorText = await res.text();
import React, { useState, useEffect } from 'react';
        console.error('[Content] Error response:', errorText);
import React, { useState, useEffect } from 'react';
        toast.error(`Erreur ${res.status}: ${errorText}`);
import React, { useState, useEffect } from 'react';
        return;
import React, { useState, useEffect } from 'react';
      }
import React, { useState, useEffect } from 'react';
      
import React, { useState, useEffect } from 'react';
      const data = await res.json();
import React, { useState, useEffect } from 'react';
      console.log('[Content] Data received:', data);
import React, { useState, useEffect } from 'react';
      
import React, { useState, useEffect } from 'react';
      if (data.content) {
import React, { useState, useEffect } from 'react';
        console.log('[Content] Setting contents, count:', data.content.length);
import React, { useState, useEffect } from 'react';
        setContents(data.content);
import React, { useState, useEffect } from 'react';
      } else {
import React, { useState, useEffect } from 'react';
        console.warn('[Content] No content in response');
import React, { useState, useEffect } from 'react';
        setContents([]);
import React, { useState, useEffect } from 'react';
      }
import React, { useState, useEffect } from 'react';
    } catch (err) {
import React, { useState, useEffect } from 'react';
      console.error('[Content] Load error:', err);
import React, { useState, useEffect } from 'react';
      toast.error('Erreur de chargement: ' + err.message);
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
  const handleOpenCreateModal = () => {
import React, { useState, useEffect } from 'react';
    setEditingContent(null);
import React, { useState, useEffect } from 'react';
    setForm({
import React, { useState, useEffect } from 'react';
      type: activeTab,
import React, { useState, useEffect } from 'react';
      title: '',
import React, { useState, useEffect } from 'react';
      description: '',
import React, { useState, useEffect } from 'react';
      content: '',
import React, { useState, useEffect } from 'react';
      image_url: '',
import React, { useState, useEffect } from 'react';
      video_url: '',
import React, { useState, useEffect } from 'react';
      external_link: '',
import React, { useState, useEffect } from 'react';
      event_date: '',
import React, { useState, useEffect } from 'react';
      event_location: '',
import React, { useState, useEffect } from 'react';
      stream_url: '',
import React, { useState, useEffect } from 'react';
      is_published: true,
import React, { useState, useEffect } from 'react';
      is_pinned: false
import React, { useState, useEffect } from 'react';
    });
import React, { useState, useEffect } from 'react';
    setShowModal(true);
import React, { useState, useEffect } from 'react';
  };
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const handleOpenEditModal = (content) => {
import React, { useState, useEffect } from 'react';
    setEditingContent(content);
import React, { useState, useEffect } from 'react';
    setForm({
import React, { useState, useEffect } from 'react';
      type: content.type,
import React, { useState, useEffect } from 'react';
      title: content.title || '',
import React, { useState, useEffect } from 'react';
      description: content.description || '',
import React, { useState, useEffect } from 'react';
      content: content.content || '',
import React, { useState, useEffect } from 'react';
      image_url: content.image_url || '',
import React, { useState, useEffect } from 'react';
      video_url: content.video_url || '',
import React, { useState, useEffect } from 'react';
      external_link: content.external_link || '',
import React, { useState, useEffect } from 'react';
      event_date: content.event_date ? content.event_date.replace(' ', 'T').substring(0, 16) : '',
import React, { useState, useEffect } from 'react';
      event_location: content.event_location || '',
import React, { useState, useEffect } from 'react';
      stream_url: content.stream_url || '',
import React, { useState, useEffect } from 'react';
      is_published: content.is_published == 1,
import React, { useState, useEffect } from 'react';
      is_pinned: content.is_pinned == 1
import React, { useState, useEffect } from 'react';
    });
import React, { useState, useEffect } from 'react';
    setShowModal(true);
import React, { useState, useEffect } from 'react';
  };
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const handleSubmit = async (e) => {
import React, { useState, useEffect } from 'react';
    e.preventDefault();
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
    if (!form.title) {
import React, { useState, useEffect } from 'react';
      toast.error('Le titre est requis');
import React, { useState, useEffect } from 'react';
      return;
import React, { useState, useEffect } from 'react';
    }
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
    try {
import React, { useState, useEffect } from 'react';
      const payload = { ...form };
import React, { useState, useEffect } from 'react';
      if (editingContent) {
import React, { useState, useEffect } from 'react';
        payload.id = editingContent.id;
import React, { useState, useEffect } from 'react';
      }
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
      // Convert datetime-local to MySQL format
import React, { useState, useEffect } from 'react';
      if (payload.event_date) {
import React, { useState, useEffect } from 'react';
        payload.event_date = payload.event_date.replace('T', ' ') + ':00';
import React, { useState, useEffect } from 'react';
      }
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
      console.log('[Content] Submitting:', payload);
import React, { useState, useEffect } from 'react';
      const res = await fetch(`${API_BASE}/admin/content.php`, {
import React, { useState, useEffect } from 'react';
        method: editingContent ? 'PUT' : 'POST',
import React, { useState, useEffect } from 'react';
        credentials: 'include',
import React, { useState, useEffect } from 'react';
        headers: { 'Content-Type': 'application/json' },
import React, { useState, useEffect } from 'react';
        body: JSON.stringify(payload)
import React, { useState, useEffect } from 'react';
      });
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
      console.log('[Content] Submit response status:', res.status);
import React, { useState, useEffect } from 'react';
      const data = await res.json();
import React, { useState, useEffect } from 'react';
      console.log('[Content] Submit response data:', data);
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
      if (data.success || res.ok) {
import React, { useState, useEffect } from 'react';
        toast.success(editingContent ? 'Contenu mis à jour !' : 'Contenu créé !');
import React, { useState, useEffect } from 'react';
        setShowModal(false);
import React, { useState, useEffect } from 'react';
        console.log('[Content] Reloading content after submit...');
import React, { useState, useEffect } from 'react';
        await loadContent();
import React, { useState, useEffect } from 'react';
        await loadStats(); // Reload stats to keep them in sync
import React, { useState, useEffect } from 'react';
      } else {
import React, { useState, useEffect } from 'react';
        toast.error(data.error || 'Erreur lors de l\'enregistrement');
import React, { useState, useEffect } from 'react';
      }
import React, { useState, useEffect } from 'react';
    } catch (err) {
import React, { useState, useEffect } from 'react';
      toast.error('Erreur de connexion');
import React, { useState, useEffect } from 'react';
      console.error('[Content] Submit error:', err);
import React, { useState, useEffect } from 'react';
    }
import React, { useState, useEffect } from 'react';
  };
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const deleteContent = async (id) => {
import React, { useState, useEffect } from 'react';
    if (!confirm('Supprimer ce contenu ?')) return;
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
    try {
import React, { useState, useEffect } from 'react';
      console.log('[Content] Deleting content id:', id);
import React, { useState, useEffect } from 'react';
      const res = await fetch(`${API_BASE}/admin/content.php?id=${id}`, {
import React, { useState, useEffect } from 'react';
        method: 'DELETE',
import React, { useState, useEffect } from 'react';
        credentials: 'include'
import React, { useState, useEffect } from 'react';
      });
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
      console.log('[Content] Delete response status:', res.status);
import React, { useState, useEffect } from 'react';
      const data = await res.json();
import React, { useState, useEffect } from 'react';
      console.log('[Content] Delete response data:', data);
import React, { useState, useEffect } from 'react';
      
import React, { useState, useEffect } from 'react';
      if (data.success) {
import React, { useState, useEffect } from 'react';
        toast.success('Contenu supprimé');
import React, { useState, useEffect } from 'react';
        await loadContent();
import React, { useState, useEffect } from 'react';
        await loadStats(); // Reload stats to keep them in sync
import React, { useState, useEffect } from 'react';
      } else {
import React, { useState, useEffect } from 'react';
        toast.error(data.error || 'Erreur');
import React, { useState, useEffect } from 'react';
      }
import React, { useState, useEffect } from 'react';
    } catch (err) {
import React, { useState, useEffect } from 'react';
      console.error('[Content] Delete error:', err);
import React, { useState, useEffect } from 'react';
      toast.error('Erreur de suppression: ' + err.message);
import React, { useState, useEffect } from 'react';
    }
import React, { useState, useEffect } from 'react';
  };
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  const filteredContents = contents.filter(c =>
import React, { useState, useEffect } from 'react';
    c.title.toLowerCase().includes(searchTerm.toLowerCase())
import React, { useState, useEffect } from 'react';
  );
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
  return (
import React, { useState, useEffect } from 'react';
    <div className="min-h-screen bg-gradient-to-br from-purple-900 via-indigo-900 to-blue-900">
import React, { useState, useEffect } from 'react';
      <Navigation userType="admin" />
import React, { useState, useEffect } from 'react';
      
import React, { useState, useEffect } from 'react';
      <div className="lg:pl-64">
import React, { useState, useEffect } from 'react';
        <div className="container mx-auto px-4 py-8">
import React, { useState, useEffect } from 'react';
          {/* Header */}
import React, { useState, useEffect } from 'react';
          <div className="bg-white rounded-xl shadow-lg p-6 mb-6">
import React, { useState, useEffect } from 'react';
            <h1 className="text-3xl font-bold text-purple-600 mb-2 flex items-center gap-3">
import React, { useState, useEffect } from 'react';
              <Newspaper className="w-8 h-8" />
import React, { useState, useEffect } from 'react';
              Gestion de Contenu
import React, { useState, useEffect } from 'react';
            </h1>
import React, { useState, useEffect } from 'react';
            <p className="text-gray-600">Gérez vos actualités, événements, galerie et streams</p>
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
          {/* Global Statistics */}
import React, { useState, useEffect } from 'react';
          {stats && (
import React, { useState, useEffect } from 'react';
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
import React, { useState, useEffect } from 'react';
              {/* Gallery Stats */}
import React, { useState, useEffect } from 'react';
              <div className="bg-gradient-to-br from-purple-500 to-purple-700 rounded-xl shadow-lg p-6 text-white">
import React, { useState, useEffect } from 'react';
                <div className="flex items-center justify-between mb-2">
import React, { useState, useEffect } from 'react';
                  <ImageIcon className="w-8 h-8 opacity-80" />
import React, { useState, useEffect } from 'react';
                  <span className="text-3xl font-bold">{stats.by_type.gallery.count}</span>
import React, { useState, useEffect } from 'react';
                </div>
import React, { useState, useEffect } from 'react';
                <h3 className="text-lg font-semibold">Galerie</h3>
import React, { useState, useEffect } from 'react';
                <div className="mt-3 text-sm opacity-90 space-y-1">
import React, { useState, useEffect } from 'react';
                  <div className="flex justify-between">
import React, { useState, useEffect } from 'react';
                    <span>👁️ Vues:</span>
import React, { useState, useEffect } from 'react';
                    <span className="font-semibold">{stats.by_type.gallery.views.toLocaleString()}</span>
import React, { useState, useEffect } from 'react';
                  </div>
import React, { useState, useEffect } from 'react';
                  <div className="flex justify-between">
import React, { useState, useEffect } from 'react';
                    <span>🔗 Partages:</span>
import React, { useState, useEffect } from 'react';
                    <span className="font-semibold">{stats.by_type.gallery.shares}</span>
import React, { useState, useEffect } from 'react';
                  </div>
import React, { useState, useEffect } from 'react';
                </div>
import React, { useState, useEffect } from 'react';
              </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
              {/* Events Stats */}
import React, { useState, useEffect } from 'react';
              <div className="bg-gradient-to-br from-blue-500 to-blue-700 rounded-xl shadow-lg p-6 text-white">
import React, { useState, useEffect } from 'react';
                <div className="flex items-center justify-between mb-2">
import React, { useState, useEffect } from 'react';
                  <Calendar className="w-8 h-8 opacity-80" />
import React, { useState, useEffect } from 'react';
                  <span className="text-3xl font-bold">{stats.by_type.event.count}</span>
import React, { useState, useEffect } from 'react';
                </div>
import React, { useState, useEffect } from 'react';
                <h3 className="text-lg font-semibold">Événements</h3>
import React, { useState, useEffect } from 'react';
                <div className="mt-3 text-sm opacity-90 space-y-1">
import React, { useState, useEffect } from 'react';
                  <div className="flex justify-between">
import React, { useState, useEffect } from 'react';
                    <span>👁️ Vues:</span>
import React, { useState, useEffect } from 'react';
                    <span className="font-semibold">{stats.by_type.event.views.toLocaleString()}</span>
import React, { useState, useEffect } from 'react';
                  </div>
import React, { useState, useEffect } from 'react';
                  <div className="flex justify-between">
import React, { useState, useEffect } from 'react';
                    <span>🔗 Partages:</span>
import React, { useState, useEffect } from 'react';
                    <span className="font-semibold">{stats.by_type.event.shares}</span>
import React, { useState, useEffect } from 'react';
                  </div>
import React, { useState, useEffect } from 'react';
                </div>
import React, { useState, useEffect } from 'react';
              </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
              {/* News Stats */}
import React, { useState, useEffect } from 'react';
              <div className="bg-gradient-to-br from-green-500 to-green-700 rounded-xl shadow-lg p-6 text-white">
import React, { useState, useEffect } from 'react';
                <div className="flex items-center justify-between mb-2">
import React, { useState, useEffect } from 'react';
                  <Newspaper className="w-8 h-8 opacity-80" />
import React, { useState, useEffect } from 'react';
                  <span className="text-3xl font-bold">{stats.by_type.news.count}</span>
import React, { useState, useEffect } from 'react';
                </div>
import React, { useState, useEffect } from 'react';
                <h3 className="text-lg font-semibold">Actualités</h3>
import React, { useState, useEffect } from 'react';
                <div className="mt-3 text-sm opacity-90 space-y-1">
import React, { useState, useEffect } from 'react';
                  <div className="flex justify-between">
import React, { useState, useEffect } from 'react';
                    <span>👁️ Vues:</span>
import React, { useState, useEffect } from 'react';
                    <span className="font-semibold">{stats.by_type.news.views.toLocaleString()}</span>
import React, { useState, useEffect } from 'react';
                  </div>
import React, { useState, useEffect } from 'react';
                  <div className="flex justify-between">
import React, { useState, useEffect } from 'react';
                    <span>🔗 Partages:</span>
import React, { useState, useEffect } from 'react';
                    <span className="font-semibold">{stats.by_type.news.shares}</span>
import React, { useState, useEffect } from 'react';
                  </div>
import React, { useState, useEffect } from 'react';
                </div>
import React, { useState, useEffect } from 'react';
              </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
              {/* Streams Stats */}
import React, { useState, useEffect } from 'react';
              <div className="bg-gradient-to-br from-red-500 to-red-700 rounded-xl shadow-lg p-6 text-white">
import React, { useState, useEffect } from 'react';
                <div className="flex items-center justify-between mb-2">
import React, { useState, useEffect } from 'react';
                  <Video className="w-8 h-8 opacity-80" />
import React, { useState, useEffect } from 'react';
                  <span className="text-3xl font-bold">{stats.by_type.stream.count}</span>
import React, { useState, useEffect } from 'react';
                </div>
import React, { useState, useEffect } from 'react';
                <h3 className="text-lg font-semibold">Streams</h3>
import React, { useState, useEffect } from 'react';
                <div className="mt-3 text-sm opacity-90 space-y-1">
import React, { useState, useEffect } from 'react';
                  <div className="flex justify-between">
import React, { useState, useEffect } from 'react';
                    <span>👁️ Vues:</span>
import React, { useState, useEffect } from 'react';
                    <span className="font-semibold">{stats.by_type.stream.views.toLocaleString()}</span>
import React, { useState, useEffect } from 'react';
                  </div>
import React, { useState, useEffect } from 'react';
                  <div className="flex justify-between">
import React, { useState, useEffect } from 'react';
                    <span>🔗 Partages:</span>
import React, { useState, useEffect } from 'react';
                    <span className="font-semibold">{stats.by_type.stream.shares}</span>
import React, { useState, useEffect } from 'react';
                  </div>
import React, { useState, useEffect } from 'react';
                </div>
import React, { useState, useEffect } from 'react';
              </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
              {/* Total Engagement Stats */}
import React, { useState, useEffect } from 'react';
              <div className="bg-gradient-to-br from-indigo-500 to-indigo-700 rounded-xl shadow-lg p-6 text-white md:col-span-2">
import React, { useState, useEffect } from 'react';
                <h3 className="text-lg font-semibold mb-4 flex items-center justify-between">
import React, { useState, useEffect } from 'react';
                  <span>Engagement Total</span>
import React, { useState, useEffect } from 'react';
                  <button 
import React, { useState, useEffect } from 'react';
                    onClick={loadStats}
import React, { useState, useEffect } from 'react';
                    className="text-xs bg-white/20 hover:bg-white/30 px-3 py-1 rounded transition-colors"
import React, { useState, useEffect } from 'react';
                    title="Actualiser"
import React, { useState, useEffect } from 'react';
                  >
import React, { useState, useEffect } from 'react';
                    🔄 Actualiser
import React, { useState, useEffect } from 'react';
                  </button>
import React, { useState, useEffect } from 'react';
                </h3>
import React, { useState, useEffect } from 'react';
                <div className="grid grid-cols-2 gap-4">
import React, { useState, useEffect } from 'react';
                  <div>
import React, { useState, useEffect } from 'react';
                    <div className="text-2xl font-bold">{stats.total_likes.toLocaleString()}</div>
import React, { useState, useEffect } from 'react';
                    <div className="text-sm opacity-90">❤️ Likes totaux</div>
import React, { useState, useEffect } from 'react';
                  </div>
import React, { useState, useEffect } from 'react';
                  <div>
import React, { useState, useEffect } from 'react';
                    <div className="text-2xl font-bold">{stats.total_comments.toLocaleString()}</div>
import React, { useState, useEffect } from 'react';
                    <div className="text-sm opacity-90">💬 Commentaires</div>
import React, { useState, useEffect } from 'react';
                  </div>
import React, { useState, useEffect } from 'react';
                  <div>
import React, { useState, useEffect } from 'react';
                    <div className="text-2xl font-bold">
import React, { useState, useEffect } from 'react';
                      {Object.values(stats.by_type).reduce((sum, type) => sum + type.views, 0).toLocaleString()}
import React, { useState, useEffect } from 'react';
                    </div>
import React, { useState, useEffect } from 'react';
                    <div className="text-sm opacity-90">👁️ Vues totales</div>
import React, { useState, useEffect } from 'react';
                  </div>
import React, { useState, useEffect } from 'react';
                  <div>
import React, { useState, useEffect } from 'react';
                    <div className="text-2xl font-bold">
import React, { useState, useEffect } from 'react';
                      {Object.values(stats.by_type).reduce((sum, type) => sum + type.shares, 0).toLocaleString()}
import React, { useState, useEffect } from 'react';
                    </div>
import React, { useState, useEffect } from 'react';
                    <div className="text-sm opacity-90">🔗 Partages totaux</div>
import React, { useState, useEffect } from 'react';
                  </div>
import React, { useState, useEffect } from 'react';
                </div>
import React, { useState, useEffect } from 'react';
              </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
              {/* Top Content */}
import React, { useState, useEffect } from 'react';
              <div className="bg-white rounded-xl shadow-lg p-6 md:col-span-2">
import React, { useState, useEffect } from 'react';
                <h3 className="text-lg font-semibold mb-4 text-gray-800 flex items-center gap-2">
import React, { useState, useEffect } from 'react';
                  🔥 Top Contenus (Vues)
import React, { useState, useEffect } from 'react';
                </h3>
import React, { useState, useEffect } from 'react';
                {stats.top_views && stats.top_views.length > 0 ? (
import React, { useState, useEffect } from 'react';
                  <div className="space-y-3">
import React, { useState, useEffect } from 'react';
                    {stats.top_views.slice(0, 5).map((item, idx) => (
import React, { useState, useEffect } from 'react';
                      <div key={item.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
import React, { useState, useEffect } from 'react';
                        <div className="flex items-center gap-3 flex-1 min-w-0">
import React, { useState, useEffect } from 'react';
                          <span className="text-2xl font-bold text-gray-400 min-w-[30px]">#{idx + 1}</span>
import React, { useState, useEffect } from 'react';
                          <div className="flex-1 min-w-0">
import React, { useState, useEffect } from 'react';
                            <div className="font-semibold text-gray-800 truncate">{item.title}</div>
import React, { useState, useEffect } from 'react';
                            <div className="text-xs text-gray-500 capitalize">{item.type}</div>
import React, { useState, useEffect } from 'react';
                          </div>
import React, { useState, useEffect } from 'react';
                        </div>
import React, { useState, useEffect } from 'react';
                        <div className="flex items-center gap-2 text-purple-600 font-bold">
import React, { useState, useEffect } from 'react';
                          <Eye className="w-4 h-4" />
import React, { useState, useEffect } from 'react';
                          <span>{item.views_count.toLocaleString()}</span>
import React, { useState, useEffect } from 'react';
                        </div>
import React, { useState, useEffect } from 'react';
                      </div>
import React, { useState, useEffect } from 'react';
                    ))}
import React, { useState, useEffect } from 'react';
                  </div>
import React, { useState, useEffect } from 'react';
                ) : (
import React, { useState, useEffect } from 'react';
                  <div className="text-center text-gray-400 py-8">
import React, { useState, useEffect } from 'react';
                    Aucun contenu pour le moment
import React, { useState, useEffect } from 'react';
                  </div>
import React, { useState, useEffect } from 'react';
                )}
import React, { useState, useEffect } from 'react';
              </div>
import React, { useState, useEffect } from 'react';
            </div>
import React, { useState, useEffect } from 'react';
          )}
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
          {/* Content List */}
import React, { useState, useEffect } from 'react';
          <div className="bg-white rounded-xl shadow-lg p-6">
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
                  placeholder="Rechercher..."
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
                Créer
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
              </div>
import React, { useState, useEffect } from 'react';
            ) : (
import React, { useState, useEffect } from 'react';
              <div className="overflow-x-auto">
import React, { useState, useEffect } from 'react';
                <table className="w-full">
import React, { useState, useEffect } from 'react';
                  <thead className="bg-gray-50">
import React, { useState, useEffect } from 'react';
                    <tr>
import React, { useState, useEffect } from 'react';
                      <th className="px-4 py-3 text-left">Titre</th>
import React, { useState, useEffect } from 'react';
                      <th className="px-4 py-3 text-left">Auteur</th>
import React, { useState, useEffect } from 'react';
                      <th className="px-4 py-3 text-left">Date</th>
import React, { useState, useEffect } from 'react';
                      <th className="px-4 py-3 text-left">Statut</th>
import React, { useState, useEffect } from 'react';
                      <th className="px-4 py-3 text-left">Stats</th>
import React, { useState, useEffect } from 'react';
                      <th className="px-4 py-3 text-left">Actions</th>
import React, { useState, useEffect } from 'react';
                    </tr>
import React, { useState, useEffect } from 'react';
                  </thead>
import React, { useState, useEffect } from 'react';
                  <tbody>
import React, { useState, useEffect } from 'react';
                    {filteredContents.length === 0 ? (
import React, { useState, useEffect } from 'react';
                      <tr>
import React, { useState, useEffect } from 'react';
                        <td colSpan="6" className="px-4 py-12 text-center text-gray-500">
import React, { useState, useEffect } from 'react';
                          <div className="text-lg mb-2">Aucun contenu pour le moment</div>
import React, { useState, useEffect } from 'react';
                          <div className="text-sm">Cliquez sur "Créer" pour ajouter du contenu</div>
import React, { useState, useEffect } from 'react';
                        </td>
import React, { useState, useEffect } from 'react';
                      </tr>
import React, { useState, useEffect } from 'react';
                    ) : (
import React, { useState, useEffect } from 'react';
                      filteredContents.map((content) => (
import React, { useState, useEffect } from 'react';
                        <tr key={content.id} className="border-b hover:bg-gray-50">
import React, { useState, useEffect } from 'react';
                          <td className="px-4 py-3">
import React, { useState, useEffect } from 'react';
                            <div className="font-semibold">{content.title}</div>
import React, { useState, useEffect } from 'react';
                            {content.is_pinned == 1 && (
import React, { useState, useEffect } from 'react';
                              <span className="inline-flex items-center gap-1 text-xs text-amber-600">
import React, { useState, useEffect } from 'react';
                                <Pin className="w-3 h-3" />
import React, { useState, useEffect } from 'react';
                                Épinglé
import React, { useState, useEffect } from 'react';
                              </span>
import React, { useState, useEffect } from 'react';
                            )}
import React, { useState, useEffect } from 'react';
                          </td>
import React, { useState, useEffect } from 'react';
                          <td className="px-4 py-3">{content.author_name || 'Admin'}</td>
import React, { useState, useEffect } from 'react';
                          <td className="px-4 py-3 text-sm text-gray-600">
import React, { useState, useEffect } from 'react';
                            {new Date(content.published_at || content.created_at).toLocaleDateString('fr-FR')}
import React, { useState, useEffect } from 'react';
                          </td>
import React, { useState, useEffect } from 'react';
                          <td className="px-4 py-3">
import React, { useState, useEffect } from 'react';
                            <span className={`px-2 py-1 text-xs rounded ${
import React, { useState, useEffect } from 'react';
                              content.is_published == 1 
import React, { useState, useEffect } from 'react';
                                ? 'bg-green-100 text-green-700' 
import React, { useState, useEffect } from 'react';
                                : 'bg-gray-100 text-gray-700'
import React, { useState, useEffect } from 'react';
                            }`}>
import React, { useState, useEffect } from 'react';
                              {content.is_published == 1 ? (
import React, { useState, useEffect } from 'react';
                                <span className="flex items-center gap-1">
import React, { useState, useEffect } from 'react';
                                  <Eye className="w-3 h-3" />
import React, { useState, useEffect } from 'react';
                                  Publié
import React, { useState, useEffect } from 'react';
                                </span>
import React, { useState, useEffect } from 'react';
                              ) : (
import React, { useState, useEffect } from 'react';
                                <span className="flex items-center gap-1">
import React, { useState, useEffect } from 'react';
                                  <EyeOff className="w-3 h-3" />
import React, { useState, useEffect } from 'react';
                                  Brouillon
import React, { useState, useEffect } from 'react';
                                </span>
import React, { useState, useEffect } from 'react';
                              )}
import React, { useState, useEffect } from 'react';
                            </span>
import React, { useState, useEffect } from 'react';
                          </td>
import React, { useState, useEffect } from 'react';
                          <td className="px-4 py-3 text-sm text-gray-600">
import React, { useState, useEffect } from 'react';
                            👁️ {content.views_count || 0} • 
import React, { useState, useEffect } from 'react';
                            ❤️ {content.likes_count || 0} • 
import React, { useState, useEffect } from 'react';
                            💬 {content.comments_count || 0}
import React, { useState, useEffect } from 'react';
                          </td>
import React, { useState, useEffect } from 'react';
                          <td className="px-4 py-3">
import React, { useState, useEffect } from 'react';
                            <button
import React, { useState, useEffect } from 'react';
                              onClick={() => handleOpenEditModal(content)}
import React, { useState, useEffect } from 'react';
                              className="text-blue-600 hover:underline text-sm mr-2"
import React, { useState, useEffect } from 'react';
                            >
import React, { useState, useEffect } from 'react';
                              Modifier
import React, { useState, useEffect } from 'react';
                            </button>
import React, { useState, useEffect } from 'react';
                            <button
import React, { useState, useEffect } from 'react';
                              onClick={() => deleteContent(content.id)}
import React, { useState, useEffect } from 'react';
                              className="text-red-600 hover:underline text-sm"
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
                      ))
import React, { useState, useEffect } from 'react';
                    )}
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
        </div>
import React, { useState, useEffect } from 'react';
      </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
      {/* Content Form Modal */}
import React, { useState, useEffect } from 'react';
      {showModal && (
import React, { useState, useEffect } from 'react';
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 overflow-y-auto">
import React, { useState, useEffect } from 'react';
          <div className="bg-white rounded-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
import React, { useState, useEffect } from 'react';
            <div className="sticky top-0 bg-white border-b p-6 flex justify-between items-center">
import React, { useState, useEffect } from 'react';
              <h2 className="text-2xl font-bold text-purple-600">
import React, { useState, useEffect } from 'react';
                {editingContent ? 'Modifier' : 'Créer'} - {tabs.find(t => t.id === form.type)?.label}
import React, { useState, useEffect } from 'react';
              </h2>
import React, { useState, useEffect } from 'react';
              <button
import React, { useState, useEffect } from 'react';
                onClick={() => setShowModal(false)}
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
            <form onSubmit={handleSubmit} className="p-6">
import React, { useState, useEffect } from 'react';
              <div className="space-y-4">
import React, { useState, useEffect } from 'react';
                {/* Titre */}
import React, { useState, useEffect } from 'react';
                <div>
import React, { useState, useEffect } from 'react';
                  <label className="block text-sm font-semibold mb-2">Titre *</label>
import React, { useState, useEffect } from 'react';
                  <input
import React, { useState, useEffect } from 'react';
                    type="text"
import React, { useState, useEffect } from 'react';
                    required
import React, { useState, useEffect } from 'react';
                    value={form.title}
import React, { useState, useEffect } from 'react';
                    onChange={(e) => setForm({...form, title: e.target.value})}
import React, { useState, useEffect } from 'react';
                    className="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500"
import React, { useState, useEffect } from 'react';
                    placeholder="Titre du contenu"
import React, { useState, useEffect } from 'react';
                  />
import React, { useState, useEffect } from 'react';
                </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
                {/* Description */}
import React, { useState, useEffect } from 'react';
                <div>
import React, { useState, useEffect } from 'react';
                  <label className="block text-sm font-semibold mb-2">Description courte</label>
import React, { useState, useEffect } from 'react';
                  <textarea
import React, { useState, useEffect } from 'react';
                    value={form.description}
import React, { useState, useEffect } from 'react';
                    onChange={(e) => setForm({...form, description: e.target.value})}
import React, { useState, useEffect } from 'react';
                    rows="2"
import React, { useState, useEffect } from 'react';
                    className="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500"
import React, { useState, useEffect } from 'react';
                    placeholder="Description courte pour l'aperçu"
import React, { useState, useEffect } from 'react';
                  />
import React, { useState, useEffect } from 'react';
                </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
                {/* Contenu */}
import React, { useState, useEffect } from 'react';
                <div>
import React, { useState, useEffect } from 'react';
                  <label className="block text-sm font-semibold mb-2">Contenu complet</label>
import React, { useState, useEffect } from 'react';
                  <textarea
import React, { useState, useEffect } from 'react';
                    value={form.content}
import React, { useState, useEffect } from 'react';
                    onChange={(e) => setForm({...form, content: e.target.value})}
import React, { useState, useEffect } from 'react';
                    rows="6"
import React, { useState, useEffect } from 'react';
                    className="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500"
import React, { useState, useEffect } from 'react';
                    placeholder="Contenu détaillé (HTML supporté)"
import React, { useState, useEffect } from 'react';
                  />
import React, { useState, useEffect } from 'react';
                </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
                {/* Image URL */}
import React, { useState, useEffect } from 'react';
                <div>
import React, { useState, useEffect } from 'react';
                  <ImageUpload
import React, { useState, useEffect } from 'react';
                    value={form.image_url}
import React, { useState, useEffect } from 'react';
                    onChange={(url) => setForm({ ...form, image_url: url })}
import React, { useState, useEffect } from 'react';
                    label="URL de l'image"
import React, { useState, useEffect } from 'react';
                  />
import React, { useState, useEffect } from 'react';
                </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
                {/* Champs spécifiques événement */}
import React, { useState, useEffect } from 'react';
                {form.type === 'event' && (
import React, { useState, useEffect } from 'react';
                  <>
import React, { useState, useEffect } from 'react';
                    <div className="grid grid-cols-2 gap-4">
import React, { useState, useEffect } from 'react';
                      <div>
import React, { useState, useEffect } from 'react';
                        <label className="block text-sm font-semibold mb-2">Date événement</label>
import React, { useState, useEffect } from 'react';
                        <input
import React, { useState, useEffect } from 'react';
                          type="datetime-local"
import React, { useState, useEffect } from 'react';
                          value={form.event_date}
import React, { useState, useEffect } from 'react';
                          onChange={(e) => setForm({...form, event_date: e.target.value})}
import React, { useState, useEffect } from 'react';
                          className="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500"
import React, { useState, useEffect } from 'react';
                        />
import React, { useState, useEffect } from 'react';
                      </div>
import React, { useState, useEffect } from 'react';
                      <div>
import React, { useState, useEffect } from 'react';
                        <label className="block text-sm font-semibold mb-2">Lieu</label>
import React, { useState, useEffect } from 'react';
                        <input
import React, { useState, useEffect } from 'react';
                          type="text"
import React, { useState, useEffect } from 'react';
                          value={form.event_location}
import React, { useState, useEffect } from 'react';
                          onChange={(e) => setForm({...form, event_location: e.target.value})}
import React, { useState, useEffect } from 'react';
                          className="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500"
import React, { useState, useEffect } from 'react';
                          placeholder="Lieu de l'événement"
import React, { useState, useEffect } from 'react';
                        />
import React, { useState, useEffect } from 'react';
                      </div>
import React, { useState, useEffect } from 'react';
                    </div>
import React, { useState, useEffect } from 'react';
                  </>
import React, { useState, useEffect } from 'react';
                )}
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
                {/* Champs spécifiques stream */}
import React, { useState, useEffect } from 'react';
                {form.type === 'stream' && (
import React, { useState, useEffect } from 'react';
                  <div>
import React, { useState, useEffect } from 'react';
                    <label className="block text-sm font-semibold mb-2">URL du Stream</label>
import React, { useState, useEffect } from 'react';
                    <input
import React, { useState, useEffect } from 'react';
                      type="url"
import React, { useState, useEffect } from 'react';
                      value={form.stream_url}
import React, { useState, useEffect } from 'react';
                      onChange={(e) => setForm({...form, stream_url: e.target.value})}
import React, { useState, useEffect } from 'react';
                      className="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500"
import React, { useState, useEffect } from 'react';
                      placeholder="https://twitch.tv/..."
import React, { useState, useEffect } from 'react';
                    />
import React, { useState, useEffect } from 'react';
                  </div>
import React, { useState, useEffect } from 'react';
                )}
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
                {/* Champs spécifiques galerie */}
import React, { useState, useEffect } from 'react';
                {form.type === 'gallery' && (
import React, { useState, useEffect } from 'react';
                  <div>
import React, { useState, useEffect } from 'react';
                    <label className="block text-sm font-semibold mb-2">URL de la vidéo</label>
import React, { useState, useEffect } from 'react';
                    <input
import React, { useState, useEffect } from 'react';
                      type="url"
import React, { useState, useEffect } from 'react';
                      value={form.video_url}
import React, { useState, useEffect } from 'react';
                      onChange={(e) => setForm({...form, video_url: e.target.value})}
import React, { useState, useEffect } from 'react';
                      className="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500"
import React, { useState, useEffect } from 'react';
                      placeholder="https://youtube.com/..."
import React, { useState, useEffect } from 'react';
                    />
import React, { useState, useEffect } from 'react';
                  </div>
import React, { useState, useEffect } from 'react';
                )}
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
                {/* Lien externe */}
import React, { useState, useEffect } from 'react';
                <div>
import React, { useState, useEffect } from 'react';
                  <label className="block text-sm font-semibold mb-2">Lien externe (optionnel)</label>
import React, { useState, useEffect } from 'react';
                  <input
import React, { useState, useEffect } from 'react';
                    type="url"
import React, { useState, useEffect } from 'react';
                    value={form.external_link}
import React, { useState, useEffect } from 'react';
                    onChange={(e) => setForm({...form, external_link: e.target.value})}
import React, { useState, useEffect } from 'react';
                    className="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500"
import React, { useState, useEffect } from 'react';
                    placeholder="https://..."
import React, { useState, useEffect } from 'react';
                  />
import React, { useState, useEffect } from 'react';
                </div>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
                {/* Options */}
import React, { useState, useEffect } from 'react';
                <div className="grid grid-cols-2 gap-4">
import React, { useState, useEffect } from 'react';
                  <label className="flex items-center gap-2">
import React, { useState, useEffect } from 'react';
                    <input
import React, { useState, useEffect } from 'react';
                      type="checkbox"
import React, { useState, useEffect } from 'react';
                      checked={form.is_published}
import React, { useState, useEffect } from 'react';
                      onChange={(e) => setForm({...form, is_published: e.target.checked})}
import React, { useState, useEffect } from 'react';
                      className="w-4 h-4"
import React, { useState, useEffect } from 'react';
                    />
import React, { useState, useEffect } from 'react';
                    <span className="text-sm font-semibold">Publier immédiatement</span>
import React, { useState, useEffect } from 'react';
                  </label>
import React, { useState, useEffect } from 'react';

import React, { useState, useEffect } from 'react';
                  <label className="flex items-center gap-2">
import React, { useState, useEffect } from 'react';
                    <input
import React, { useState, useEffect } from 'react';
                      type="checkbox"
import React, { useState, useEffect } from 'react';
                      checked={form.is_pinned}
import React, { useState, useEffect } from 'react';
                      onChange={(e) => setForm({...form, is_pinned: e.target.checked})}
import React, { useState, useEffect } from 'react';
                      className="w-4 h-4"
import React, { useState, useEffect } from 'react';
                    />
import React, { useState, useEffect } from 'react';
                    <span className="text-sm font-semibold">Épingler en haut</span>
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
                  onClick={() => setShowModal(false)}
import React, { useState, useEffect } from 'react';
                  className="flex-1 px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-semibold"
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
                  className="flex-1 px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-semibold"
import React, { useState, useEffect } from 'react';
                >
import React, { useState, useEffect } from 'react';
                  {editingContent ? 'Mettre à Jour' : 'Créer'}
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
    </div>
import React, { useState, useEffect } from 'react';
  );
import React, { useState, useEffect } from 'react';
}
