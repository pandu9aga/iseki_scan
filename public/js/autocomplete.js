/**
 * Custom Autocomplete Input Component
 * Easy to use multiple times by importing this script
 *
 * Usage:
 * new Autocomplete('#inputId', {
 *     searchUrl: '/your-search-endpoint',
 *     minChars: 1,
 *     onSelect: function(item) { console.log(item); }
 * });
 */

class Autocomplete {
    constructor(inputSelector, options = {}) {
        this.input = document.querySelector(inputSelector);
        if (!this.input) {
            console.error('Autocomplete: Input element not found:', inputSelector);
            return;
        }

        this.options = {
            searchUrl: options.searchUrl || '',
            minChars: options.minChars || 1,
            delay: options.delay || 300,
            onSelect: options.onSelect || null,
            displayField: options.displayField || 'rack_no',
            searchFields: options.searchFields || ['rack_no', 'part_name', 'item_code'],
            maxResults: options.maxResults || 10,
            autoFocusNext: options.autoFocusNext !== false,
            placeholder: options.placeholder || 'Type to search...',
        };

        this.resultsContainer = null;
        this.searchTimeout = null;
        this.selectedIndex = -1;
        this.results = [];
        this.isLoading = false;

        this.init();
    }

    init() {
        // Set placeholder
        this.input.setAttribute('placeholder', this.options.placeholder);
        this.input.setAttribute('autocomplete', 'off');

        // Create results container
        this.createResultsContainer();

        // Bind events
        this.bindEvents();
    }

    createResultsContainer() {
        // Create wrapper if not exists
        if (!this.input.parentElement.classList.contains('autocomplete-wrapper')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'autocomplete-wrapper';
            wrapper.style.position = 'relative';
            this.input.parentNode.insertBefore(wrapper, this.input);
            wrapper.appendChild(this.input);
        }

        // Create results container
        this.resultsContainer = document.createElement('div');
        this.resultsContainer.className = 'autocomplete-results';
        this.input.parentElement.appendChild(this.resultsContainer);
    }

    bindEvents() {
        // Input event
        this.input.addEventListener('input', (e) => {
            this.handleInput(e);
        });

        // Keydown for navigation
        this.input.addEventListener('keydown', (e) => {
            this.handleKeydown(e);
        });

        // Focus event
        this.input.addEventListener('focus', () => {
            if (this.input.value.length >= this.options.minChars && this.results.length > 0) {
                this.showResults();
            }
        });

        // Click outside to close
        document.addEventListener('click', (e) => {
            if (!this.input.parentElement.contains(e.target)) {
                this.results = [];
                this.resultsContainer.innerHTML = '';
                this.hideResults();
            }
        });
    }

    handleInput(e) {
        const query = e.target.value.trim();

        clearTimeout(this.searchTimeout);

        if (query.length < this.options.minChars) {
            this.results = [];
            this.resultsContainer.innerHTML = '';
            this.hideResults();
            return;
        }

        this.searchTimeout = setTimeout(() => {
            this.search(query);
        }, this.options.delay);
    }

    handleKeydown(e) {
        if (!this.resultsContainer.classList.contains('show')) {
            return;
        }

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.selectedIndex = Math.min(this.selectedIndex + 1, this.results.length - 1);
                this.updateSelection();
                break;
            case 'ArrowUp':
                e.preventDefault();
                this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
                this.updateSelection();
                break;
            case 'Enter':
                e.preventDefault();
                if (this.selectedIndex >= 0) {
                    this.selectItem(this.results[this.selectedIndex]);
                }
                break;
            case 'Escape':
                this.hideResults();
                break;
        }
    }

    async search(query) {
        if (!this.options.searchUrl) {
            console.error('Autocomplete: searchUrl not provided');
            return;
        }

        this.isLoading = true;
        this.showLoading();

        try {
            const response = await fetch(`${this.options.searchUrl}?query=${encodeURIComponent(query)}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const data = await response.json();
            this.results = data.slice(0, this.options.maxResults);
            this.renderResults();
        } catch (error) {
            console.error('Autocomplete search error:', error);
            this.showError('Failed to fetch results');
        } finally {
            this.isLoading = false;
        }
    }

    showLoading() {
        this.resultsContainer.innerHTML = '<div class="autocomplete-loading">Searching...</div>';
        this.resultsContainer.classList.add('show');
        this.input.classList.add('has-results');
    }

    showError(message) {
        this.resultsContainer.innerHTML = `<div class="autocomplete-error">${message}</div>`;
        this.resultsContainer.classList.add('show');
        this.input.classList.add('has-results');
    }

    renderResults() {
        if (this.results.length === 0) {
            this.resultsContainer.innerHTML = '<div class="autocomplete-empty">No results found</div>';
            this.resultsContainer.classList.add('show');
            this.input.classList.add('has-results');
            return;
        }

        this.selectedIndex = -1;

        const html = this.results.map((item, index) => {
            const mainText = item[this.options.displayField] || '';
            const subTexts = this.options.searchFields
                .filter(field => field !== this.options.displayField && item[field])
                .map(field => item[field])
                .join(' • ');

            return `
                <div class="autocomplete-item" data-index="${index}">
                    <div class="autocomplete-main">${this.escapeHtml(mainText)}</div>
                    ${subTexts ? `<div class="autocomplete-sub">${this.escapeHtml(subTexts)}</div>` : ''}
                </div>
            `;
        }).join('');

        this.resultsContainer.innerHTML = html;
        this.resultsContainer.classList.add('show');
        this.input.classList.add('has-results');

        // Bind click events
        this.resultsContainer.querySelectorAll('.autocomplete-item').forEach((item) => {
            item.addEventListener('click', () => {
                const index = parseInt(item.dataset.index);
                this.selectItem(this.results[index]);
            });
        });
    }

    updateSelection() {
        const items = this.resultsContainer.querySelectorAll('.autocomplete-item');
        items.forEach((item, index) => {
            if (index === this.selectedIndex) {
                item.classList.add('selected');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('selected');
            }
        });
    }

    selectItem(item) {
        if (!item) return;

        // Set input value
        this.input.value = item[this.options.displayField] || '';

        // Store the selected item data
        this.input.dataset.selectedItem = JSON.stringify(item);

        // Clear results array
        this.results = [];

        // Hide and clear results container
        this.hideResults();
        this.resultsContainer.innerHTML = '';

        // Call callback
        if (typeof this.options.onSelect === 'function') {
            this.options.onSelect(item, this.input);
        }

        // Auto focus next input
        if (this.options.autoFocusNext) {
            this.focusNextInput();
        }
    }

    focusNextInput() {
        const form = this.input.closest('form');
        if (!form) return;

        const inputs = Array.from(form.querySelectorAll('input, select, textarea'))
            .filter(el => !el.disabled && !el.readOnly && el.offsetParent !== null);

        const currentIndex = inputs.indexOf(this.input);
        if (currentIndex >= 0 && currentIndex < inputs.length - 1) {
            inputs[currentIndex + 1].focus();
        }
    }

    showResults() {
        this.resultsContainer.classList.add('show');
        this.input.classList.add('has-results');
    }

    hideResults() {
        this.resultsContainer.classList.remove('show');
        this.input.classList.remove('has-results');
        this.selectedIndex = -1;
        // Optionally clear the container content after animation
        setTimeout(() => {
            if (!this.resultsContainer.classList.contains('show')) {
                this.resultsContainer.innerHTML = '';
            }
        }, 200);
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Public method to get selected item
    getSelectedItem() {
        try {
            return JSON.parse(this.input.dataset.selectedItem || '{}');
        } catch (e) {
            return {};
        }
    }

    // Public method to clear
    clear() {
        this.input.value = '';
        delete this.input.dataset.selectedItem;
        this.hideResults();
    }

    // Public method to destroy
    destroy() {
        if (this.resultsContainer) {
            this.resultsContainer.remove();
        }
        clearTimeout(this.searchTimeout);
    }
}

// Make it available globally
window.Autocomplete = Autocomplete;

