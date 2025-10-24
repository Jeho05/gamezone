import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { createBrowserRouter, RouterProvider } from 'react-router-dom';
import Root from './app/root';

// Import des routes
import { routes } from 'react-router';

// Créer le router
const router = createBrowserRouter([
  {
    path: '*',
    Component: Root,
  },
]);

const rootElement = document.getElementById('root');
if (rootElement) {
  createRoot(rootElement).render(
    <StrictMode>
      <RouterProvider router={router} />
    </StrictMode>
  );
}
