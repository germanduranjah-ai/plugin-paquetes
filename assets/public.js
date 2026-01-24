
(function(){
  function parseLines(text){
    if(!text) return [];
    return String(text)
      .split(/\r?\n/)
      .map(s => s.trim())
      .filter(Boolean);
  }

  function money(amount, currency){
    if(amount === null || amount === undefined) return '';
    try{
      const n = Number(amount);
      if(!isFinite(n)) return '';
      // Simple formatting: 0 decimals, dot thousands, comma decimals (AR)
      const formatted = n.toLocaleString('es-AR', { maximumFractionDigits: 0 });
      return (currency ? currency + ' ' : '') + formatted;
    }catch(e){
      return (currency ? currency + ' ' : '') + amount;
    }
  }

  const modal = document.getElementById('udpq-modal');
  if(!modal) return;

  const img = modal.querySelector('.udpq-modal__img');
  const title = modal.querySelector('.udpq-modal__title');
  const subtitle = modal.querySelector('.udpq-modal__subtitle');
  const from = modal.querySelector('.udpq-modal__from');
  const ulServicios = modal.querySelector('[data-udpq-servicios]');
  const ulBeneficios = modal.querySelector('[data-udpq-beneficios]');
  const alojamiento = modal.querySelector('[data-udpq-alojamiento]');
  const sel = modal.querySelector('[data-udpq-select]');
  const total = modal.querySelector('[data-udpq-total]');
  const pp = modal.querySelector('[data-udpq-pp]');
  const note = modal.querySelector('[data-udpq-note]');
  const reservar = modal.querySelector('[data-udpq-reservar]');

  // Filter toggle functionality
  const filterToggles = document.querySelectorAll('.udpq-filters__toggle');
  filterToggles.forEach(function(toggle) {
    toggle.addEventListener('click', function() {
      const content = this.closest('.udpq-filters').querySelector('.udpq-filters__content');
      const isExpanded = this.getAttribute('aria-expanded') === 'true';

      content.style.display = isExpanded ? 'none' : 'block';
      this.setAttribute('aria-expanded', !isExpanded);
      this.textContent = isExpanded ? 'Mostrar filtros' : 'Ocultar filtros';
    });
  });

  let current = null;

  function close(){
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  function open(payload){
    current = payload;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    img.src = payload.thumb || '';
    img.alt = payload.title || '';

    title.textContent = payload.title || '';
    const subBits = [];
    if(payload.destino) subBits.push(payload.destino);
    if(payload.noches) subBits.push(payload.noches + ' noches');
    if(payload.fecha) subBits.push(payload.fecha);
    subtitle.innerHTML = '';
    subBits.forEach(function(bit){
      const sp = document.createElement('span');
      sp.textContent = bit;
      subtitle.appendChild(sp);
    });
from.innerHTML = payload.price_from ? ('Desde <strong>' + money(payload.price_from, payload.currency) + ' por persona</strong>') : '';

    // Servicios: si no hay textarea, armamos algo con compañía/equipaje/traslados
    const servicios = parseLines(payload.servicios);
    if(ulServicios){
      ulServicios.innerHTML = '';
      const fallback = [];
      if(payload.compania) fallback.push('Aéreos ida y vuelta con ' + payload.compania);
      if(payload.equipaje) fallback.push('Incluye equipaje: ' + payload.equipaje);
      if(payload.salida_vuelo) fallback.push('Salida: ' + payload.salida_vuelo);
      if(payload.seguro_traslados) fallback.push(payload.seguro_traslados);
      const list = servicios.length ? servicios : fallback;
      list.forEach(item => {
        const li = document.createElement('li');
        li.textContent = item;
        ulServicios.appendChild(li);
      });
    }

    // Alojamiento
    const aloj = [];
    if(payload.noches) aloj.push(payload.noches + ' noches');
    if(payload.hotel) aloj.push('en ' + payload.hotel);
    const reg = payload.regimen ? (' – ' + payload.regimen) : '';
    alojamiento.textContent = (aloj.join(' ') + reg).trim();

    // Beneficios
    const beneficios = parseLines(payload.beneficios);
    ulBeneficios.innerHTML = '';
    beneficios.forEach(item => {
      const li = document.createElement('li');
      li.textContent = item;
      ulBeneficios.appendChild(li);
    });
    if(!beneficios.length){
      // keep empty but hide section list if no items
      const li = document.createElement('li');
      li.textContent = '—';
      ulBeneficios.appendChild(li);
    }

    // Options select
    sel.innerHTML = '';
    const options = Array.isArray(payload.options) ? payload.options : [];
    let firstActiveIndex = options.findIndex(o => o && o.active);
    if(firstActiveIndex < 0) firstActiveIndex = 0;

    options.forEach((o, idx) => {
      const opt = document.createElement('option');
      const label = o.label || ('Opción ' + (idx+1));
      opt.value = String(idx);
      opt.textContent = label + ' — ' + money(o.price, o.currency || payload.currency);
      opt.disabled = !o.active;
      sel.appendChild(opt);
    });

    if(options.length){
      sel.value = String(firstActiveIndex);
      updatePrice();
    } else {
      total.textContent = '';
      pp.textContent = '';
      note.textContent = '';
    }

    reservar.href = (payload.permalink || '#') + '#udpq-reserva';
  }

  function updatePrice(){
    if(!current) return;
    const idx = Number(sel.value);
    const o = (current.options && current.options[idx]) ? current.options[idx] : null;
    if(!o) return;

    const currency = o.currency || current.currency || 'ARS';
    const price = Number(o.price || 0);
    pp.textContent = money(price, currency);
    total.textContent = money(price * 2, currency);
    note.textContent = o.note ? String(o.note) : '';
  }

  document.addEventListener('click', function(e){
    const btn = e.target.closest('.udpq-open');
    if(btn){
      const card = btn.closest('.udpq-card');
      if(!card) return;
      const raw = card.getAttribute('data-udpq');
      if(!raw) return;
      try{
        const payload = JSON.parse(raw);
        open(payload);
      }catch(err){
        console.error('UDPQ payload error', err);
      }
      e.preventDefault();
      return;
    }

    if(e.target.closest('[data-udpq-close]')){
      e.preventDefault();
      close();
    }
  });

  sel.addEventListener('change', updatePrice);

  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape' && modal.classList.contains('is-open')) close();
  });
})();
