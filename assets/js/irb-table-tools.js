window.IRBTableTools = window.IRBTableTools || (function () {
    function init(config) {
        const searchInput = document.getElementById(config.searchInputId);
        const statusFilter = document.getElementById(config.statusFilterId);
        const resetButton = document.getElementById(config.resetButtonId);
        const sortHeader = document.getElementById(config.sortHeaderId);
        const sortIcon = document.getElementById(config.sortIconId);
        const resultsCount = document.getElementById(config.resultsCountId);
        const tableBody = document.getElementById(config.tableBodyId);
        const noResultsRow = config.noResultsRowId ? document.getElementById(config.noResultsRowId) : null;
        const paginationContainer = config.paginationContainerId ? document.getElementById(config.paginationContainerId) : null;
        const pageSize = Number(config.pageSize || 10);

        if (!searchInput || !statusFilter || !resetButton || !sortHeader || !sortIcon || !tableBody) {
            return;
        }

        let sortDirection = config.defaultSort === 'asc' ? 'asc' : 'desc';
        let currentPage = 1;

        const toArabicDigits = (value) => String(value).replace(/[0-9]/g, (digit) => ({
            '0': '٠',
            '1': '١',
            '2': '٢',
            '3': '٣',
            '4': '٤',
            '5': '٥',
            '6': '٦',
            '7': '٧',
            '8': '٨',
            '9': '٩',
        }[digit]));

        const getRows = () => Array.from(tableBody.querySelectorAll('tr[data-search]'));

        const renderPagination = (totalItems) => {
            if (!paginationContainer) {
                return;
            }

            const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));

            if (totalItems === 0) {
                paginationContainer.innerHTML = '';
                return;
            }

            const safePage = Math.min(Math.max(currentPage, 1), totalPages);
            currentPage = safePage;

            const startIndex = toArabicDigits((safePage - 1) * pageSize + 1);
            const endIndex = toArabicDigits(Math.min(safePage * pageSize, totalItems));
            const totalLabel = toArabicDigits(totalItems);
            const currentPageLabel = toArabicDigits(safePage);
            const totalPageLabel = toArabicDigits(totalPages);

            paginationContainer.innerHTML = [
                '<div class="irb-pagination__bar">',
                '<div class="irb-pagination__meta">',
                '<span class="irb-pagination__chip">عرض ' + startIndex + ' - ' + endIndex + ' من ' + totalLabel + '</span>',
                '<span class="irb-pagination__chip irb-pagination__chip--soft">' + currentPageLabel + ' / ' + totalPageLabel + '</span>',
                '</div>',
                '<div class="irb-pagination__controls">',
                '<button type="button" class="irb-pagination__arrow" data-page="prev" ' + (safePage === 1 ? 'disabled' : '') + ' aria-label="الصفحة السابقة"><i class="fa-solid fa-chevron-right"></i></button>',
                '<button type="button" class="irb-pagination__arrow" data-page="next" ' + (safePage === totalPages ? 'disabled' : '') + ' aria-label="الصفحة التالية"><i class="fa-solid fa-chevron-left"></i></button>',
                '</div>',
                '</div>'
            ].join('');

            paginationContainer.querySelectorAll('[data-page]').forEach((button) => {
                button.addEventListener('click', () => {
                    const target = button.getAttribute('data-page');

                    if (target === 'prev') {
                        currentPage = Math.max(1, currentPage - 1);
                    } else if (target === 'next') {
                        currentPage = Math.min(totalPages, currentPage + 1);
                    } else {
                        currentPage = Number(target);
                    }

                    applyFilters();
                });
            });
        };

        const updateSortUI = () => {
            const isAscending = sortDirection === 'asc';
            sortIcon.innerHTML = isAscending
                ? '<i class="fa-solid fa-arrow-up-wide-short"></i>'
                : '<i class="fa-solid fa-arrow-down-wide-short"></i>';
            sortHeader.setAttribute('aria-pressed', String(isAscending));
        };

        const applyFilters = () => {
            const rows = getRows();

            if (!rows.length) {
                if (paginationContainer) {
                    paginationContainer.innerHTML = '';
                }
                return;
            }

            const query = searchInput.value.trim().toLowerCase();
            const status = statusFilter.value;

            const filteredRows = rows.filter((row) => {
                const matchesSearch = row.dataset.search.includes(query);
                const matchesStatus = status === 'all' || row.dataset.status === status;
                return matchesSearch && matchesStatus;
            });

            filteredRows.sort((a, b) => {
                const aTime = new Date(a.dataset.date).getTime();
                const bTime = new Date(b.dataset.date).getTime();
                return sortDirection === 'asc' ? aTime - bTime : bTime - aTime;
            });

            const totalItems = filteredRows.length;
            const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
            currentPage = Math.min(currentPage, totalPages);
            const startIndex = (currentPage - 1) * pageSize;
            const visibleRows = filteredRows.slice(startIndex, startIndex + pageSize);

            rows.forEach((row) => {
                row.classList.add('row-hidden');
            });

            visibleRows.forEach((row) => {
                row.classList.remove('row-hidden');
                tableBody.appendChild(row);
            });

            if (noResultsRow) {
                noResultsRow.style.display = filteredRows.length ? 'none' : 'table-row';
            }

            if (resultsCount) {
                resultsCount.textContent = filteredRows.length;
            }

            renderPagination(totalItems);
        };

        const toggleSort = () => {
            sortDirection = sortDirection === 'desc' ? 'asc' : 'desc';
            updateSortUI();
            applyFilters();
        };

        searchInput.addEventListener('input', () => {
            currentPage = 1;
            applyFilters();
        });
        statusFilter.addEventListener('change', () => {
            currentPage = 1;
            applyFilters();
        });
        sortHeader.addEventListener('click', () => {
            currentPage = 1;
            toggleSort();
        });
        resetButton.addEventListener('click', () => {
            searchInput.value = '';
            statusFilter.value = 'all';
            sortDirection = config.defaultSort === 'asc' ? 'asc' : 'desc';
            currentPage = 1;
            updateSortUI();
            applyFilters();
            searchInput.focus();
        });

        updateSortUI();
        applyFilters();
    }

    return {
        init,
    };
}());
