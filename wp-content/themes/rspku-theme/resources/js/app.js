import Alpine from 'alpinejs';
import '../css/app.css';

window.Alpine = Alpine;

Alpine.data('siteNavigation', () => ({
  open: false,
  panel: null,
  closeTimer: null,
  openPanel(name) {
    this.cancelClose();
    this.panel = name;
  },
  scheduleClose() {
    this.cancelClose();
    this.closeTimer = window.setTimeout(() => {
      this.panel = null;
    }, 180);
  },
  cancelClose() {
    if (this.closeTimer) {
      window.clearTimeout(this.closeTimer);
      this.closeTimer = null;
    }
  },
  close() {
    this.cancelClose();
    this.open = false;
    this.panel = null;
  },
  toggle() {
    this.open = !this.open;
    if (!this.open) {
      this.panel = null;
    }
  },
  togglePanel(name) {
    this.cancelClose();
    this.panel = this.panel === name ? null : name;
  },
  isPanel(name) {
    return this.panel === name;
  },
}));

Alpine.data('searchableSelect', () => ({
  open: false,
  query: '',
  value: '',
  selectedLabel: '',
  highlightedIndex: 0,
  options: [],
  init() {
    this.select = this.$el.querySelector('select');
    this.refresh();
    this.select?.addEventListener('change', () => this.refresh());
    this.select?.form?.addEventListener('reset', () => window.setTimeout(() => this.refresh()));
  },
  get filteredOptions() {
    const query = this.query.trim().toLowerCase();
    return query ? this.options.filter((option) => option.label.toLowerCase().includes(query)) : this.options;
  },
  refresh() {
    if (!this.select) {
      return;
    }

    this.options = Array.from(this.select.options).map((option) => ({
      value: option.value,
      label: option.textContent.trim(),
    }));
    this.value = this.select.value;
    this.selectedLabel = this.options.find((option) => option.value === this.value)?.label || this.options[0]?.label || '';
  },
  toggle() {
    this.open ? this.close() : this.openList();
  },
  openList() {
    this.open = true;
    this.query = '';
    this.highlightedIndex = Math.max(0, this.options.findIndex((option) => option.value === this.value));
    this.$nextTick(() => this.$refs.search?.focus());
  },
  close() {
    this.open = false;
    this.query = '';
  },
  next() {
    this.highlightedIndex = Math.min(this.highlightedIndex + 1, Math.max(this.filteredOptions.length - 1, 0));
  },
  previous() {
    this.highlightedIndex = Math.min(Math.max(this.highlightedIndex - 1, 0), Math.max(this.filteredOptions.length - 1, 0));
  },
  chooseHighlighted() {
    const option = this.filteredOptions[this.highlightedIndex] || this.filteredOptions[0];
    if (option) {
      this.choose(option);
    }
  },
  choose(option) {
    if (!this.select) {
      return;
    }

    this.select.value = option.value;
    this.select.dispatchEvent(new Event('change', { bubbles: true }));
    this.close();
  },
}));

Alpine.data('scheduleTable', () => ({
  query: '',
  specialization: '',
  totalRows: 0,
  visibleRows: 0,
  rows: [],
  filteredRows: [],
  currentPage: 1,
  perPage: 12,
  init() {
    queueMicrotask(() => {
      const rows = Array.from(this.$el.querySelectorAll('[data-schedule-row]'));
      if (!rows.length) {
        return;
      }

      this.rows = rows;
      this.totalRows = this.rows.length;
      this.applyFilters(false);
    });
  },
  applyFilters(resetPage = true) {
    const keyword = this.query.trim().toLowerCase();
    const specialization = this.specialization.trim().toLowerCase();
    const filteredRows = [];

    this.rows.forEach((row) => {
      const name = (row.dataset.scheduleName || '').toLowerCase();
      const spec = (row.dataset.scheduleSpecialization || '').toLowerCase();
      const specs = spec.split(/\s+/).filter(Boolean);
      const category = (row.dataset.scheduleCategory || '').toLowerCase();
      const text = row.textContent?.toLowerCase() || '';
      const queryMatch = keyword === '' || name.includes(keyword) || spec.includes(keyword) || category.includes(keyword) || text.includes(keyword);
      const specializationMatch = specialization === '' || specs.includes(specialization) || category === specialization;
      const match = queryMatch && specializationMatch;

      if (match) {
        filteredRows.push(row);
      }
    });

    this.filteredRows = filteredRows;
    this.visibleRows = filteredRows.length;

    if (resetPage) {
      this.currentPage = 1;
    } else {
      this.currentPage = Math.min(this.currentPage, this.totalPages());
    }

    this.renderPage();
  },
  renderPage() {
    const start = (this.currentPage - 1) * this.perPage;
    const end = start + this.perPage;

    this.rows.forEach((row) => {
      row.hidden = true;
    });

    this.filteredRows.forEach((row, index) => {
      row.hidden = index < start || index >= end;
    });
  },
  filterRows() {
    this.applyFilters(true);
  },
  totalPages() {
    return Math.max(1, Math.ceil(this.visibleRows / this.perPage));
  },
  pages() {
    const total = this.totalPages();
    const current = this.currentPage;
    let start = Math.max(1, current - 2);
    let end = Math.min(total, start + 4);

    start = Math.max(1, end - 4);

    return Array.from({ length: end - start + 1 }, (_, index) => start + index);
  },
  goToPage(page) {
    const nextPage = Math.min(Math.max(1, page), this.totalPages());
    if (nextPage === this.currentPage) {
      return;
    }

    this.currentPage = nextPage;
    this.renderPage();
  },
  pageStart() {
    if (this.visibleRows === 0) {
      return 0;
    }

    return (this.currentPage - 1) * this.perPage + 1;
  },
  pageEnd() {
    return Math.min(this.currentPage * this.perPage, this.visibleRows);
  },
  reset() {
    this.query = '';
    this.specialization = '';
    const specialization = this.$refs.specialization || this.$el.querySelector('#schedule-specialization');
    if (specialization) {
      specialization.value = '';
      specialization.dispatchEvent(new Event('change', { bubbles: true }));
    }
    this.applyFilters(true);
  },
}));

Alpine.data('doctorSearch', () => ({
  loading: false,
  init() {
    queueMicrotask(() => {
      const results = this.$refs.results;
      if (!results) {
        return;
      }

      results.addEventListener('click', (event) => {
        const link = event.target.closest('.pagination a');
        if (!link) {
          return;
        }

        const page = this.pageFromHref(link.getAttribute('href'));
        if (!page) {
          return;
        }

        event.preventDefault();
        this.submit(page);
      });
    });
  },
  pageFromHref(href) {
    if (!href) {
      return 0;
    }

    try {
      const url = new URL(href, window.location.origin);
      const searchPage = url.searchParams.get('paged') || url.searchParams.get('page');
      if (searchPage) {
        return Number.parseInt(searchPage, 10);
      }

      const pathMatch = url.pathname.match(/\/page\/(\d+)\/?$/);
      if (pathMatch) {
        return Number.parseInt(pathMatch[1], 10);
      }

      const hashMatch = url.hash.match(/page=(\d+)/);
      if (hashMatch) {
        return Number.parseInt(hashMatch[1], 10);
      }

      return 1;
    } catch (error) {
      return 0;
    }
  },
  syncPage(page) {
    const form = this.$refs.form;
    if (!form) {
      return;
    }

    const pageInput = form.querySelector('input[name="page"]');
    if (pageInput) {
      pageInput.value = String(page);
    }
  },
  async submit(page = 1) {
    const form = this.$refs.form;
    const results = this.$refs.results;

    if (!form || !results) {
      return;
    }

    this.syncPage(page);

    const body = new FormData(form);
    body.set('action', 'rspku_doctor_search');
    body.set('nonce', this.$el.dataset.nonce || '');
    body.set('per_page', this.$el.dataset.perPage || '12');
    body.set('page', String(page));

    this.loading = true;

    try {
      const response = await fetch(this.$el.dataset.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body,
      });

      const payload = await response.json().catch(() => null);

      if (!response.ok || !payload?.success) {
        this.showError(results, payload?.data?.message || payload?.message || 'Pencarian belum bisa diproses. Silakan coba lagi.');
        return;
      }

      if (payload.data?.html) {
        results.innerHTML = payload.data.html;
      }
    } catch (error) {
      this.showError(results, 'Pencarian belum bisa diproses. Silakan coba lagi.');
    } finally {
      this.loading = false;
    }
  },
  showError(results, message) {
    const paragraph = document.createElement('p');
    paragraph.className = 'rounded-2xl bg-red-50 p-4 text-sm text-red-700';
    paragraph.textContent = message;
    results.replaceChildren(paragraph);
  },
  resetFilters() {
    const form = this.$refs.form;
    if (!form) {
      return;
    }

    form.reset();
    this.submit(1);
  },
}));

Alpine.data('accordionItem', () => ({
  open: false,
  toggle() {
    this.open = !this.open;
  },
}));

Alpine.data('promoSlider', () => ({
  index: 0,
  count: 0,
  userPaused: false,
  interacting: false,
  reducedMotion: false,
  timer: null,
  setup(count) {
    this.count = count;
    this.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    this.start();
  },
  start() {
    this.stop();
    if (this.count < 2 || this.reducedMotion || this.userPaused || this.interacting) {
      return;
    }
    this.timer = window.setInterval(() => this.next(), 8000);
  },
  stop() {
    if (this.timer) {
      window.clearInterval(this.timer);
      this.timer = null;
    }
  },
  pause() {
    this.interacting = true;
    this.stop();
  },
  resume() {
    this.interacting = false;
    this.start();
  },
  toggleAutoplay() {
    this.userPaused = !this.userPaused;
    this.userPaused ? this.stop() : this.start();
  },
  goTo(index) {
    this.index = (index + this.count) % this.count;
    this.start();
  },
  next() {
    this.goTo(this.index + 1);
  },
  prev() {
    this.goTo(this.index - 1);
  },
  onKeydown(event) {
    if (event.key === 'ArrowLeft') {
      event.preventDefault();
      this.prev();
    }
    if (event.key === 'ArrowRight') {
      event.preventDefault();
      this.next();
    }
  },
}));

Alpine.data('reviewsCarousel', () => ({
  dragging: false,
  startX: 0,
  startScrollLeft: 0,
  pointerId: null,
  velocity: 0,
  lastX: 0,
  lastTime: 0,
  momentumId: null,
  scroll(direction) {
    const track = this.$refs.track;
    if (!track) {
      return;
    }

    const amount = Math.max(track.clientWidth * 0.82, 300);
    track.scrollBy({
      left: amount * direction,
      behavior: 'smooth',
    });
  },
  start(event) {
    const track = this.$refs.track;
    if (!track) {
      return;
    }

    // Stop any ongoing momentum
    if (this.momentumId) {
      cancelAnimationFrame(this.momentumId);
      this.momentumId = null;
    }

    // Disable scroll-snap during drag for smooth movement
    track.style.scrollSnapType = 'none';

    this.dragging = true;
    this.pointerId = event.pointerId;
    this.startX = event.clientX;
    this.lastX = event.clientX;
    this.lastTime = Date.now();
    this.velocity = 0;
    this.startScrollLeft = track.scrollLeft;
    track.setPointerCapture(event.pointerId);
  },
  move(event) {
    const track = this.$refs.track;
    if (!track || !this.dragging || this.pointerId !== event.pointerId) {
      return;
    }

    event.preventDefault();

    const delta = event.clientX - this.startX;
    track.scrollLeft = this.startScrollLeft - delta;

    // Track velocity for momentum
    const now = Date.now();
    const timeDelta = now - this.lastTime;
    if (timeDelta > 0) {
      this.velocity = (event.clientX - this.lastX) / timeDelta;
    }
    this.lastX = event.clientX;
    this.lastTime = now;
  },
  end(event) {
    const track = this.$refs.track;
    if (track && this.pointerId !== null && event?.pointerId === this.pointerId) {
      try {
        track.releasePointerCapture(this.pointerId);
      } catch (error) {
        // ignore capture release issues
      }
    }

    this.dragging = false;
    this.pointerId = null;

    // Apply momentum scrolling
    if (track && Math.abs(this.velocity) > 0.1) {
      this.applyMomentum(track);
    } else if (track) {
      // Re-enable scroll-snap after drag completes
      this.restoreSnap(track);
    }
  },
  applyMomentum(track) {
    let currentVelocity = this.velocity * 16; // Convert to px per frame (~60fps)
    const friction = 0.94; // Decay factor per frame
    const minVelocity = 0.5;

    const step = () => {
      if (Math.abs(currentVelocity) < minVelocity) {
        this.momentumId = null;
        this.restoreSnap(track);
        return;
      }

      track.scrollLeft -= currentVelocity;
      currentVelocity *= friction;
      this.momentumId = requestAnimationFrame(step);
    };

    this.momentumId = requestAnimationFrame(step);
  },
  restoreSnap(track) {
    // Restore scroll-snap smoothly
    track.style.scrollSnapType = 'x mandatory';
  },
}));

Alpine.data('readingProgress', () => ({
  progress: 0,
  init() {
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReduced) {
      this.$el.style.transition = 'none';
    }

    const update = () => {
      const article = document.querySelector('[x-ref="articleBody"]');
      if (!article) return;

      const rect = article.getBoundingClientRect();
      const articleTop = rect.top + window.scrollY;
      const articleHeight = rect.height;
      const scrolled = window.scrollY - articleTop;
      const viewportHeight = window.innerHeight;

      if (scrolled <= 0) {
        this.progress = 0;
      } else if (scrolled >= articleHeight - viewportHeight) {
        this.progress = 100;
      } else {
        this.progress = Math.min(100, Math.max(0, (scrolled / (articleHeight - viewportHeight)) * 100));
      }
    };

    window.addEventListener('scroll', update, { passive: true });
    update();
  },
}));

Alpine.data('tocHighlight', () => ({
  active: '',
  init() {
    const headings = document.querySelectorAll('.rspku-prose h2[id], .rspku-prose h3[id]');
    if (!headings.length) return;

    const observer = new IntersectionObserver(
      (entries) => {
        for (const entry of entries) {
          if (entry.isIntersecting) {
            this.active = entry.target.id;
          }
        }
      },
      { rootMargin: '-80px 0px -70% 0px' }
    );

    headings.forEach((h) => observer.observe(h));

    // Smooth scroll with sticky header offset for TOC links.
    this.$el.addEventListener('click', (e) => {
      const link = e.target.closest('a[href^="#"]');
      if (!link) return;

      e.preventDefault();
      const targetId = link.getAttribute('href').slice(1);
      const target = document.getElementById(targetId);
      if (!target) return;

      const headerOffset = 80; // sticky header height
      const top = target.getBoundingClientRect().top + window.scrollY - headerOffset;

      window.scrollTo({ top, behavior: 'smooth' });

      // Update URL hash without jump
      history.replaceState(null, '', '#' + targetId);
    });
  },
}));

Alpine.start();
