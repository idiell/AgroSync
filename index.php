<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>AgroSync</title>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

  <!-- Tailwind (CDN) -->
  <link href="../public/app.css" rel="stylesheet">

  <style>
    /* Use your fonts */
    :root { --nav-h: 72px; }
    body{ font-family:'Poppins',system-ui,Arial,sans-serif }
    .font-playfair{ font-family:"Playfair Display", ui-serif, Georgia, serif; }
  </style>
</head>
<body class="text-[#0e2319]">
  <!-- NAV -->
  <div class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-[#e9ecef]">
    <div class="h-[72px] max-w-[1200px] w-[92vw] mx-auto flex items-center justify-between gap-4">
      <a href="index.php" class="text-[#0c2b1c] font-extrabold text-[22px] tracking-wide">AgroSync</a>

      <div class="flex items-center gap-2">
        <a href="../modules/dashboard/index.php" class="inline-block rounded-full px-4 py-2 font-semibold border border-[#dde5df] bg-white text-[#111] hover:-translate-y-[1px] transition">Log In</a>
        <a href="#" class="inline-block rounded-full px-4 py-2 font-semibold border border-[#dde5df] bg-green-600 text-white hover:-translate-y-[1px] transition">Sign up</a>
      </div>
    </div>
  </div>

  <!-- SECTION 1 (Hero) -->
  <section class="relative min-h-[calc(100vh-72px)] flex items-center">
    <!-- Background + overlay -->
    <div class="absolute inset-0">
      <div class="absolute inset-0 bg-gradient-to-b from-black/35 to-black/55 z-[1]"></div>
      <img
        src="https://i.pinimg.com/1200x/d3/64/91/d36491a34ee31579c229ba444bdf5c27.jpg"
        alt="farming bg"
        class="w-full h-full object-cover"
      />
    </div>

    <div class="relative z-[2] max-w-[1200px] w-[92vw] mx-auto grid gap-6 md:grid-cols-[1.05fr_.95fr] items-center">
      <!-- Left copy -->
      <div class="text-white">
        <h1 class="font-playfair text-[clamp(36px,5.5vw,68px)] leading-tight font-light drop-shadow-lg">
          Effortless farm<br/>data, from field<br/>to report
        </h1>
        <p class="mt-3 text-[18px] leading-7/relaxed opacity-95 max-w-[48ch]">
          Accurate, real-time farm data without the paperwork — from regulatory reporting
          to clean financial statements.
        </p>
        <div class="mt-4 flex items-center gap-3">
          <a href="#"
             class="inline-block rounded-full px-5 py-3 font-semibold border border-[#e5e7eb] bg-white text-[#111] hover:shadow transition">
            Get Started
          </a>
        </div>
      </div>

      <!-- Right mock (optional) -->
      <div class="flex justify-center items-center gap-4">
        <div class="rounded-2xl overflow-hidden bg-black shadow-2xl border border-white/10 w-[540px] aspect-[16/10] max-w-full">
          <img src="https://images.unsplash.com/photo-1559163179-4b48b0b72f9b?q=80&w=1200&auto=format&fit=crop" alt="Tablet UI" class="w-full h-full object-cover" />
        </div>
      </div>
    </div>
  </section>

  <!-- SECTION 2 (Sponsors) -->
  <section class="max-w-[1100px] w-[92vw] mx-auto my-20">
    <p class="text-[clamp(20px,3vw,40px)] leading-tight text-black mb-8">
      Join us and discover the <br />
      future of farming at your fingertips
    </p>

    <div class="flex flex-col items-center gap-4">
      <span class="text-[16px] text-[#222] font-semibold lowercase tracking-[0.2px] mb-6">support by</span>

      <div class="flex flex-wrap items-center justify-center gap-10">
        <a href="https://www.lpp.gov.my/">
          <img class="w-[150px] max-w-[40vw] hover:-translate-y-[3px] transition"
               src="../uploads/Lembaga Pertubuhan Peladang.png"
               alt="Lembaga Pertubuhan Peladang" loading="lazy"/>
        </a>
        <a href="https://nafas.com.my/v4/">
          <img class="w-[150px] max-w-[40vw] hover:-translate-y-[3px] transition"
               src="../uploads/Logo Pertubuhan Peladang Kebangsaan.png"
               alt="NAFAS" loading="lazy"/>
        </a>
        </a>
        <a href="https://www.felda.gov.my/">
          <img class="w-[150px] max-w-[40vw] hover:-translate-y-[3px] transition"
               src="../uploads/Felda.png"
               alt="FELDA" loading="lazy"/>
        </a>
      </div>
    </div>
  </section>

  <!-- SECTION 3 (Features) -->
  <section class="bg-[#e7f1eb] py-20">
    <div class="max-w-[1200px] w-[92vw] mx-auto mb-14 grid grid-cols-1 md:grid-cols-2 gap-8">
      <h2 class="text-[clamp(25px,3vw,45px)] font-medium leading-snug text-[#0b2916]">
        Step into the future<br/>of smart farm.
      </h2>
      <p class="text-[18px] leading-[1.55] text-[#1e3a23]">
        AgroSync equips Farmers and Agriculture Organizations with solutions that
        cut time and costs by integrating data and insights for smarter decisions.
      </p>
    </div>

    <div class="max-w-[1200px] w-[92vw] mx-auto grid gap-7 lg:grid-cols-3 sm:grid-cols-2 grid-cols-1 text-left sm:text-left">
      <!-- Card 1 -->
      <div>
        <div class="mb-3">
          <img src="../uploads/barn.png" alt="Farm Management Icon" width="35" height="35" class="mx-0 sm:mx-0"/>
        </div>
        <h3 class="text-2xl font-bold text-[#0a2714] mb-3">Farm Management</h3>
        <p class="text-[16px] leading-[1.5] text-[#2b4734]">
          Track crop cycles, field work, and livestock tasks with precision to boost farm productivity and efficiency.
        </p>
      </div>

      <!-- Card 2 -->
      <div>
        <div class="mb-3 text-black">
          <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" fill="currentColor" class="bi bi-cash" viewBox="0 0 16 16">
            <path d="M8 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4"/>
            <path d="M0 4a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1zm3 0a2 2 0 0 1-2 2v4a2 2 0 0 1 2 2h10a2 2 0 0 1 2-2V6a2 2 0 0 1-2-2z"/>
          </svg>
        </div>
        <h3 class="text-2xl font-bold text-[#0a2714] mb-3">Sell Management</h3>
        <p class="text-[16px] leading-[1.5] text-[#2b4734]">
          Manage pricing, buyers, and transactions in one place for better profits and easier decision-making.
        </p>
      </div>

      <!-- Card 3 -->
      <div>
        <div class="mb-3 text-black">
          <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" fill="currentColor" class="bi bi-robot" viewBox="0 0 16 16">
            <path d="M6 12.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5M3 8.062C3 6.76 4.235 5.765 5.53 5.886a26.6 26.6 0 0 0 4.94 0C11.765 5.765 13 6.76 13 8.062v1.157a.93.93 0 0 1-.765.935c-.845.147-2.34.346-4.235.346s-3.39-.2-4.235-.346A.93.93 0 0 1 3 9.219zm4.542-.827a.25.25 0 0 0-.217.068l-.92.9a25 25 0 0 1-1.871-.183.25.25 0 0 0-.068.495c.55.076 1.232.149 2.02.193a.25.25 0 0 0 .189-.071l.754-.736.847 1.71a.25.25 0 0 0 .404.062l.932-.97a25 25 0 0 0 1.922-.188.25.25 0 0 0-.068-.495c-.538.074-1.207.145-1.98.189a.25.25 0 0 0-.166.076l-.754.785-.842-1.7a.25.25 0 0 0-.182-.135"/>
            <path d="M8.5 1.866a1 1 0 1 0-1 0V3h-2A4.5 4.5 0 0 0 1 7.5V8a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1v1a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-1a1 1 0 0 0 1-1V9a1 1 0 0 0-1-1v-.5A4.5 4.5 0 0 0 10.5 3h-2zM14 7.5V13a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V7.5A3.5 3.5 0 0 1 5.5 4h5A3.5 3.5 0 0 1 14 7.5"/>
          </svg>
        </div>
        <h3 class="text-2xl font-bold text-[#0a2714] mb-3">Aira.ai</h3>
        <p class="text-[16px] leading-[1.5] text-[#2b4734]">
          Your smart farming assistant. Ask questions and get instant guidance powered by
          agricultural intelligence — anytime, anywhere.
        </p>
      </div>
    </div>
  </section>

  <!-- ARTICLES -->
  <section class="max-w-[1200px] w-[92vw] mx-auto my-24">
    <h2 class="text-[clamp(28px,4vw,42px)] font-bold mb-10 text-[#0a2714]">News and Articles</h2>

    <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-3">
      <!-- Card 1 -->
      <article class="flex flex-col gap-4">
        <img src="https://source.unsplash.com/600x350/?flag,usda" class="w-full h-52 object-cover rounded-xl shadow-lg" alt="USDA Update">
        <div class="flex gap-3 text-sm items-center">
          <span class="bg-[#d4f3d4] text-[#0a6a42] px-3 py-1 rounded-full font-semibold">Grant Management</span>
          <span class="text-[#6d7d74]">October 22, 2025</span>
        </div>
        <h3 class="text-xl font-bold leading-snug text-[#0b2916]">USDA FSA Offices are Reopening. What Does This Mean?</h3>
        <p class="text-[#37493f]/90 leading-relaxed">The USDA announced that around 2,100 county-level FSA offices will reopen for ...</p>
        <a href="#" class="font-semibold text-[#0a6a42] flex items-center gap-1 hover:gap-2 transition-all">Read more →</a>
      </article>

      <!-- Card 2 -->
      <article class="flex flex-col gap-4">
        <img src="https://source.unsplash.com/600x350/?endangered-species,forest" class="w-full h-52 object-cover rounded-xl shadow-lg" alt="Wildlife Rules">
        <div class="flex gap-3 text-sm items-center">
          <span class="bg-[#d4f3d4] text-[#0a6a42] px-3 py-1 rounded-full font-semibold">Grant Management</span>
          <span class="text-[#6d7d74]">October 24, 2025</span>
        </div>
        <h3 class="text-xl font-bold leading-snug text-[#0b2916]">Understanding Endangered Species Compliance in AMP Projects</h3>
        <p class="text-[#37493f]/90 leading-relaxed">This blog takes a biologist’s lens to endangered species compliance in AMP pro...</p>
        <a href="#" class="font-semibold text-[#0a6a42] flex items-center gap-1 hover:gap-2 transition-all">Read more →</a>
      </article>

      <!-- Card 3 -->
      <article class="flex flex-col gap-4">
        <img src="https://source.unsplash.com/600x350/?financial,report" class="w-full h-52 object-cover rounded-xl shadow-lg" alt="Income Statements">
        <div class="flex gap-3 text-sm items-center">
          <span class="bg-[#d6e8ff] text-[#0a236a] px-3 py-1 rounded-full font-semibold">Farm Management</span>
          <span class="text-[#6d7d74]">October 23, 2025</span>
        </div>
        <h3 class="text-xl font-bold leading-snug text-[#0b2916]">The Crucial Role of Income Statements for Agricultural Lenders</h3>
        <p class="text-[#37493f]/90 leading-relaxed">Why income statements are crucial in evaluating farm financial health...</p>
        <a href="#" class="font-semibold text-[#0a6a42] flex items-center gap-1 hover:gap-2 transition-all">Read more →</a>
      </article>
    </div>
  </section>

  <!-- Footer include -->
  <footer>
    <?php include './components/footer.php';?>
  </footer>

</body>
</html>
