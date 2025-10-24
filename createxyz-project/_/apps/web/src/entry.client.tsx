import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { HydratedRouter } from 'react-router/dom';

const rootElement = document.getElementById('root');
if (rootElement) {
  createRoot(rootElement).render(
    <StrictMode>
      <HydratedRouter />
    </StrictMode>
  );
}
