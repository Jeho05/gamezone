/**
 * Vercel Serverless Function - CORS Proxy to InfinityFree Backend
 * This proxies requests from frontend to backend, bypassing CORS restrictions
 */

export default async function handler(req, res) {
  // Get the target endpoint from query parameter
  const { endpoint } = req.query;
  
  if (!endpoint) {
    return res.status(400).json({ error: 'Missing endpoint parameter' });
  }

  // Construct backend URL
  const backendUrl = `https://ismo.gamer.gd/api/${endpoint}`;

  try {
    // Forward the request to backend
    const backendResponse = await fetch(backendUrl, {
      method: req.method,
      headers: {
        'Content-Type': 'application/json',
        // Forward cookies if present
        ...(req.headers.cookie ? { Cookie: req.headers.cookie } : {}),
      },
      // Forward body for POST/PUT/PATCH
      ...(req.body && req.method !== 'GET' ? { body: JSON.stringify(req.body) } : {}),
    });

    // Get response data
    const data = await backendResponse.json();

    // Forward Set-Cookie headers from backend
    const setCookie = backendResponse.headers.get('set-cookie');
    if (setCookie) {
      res.setHeader('Set-Cookie', setCookie);
    }

    // Set CORS headers (Vercel allows this)
    res.setHeader('Access-Control-Allow-Origin', req.headers.origin || '*');
    res.setHeader('Access-Control-Allow-Credentials', 'true');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-CSRF-Token');

    // Handle OPTIONS preflight
    if (req.method === 'OPTIONS') {
      return res.status(204).end();
    }

    // Return backend response
    return res.status(backendResponse.status).json(data);
  } catch (error) {
    console.error('Proxy error:', error);
    return res.status(500).json({ 
      error: 'Proxy request failed', 
      details: error.message 
    });
  }
}
