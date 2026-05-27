(function () {
    const root = document.querySelector('[data-vs-vehicle-grid]');
    if (!root) {
        return;
    }

    const searchInput = root.querySelector('[data-vehicle-filter-search]');
    const activeSelect = root.querySelector('[data-vehicle-filter-active]');
    const cnhSelect = root.querySelector('[data-vehicle-filter-cnh]');
    const clearButton = root.querySelector('[data-vehicle-clear-filters]');
    const rows = Array.from(root.querySelectorAll('[data-vehicle-row]'));
    const emptyState = root.querySelector('[data-vehicle-empty]');
    const resultCount = root.querySelector('[data-vehicle-result-count]');
    const showingAllTemplate = resultCount && resultCount.dataset.showingAll
        ? resultCount.dataset.showingAll
        : 'Showing %d vehicles';
    const showingFilteredTemplate = resultCount && resultCount.dataset.showingFiltered
        ? resultCount.dataset.showingFiltered
        : 'Showing %d of %d vehicles';

    function normalize(value) {
        return String(value || '').toLowerCase().trim();
    }

    function formatCount(template, ...values) {
        return template.replace(/%d/g, () => (values.length ? values.shift() : ''));
    }

    function applyFilters() {
        const search = normalize(searchInput ? searchInput.value : '');
        const active = normalize(activeSelect ? activeSelect.value : 'all');
        const cnh = normalize(cnhSelect ? cnhSelect.value : 'all');

        let visibleCount = 0;

        rows.forEach((row) => {
            const rowSearch = normalize(row.getAttribute('data-search'));
            const rowActive = normalize(row.getAttribute('data-active'));
            const rowCnh = normalize(row.getAttribute('data-required-cnh'));

            const matchesSearch = search === '' || rowSearch.includes(search);
            const matchesActive = active === 'all' || rowActive === active;
            const matchesCnh = cnh === 'all' || rowCnh === cnh;

            const visible = matchesSearch && matchesActive && matchesCnh;
            row.hidden = !visible;

            if (visible) {
                visibleCount += 1;
            }
        });

        if (emptyState) {
            emptyState.hidden = visibleCount !== 0;
        }

        if (resultCount) {
            const template = visibleCount === rows.length ? showingAllTemplate : showingFilteredTemplate;
            resultCount.textContent = visibleCount === rows.length
                ? formatCount(template, visibleCount)
                : formatCount(template, visibleCount, rows.length);
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }

    if (activeSelect) {
        activeSelect.addEventListener('change', applyFilters);
    }

    if (cnhSelect) {
        cnhSelect.addEventListener('change', applyFilters);
    }

    if (clearButton) {
        clearButton.addEventListener('click', function () {
            if (searchInput) {
                searchInput.value = '';
            }

            if (activeSelect) {
                activeSelect.value = 'all';
            }

            if (cnhSelect) {
                cnhSelect.value = 'all';
            }

            applyFilters();
        });
    }

    applyFilters();
})();
