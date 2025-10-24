import { createRoot } from 'react-dom/client';
import FullApp from './FullApp';

// Initialiser l'application
const rootElement = document.getElementById('root');
if (rootElement) {
  console.log('✅ Root element found, rendering full app...');
  try {
    createRoot(rootElement).render(<FullApp />);
    console.log('✅ Full app rendered!');
  } catch (error) {
    console.error('❌ Error rendering app:', error);
    document.body.innerHTML = `<div style="color: red; text-align: center; margin-top: 50px;"><h1>ERROR:</h1><pre>${error}</pre></div>`;
  }
} else {
  console.error('❌ Root element not found');
  document.body.innerHTML = '<h1 style="color: red; text-align: center; margin-top: 50px;">ERROR: Root element not found</h1>';
}
