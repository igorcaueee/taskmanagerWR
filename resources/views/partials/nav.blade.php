<header id="main-header" class="fixed top-0 left-0 right-0 z-50 border-b border-transparent" style="transition: background .4s, border-color .4s, backdrop-filter .4s, box-shadow .4s; box-shadow: 0 2px 20px rgba(0,0,0,.35);">
    <nav class="w-full px-6 lg:px-10 h-[68px] flex items-center justify-between">

        <!-- Logo (esquerda) -->
        <a href="/" class="flex items-center gap-3 shrink-0" style="text-decoration: none;">
            <img src="/images/torresemfundo.png" alt="WR Assessoria" class="w-9 h-9 object-contain">
            <div class="leading-tight">
                <p class="text-white text-[15px] leading-none font-semibold" style="margin:0;"><b>WR</b> Assessoria</p>
                <p class="text-[10px] font-semibold tracking-[0.14em] uppercase leading-none mt-1" style="color:#0084aa; margin:0;">Contabilidade &amp; Gestão Empresarial</p>
            </div>
        </a>

        <!-- Links + CTA (direita) -->
        <div class="hidden md:flex items-center gap-1">
            <a href="#services" class="text-sm font-medium px-4 py-2 rounded-lg transition-all duration-200 hover:bg-white/10 hover:text-white no-underline" style="color:rgba(255,255,255,.8);">Serviços</a>
            <a href="#about"    class="text-sm font-medium px-4 py-2 rounded-lg transition-all duration-200 hover:bg-white/10 hover:text-white no-underline" style="color:rgba(255,255,255,.8);">Sobre</a>
            <a href="#location" class="text-sm font-medium px-4 py-2 rounded-lg transition-all duration-200 hover:bg-white/10 hover:text-white no-underline" style="color:rgba(255,255,255,.8);">Localização</a>
            <a href="{{ route('funil.captura') }}" class="ml-4 inline-flex items-center gap-2 shrink-0 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-all duration-300 hover:shadow-[0_0_25px_rgba(0,132,170,.5)] hover:scale-[1.02] no-underline" style="background:#0084aa;">
                <i class="far fa-calendar-check text-sm"></i>
                Fale com um especialista
            </a>
        </div>

        <!-- Mobile menu button -->
        <button id="mobile-menu-btn" class="md:hidden text-white/70 hover:text-white p-2">
            <i class="fas fa-bars text-lg"></i>
        </button>
    </nav>

    <!-- Mobile menu -->
    <div id="mobile-menu" class="hidden md:hidden px-6 pb-4 flex-col gap-1" style="background:rgba(0,0,0,.95);">
        <a href="#services" class="text-white/70 hover:text-white text-sm font-medium py-3 border-b border-white/10 no-underline block">Serviços</a>
        <a href="#about"    class="text-white/70 hover:text-white text-sm font-medium py-3 border-b border-white/10 no-underline block">Sobre</a>
        <a href="#location" class="text-white/70 hover:text-white text-sm font-medium py-3 border-b border-white/10 no-underline block">Localização</a>
        <a href="{{ route('funil.captura') }}"  class="text-white/70 hover:text-white text-sm font-medium py-3 border-b border-white/10 no-underline block">Contato</a>
        <a href="{{ route('funil.captura') }}"  class="mt-3 inline-flex items-center gap-2 text-white text-sm font-semibold px-5 py-3 rounded-xl no-underline" style="background:#0084aa;">
            <i class="far fa-calendar-check"></i> Fale com um especialista
        </a>
    </div>
</header>

<script>
(function () {
    const header = document.getElementById('main-header');
    const mobileBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');

    function updateHeader() {
        if (window.scrollY > 40) {
            header.style.background = 'rgba(0,0,0,.88)';
            header.style.borderColor = 'rgba(255,255,255,.08)';
            header.style.backdropFilter = 'blur(16px)';
            header.style.boxShadow = '0 1px 30px rgba(0,0,0,.5)';
        } else {
            header.style.background = 'transparent';
            header.style.borderColor = 'transparent';
            header.style.backdropFilter = 'blur(0px)';
            header.style.boxShadow = 'none';
        }
    }

    window.addEventListener('scroll', updateHeader, { passive: true });
    updateHeader();

    if (mobileBtn) {
        mobileBtn.addEventListener('click', function () {
            mobileMenu.classList.toggle('hidden');
            mobileMenu.classList.toggle('flex');
        });
    }
}());
</script>
