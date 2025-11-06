<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>AI Assistant | AgroSync</title>
  <link href="../../public/app.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @keyframes gradient {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }
    .gradient-blob {
      background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #4facfe);
      background-size: 400% 400%;
      animation: gradient 15s ease infinite;
    }
    .chat-message { animation: fadeIn 0.3s ease-in; }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .typing-indicator span { animation: blink 1.4s infinite both; }
    .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
    .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
    @keyframes blink {
      0%, 80%, 100% { opacity: 0; }
      40% { opacity: 1; }
    }
  </style>
</head>
<body class="flex h-screen bg-gray-50">
  <?php include '../../components/sidebar.php'; ?> 
  
  <main class="flex-1 min-w-0 overflow-hidden flex flex-col"> <!-- min-h-0 + flex column root -->
    <div class="h-full flex flex-col min-h-0"> <!-- min-h-0 ensures children can overflow -->
      <!-- Header -->
      <header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between flex-shrink-0">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 bg-indigo-500 rounded-full flex items-center justify-center">
            <i class="bi bi-robot text-white text-xl"></i>
          </div>
          <div>
            <h1 class="text-lg font-semibold text-gray-800">AgroBot</h1>
            <p class="text-xs text-gray-500">Smart Farm Management</p>
          </div>
        </div>
        <button onclick="newChat()" class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-800 transition">
          <i class="bi bi-plus-lg mr-1"></i> New Chat
        </button>
      </header>

      <!-- Chat Container (is the ONLY scrollable area) -->
      <div id="chatContainer" class="flex-1 min-h-0 overflow-y-auto px-4 py-8">
        <!-- Welcome Screen (centered within the scroll area) -->
        <div id="welcomeScreen" class="max-w-2xl mx-auto flex flex-col items-center justify-center min-h-full text-center">
          <div class="w-32 h-32 mx-auto mb-8 rounded-full gradient-blob opacity-80"></div>
          <h2 class="text-4xl font-bold text-gray-800 mb-4">Good Day, Farmer</h2>
          <p class="text-2xl text-gray-600 mb-8">
            How Can I <span class="text-indigo-600 font-semibold">Assist You Today?</span>
          </p>
          
          <!-- Suggestion Chips -->
          <div class="flex flex-wrap gap-3 justify-center mt-8">
            <button onclick="sendSuggestion('What crops should I plant this season?')" class="bg-white border border-gray-200 px-4 py-2 rounded-full text-sm text-gray-700 hover:border-indigo-500 hover:text-indigo-600 transition">
              🌱 Crop Recommendations
            </button>
            <button onclick="sendSuggestion('Analyze my soil conditions')" class="bg-white border border-gray-200 px-4 py-2 rounded-full text-sm text-gray-700 hover:border-indigo-500 hover:text-indigo-600 transition">
              🌍 Soil Analysis
            </button>
            <button onclick="sendSuggestion('How to prevent pest infestations?')" class="bg-white border border-gray-200 px-4 py-2 rounded-full text-sm text-gray-700 hover:border-indigo-500 hover:text-indigo-600 transition">
              🐛 Pest Control
            </button>
            <button onclick="sendSuggestion('Optimize my irrigation schedule')" class="bg-white border border-gray-200 px-4 py-2 rounded-full text-sm text-gray-700 hover:border-indigo-500 hover:text-indigo-600 transition">
              💧 Irrigation Tips
            </button>
          </div>
        </div>

        <!-- Chat Messages -->
        <div id="chatMessages" class="hidden w-full max-w-4xl mx-auto space-y-4 mb-4"></div>
      </div>

      <!-- Input Area -->
      <div class="bg-white border-t border-gray-200 px-4 py-4 flex-shrink-0">
        <div class="max-w-4xl mx-auto">
          <div class="flex items-center space-x-3 bg-gray-50 rounded-2xl border border-gray-200 p-2">
            <button class="p-2 text-gray-400 hover:text-gray-600 transition">
              <i class="bi bi-paperclip text-lg"></i>
            </button>
            <input 
              type="text" 
              id="messageInput" 
              placeholder="Ask about crops, weather, soil, irrigation, or farm management..."
              class="flex-1 bg-transparent border-none outline-none text-gray-700 placeholder-gray-400"
              onkeypress="if(event.key==='Enter') sendMessage()"
            >
            <button onclick="sendMessage()" class="bg-indigo-600 text-white p-2 rounded-xl hover:bg-indigo-700 transition">
              <i class="bi bi-send-fill"></i>
            </button>
          </div>
          <div class="flex items-center justify-center space-x-4 mt-3 text-xs text-gray-500">
            <button class="flex items-center space-x-1 hover:text-indigo-600 transition">
              <i class="bi bi-lightbulb"></i>
              <span>Smart Analysis</span>
            </button>
            <button class="flex items-center space-x-1 hover:text-indigo-600 transition">
              <i class="bi bi-image"></i>
              <span>Upload Image</span>
            </button>
            <button class="flex items-center space-x-1 hover:text-indigo-600 transition">
              <i class="bi bi-bar-chart"></i>
              <span>Farm Data</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script>
const API_URL = 'http://localhost:3000/api/chat'; // Node proxy

let conversationHistory = [];

const SYSTEM_PROMPT = `You are FarmBot AI, an expert agricultural assistant specialized in smart farming, precision agriculture, and sustainable farm management. Your knowledge covers:

- Crop selection, rotation, and optimization based on climate, soil, and market conditions
- Soil health analysis, testing interpretation, and amendment recommendations
- Integrated pest management (IPM) and organic/sustainable pest control
- Irrigation systems, water management, and conservation techniques
- Weather pattern analysis and climate-adaptive farming strategies
- Fertilizer management and nutrient optimization
- Livestock management and animal husbandry
- Farm automation, IoT sensors, and precision agriculture technology
- Harvest timing, post-harvest handling, and storage optimization
- Market analysis and crop profitability calculations
- Sustainable and regenerative agriculture practices
- Disease identification and prevention strategies

Always provide:
- Practical, actionable advice tailored to the farmer's situation
- Scientific reasoning behind recommendations
- Cost-effective solutions when possible
- Seasonal considerations and timing recommendations
- Warning about potential risks or challenges
- Follow-up questions to better understand the farmer's needs

Be conversational, supportive, and encouraging while maintaining expertise. Use emojis sparingly for clarity (🌱💧🌾☀️🌧️).`;

// ---------- UI helpers ----------
function scrollToBottom() {
  const container = document.getElementById('chatContainer');
  container.scrollTop = container.scrollHeight;
}

function newChat() {
  conversationHistory = [];
  document.getElementById('welcomeScreen').classList.remove('hidden');
  document.getElementById('chatMessages').classList.add('hidden');
  document.getElementById('chatMessages').innerHTML = '';
  document.getElementById('messageInput').value = '';
  scrollToBottom();
}

function sendSuggestion(text) {
  document.getElementById('messageInput').value = text;
  sendMessage();
}

function sendMessage() {
  const input = document.getElementById('messageInput');
  const message = input.value.trim();
  if (!message) return;

  document.getElementById('welcomeScreen').classList.add('hidden');
  document.getElementById('chatMessages').classList.remove('hidden');

  addMessage(message, 'user');
  input.value = '';

  conversationHistory.push({ role: 'user', content: message });

  showTypingIndicator();
  scrollToBottom();

  callAPI();
}

function addMessage(text, sender) {
  const messagesDiv = document.getElementById('chatMessages');
  const wrapper = document.createElement('div');
  wrapper.className = `chat-message flex ${sender === 'user' ? 'justify-end' : 'justify-start'}`;

  if (sender === 'user') {
    wrapper.innerHTML = `
      <div class="bg-indigo-600 text-white rounded-2xl rounded-tr-sm px-4 py-3 max-w-2xl">
        <p class="text-sm">${escapeHtml(text)}</p>
      </div>
    `;
  } else {
    wrapper.innerHTML = `
      <div class="flex space-x-3 max-w-3xl">
        <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center flex-shrink-0">
          <i class="bi bi-robot text-white"></i>
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl rounded-tl-sm px-4 py-3">
          <p class="text-sm text-gray-800 whitespace-pre-wrap">${escapeHtml(text)}</p>
        </div>
      </div>
    `;
  }

  messagesDiv.appendChild(wrapper);
  scrollToBottom();
}

function showTypingIndicator() {
  const messagesDiv = document.getElementById('chatMessages');
  const typingDiv = document.createElement('div');
  typingDiv.id = 'typingIndicator';
  typingDiv.className = 'chat-message flex justify-start';
  typingDiv.innerHTML = `
    <div class="flex space-x-3">
      <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center">
        <i class="bi bi-robot text-white"></i>
      </div>
      <div class="bg-white border border-gray-200 rounded-2xl rounded-tl-sm px-4 py-3">
        <div class="typing-indicator flex space-x-1">
          <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
          <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
          <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
        </div>
      </div>
    </div>
  `;
  messagesDiv.appendChild(typingDiv);
  scrollToBottom();
}

function removeTypingIndicator() {
  const indicator = document.getElementById('typingIndicator');
  if (indicator) indicator.remove();
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// ---------- API caller with robust handling + model fallback ----------
const PRIMARY_MODEL = 'amazon/nova-premier-v1';
const FALLBACK_MODEL = 'openai/gpt-4o-mini';

async function callAPI() {
  // Try primary; on model-specific failures, auto-fallback to secondary
  const tryModels = [PRIMARY_MODEL, FALLBACK_MODEL];
  let lastErr = null;

  for (const model of tryModels) {
    try {
      const msg = await fetchAndParse(model);
      conversationHistory.push({ role: 'assistant', content: msg });
      removeTypingIndicator();
      addMessage(msg, 'assistant');
      scrollToBottom();
      return; // success; stop trying others
    } catch (err) {
      lastErr = err;
      // If it was primary model and error looks model-specific, try fallback automatically
      if (model === PRIMARY_MODEL) {
        // Continue loop to fallback
        continue;
      } else {
        // Fallback also failed → show error
        break;
      }
    }
  }

  removeTypingIndicator();
  const errMsg = lastErr?.message || 'Unknown error';
  addMessage('⚠️ ' + errMsg, 'assistant');
}

async function fetchAndParse(model) {
  // Abort after 45s to avoid hanging
  const controller = new AbortController();
  const t = setTimeout(() => controller.abort(), 45000);

  try {
    const response = await fetch(API_URL, {
      method: 'POST',
      signal: controller.signal,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        model,
        messages: [
          { role: 'system', content: SYSTEM_PROMPT },
          ...conversationHistory
        ],
        temperature: 0.7,
        max_tokens: 2000
      })
    });

    // Read as text first to catch HTML/non-JSON cases
    const raw = await response.text();

    if (!response.ok) {
      // Try to parse upstream JSON error; else show HTML snippet
      let errMsg = `API Error ${response.status}`;
      try {
        const j = JSON.parse(raw);
        errMsg = j.error || j.message || errMsg;
      } catch {
        const snippet = raw.replace(/\s+/g, ' ').slice(0, 200);
        errMsg = `${errMsg}: ${snippet}`;
      }
      throw new Error(errMsg);
    }

    // Expect JSON body on success
    let data;
    try {
      data = JSON.parse(raw);
    } catch {
      const snippet = raw.replace(/\s+/g, ' ').slice(0, 200);
      throw new Error(`Unexpected non-JSON response: ${snippet}`);
    }

    const assistantMessage =
      data?.choices?.[0]?.message?.content ??
      data?.choices?.[0]?.text ?? // some providers return "text"
      '(no content)';

    return assistantMessage;
  } finally {
    clearTimeout(t);
  }
}

// Focus input on load
window.addEventListener('load', function () {
  document.getElementById('messageInput').focus();
});
</script>

</body>
</html>
