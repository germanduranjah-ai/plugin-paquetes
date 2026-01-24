(function() {
  'use strict';

  const modal = document.getElementById('udpq-reserva-modal');
  if (!modal) return;

  const openButtons = document.querySelectorAll('[data-udpq-open-reserva]');
  const closeButtons = document.querySelectorAll('[data-udpq-close-reserva]');
  const form = document.getElementById('udpq-reserva-form');

  function getSelectedMpUrl() {
    // Default form (fallback)
    const defaultChoice = modal.querySelector('input[name="price_idx"]:checked');
    if (defaultChoice && defaultChoice.dataset && defaultChoice.dataset.mpUrl) {
      return defaultChoice.dataset.mpUrl;
    }

    // CF7 / Elementor integration selector
    const choice = modal.querySelector('input[name="udpq_price_choice"]:checked');
    if (choice && choice.dataset && choice.dataset.mpUrl) {
      return choice.dataset.mpUrl;
    }

    return '';
  }

  function openModal() {
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    
    // Focus first input
    const firstInput = modal.querySelector('input[type="text"], input[type="email"]');
    if (firstInput) {
      setTimeout(() => firstInput.focus(), 100);
    }
  }

  function closeModal() {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  // Open modal
  openButtons.forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      openModal();
    });
  });

  // Close modal
  closeButtons.forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      closeModal();
    });
  });

  // Close on overlay click
  modal.addEventListener('click', function(e) {
    if (e.target === modal || e.target.classList.contains('udpq-reserva-modal__overlay')) {
      closeModal();
    }
  });

  // Close on ESC key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && modal.classList.contains('is-open')) {
      closeModal();
    }
  });

  // Handle form submission
  if (form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      // MP URL es opcional (si existe, se redirecciona).
      const mpUrl = getSelectedMpUrl();

      // Default form submission
      const formData = new FormData(form);
      
      // Show loading state
      const submitBtn = form.querySelector('button[type="submit"]');
      const originalText = submitBtn.textContent;
      submitBtn.disabled = true;
      submitBtn.textContent = 'Procesando...';

      // Submit via AJAX or direct POST
      const ajaxUrl = typeof udpqSingle !== 'undefined' ? udpqSingle.ajaxUrl : '/wp-admin/admin-ajax.php';
      const nonce = typeof udpqSingle !== 'undefined' ? udpqSingle.nonce : '';
      
      formData.append('action', 'udpq_reserva');
      if (nonce) {
        formData.append('nonce', nonce);
      }

      fetch(ajaxUrl, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          if (data.data && data.data.redirect) {
            window.location.href = data.data.redirect;
            return;
          }
          alert((data.data && data.data.message) ? data.data.message : 'Solicitud enviada.');
          submitBtn.disabled = false;
          submitBtn.textContent = originalText;
          closeModal();
        } else {
          alert(data.data.message || 'Error al procesar la reserva.');
          submitBtn.disabled = false;
          submitBtn.textContent = originalText;
        }
      })
      .catch(error => {
        console.error('Error:', error);
        // Fallback to direct form submission
        form.submit();
      });
    });
  }

  // CF7 integration: before submit, store redirect URL based on chosen option.
  const cf7Forms = modal.querySelectorAll('.wpcf7 form');
  if (cf7Forms.length) {
    cf7Forms.forEach(f => {
      f.addEventListener('submit', function(e){
        const mpUrl = getSelectedMpUrl();
        if (mpUrl) {
          sessionStorage.setItem('udpq_mp_url', mpUrl);
        }
      });
    });
  }

  // Elementor integration: before submit, store redirect URL based on chosen option.
  const elementorForms = modal.querySelectorAll('.elementor-form');
  if (elementorForms.length) {
    elementorForms.forEach(f => {
      f.addEventListener('submit', function(e){
        const mpUrl = getSelectedMpUrl();
        if (mpUrl) {
          sessionStorage.setItem('udpq_mp_url', mpUrl);
        }
      });
    });
  }

  // Handle Contact Form 7 success
  if (typeof wpcf7 !== 'undefined') {
    document.addEventListener('wpcf7mailsent', function(event) {
      const mpUrl = sessionStorage.getItem('udpq_mp_url');
      if (mpUrl) {
        sessionStorage.removeItem('udpq_mp_url');
        window.location.href = mpUrl;
      }
    });
  }

  // Handle Elementor form success
  if (typeof elementorFrontend !== 'undefined') {
    jQuery(document).on('submit_success', '.elementor-form', function(e) {
      const mpUrl = sessionStorage.getItem('udpq_mp_url');
      if (mpUrl) {
        sessionStorage.removeItem('udpq_mp_url');
        setTimeout(() => {
          window.location.href = mpUrl;
        }, 1000);
      }
    });
  }

})();
