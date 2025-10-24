import { Suspense } from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { ChakraProvider } from '@chakra-ui/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { Toaster } from 'sonner';

// Import DIRECT de la page minimale (pas de lazy loading)
import HomePage from './app/page-minimal';

// Import global styles
import './app/global.css';
import './styles/animations.css';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 5,
      retry: 1,
      refetchOnWindowFocus: false,
    },
  },
});

// Page de fallback simple pour les routes manquantes
function NotFoundPage() {
  return (
    <div style={{
      minHeight: '100vh',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      background: 'linear-gradient(135deg, #0f0c29, #302b63, #24243e)',
      color: 'white',
      textAlign: 'center',
      fontFamily: 'system-ui'
    }}>
      <div>
        <h1 style={{ fontSize: '5rem', margin: 0 }}>404</h1>
        <p style={{ fontSize: '1.5rem', marginTop: '20px' }}>Page non trouvée</p>
        <a href="/gamezone/" style={{ 
          display: 'inline-block',
          marginTop: '30px',
          padding: '15px 30px',
          background: '#667eea',
          color: 'white',
          textDecoration: 'none',
          borderRadius: '8px',
          fontWeight: 'bold'
        }}>
          Retour à l'accueil
        </a>
      </div>
    </div>
  );
}

// Page temporaire pour les routes non implémentées
function ComingSoonPage({ title }: { title: string }) {
  return (
    <div style={{
      minHeight: '100vh',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
      color: 'white',
      textAlign: 'center',
      fontFamily: 'system-ui'
    }}>
      <div>
        <h1 style={{ fontSize: '3rem', marginBottom: '20px' }}>🚧 {title}</h1>
        <p style={{ fontSize: '1.3rem', marginBottom: '30px' }}>Cette page est en construction</p>
        <a href="/gamezone/" style={{ 
          display: 'inline-block',
          padding: '15px 30px',
          background: 'white',
          color: '#764ba2',
          textDecoration: 'none',
          borderRadius: '8px',
          fontWeight: 'bold'
        }}>
          Retour à l'accueil
        </a>
      </div>
    </div>
  );
}

export default function FullApp() {
  console.log('✅ FullApp-NoLazy rendering...');
  
  return (
    <ChakraProvider>
      <QueryClientProvider client={queryClient}>
        <BrowserRouter basename="/">
          <Routes>
            {/* Page d'accueil SEULE */}
            <Route path="/" element={<HomePage />} />
            
            {/* Auth pages - Coming Soon */}
            <Route path="/auth/login" element={<ComingSoonPage title="Connexion" />} />
            <Route path="/auth/register" element={<ComingSoonPage title="Inscription" />} />
            
            {/* Player pages - Coming Soon */}
            <Route path="/player/*" element={<ComingSoonPage title="Espace Joueur" />} />
            
            {/* Admin pages - Coming Soon */}
            <Route path="/admin/*" element={<ComingSoonPage title="Espace Admin" />} />
            
            {/* 404 pour tout le reste */}
            <Route path="*" element={<NotFoundPage />} />
          </Routes>
        </BrowserRouter>
        <Toaster position="top-right" />
      </QueryClientProvider>
    </ChakraProvider>
  );
}
