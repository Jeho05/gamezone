import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import HelpWidget from './components/HelpWidget';
import './app/global.css'; // Import CSS early

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 5, // 5 minutes
      cacheTime: 1000 * 60 * 30, // 30 minutes
      retry: 1,
      refetchOnWindowFocus: false,
    },
  },
});

export default function RootLayout({children}) {
  return (
    <QueryClientProvider client={queryClient}>
      <div className="loading-placeholder" id="loading-placeholder">
        Chargement de l'interface admin...
      </div>
      <script 
        dangerouslySetInnerHTML={{
          __html: `
            window.addEventListener('load', function() {
              const loader = document.getElementById('loading-placeholder');
              if (loader) {
                loader.style.display = 'none';
              }
            });
          `
        }}
      />
      {children}
      <HelpWidget />
    </QueryClientProvider>
  );
}