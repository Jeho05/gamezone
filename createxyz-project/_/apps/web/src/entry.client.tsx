import { createRoot } from 'react-dom/client';
import FullApp from './FullApp-NoLazy';

// Initialiser l'application
console.log('🚀 Starting GameZone app...');

const rootElement = document.getElementById('root');
if (rootElement) {
  console.log('✅ Root element found, rendering app...');
  try {
    createRoot(rootElement).render(<FullApp />);
    console.log('✅ App rendered successfully!');
  } catch (error) {
    console.error('❌ Error rendering app:', error);
    document.body.innerHTML = `<div style="color: red; text-align: center; margin-top: 50px; padding: 20px; font-family: monospace;">
      <h1>❌ ERREUR DE CHARGEMENT</h1>
      <pre style="background: #000; color: #f00; padding: 20px; border-radius: 5px; text-align: left;">${error}</pre>
      <p>Ouvrez la console (F12) pour plus de détails</p>
    </div>`;
  }
} else {
  console.error('❌ Root element not found');
  document.body.innerHTML = `<div style="color: red; text-align: center; margin-top: 50px; padding: 20px; font-family: monospace;">
    <h1>❌ ERREUR</h1>
    <p>Element root introuvable dans le DOM</p>
  </div>`;
}
