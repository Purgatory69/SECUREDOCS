<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>SecureDocs | AI-Driven Document Management for Secure & Effortless Access</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; background: #f8fafc;}
    .gradient-bg {background: linear-gradient(90deg, #2747c7 0%, #1597ee 100%);}
    .glass {background: rgba(255,255,255,0.9); backdrop-filter: blur(20px);}
    .modal-bg {background: rgba(36,54,81,.6);}
    .feature-icon {background: #f1f5fb; border-radius: 9999px; width: 3.5rem; height:3.5rem; display: flex; align-items: center; justify-content: center;}
    /* Remove focus ring for pdf export aesthetics */
    button:focus {outline: none; box-shadow: none;}
    /* Remove scrollbars for PDF export */
    ::-webkit-scrollbar {width: 0 !important;}
    html {scroll-behavior:auto !important;}
  </style>
</head>
<body class="antialiased text-gray-800">
  <!-- NAVBAR -->
  <header class="gradient-bg text-white sticky w-full z-30 top-0">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
      <div class="flex items-center space-x-3">
        <span class="text-2xl font-extrabold tracking-tight"><i class="fa-solid fa-shield-halved mr-2"></i>SecureDocs</span>
      </div>
      <nav class="hidden md:flex space-x-8 text-base font-medium">
        <a href="#features" class="hover:underline transition">Features</a>
        <a href="#howitworks" class="hover:underline transition">How it Works</a>
        <a href="#benefits" class="hover:underline transition">Benefits</a>
        <a href="#contact" class="hover:underline transition">Contact</a>
      </nav>
      <div class="flex space-x-4">
        <a href="/login" class="bg-white text-blue-700 font-semibold px-5 py-2 rounded-full hover:bg-blue-50 shadow transition"><i class="fa-solid fa-right-to-bracket mr-2"></i>Log In</a>
        <a href="/register" class="hidden md:inline-block gradient-bg text-white font-semibold px-5 py-2 rounded-full hover:opacity-90 shadow transition"><i class="fa-solid fa-user-plus mr-2"></i>Sign Up</a>
      </div>
    </div>
  </header>
  <!-- HERO -->
  <section class="w-full gradient-bg py-16 md:py-24">
    <div class="max-w-4xl mx-auto px-6 flex flex-col items-center text-center space-y-6">
      <h1 class="text-4xl md:text-6xl font-extrabold leading-tight text-white drop-shadow-lg">AI-Driven <span class="text-blue-200">Document Management</span><br>For Secure & Effortless Access</h1>
      <p class="text-white text-lg md:text-2xl font-medium opacity-90 max-w-2xl">Experience next-generation document storage with <strong>AI-powered categorization</strong>, <strong>blockchain audit trails</strong>, <strong>biometric authentication</strong>, and <strong>unparalleled data security</strong>—all in one place.</p>
      <div class="flex flex-col md:flex-row space-y-3 md:space-y-0 md:space-x-4 w-full md:w-auto justify-center">
        <button onclick="openSignup()" class="w-full md:w-auto gradient-bg text-white font-semibold px-7 py-3 rounded-full shadow-lg text-lg hover:opacity-90 focus:outline-none transition"><i class="fa-solid fa-rocket mr-2"></i>Get Started Free</button>
        <button onclick="scrollToFeatures()" class="w-full md:w-auto bg-white text-blue-800 font-semibold px-7 py-3 rounded-full shadow-lg text-lg hover:bg-blue-50 focus:outline-none transition"><i class="fa-solid fa-circle-info mr-2"></i>Learn More</button>
      </div>
    </div>
  </section>
  <!-- FEATURES -->
  <section id="features" class="w-full max-w-7xl mx-auto px-6 py-14">
    <h2 class="text-3xl md:text-4xl font-extrabold text-center mb-8">Core Platform Features</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      <div class="p-6 glass rounded-xl shadow flex flex-col items-center space-y-3">
        <div class="feature-icon mb-2"><i class="fa-solid fa-fingerprint text-blue-700 text-xl"></i></div>
        <span class="font-bold text-xl">Biometric + MFA</span>
        <p class="text-gray-500 text-center">Multi-factor authentication and biometric verification (face/fingerprint)—only you can access your sensitive data.</p>
      </div>
      <div class="p-6 glass rounded-xl shadow flex flex-col items-center space-y-3">
        <div class="feature-icon mb-2"><i class="fa-solid fa-robot text-blue-700 text-xl"></i></div>
        <span class="font-bold text-xl">AI-Powered Document Management</span>
        <p class="text-gray-500 text-center">Smart document organization, fast NLP-based search, automatic OCR extraction, and intelligent anomaly detection.</p>
      </div>
      <div class="p-6 glass rounded-xl shadow flex flex-col items-center space-y-3">
        <div class="feature-icon mb-2"><i class="fa-solid fa-lock text-blue-700 text-xl"></i></div>
        <span class="font-bold text-xl">End-to-End Encryption</span>
        <p class="text-gray-500 text-center">Elliptic curve and AES encryption for all files at-rest and in-transit, backed by secure cloud storage and auto-backups.</p>
      </div>
      <div class="p-6 glass rounded-xl shadow flex flex-col items-center space-y-3">
        <div class="feature-icon mb-2"><i class="fa-brands fa-bitcoin text-blue-700 text-xl"></i></div>
        <span class="font-bold text-xl">Blockchain Audit Trail</span>
        <p class="text-gray-500 text-center">Tamper-proof blockchain records track every access, edit, and signature—ensuring non-repudiation and accountability.</p>
      </div>
      <div class="p-6 glass rounded-xl shadow flex flex-col items-center space-y-3">
        <div class="feature-icon mb-2"><i class="fa-solid fa-language text-blue-700 text-xl"></i></div>
        <span class="font-bold text-xl">Voice & Multilingual</span>
        <p class="text-gray-500 text-center">Navigate and search with intuitive voice commands; inclusive, with full multilingual UI and accessibility features.</p>
      </div>
      <div class="p-6 glass rounded-xl shadow flex flex-col items-center space-y-3">
        <div class="feature-icon mb-2"><i class="fa-solid fa-user-shield text-blue-700 text-xl"></i></div>
        <span class="font-bold text-xl">Role-Based & Context Access</span>
        <p class="text-gray-500 text-center">Adaptive permissions using RBAC and context awareness—users only see what they’re meant to, precisely when needed.</p>
      </div>
    </div>
  </section>
  <!-- HOW IT WORKS -->
  <section id="howitworks" class="w-full max-w-5xl mx-auto px-6 py-14">
    <h2 class="text-3xl font-extrabold text-center mb-8">How SecureDocs Works</h2>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
      <div class="flex flex-col items-center space-y-2 text-center">
        <div class="feature-icon mb-2"><i class="fa-solid fa-user-plus text-blue-700 text-lg"></i></div>
        <span class="text-lg font-semibold">1. Secure Sign Up</span>
        <p class="text-sm text-gray-500">Register with email and choose biometrics or MFA for authentication.</p>
      </div>
      <div class="flex flex-col items-center space-y-2 text-center">
        <div class="feature-icon mb-2"><i class="fa-solid fa-cloud-arrow-up text-blue-700 text-lg"></i></div>
        <span class="text-lg font-semibold">2. Upload & Organize</span>
        <p class="text-sm text-gray-500">Import files—AI organizes, categorizes, and encrypts your documents securely.</p>
      </div>
      <div class="flex flex-col items-center space-y-2 text-center">
        <div class="feature-icon mb-2"><i class="fa-solid fa-search text-blue-700 text-lg"></i></div>
        <span class="text-lg font-semibold">3. Find Instantly</span>
        <p class="text-sm text-gray-500">Retrieve any doc with AI-powered NLP search or voice commands, from any device.</p>
      </div>
      <div class="flex flex-col items-center space-y-2 text-center">
        <div class="feature-icon mb-2"><i class="fa-solid fa-share-nodes text-blue-700 text-lg"></i></div>
        <span class="text-lg font-semibold">4. Collaborate & Share</span>
        <p class="text-sm text-gray-500">Share securely, track revisions, sign and verify with digital signatures or blockchain.</p>
      </div>
    </div>
  </section>
  <!-- BENEFITS -->
  <section id="benefits" class="w-full max-w-6xl mx-auto px-6 py-14">
    <h2 class="text-3xl font-extrabold text-center mb-8">Why Trust SecureDocs?</h2>
    <div class="grid md:grid-cols-2 gap-8">
      <div class="p-8 rounded-xl glass shadow flex flex-col h-full">
        <div class="flex items-center space-x-3 mb-2">
          <i class="fa-solid fa-user-lock text-xl text-blue-700"></i>
          <span class="font-semibold">Unmatched Security</span>
        </div>
        <ul class="list-disc pl-6 text-gray-500 space-y-1 text-base">
          <li>Multi-layered encryption and blockchain audit logs</li>
          <li>Biometric/MFA user authentication</li>
          <li>Continuous anomaly detection and instant breach alerts</li>
        </ul>
      </div>
      <div class="p-8 rounded-xl glass shadow flex flex-col h-full">
        <div class="flex items-center space-x-3 mb-2">
          <i class="fa-solid fa-brain text-xl text-blue-700"></i>
          <span class="font-semibold">Powered by AI</span>
        </div>
        <ul class="list-disc pl-6 text-gray-500 space-y-1 text-base">
          <li>Category & context-aware organization for easy retrieval</li>
          <li>Smart, voice-enabled, multilingual search</li>
          <li>Intelligent OCR & NLP for document understanding</li>
        </ul>
      </div>
      <div class="p-8 rounded-xl glass shadow flex flex-col h-full">
        <div class="flex items-center space-x-3 mb-2">
          <i class="fa-solid fa-users text-xl text-blue-700"></i>
          <span class="font-semibold">Inclusive & Accessible</span>
        </div>
        <ul class="list-disc pl-6 text-gray-500 space-y-1 text-base">
          <li>Voice commands and assistive accessibility</li>
          <li>Multi-language support for global teams</li>
          <li>User-friendly interface on all devices</li>
        </ul>
      </div>
      <div class="p-8 rounded-xl glass shadow flex flex-col h-full">
        <div class="flex items-center space-x-3 mb-2">
          <i class="fa-solid fa-database text-xl text-blue-700"></i>
          <span class="font-semibold">Reliability & Compliance</span>
        </div>
        <ul class="list-disc pl-6 text-gray-500 space-y-1 text-base">
          <li>Automatic backups, offline access, and data recovery</li>
          <li>Regulatory compliance and transparent audit trails</li>
          <li>Version control and digital signatures</li>
        </ul>
      </div>
    </div>
  </section>
  <!-- TESTIMONIALS -->
  <section class="w-full max-w-4xl mx-auto px-6 py-14">
    <h2 class="text-3xl font-extrabold text-center mb-2">What Users Say</h2>
    <div class="flex flex-wrap justify-center items-stretch gap-8 mt-8">
      <blockquote class="glass rounded-xl shadow p-6 w-full md:w-1/2 flex flex-col justify-between">
        <p class="text-lg text-gray-700 font-medium mb-2">“With SecureDocs, I'm confident that all our contracts are safe and only accessible by authorized staff. The AI search is incredibly fast!”</p>
        <div class="flex items-center space-x-3 mt-3">
          <span class="inline-block w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center"><i class="fa-solid fa-user-tie text-blue-700"></i></span>
          <span class="font-semibold text-gray-700">Elaine T. – Legal Director</span>
        </div>
      </blockquote>
      <blockquote class="glass rounded-xl shadow p-6 w-full md:w-1/2 flex flex-col justify-between">
        <p class="text-lg text-gray-700 font-medium mb-2">“Switching to SecureDocs gave our team real peace of mind for our confidential research. MFA login and blockchain logs are game changers.”</p>
        <div class="flex items-center space-x-3 mt-3">
          <span class="inline-block w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center"><i class="fa-solid fa-user-graduate text-blue-700"></i></span>
          <span class="font-semibold text-gray-700">Derek S. – Data Scientist</span>
        </div>
      </blockquote>
    </div>
  </section>
  <!-- FAQ -->
  <section class="w-full max-w-4xl mx-auto px-6 py-14">
    <h2 class="text-3xl font-extrabold text-center mb-8">Frequently Asked Questions</h2>
    <div class="divide-y divide-gray-200">
      <div class="py-4">
        <button type="button" onclick="toggleFAQ(0)" class="w-full text-left flex items-center justify-between focus:outline-none">
          <span class="font-semibold text-gray-800">How secure is SecureDocs?</span>
          <i id="faqchevron0" class="fa-solid fa-chevron-down fa-sm text-gray-500"></i>
        </button>
        <div id="faqans0" class="hidden mt-2 text-gray-600 text-sm pl-1">Your documents are protected by state-of-the-art encryption, blockchain audit trails, and strict multi-factor/biometric authentication. Only you and your authorized collaborators can access your documents.</div>
      </div>
      <div class="py-4">
        <button type="button" onclick="toggleFAQ(1)" class="w-full text-left flex items-center justify-between focus:outline-none">
          <span class="font-semibold text-gray-800">Which login methods are supported?</span>
          <i id="faqchevron1" class="fa-solid fa-chevron-down fa-sm text-gray-500"></i>
        </button>
        <div id="faqans1" class="hidden mt-2 text-gray-600 text-sm pl-1">We support email/password, one-time passcodes, and advanced biometric (fingerprint/face) authentication for top-tier security and convenience.</div>
      </div>
      <div class="py-4">
        <button type="button" onclick="toggleFAQ(2)" class="w-full text-left flex items-center justify-between focus:outline-none">
          <span class="font-semibold text-gray-800">Can I access my documents offline?</span>
          <i id="faqchevron2" class="fa-solid fa-chevron-down fa-sm text-gray-500"></i>
        </button>
        <div id="faqans2" class="hidden mt-2 text-gray-600 text-sm pl-1">Yes. SecureDocs enables offline document access; changes sync automatically when you’re back online for seamless productivity.</div>
      </div>
      <div class="py-4">
        <button type="button" onclick="toggleFAQ(3)" class="w-full text-left flex items-center justify-between focus:outline-none">
          <span class="font-semibold text-gray-800">Is there real-time support available?</span>
          <i id="faqchevron3" class="fa-solid fa-chevron-down fa-sm text-gray-500"></i>
        </button>
        <div id="faqans3" class="hidden mt-2 text-gray-600 text-sm pl-1">Absolutely! Our AI-powered chatbot is on hand 24/7 for instant answers and assistance, with human support just a click away on complex issues.</div>
      </div>
    </div>
  </section>
  <!-- CONTACT/REQUEST DEMO -->
  <section id="contact" class="w-full max-w-2xl mx-auto px-6 py-14">
    <h2 class="text-3xl font-extrabold text-center mb-6">Contact Us / Request Demo</h2>
    <form class="glass rounded-xl shadow-lg px-6 py-8 flex flex-col space-y-4" autocomplete="off">
      <div>
        <label class="block font-semibold mb-1" for="name">Full Name</label>
        <input class="w-full rounded-md border border-gray-200 px-4 py-2 focus:ring-2 focus:ring-blue-200" name="name" id="name" placeholder="Your Name" required>
      </div>
      <div>
        <label class="block font-semibold mb-1" for="email">Email Address</label>
        <input class="w-full rounded-md border border-gray-200 px-4 py-2 focus:ring-2 focus:ring-blue-200" name="email" id="email" type="email" placeholder="you@email.com" required>
      </div>
      <div>
        <label class="block font-semibold mb-1" for="msg">Your Message</label>
        <textarea class="w-full rounded-md border border-gray-200 px-4 py-2 focus:ring-2 focus:ring-blue-200" name="msg" id="msg" rows="3" placeholder="How can we help you?" required></textarea>
      </div>
      <button type="submit" class="w-full gradient-bg rounded-full text-white font-semibold py-3 shadow text-lg mt-2 hover:opacity-90 transition">
        <i class="fa-solid fa-paper-plane mr-1"></i>Send Message
      </button>
    </form>
  </section>
  <!-- FOOTER -->
  <footer class="w-full border-t bg-white mt-10 py-7 text-center text-gray-500 font-medium">
    <span>&copy; 2025 SecureDocs. Designed by the SecureDocs Capstone Team, University of Cebu Lapu-Lapu and Mandaue.</span>
  </footer>
  <!-- JS SCRIPTS -->
  <script>
    function openSignup() {
        window.location.href = '/register';
    }
    // FAQ Accordion
    function toggleFAQ(idx) {
      let ans = document.getElementById('faqans'+idx);
      let chevron = document.getElementById('faqchevron'+idx);
      if(ans.classList.contains('hidden')) {
        ans.classList.remove('hidden');
        chevron.classList.remove('fa-chevron-down');
        chevron.classList.add('fa-chevron-up');
      } else {
        ans.classList.add('hidden');
        chevron.classList.remove('fa-chevron-up');
        chevron.classList.add('fa-chevron-down');
      }
    }
    // Remove scroll on open modal for pdf
    // Smooth scroll for "Learn More"
    function scrollToFeatures() {
      window.scrollTo({top: document.getElementById('features').offsetTop-30, behavior:'smooth'});
    }
    // Prevent actual form submissions (for demo and pdf export)
    document.querySelectorAll('form').forEach(f=>{f.onsubmit=e=>{e.preventDefault();alert('Form simulated for demo purpose.');closeModal();}})

    document.addEventListener('DOMContentLoaded', function() {
      document.body.classList.remove('overflow-y-scroll');
      document.body.style.overflowY = 'auto';
    });
  </script>
</body>
</html>
