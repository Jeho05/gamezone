import React from 'react';
import Navigation from '../../components/Navigation';

export default function TestColors() {
  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900">
      <Navigation userType="admin" currentPage="test" />
      
      <div className="lg:pl-64 p-8">
        <h1 className="text-3xl font-bold text-white mb-8">Test de Couleurs Admin</h1>
        
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {/* Carte avec texte noir sur fond blanc */}
          <div className="bg-white p-6 rounded-xl shadow-lg">
            <h2 className="text-xl font-bold text-black mb-2">Texte Noir</h2>
            <p className="text-gray-800">Ce texte devrait être visible</p>
          </div>
          
          {/* Carte avec texte blanc sur fond foncé */}
          <div className="bg-gray-800 p-6 rounded-xl shadow-lg">
            <h2 className="text-xl font-bold text-white mb-2">Texte Blanc</h2>
            <p className="text-gray-300">Ce texte devrait être visible</p>
          </div>
          
          {/* Carte avec texte blanc sur fond blanc (PROBLÈME) */}
          <div className="bg-white p-6 rounded-xl shadow-lg">
            <h2 className="text-xl font-bold text-white mb-2">Texte Blanc sur Fond Blanc</h2>
            <p className="text-white">❌ CE TEXTE NE DEVRAIT PAS ÊTRE VISIBLE</p>
          </div>
          
          {/* Carte avec texte noir sur fond noir (PROBLÈME) */}
          <div className="bg-black p-6 rounded-xl shadow-lg">
            <h2 className="text-xl font-bold text-black mb-2">Texte Noir sur Fond Noir</h2>
            <p className="text-black">❌ CE TEXTE NE DEVRAIT PAS ÊTRE VISIBLE</p>
          </div>
        </div>
      </div>
    </div>
  );
}