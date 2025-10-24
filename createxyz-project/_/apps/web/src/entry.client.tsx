import { createRoot } from 'react-dom/client';
import SimpleApp from './SimpleApp';

// Initialiser l'application
const rootElement = document.getElementById('root');
if (rootElement) {
  console.log('✅ Root element found, rendering app...');
  createRoot(rootElement).render(<SimpleApp />);
  console.log('✅ App rendered!');
} else {
  console.error('❌ Root element not found');
  document.body.innerHTML = '<h1 style="color: red; text-align: center; margin-top: 50px;">ERROR: Root element not found</h1>';
}
