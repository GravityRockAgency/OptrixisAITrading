(() => {
  const root = document.getElementById('osc-concierge');
  if (!root || !window.OSC_WIDGET) return;

  const cfg = window.OSC_WIDGET;
  const launcher = root.querySelector('.osc-launcher');
  const closeBtn = root.querySelector('.osc-close');
  const screens = root.querySelectorAll('.osc-screen');
  const quiz = {
    step: 0,
    answers: {},
    questions: [
      { key: 'skin_type', title: 'Quel est votre type de peau ?', options: ['Normale', 'Sèche', 'Grasse', 'Mixte', 'Sensible'] },
      { key: 'usage_type', title: 'Votre usage principal ?', options: ['Plage', 'Sport / surf', 'Ville', 'Voyage', 'Cheveux exposés au soleil', 'Famille'] },
      { key: 'texture_preference', title: 'Votre préférence texture ?', options: ['Invisible', 'Non grasse', 'Minérale', 'Hydratante', 'Format stick', 'Protection cheveux'] },
      { key: 'zone', title: 'Quelle zone souhaitez-vous protéger ?', options: ['Visage', 'Corps', 'Lèvres / nez / zones sensibles', 'Cheveux', 'Routine complète'] },
    ],
    product: null,
    recommendationId: 0,
  };

  const request = async (path, data = {}, method = 'POST') => {
    const options = { method, headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce } };
    if (method !== 'GET') options.body = JSON.stringify(data);
    const response = await fetch(cfg.restUrl + path, options);
    const json = await response.json();
    if (!response.ok || json.success === false) throw new Error(json.message || cfg.i18n.networkError);
    return json;
  };

  const show = (name) => {
    screens.forEach((screen) => screen.classList.toggle('is-active', screen.dataset.screen === name));
  };

  const setOpen = (open) => {
    root.dataset.open = open ? 'true' : 'false';
    launcher.setAttribute('aria-expanded', open ? 'true' : 'false');
  };

  const whatsappUrl = (message) => {
    if (!cfg.settings.whatsappEnabled || !cfg.settings.whatsappNumber) return '';
    return `https://wa.me/${cfg.settings.whatsappNumber}?text=${encodeURIComponent(message)}`;
  };

  const trackWhatsapp = (context, message) => {
    request('track-whatsapp-click', { context, message, source_page: cfg.sourcePage }).catch(() => {});
  };

  const openWhatsapp = (context, message) => {
    const url = whatsappUrl(message);
    if (!url) {
      alert(cfg.i18n.whatsappUnavailable);
      return;
    }
    trackWhatsapp(context, message);
    window.open(url, '_blank', 'noopener');
  };

  const renderQuiz = () => {
    show('quiz');
    const q = quiz.questions[quiz.step];
    root.querySelector('.osc-quiz-title').textContent = q.title;
    root.querySelector('.osc-quiz-progress span').style.width = `${((quiz.step + 1) / quiz.questions.length) * 100}%`;
    const holder = root.querySelector('.osc-quiz-options');
    holder.innerHTML = '';
    q.options.forEach((option) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'osc-choice';
      btn.textContent = option;
      btn.addEventListener('click', async () => {
        quiz.answers[q.key] = option;
        if (quiz.step < quiz.questions.length - 1) {
          quiz.step += 1;
          renderQuiz();
          return;
        }
        await finishQuiz();
      });
      holder.appendChild(btn);
    });
  };

  const productFallbackName = (product) => product.name || 'Votre sélection Oceara';

  const productWhatsappMessage = (product) => `Bonjour Oceara,

J’aimerais avoir un conseil pour choisir ma protection solaire.

Type de peau : ${quiz.answers.skin_type || ''}
Usage principal : ${quiz.answers.usage_type || ''}
Préférence : ${quiz.answers.texture_preference || ''}
Zone concernée : ${quiz.answers.zone || ''}
Produit recommandé : ${productFallbackName(product)}

Merci.`;

  const finishQuiz = async () => {
    try {
      const json = await request('save-recommendation', { ...quiz.answers, action_clicked: 'none', source_page: cfg.sourcePage });
      quiz.product = json.product;
      quiz.recommendationId = json.id || 0;
      renderResult(json.product);
    } catch (error) {
      alert(error.message || cfg.i18n.networkError);
    }
  };

  const renderResult = (product) => {
    show('result');
    const card = root.querySelector('[data-result-card]');
    const image = product.image ? `<img src="${escapeAttr(product.image)}" alt="${escapeAttr(productFallbackName(product))}">` : '';
    const view = product.permalink ? `<a class="is-primary" href="${escapeAttr(product.permalink)}" data-product-action="view_product">Voir le produit</a>` : '';
    const cart = product.add_to_cart_url && product.available ? `<a href="${escapeAttr(product.add_to_cart_url)}" data-product-action="add_to_cart">Ajouter au panier</a>` : '';
    card.innerHTML = `${image}<h3>${escapeHtml(productFallbackName(product))}</h3><p>${escapeHtml(product.advice || '')}</p>${product.price ? `<strong>${escapeHtml(product.price)}</strong>` : ''}<div class="osc-product-actions">${view}${cart}<button type="button" data-whatsapp="product">Demander conseil sur WhatsApp</button></div>`;
  };

  const escapeHtml = (value) => String(value).replace(/[&<>'"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char]));
  const escapeAttr = escapeHtml;

  root.addEventListener('click', (event) => {
    const target = event.target.closest('button,a');
    if (!target) return;
    if (target === launcher) setOpen(root.dataset.open !== 'true');
    if (target === closeBtn) setOpen(false);
    if (target.dataset.screenTarget) show(target.dataset.screenTarget);
    if (target.dataset.action === 'quiz') {
      quiz.step = 0;
      quiz.answers = {};
      renderQuiz();
    }
    if (target.dataset.whatsapp === 'product') openWhatsapp('product_advice', productWhatsappMessage(quiz.product || {}));
    if (target.dataset.whatsapp === 'collaboration') openWhatsapp('collaboration', 'Bonjour Oceara,\n\nJe souhaite avoir plus d’informations pour devenir revendeur / partenaire Oceara.\n\nNom :\nTéléphone :\nVille :\nType de point de vente :\n\nMerci.');
    if (target.dataset.productAction) {
      request('save-recommendation', { recommendation_id: quiz.recommendationId, action_clicked: target.dataset.productAction, source_page: cfg.sourcePage }).catch(() => {});
    }
  });

  const contactForm = root.querySelector('[data-form="contact"]');
  contactForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const status = root.querySelector('[data-contact-status]');
    status.className = 'osc-status is-visible';
    status.textContent = 'Envoi en cours…';
    const data = Object.fromEntries(new FormData(contactForm).entries());
    data.source_page = cfg.sourcePage;
    try {
      const json = await request('submit-conversation', data);
      status.textContent = json.message;
      contactForm.reset();
    } catch (error) {
      status.className = 'osc-status is-visible is-error';
      status.textContent = error.message || cfg.i18n.networkError;
    }
  });

  const orderForm = root.querySelector('[data-form="order"]');
  orderForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const status = root.querySelector('[data-order-status]');
    status.className = 'osc-status is-visible';
    status.textContent = 'Recherche de votre commande…';
    const data = Object.fromEntries(new FormData(orderForm).entries());
    data.source_page = cfg.sourcePage;
    try {
      const json = await request('track-order', data);
      if (json.success && json.order) {
        status.innerHTML = `<strong>${escapeHtml(json.order.message)}</strong><br>Statut : ${escapeHtml(json.order.status)}<br>Date : ${escapeHtml(json.order.date)}<br>Total : ${escapeHtml(json.order.total)}<br><button type="button" class="osc-whatsapp-link" data-order-whatsapp>Demander des précisions sur WhatsApp</button>`;
        status.querySelector('[data-order-whatsapp]')?.addEventListener('click', () => {
          openWhatsapp('order_tracking', `Bonjour Oceara,\n\nJ’aimerais avoir des informations sur ma commande.\n\nNuméro de commande : ${data.order_id}\nEmail : ${data.email}\n\nMerci.`);
        });
      } else {
        status.className = 'osc-status is-visible is-error';
        status.textContent = json.message;
      }
    } catch (error) {
      status.className = 'osc-status is-visible is-error';
      status.textContent = error.message || cfg.i18n.networkError;
    }
  });
})();
