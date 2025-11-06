// server.js
require('dotenv').config();
const express = require('express');
const cors = require('cors');

const app = express();

// CORS: during dev allow all; restrict in prod.
app.use(cors({
  origin: true, // reflect request origin
  methods: ['GET', 'POST', 'OPTIONS'],
  allowedHeaders: ['Content-Type', 'Authorization'],
  credentials: false
}));

// Make sure JSON body is parsed:
app.use(express.json({ limit: '2mb' }));

// Ensure preflight succeeds fast:
app.options('*', (req, res) => res.sendStatus(204));

const PORT = process.env.PORT || 3001;
const OPENROUTER_API_KEY = process.env.OPENROUTER_API_KEY || '';
const PUBLIC_URL = process.env.PUBLIC_URL || 'http://localhost';

// Health check
app.get('/api/health', (_req, res) => res.json({ ok: true }));

app.post('/api/chat', async (req, res) => {
  try {
    const { model, messages, temperature, max_tokens } = req.body || {};
    if (!Array.isArray(messages)) {
      return res.status(400).json({ error: 'Invalid request: "messages" must be an array' });
    }

    const payload = {
      model: model || 'amazon/nova-premier-v1',
      messages,
      temperature: typeof temperature === 'number' ? temperature : 0.7,
      max_tokens: typeof max_tokens === 'number' ? max_tokens : 2000
    };

    // Timeout guard
    const controller = new AbortController();
    const to = setTimeout(() => controller.abort(), 45000);

    const upstream = await fetch('https://openrouter.ai/api/v1/chat/completions', {
      method: 'POST',
      signal: controller.signal,
      headers: {
        'Authorization': `Bearer ${OPENROUTER_API_KEY}`,
        'Content-Type': 'application/json',
        'HTTP-Referer': PUBLIC_URL,
        'X-Title': 'AgroSync FarmBot AI',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    clearTimeout(to);

    const raw = await upstream.text();
    if (!upstream.ok) {
      let msg = `OpenRouter error ${upstream.status}`;
      try {
        const j = JSON.parse(raw);
        msg = j?.error?.message || j?.error || j?.message || msg;
        return res.status(upstream.status).json({ error: msg, upstream: j });
      } catch {
        return res.status(upstream.status).json({ error: msg, upstream_raw: raw.slice(0, 400) });
      }
    }

    try {
      const data = JSON.parse(raw);
      return res.status(200).json(data);
    } catch {
      return res.status(502).json({ error: 'Unexpected non-JSON from OpenRouter', upstream_raw: raw.slice(0, 400) });
    }
  } catch (err) {
    // If aborted or network error, return JSON (browser otherwise shows "Failed to fetch")
    const msg = err?.name === 'AbortError' ? 'Proxy timeout' : (err?.message || 'Server error');
    return res.status(502).json({ error: msg });
  }
});

// IMPORTANT: listen on all interfaces
app.listen(PORT, '0.0.0.0', () => {
  console.log(`Node proxy running on http://localhost:${PORT}`);
});
