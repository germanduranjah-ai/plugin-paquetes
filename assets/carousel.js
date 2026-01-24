(function(){
  function init(){
    if(typeof Swiper === 'undefined') return;

    document.querySelectorAll('.udpq-carousel[data-udpq-carousel]').forEach(function(el){
      // Avoid double init
      if(el.__udpqSwiper) return;

      var wrap = el.closest('.udpq-wrap');
      var nextEl = wrap ? wrap.querySelector('.udpq-swiper-next') : null;
      var prevEl = wrap ? wrap.querySelector('.udpq-swiper-prev') : null;
      var pagEl  = wrap ? wrap.querySelector('.udpq-swiper-pagination') : null;

      el.__udpqSwiper = new Swiper(el, {
        loop: false,
        watchOverflow: true,
        spaceBetween: 16,
        slidesPerView: 1.00,
        centeredSlides: false,
        navigation: (nextEl && prevEl) ? { nextEl: nextEl, prevEl: prevEl } : undefined,
        pagination: pagEl ? { el: pagEl, clickable: true } : undefined,
        breakpoints: {
          640: { slidesPerView: 2.05, spaceBetween: 16 },
          1024: { slidesPerView: 3.05, spaceBetween: 22 }
        }
      });
    });
  }

  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
