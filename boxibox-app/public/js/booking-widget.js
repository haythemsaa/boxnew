/**
 * BoxiBox Booking Widget
 * Standalone JavaScript widget for embedding booking on external sites
 *
 * Usage:
 * <div id="boxibox-booking-widget" data-widget-key="YOUR_KEY"></div>
 * <script src="https://yoursite.com/js/booking-widget.js" async></script>
 */

(function() {
    'use strict';

    const WIDGET_VERSION = '1.0.0';
    const API_BASE = window.BOXIBOX_API_URL || (document.currentScript?.src?.replace('/js/booking-widget.js', '') || '');

    // Widget styles (injected once)
    const WIDGET_STYLES = `
        .boxibox-widget {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            max-width: 500px;
            margin: 0 auto;
        }
        .boxibox-widget * {
            box-sizing: border-box;
        }
        .boxibox-widget-container {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .boxibox-widget-header {
            padding: 20px;
            color: #ffffff;
            text-align: center;
        }
        .boxibox-widget-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
        }
        .boxibox-widget-header p {
            margin: 8px 0 0;
            opacity: 0.9;
            font-size: 0.875rem;
        }
        .boxibox-widget-body {
            padding: 20px;
        }
        .boxibox-widget-section {
            margin-bottom: 16px;
        }
        .boxibox-widget-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            margin-bottom: 8px;
        }
        .boxibox-widget-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            background: #ffffff;
            cursor: pointer;
            transition: border-color 0.2s, box-shadow 0.2s;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 20px;
        }
        .boxibox-widget-select:focus {
            outline: none;
            border-color: var(--primary-color, #3b82f6);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .boxibox-widget-boxes {
            display: grid;
            gap: 12px;
        }
        .boxibox-widget-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            background: #ffffff;
        }
        .boxibox-widget-box:hover {
            border-color: #d1d5db;
            background: #f9fafb;
        }
        .boxibox-widget-box.selected {
            border-color: var(--primary-color, #3b82f6);
            background: rgba(59, 130, 246, 0.05);
        }
        .boxibox-widget-box-info {
            display: flex;
            flex-direction: column;
        }
        .boxibox-widget-box-name {
            font-weight: 600;
            color: #111827;
        }
        .boxibox-widget-box-details {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 2px;
        }
        .boxibox-widget-box-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color, #3b82f6);
        }
        .boxibox-widget-box-price span {
            font-size: 0.75rem;
            font-weight: 400;
            color: #6b7280;
        }
        .boxibox-widget-btn {
            width: 100%;
            padding: 14px 20px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            color: #ffffff;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .boxibox-widget-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .boxibox-widget-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        .boxibox-widget-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            color: #6b7280;
        }
        .boxibox-widget-spinner {
            width: 24px;
            height: 24px;
            border: 3px solid #e5e7eb;
            border-top-color: var(--primary-color, #3b82f6);
            border-radius: 50%;
            animation: boxibox-spin 0.8s linear infinite;
            margin-right: 12px;
        }
        @keyframes boxibox-spin {
            to { transform: rotate(360deg); }
        }
        .boxibox-widget-error {
            padding: 20px;
            text-align: center;
            color: #dc2626;
        }
        .boxibox-widget-footer {
            padding: 12px 20px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            text-align: center;
        }
        .boxibox-widget-footer a {
            font-size: 0.75rem;
            color: #9ca3af;
            text-decoration: none;
        }
        .boxibox-widget-footer a:hover {
            color: #6b7280;
        }
        .boxibox-widget-calculator {
            background: #f3f4f6;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
        }
        .boxibox-widget-calculator-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .boxibox-widget-calculator-items {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .boxibox-widget-calc-item {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.875rem;
            background: #ffffff;
            cursor: pointer;
            transition: all 0.2s;
        }
        .boxibox-widget-calc-item:hover {
            border-color: var(--primary-color, #3b82f6);
        }
        .boxibox-widget-calc-item.active {
            background: var(--primary-color, #3b82f6);
            color: #ffffff;
            border-color: var(--primary-color, #3b82f6);
        }
        .boxibox-widget-recommendation {
            margin-top: 12px;
            padding: 12px;
            background: #ffffff;
            border-radius: 8px;
            border-left: 3px solid var(--primary-color, #3b82f6);
        }
        .boxibox-widget-recommendation-text {
            font-size: 0.875rem;
            color: #374151;
        }
        .boxibox-widget-recommendation strong {
            color: var(--primary-color, #3b82f6);
        }

        /* Button widget type */
        .boxibox-widget-button-only {
            display: inline-block;
        }

        /* Compact widget */
        .boxibox-widget-compact .boxibox-widget-container {
            border-radius: 12px;
        }
        .boxibox-widget-compact .boxibox-widget-header {
            padding: 12px 16px;
        }
        .boxibox-widget-compact .boxibox-widget-body {
            padding: 16px;
        }
    `;

    // Size calculator data
    const SIZE_ITEMS = [
        { id: 'boxes', label: '10 cartons', volume: 1, icon: '📦' },
        { id: 'studio', label: 'Studio', volume: 3, icon: '🏠' },
        { id: 'apt2', label: 'T2', volume: 5, icon: '🏠' },
        { id: 'apt3', label: 'T3', volume: 8, icon: '🏘️' },
        { id: 'apt4', label: 'T4+', volume: 12, icon: '🏘️' },
        { id: 'house', label: 'Maison', volume: 20, icon: '🏡' },
    ];

    class BoxiBoxWidget {
        constructor(container, options = {}) {
            this.container = container;
            this.widgetKey = options.widgetKey;
            this.siteId = options.siteId;
            this.primaryColor = options.primaryColor || '#3b82f6';
            this.widgetType = options.widgetType || 'full';
            this.showCalculator = options.showCalculator !== false;

            this.data = null;
            this.selectedSite = null;
            this.selectedBox = null;
            this.calculatorVolume = 0;

            this.init();
        }

        async init() {
            this.injectStyles();
            this.render();
            await this.fetchData();
        }

        injectStyles() {
            if (document.getElementById('boxibox-widget-styles')) return;

            const style = document.createElement('style');
            style.id = 'boxibox-widget-styles';
            style.textContent = WIDGET_STYLES;
            document.head.appendChild(style);
        }

        render() {
            this.container.innerHTML = `
                <div class="boxibox-widget boxibox-widget-${this.widgetType}" style="--primary-color: ${this.primaryColor}">
                    <div class="boxibox-widget-container">
                        <div class="boxibox-widget-loading">
                            <div class="boxibox-widget-spinner"></div>
                            Chargement...
                        </div>
                    </div>
                </div>
            `;
        }

        async fetchData() {
            try {
                const response = await fetch(`${API_BASE}/api/widget/${this.widgetKey}`);
                if (!response.ok) throw new Error('Widget not found');

                this.data = await response.json();
                this.primaryColor = this.data.settings?.primary_color || this.primaryColor;
                this.renderWidget();
            } catch (error) {
                this.renderError(error.message);
            }
        }

        renderError(message) {
            const widgetEl = this.container.querySelector('.boxibox-widget-container');
            widgetEl.innerHTML = `
                <div class="boxibox-widget-error">
                    <p>Impossible de charger le widget</p>
                    <small>${message}</small>
                </div>
            `;
        }

        renderWidget() {
            const { settings, tenant, sites } = this.data;

            // For button type, just show a button
            if (this.widgetType === 'button') {
                this.container.innerHTML = `
                    <div class="boxibox-widget boxibox-widget-button-only" style="--primary-color: ${this.primaryColor}">
                        <a href="${this.getBookingUrl()}"
                           class="boxibox-widget-btn"
                           style="background-color: ${this.primaryColor}; text-decoration: none;">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            Réserver un box
                        </a>
                    </div>
                `;
                return;
            }

            const isCompact = this.widgetType === 'compact';

            this.container.innerHTML = `
                <div class="boxibox-widget boxibox-widget-${this.widgetType}" style="--primary-color: ${this.primaryColor}">
                    <div class="boxibox-widget-container">
                        <div class="boxibox-widget-header" style="background: linear-gradient(135deg, ${this.primaryColor}, ${this.adjustColor(this.primaryColor, -20)})">
                            <h3>${settings?.company_name || tenant?.name || 'Réserver un box'}</h3>
                            ${!isCompact && settings?.welcome_message ? `<p>${settings.welcome_message}</p>` : ''}
                        </div>
                        <div class="boxibox-widget-body">
                            ${this.showCalculator && !isCompact ? this.renderCalculator() : ''}
                            ${this.renderSiteSelector(sites)}
                            <div id="boxibox-boxes-container"></div>
                            <button class="boxibox-widget-btn" id="boxibox-continue-btn"
                                    style="background-color: ${this.primaryColor}" disabled>
                                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                </svg>
                                Continuer la réservation
                            </button>
                        </div>
                        <div class="boxibox-widget-footer">
                            <a href="https://boxibox.fr" target="_blank">Propulsé par BoxiBox</a>
                        </div>
                    </div>
                </div>
            `;

            this.bindEvents();

            // Auto-select site if only one
            if (sites.length === 1) {
                this.selectedSite = sites[0].id;
                this.container.querySelector('#boxibox-site-select').value = sites[0].id;
                this.onSiteChange();
            }
        }

        renderCalculator() {
            return `
                <div class="boxibox-widget-calculator">
                    <div class="boxibox-widget-calculator-title">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <rect x="4" y="4" width="16" height="16" rx="2"/>
                            <path d="M4 10h16M10 4v16"/>
                        </svg>
                        Estimez votre besoin
                    </div>
                    <div class="boxibox-widget-calculator-items">
                        ${SIZE_ITEMS.map(item => `
                            <button class="boxibox-widget-calc-item" data-volume="${item.volume}" data-id="${item.id}">
                                ${item.icon} ${item.label}
                            </button>
                        `).join('')}
                    </div>
                    <div id="boxibox-recommendation" class="boxibox-widget-recommendation" style="display: none;">
                        <div class="boxibox-widget-recommendation-text">
                            Nous recommandons un box d'au moins <strong id="boxibox-rec-volume">0</strong> m³
                        </div>
                    </div>
                </div>
            `;
        }

        renderSiteSelector(sites) {
            if (sites.length === 0) {
                return '<p class="boxibox-widget-error">Aucun site disponible</p>';
            }

            return `
                <div class="boxibox-widget-section">
                    <label class="boxibox-widget-label">Choisir un site</label>
                    <select class="boxibox-widget-select" id="boxibox-site-select">
                        <option value="">Sélectionnez un site</option>
                        ${sites.map(site => `
                            <option value="${site.id}">${site.name} - ${site.city} (${site.available_boxes_count} dispo.)</option>
                        `).join('')}
                    </select>
                </div>
            `;
        }

        renderBoxes(boxes) {
            const container = this.container.querySelector('#boxibox-boxes-container');

            if (!boxes || boxes.length === 0) {
                container.innerHTML = '';
                return;
            }

            // Calculate volume from dimensions if available
            const boxesWithVolume = boxes.map(b => ({
                ...b,
                volume: b.size || (b.width && b.length && b.height ? (b.width * b.length * b.height) : 0)
            }));

            // Filter by recommended volume if calculator used
            let filteredBoxes = boxesWithVolume;
            if (this.calculatorVolume > 0) {
                filteredBoxes = boxesWithVolume.filter(b => b.volume >= this.calculatorVolume * 0.8);
                // If no boxes match, show all
                if (filteredBoxes.length === 0) {
                    filteredBoxes = boxesWithVolume;
                }
            }

            // Build dimensions string
            const getDimensions = (box) => {
                if (box.width && box.length) {
                    let dim = `${box.width}×${box.length}`;
                    if (box.height) dim += `×${box.height}`;
                    return dim + ' m';
                }
                return '';
            };

            container.innerHTML = `
                <div class="boxibox-widget-section">
                    <label class="boxibox-widget-label">Choisir un box (${filteredBoxes.length} disponibles)</label>
                    <div class="boxibox-widget-boxes">
                        ${filteredBoxes.slice(0, 6).map(box => `
                            <div class="boxibox-widget-box ${this.selectedBox === box.id ? 'selected' : ''}"
                                 data-box-id="${box.id}">
                                <div class="boxibox-widget-box-info">
                                    <span class="boxibox-widget-box-name">${box.name}</span>
                                    <span class="boxibox-widget-box-details">${box.size || box.volume} m²${getDimensions(box) ? ' • ' + getDimensions(box) : ''}</span>
                                </div>
                                <div class="boxibox-widget-box-price">
                                    ${this.formatCurrency(box.price)}<span>/mois</span>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                    ${filteredBoxes.length > 6 ? `<p style="text-align: center; font-size: 0.875rem; color: #6b7280; margin-top: 12px;">+ ${filteredBoxes.length - 6} autres boxes disponibles</p>` : ''}
                </div>
            `;

            // Bind box click events
            container.querySelectorAll('.boxibox-widget-box').forEach(el => {
                el.addEventListener('click', () => this.onBoxSelect(parseInt(el.dataset.boxId)));
            });
        }

        bindEvents() {
            // Site selector
            const siteSelect = this.container.querySelector('#boxibox-site-select');
            if (siteSelect) {
                siteSelect.addEventListener('change', () => this.onSiteChange());
            }

            // Continue button
            const continueBtn = this.container.querySelector('#boxibox-continue-btn');
            if (continueBtn) {
                continueBtn.addEventListener('click', () => this.onContinue());
            }

            // Calculator items
            this.container.querySelectorAll('.boxibox-widget-calc-item').forEach(el => {
                el.addEventListener('click', () => this.onCalculatorSelect(el));
            });
        }

        onSiteChange() {
            const siteId = parseInt(this.container.querySelector('#boxibox-site-select').value);
            this.selectedSite = siteId || null;
            this.selectedBox = null;

            if (this.selectedSite) {
                const site = this.data.sites.find(s => s.id === this.selectedSite);
                this.renderBoxes(site?.boxes || []);
            } else {
                this.container.querySelector('#boxibox-boxes-container').innerHTML = '';
            }

            this.updateContinueButton();
        }

        onBoxSelect(boxId) {
            this.selectedBox = boxId;

            // Update UI
            this.container.querySelectorAll('.boxibox-widget-box').forEach(el => {
                el.classList.toggle('selected', parseInt(el.dataset.boxId) === boxId);
            });

            this.updateContinueButton();
        }

        onCalculatorSelect(el) {
            // Toggle active state
            const wasActive = el.classList.contains('active');
            this.container.querySelectorAll('.boxibox-widget-calc-item').forEach(item => {
                item.classList.remove('active');
            });

            if (!wasActive) {
                el.classList.add('active');
                this.calculatorVolume = parseInt(el.dataset.volume);
            } else {
                this.calculatorVolume = 0;
            }

            // Show recommendation
            const recEl = this.container.querySelector('#boxibox-recommendation');
            const recVolEl = this.container.querySelector('#boxibox-rec-volume');
            if (recEl && recVolEl) {
                if (this.calculatorVolume > 0) {
                    recVolEl.textContent = this.calculatorVolume;
                    recEl.style.display = 'block';
                } else {
                    recEl.style.display = 'none';
                }
            }

            // Re-render boxes with filter
            if (this.selectedSite) {
                const site = this.data.sites.find(s => s.id === this.selectedSite);
                this.renderBoxes(site?.boxes || []);
            }
        }

        updateContinueButton() {
            const btn = this.container.querySelector('#boxibox-continue-btn');
            if (btn) {
                btn.disabled = !this.selectedSite || !this.selectedBox;
            }
        }

        onContinue() {
            if (!this.selectedSite || !this.selectedBox) return;

            const url = this.getBookingUrl();
            window.open(url, '_blank');
        }

        getBookingUrl() {
            const baseUrl = this.data?.settings?.booking_url ||
                           `${API_BASE}/book/tenant/${this.data?.tenant?.id}`;

            const params = new URLSearchParams();
            if (this.selectedSite) params.set('site', this.selectedSite);
            if (this.selectedBox) params.set('box', this.selectedBox);
            params.set('utm_source', 'widget');
            params.set('utm_medium', 'embed');

            return `${baseUrl}?${params.toString()}`;
        }

        formatCurrency(amount) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'EUR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(amount || 0);
        }

        adjustColor(color, percent) {
            const num = parseInt(color.replace('#', ''), 16);
            const amt = Math.round(2.55 * percent);
            const R = (num >> 16) + amt;
            const G = (num >> 8 & 0x00FF) + amt;
            const B = (num & 0x0000FF) + amt;
            return '#' + (0x1000000 + (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
                (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 +
                (B < 255 ? B < 1 ? 0 : B : 255)).toString(16).slice(1);
        }
    }

    // Auto-initialize widgets on page load
    function initWidgets() {
        const containers = document.querySelectorAll('[id="boxibox-booking-widget"], [data-boxibox-widget]');

        containers.forEach(container => {
            if (container.dataset.initialized) return;
            container.dataset.initialized = 'true';

            new BoxiBoxWidget(container, {
                widgetKey: container.dataset.widgetKey,
                siteId: container.dataset.siteId,
                widgetType: container.dataset.widgetType || 'full',
                primaryColor: container.dataset.primaryColor,
                showCalculator: container.dataset.showCalculator !== 'false',
            });
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWidgets);
    } else {
        initWidgets();
    }

    // Expose for manual initialization
    window.BoxiBoxWidget = BoxiBoxWidget;
    window.initBoxiBoxWidgets = initWidgets;
})();
